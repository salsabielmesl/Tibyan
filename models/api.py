"""
Features implemented:
  ✓ JWT authentication on all protected endpoints
  ✓ Consent policy check before every prediction
  ✓ Single prediction with SHAP explanation + confidence
  ✓ Longitudinal risk tracking (journey categorisation)
  ✓ Distribution plot data against clinical thresholds
  ✓ Data integrity report (missing values, drift, outliers)
  ✓ ModelPerformanceLog entry after every prediction
  ✓ PDF report generation for date ranges
  ✓ Health log storage (HealthLog table)
  ✓ Rate limiting per UPID
  ✓ Full audit trail

Run with:
    pip install fastapi uvicorn sqlalchemy python-jose passlib reportlab
    uvicorn api:app --reload --port 8000

Interactive docs at: http://localhost:8000/docs
"""

# ─────────────────────────────────────────────
#  0. IMPORTS
# ─────────────────────────────────────────────
import os
import json
import hashlib
import logging
from datetime import datetime, timezone, timedelta
from typing import Optional, List, Dict, Any

from fastapi import (FastAPI, Depends, HTTPException, status,
                     Request, Response)
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from pydantic import BaseModel, Field

from jose import JWTError, jwt
from sqlalchemy import (create_engine, Column, String, Float, Boolean,
                        Integer, DateTime, Text, ForeignKey, func)
from sqlalchemy.orm import declarative_base, sessionmaker, Session,Text,text
import numpy as np

import pipeline          # your existing pipeline.py
from report_builder import build_report   # your existing report_builder.py

# ─────────────────────────────────────────────
#  1. CONFIGURATION
# ─────────────────────────────────────────────

ALGORITHM       = "HS256"
TOKEN_EXPIRE_H  = 24            # JWT token expires after 24 hours
from dotenv import load_dotenv
load_dotenv()


SECRET_KEY    = os.getenv("TIBYAN_SECRET_KEY", "change-me")
DB_URL        = os.getenv("TIBYAN_DB_URL", "sqlite:///tibyan_analytical.db")
MODEL_VERSION = os.getenv("TIBYAN_MODEL_VERSION", "v4_fixed")
RATE_LIMIT    = int(os.getenv("TIBYAN_RATE_LIMIT", "10"))

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("tibyan")

# ─────────────────────────────────────────────
#  2. DATABASE SETUP (SQLite for development,
#     swap DB_URL for PostgreSQL/MySQL in prod)
# ─────────────────────────────────────────────
engine = create_engine(DB_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base         = declarative_base()


# ── ORM Models (Analytical Store) ─────────────

class ConsentPolicy(Base):
    __tablename__ = "consent_policy"
    id                   = Column(Integer, primary_key=True, index=True)
    upid                 = Column(String(64), index=True, nullable=False)
    policy_agreed_version = Column(String(16), nullable=False)
    agreed_at            = Column(DateTime, nullable=False)
    research_opt_in      = Column(Boolean, default=False)
    withdrawal_requested_at = Column(DateTime, nullable=True)


class HealthLog(Base):
    __tablename__ = "health_log"
    id         = Column(Integer, primary_key=True, index=True)
    upid       = Column(String(64), index=True, nullable=False)
    timestamp  = Column(DateTime, default=lambda: datetime.now(timezone.utc))
    is_outlier = Column(Boolean, default=False)
    raw_input  = Column(Text)
    missing_pct = Column(Float, default=0.0)


class PredictionRecord(Base):
    __tablename__ = "prediction_record"
    id                  = Column(Integer, primary_key=True, index=True)
    upid                = Column(String(64), index=True, nullable=False)
    model_version_id    = Column(String(32), nullable=False)
    timestamp           = Column(DateTime, default=lambda: datetime.now(timezone.utc))
    probability         = Column(Float, nullable=False)
    risk_level          = Column(String(16), nullable=False)
    risk_level_dm       = Column(Integer, nullable=False)
    confidence_score    = Column(String(64), nullable=False)
    safe_fail_triggered = Column(Boolean, default=False)
    explanation_json    = Column(Text)
    subtype             = Column(String(64), nullable=True)
    threshold_used      = Column(Float, nullable=False)


class ModelPerformanceLog(Base):
    __tablename__ = "model_performance_log"
    id               = Column(Integer, primary_key=True, index=True)
    model_version_id = Column(String(32), nullable=False)
    log_date         = Column(DateTime, default=lambda: datetime.now(timezone.utc))
    upid             = Column(String(64), nullable=False)
    missing_feature_pct = Column(Float, default=0.0)
    outlier_detected    = Column(Boolean, default=False)
    drift_score         = Column(Float, nullable=True)
    safe_fail_fired     = Column(Boolean, default=False)
    notes               = Column(Text, nullable=True)


Base.metadata.create_all(bind=engine)

# ─────────────────────────────────────────────
#  3. DATABASE DEPENDENCY
# ─────────────────────────────────────────────
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# ─────────────────────────────────────────────
#  4. JWT AUTHENTICATION
# ─────────────────────────────────────────────
security = HTTPBearer()

def create_token(upid: str) -> str:
    """Creates a JWT token embedding the UPID (never PII)."""
    expire = datetime.now(timezone.utc) + timedelta(hours=TOKEN_EXPIRE_H)
    payload = {"sub": upid, "exp": expire, "iat": datetime.now(timezone.utc)}
    return jwt.encode(payload, SECRET_KEY, algorithm=ALGORITHM)

def verify_token(credentials: HTTPAuthorizationCredentials = Depends(security)) -> str:
    """Verifies JWT and returns the UPID. Raises 401 if invalid."""
    try:
        payload = jwt.decode(
            credentials.credentials, SECRET_KEY, algorithms=[ALGORITHM]
        )
        upid: str = payload.get("sub")
        if upid is None:
            raise HTTPException(status_code=401, detail="Invalid token — UPID missing")
        return upid
    except JWTError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid or expired token",
            headers={"WWW-Authenticate": "Bearer"},
        )

# ─────────────────────────────────────────────
#  5. CONSENT CHECK
# ─────────────────────────────────────────────
def verify_consent(upid: str, db: Session):
    """
    Checks ConsentPolicy table for a valid, non-withdrawn consent.
    Raises 403 if consent is missing or has been withdrawn.
    Must be called before every prediction and health log operation.
    """
    consent = db.query(ConsentPolicy).filter(
        ConsentPolicy.upid == upid,
        ConsentPolicy.withdrawal_requested_at == None   # noqa: E711
    ).order_by(ConsentPolicy.agreed_at.desc()).first()

    if not consent:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail=(
                "Consent not found. The patient must digitally agree to the "
                "Tibyān data policy before any predictions can be generated. "
                "Please call POST /consent/agree first."
            )
        )
    return consent

# ─────────────────────────────────────────────
#  6. RATE LIMITER (in-memory per UPID)
# ─────────────────────────────────────────────
_rate_store: Dict[str, List[datetime]] = {}

def check_rate_limit(upid: str):
    """Allows max RATE_LIMIT predictions per UPID per hour."""
    now = datetime.now(timezone.utc)
    window_start = now - timedelta(hours=1)
    timestamps = _rate_store.get(upid, [])
    timestamps = [t for t in timestamps if t > window_start]
    if len(timestamps) >= RATE_LIMIT:
        raise HTTPException(
            status_code=status.HTTP_429_TOO_MANY_REQUESTS,
            detail=f"Rate limit exceeded. Maximum {RATE_LIMIT} predictions per hour per UPID."
        )
    timestamps.append(now)
    _rate_store[upid] = timestamps

# ─────────────────────────────────────────────
#  7. CLINICAL REFERENCE RANGES
#     Used for distribution plot data + outlier detection
# ─────────────────────────────────────────────
CLINICAL_RANGES = {
    'LBXGLU':        {'lo': 70,   'hi': 100,  'unit': 'mg/dL',  'label': 'Fasting Glucose'},
    'LBXIN':         {'lo': 2,    'hi': 25,   'unit': 'uU/mL',  'label': 'Serum Insulin'},
    'BMXBMI':        {'lo': 18.5, 'hi': 24.9, 'unit': 'kg/m²',  'label': 'BMI'},
    'BMXWAIST':      {'lo': 0,    'hi': 88,   'unit': 'cm',     'label': 'Waist Circumference'},
    'BMPWHR':        {'lo': 0,    'hi': 0.85, 'unit': '',       'label': 'Waist-Hip Ratio'},
    'LBDHDD':        {'lo': 40,   'hi': 60,   'unit': 'mg/dL',  'label': 'HDL Cholesterol'},
    'LBXTR':         {'lo': 0,    'hi': 150,  'unit': 'mg/dL',  'label': 'Triglycerides'},
    'VNAVEBPXSY':    {'lo': 90,   'hi': 120,  'unit': 'mm Hg',  'label': 'Systolic BP'},
    'VNLBAVEBPXDI':  {'lo': 60,   'hi': 80,   'unit': 'mm Hg',  'label': 'Diastolic BP'},
    'LBXSATSI':      {'lo': 7,    'hi': 40,   'unit': 'U/L',    'label': 'ALT'},
    'LBXSAL':        {'lo': 3.5,  'hi': 5.0,  'unit': 'g/dL',   'label': 'Serum Albumin'},
    'LBXSC3SI':      {'lo': 22,   'hi': 29,   'unit': 'mmol/L', 'label': 'Bicarbonate'},
    'LBXCP':         {'lo': 0.5,  'hi': 2.0,  'unit': 'pmol/mL','label': 'C-Peptide'},
    'LBDLDL':        {'lo': 0,    'hi': 100,  'unit': 'mg/dL',  'label': 'LDL Cholesterol'},
    'URXUMA':        {'lo': 0,    'hi': 30,   'unit': 'ug/mL',  'label': 'Urinary Albumin'},
    'LBDSBUSI':      {'lo': 2.5,  'hi': 7.1,  'unit': 'mmol/L', 'label': 'BUN'},
}

# Training set median values (approximated from NHANES non-diabetic population)
# Used for drift detection
TRAINING_MEDIANS = {
    'LBXGLU': 97.5,   'LBXIN': 10.3,   'BMXBMI': 27.8,
    'BMXWAIST': 94.0, 'LBDHDD': 51.0,  'LBXTR': 118.0,
    'VNAVEBPXSY': 122.0, 'LBXSATSI': 22.0, 'LBXSAL': 4.2,
}

# ─────────────────────────────────────────────
#  8. DATA INTEGRITY ENGINE
# ─────────────────────────────────────────────
RECOMMENDED_FEATURES = [
    'LBXCP', 'LBDHDD', 'LBDLDL', 'LBXTR', 'LBXSAL',
    'LBXSC3SI', 'LBXSATSI', 'URXUMA', 'LBDSBUSI',
    'VNAVEBPXSY', 'VNLBAVEBPXDI'
]
ALL_EXPECTED_FEATURES = list(CLINICAL_RANGES.keys()) + RECOMMENDED_FEATURES

def run_data_integrity_check(raw_input: dict) -> dict:
    """
    Computes data integrity metrics for a single prediction input.
    Returns a dict that gets logged to ModelPerformanceLog.
    """
    # 1. Missing feature percentage
    total_expected = len(ALL_EXPECTED_FEATURES)
    missing = sum(
        1 for f in ALL_EXPECTED_FEATURES
        if raw_input.get(f) is None
    )
    missing_pct = round(missing / total_expected * 100, 1)

    # 2. Outlier detection (values outside 3× normal range)
    outliers_found = []
    for feat, ranges in CLINICAL_RANGES.items():
        val = raw_input.get(feat)
        if val is None:
            continue
        lo, hi = ranges['lo'], ranges['hi']
        range_width = hi - lo if hi > lo else hi
        # Flag if value is more than 3× the range width away from bounds
        if val < lo - 3 * range_width or val > hi + 3 * range_width:
            outliers_found.append({
                'feature': feat,
                'value':   val,
                'normal_range': f"{lo}–{hi}",
                'label': ranges['label']
            })
    outlier_detected = len(outliers_found) > 0

    # 3. Drift score (how far input medians deviate from training medians)
    drift_scores = []
    for feat, train_median in TRAINING_MEDIANS.items():
        val = raw_input.get(feat)
        if val is not None and train_median != 0:
            drift_scores.append(abs(val - train_median) / train_median)
    drift_score = round(float(np.mean(drift_scores)), 4) if drift_scores else None

    # 4. Build the integrity report
    report = {
        'missing_feature_count':  missing,
        'missing_feature_pct':    missing_pct,
        'total_features_expected': total_expected,
        'outlier_detected':       outlier_detected,
        'outliers':               outliers_found,
        'drift_score':            drift_score,
        'drift_interpretation':   (
            'Low drift — input resembles training distribution'
            if drift_score is not None and drift_score < 0.3 else
            'Moderate drift — input differs from training population'
            if drift_score is not None and drift_score < 0.7 else
            'High drift — prediction reliability may be reduced'
            if drift_score is not None else
            'Drift not computable — insufficient features'
        ),
        'integrity_passed': missing_pct < 70 and not outlier_detected,
        'timestamp': datetime.now(timezone.utc).isoformat(),
    }
    return report

# ─────────────────────────────────────────────
#  9. DISTRIBUTION PLOT DATA BUILDER
# ─────────────────────────────────────────────
def build_distribution_data(raw_input: dict) -> List[dict]:
    """
    Returns per-feature data for the frontend distribution plot.
    Each entry contains the patient value, normal range bounds,
    and a status flag — ready for chart rendering.
    """
    distribution = []
    for feat, ranges in CLINICAL_RANGES.items():
        val = raw_input.get(feat)
        if val is None:
            continue
        lo, hi = ranges['lo'], ranges['hi']
        if val < lo:
            status = 'below_normal'
        elif val > hi:
            status = 'above_normal'
        else:
            status = 'normal'

        distribution.append({
            'feature_key':   feat,
            'label':         ranges['label'],
            'patient_value': val,
            'normal_lo':     lo,
            'normal_hi':     hi,
            'unit':          ranges['unit'],
            'status':        status,
            'percent_of_normal_hi': round(val / hi * 100, 1) if hi > 0 else None,
        })
    return distribution

# ─────────────────────────────────────────────
#  10. LONGITUDINAL ANALYSIS
# ─────────────────────────────────────────────
def compute_longitudinal(records: List[PredictionRecord]) -> dict:
    """
    Analyses historical prediction records for a UPID.
    Returns journey category, trend direction, and summary stats.
    """
    if not records:
        return {'error': 'No historical records found'}

    probs     = [r.probability for r in records]
    risks     = [r.risk_level  for r in records]
    threshold = records[0].threshold_used if records else 0.262

    first_prob = probs[0]
    last_prob  = probs[-1]
    delta      = last_prob - first_prob

    # Journey categorisation as per document requirement
    if first_prob < threshold and last_prob >= threshold:
        journey = "Low-to-High Risk"
        journey_detail = "Risk has deteriorated — patient has crossed the risk threshold"
        journey_alert  = True
    elif first_prob >= threshold and last_prob < threshold:
        journey = "High-to-Low Risk"
        journey_detail = "Risk has improved — patient has dropped below the risk threshold"
        journey_alert  = False
    elif delta > 0.10:
        journey = "Worsening Risk"
        journey_detail = f"Probability increased by {delta*100:.1f}% without crossing threshold"
        journey_alert  = True
    elif delta < -0.10:
        journey = "Improving Risk"
        journey_detail = f"Probability decreased by {abs(delta)*100:.1f}%"
        journey_alert  = False
    else:
        journey = "Stable Risk"
        journey_detail = "No significant change in risk over the period"
        journey_alert  = False

    # Moving average (3-point) to smooth noise
    moving_avg = []
    for i in range(len(probs)):
        window = probs[max(0, i-1): i+2]
        moving_avg.append(round(float(np.mean(window)), 4))

    return {
        'journey_category':    journey,
        'journey_detail':      journey_detail,
        'journey_alert':       journey_alert,
        'first_probability':   round(first_prob, 4),
        'latest_probability':  round(last_prob, 4),
        'delta':               round(delta, 4),
        'total_assessments':   len(records),
        'high_risk_count':     risks.count('High'),
        'medium_risk_count':   risks.count('Medium'),
        'low_risk_count':      risks.count('Low'),
        'probability_series':  [round(p, 4) for p in probs],
        'moving_average':      moving_avg,
        'timestamps':          [r.timestamp.isoformat() for r in records],
        'risk_series':         risks,
    }

# ─────────────────────────────────────────────
#  11. FASTAPI APP
# ─────────────────────────────────────────────
app = FastAPI(
    title="Tibyān Diabetes Prediction API",
    description=(
        "AI-powered diabetes risk prediction for the Tibyān smart healthcare system. "
        "All endpoints require JWT authentication. "
        "Consent must be recorded before predictions can be generated."
    ),
    version="4.0.0",
    docs_url="/docs",
    redoc_url="/redoc",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],    # restrict in production
    allow_methods=["*"],
    allow_headers=["*"],
)

# ─────────────────────────────────────────────
#  12. PYDANTIC SCHEMAS
# ─────────────────────────────────────────────

class TokenRequest(BaseModel):
    upid: str = Field(..., description="Unique Patient ID — never PII")

class ConsentRequest(BaseModel):
    upid:                  str
    policy_agreed_version: str = Field(default="v1.0")
    research_opt_in:       bool = Field(default=False)

class PredictionInput(BaseModel):
    """Raw clinical lab values — all in original NHANES units."""
    # Required features
    LBXGLU:   float = Field(..., description="Fasting plasma glucose (mg/dL)")
    LBXIN:    float = Field(..., description="Serum insulin (uU/mL)")
    BMXWAIST: float = Field(..., description="Waist circumference (cm)")
    BMXBMI:   float = Field(..., description="Body mass index (kg/m²)")
    # Recommended
    LBXCP:         Optional[float] = Field(None, description="C-peptide (pmol/mL)")
    LBDHDD:        Optional[float] = Field(None, description="HDL cholesterol (mg/dL)")
    LBDLDL:        Optional[float] = Field(None, description="LDL cholesterol (mg/dL)")
    LBXTR:         Optional[float] = Field(None, description="Triglycerides (mg/dL)")
    LBXSAL:        Optional[float] = Field(None, description="Serum albumin (g/dL)")
    LBXSC3SI:      Optional[float] = Field(None, description="Serum bicarbonate (mmol/L)")
    LBXSATSI:      Optional[float] = Field(None, description="ALT (U/L)")
    URXUMA:        Optional[float] = Field(None, description="Urinary albumin (ug/mL)")
    LBDSBUSI:      Optional[float] = Field(None, description="Blood urea nitrogen (mmol/L)")
    VNAVEBPXSY:    Optional[float] = Field(None, description="Mean systolic BP (mm Hg)")
    VNLBAVEBPXDI:  Optional[float] = Field(None, description="Mean diastolic BP (mm Hg)")
    LBXSCH:        Optional[float] = Field(None, description="Total cholesterol (mg/dL)")
    LBXSTB:        Optional[float] = Field(None, description="Serum total bilirubin (mg/dL)")
    LBXSAPSI:      Optional[float] = Field(None, description="Alkaline phosphatase (U/L)")
    LBDAPBSI:      Optional[float] = Field(None, description="Apolipoprotein B (g/L)")
    LBXAPB:        Optional[float] = Field(None, description="Apolipoprotein B (mg/dL)")
    LBDLDLSI:      Optional[float] = Field(None, description="LDL cholesterol (mmol/L)")
    LBXTC:         Optional[float] = Field(None, description="Total cholesterol TC (mg/dL)")
    LBXSTR:        Optional[float] = Field(None, description="Triglycerides STR (mg/dL)")
    LBXSBU:        Optional[float] = Field(None, description="Serum BUN (mg/dL)")
    LBXSTB:        Optional[float] = Field(None, description="Total bilirubin (mg/dL)")
    # Optional — improves prediction significantly when available
    BMPWHR:  Optional[float] = Field(None, description="Waist-hip ratio (optional)")
    BMXSAD1: Optional[float] = Field(None, description="Sagittal abdominal diameter cm (optional)")
    BMXSUB:  Optional[float] = Field(None, description="Subscapular skinfold (mm)")

class ReportRequest(BaseModel):
    date_from: str = Field(..., description="Start date YYYY-MM-DD")
    date_to:   str = Field(..., description="End date YYYY-MM-DD")

class WithdrawConsentRequest(BaseModel):
    upid: str

# ─────────────────────────────────────────────
#  13. ENDPOINTS
# ─────────────────────────────────────────────

# ── 13a. Health check (public) ────────────────
@app.get("/health", tags=["System"])
def health_check():
    """Public endpoint — confirms API is running."""
    return {
        "status":        "ok",
        "model_version": MODEL_VERSION,
        "timestamp":     datetime.now(timezone.utc).isoformat(),
        "service":       "Tibyān Diabetes Prediction API v4"
    }


# ── 13b. Issue JWT token ──────────────────────
@app.post("/auth/token", tags=["Authentication"])
def issue_token(request: TokenRequest):
    """
    Issues a JWT token for a given UPID.
    In production this endpoint would be called by the Auth Service
    AFTER validating credentials against the PII Store.
    The UPID is embedded in the token — never PII.
    """
    token = create_token(request.upid)
    return {
        "access_token": token,
        "token_type":   "bearer",
        "expires_in":   f"{TOKEN_EXPIRE_H} hours",
        "upid":         request.upid,
    }

# ── Dedicated Subtype Prediction Endpoint ────────
@app.post("/predict/subtype", tags=["Prediction"])
def predict_subtype_only(
    data: PredictionInput,
    upid: str = Depends(verify_token),
    db: Session = Depends(get_db)
):
    """
    Standalone endpoint for diabetic subtype classification.
    Uses the LightGBM subtype bundle.
    """
    # 1. Rate limit & Consent
    check_rate_limit(upid)
    verify_consent(upid, db)
    
    # 2. Extract inputs
    raw_input = {k: v for k, v in data.dict().items() if v is not None}
    
    # 3. Call your new subtype function from pipeline
    # Ensure pipeline.py has the function: predict_diabetic_subtype
    subtype_result = pipeline.predict_diabetic_subtype(raw_input)
    
    return {
        "upid": upid,
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "subtype_classification": subtype_result
    }
# ── 13c. Record consent ───────────────────────
@app.post("/consent/agree", tags=["Consent"])
def record_consent(
    request: ConsentRequest,
    upid: str = Depends(verify_token),
    db:   Session = Depends(get_db)
):
    """
    Records a patient's digital consent to the data policy.
    Must be called before any prediction can be generated.
    Satisfies the requirement: 'user must digitally agree to the
    policy before their first prediction.'
    """
    # Verify the token UPID matches the request UPID
    if upid != request.upid:
        raise HTTPException(
            status_code=403,
            detail="Token UPID does not match request UPID"
        )

    consent = ConsentPolicy(
        upid                 = request.upid,
        policy_agreed_version = request.policy_agreed_version,
        agreed_at            = datetime.now(timezone.utc),
        research_opt_in      = request.research_opt_in,
        withdrawal_requested_at = None
    )
    db.add(consent)
    db.commit()
    logger.info(f"Consent recorded for UPID={request.upid}")

    return {
        "status":  "consent_recorded",
        "upid":    request.upid,
        "version": request.policy_agreed_version,
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "message": "Consent recorded. You may now generate predictions."
    }


# ── 13d. Withdraw consent ─────────────────────
@app.post("/consent/withdraw", tags=["Consent"])
def withdraw_consent(
    request: WithdrawConsentRequest,
    upid:    str     = Depends(verify_token),
    db:      Session = Depends(get_db)
):
    """
    Marks consent as withdrawn. All future predictions blocked.
    Does NOT delete historical data (audit trail must be preserved).
    """
    if upid != request.upid:
        raise HTTPException(status_code=403, detail="UPID mismatch")

    consent = db.query(ConsentPolicy).filter(
        ConsentPolicy.upid == request.upid,
        ConsentPolicy.withdrawal_requested_at == None  # noqa
    ).first()

    if not consent:
        raise HTTPException(status_code=404, detail="No active consent found")

    consent.withdrawal_requested_at = datetime.now(timezone.utc)
    db.commit()
    logger.info(f"Consent withdrawn for UPID={request.upid}")

    return {
        "status":  "consent_withdrawn",
        "upid":    request.upid,
        "message": "Consent withdrawn. No further predictions will be generated. "
                   "Historical data is retained for audit compliance.",
        "timestamp": datetime.now(timezone.utc).isoformat()
    }


# ── 13e. MAIN PREDICTION ENDPOINT ─────────────
@app.post("/predict", tags=["Prediction"])
def predict(
    data: PredictionInput,
    upid: str     = Depends(verify_token),
    db:   Session = Depends(get_db)
):
    """
    Core prediction endpoint. Returns:
      - diabetes risk prediction + probability
      - SHAP-based explanation with clinical context
      - confidence score
      - T2D subtype (if diabetic)
      - distribution plot data vs clinical thresholds
      - data integrity report
      - longitudinal trend (if prior predictions exist)

    Requires: valid JWT + active consent record
    """

    # ── Step 1: Rate limit ─────────────────────
    check_rate_limit(upid)

    # ── Step 2: Verify consent ─────────────────
    verify_consent(upid, db)

    # ── Step 3: Build raw input dict ───────────
    raw_input = {k: v for k, v in data.dict().items() if v is not None}

    # ── Step 4: Data Integrity Check ───────────
    integrity = run_data_integrity_check(raw_input)

    # ── Step 5: Run prediction pipeline ────────
    result = pipeline.predict(raw_input)

    if result['status'] == 'rejected':
        # Log the rejected attempt
        log = ModelPerformanceLog(
            model_version_id = MODEL_VERSION,
            upid             = upid,
            missing_feature_pct = integrity['missing_feature_pct'],
            outlier_detected    = integrity['outlier_detected'],
            drift_score         = integrity['drift_score'],
            safe_fail_fired     = True,
            notes               = json.dumps(result['issues'])
        )
        db.add(log)
        db.commit()
        return JSONResponse(status_code=422, content={
            "status":    "rejected",
            "issues":    result['issues'],
            "integrity": integrity,
        })

    # ── Step 6: Distribution plot data ─────────
    distribution_data = build_distribution_data(raw_input)

    # ── Step 7: Save HealthLog ─────────────────
    health_log = HealthLog(
        upid        = upid,
        timestamp   = datetime.now(timezone.utc),
        is_outlier  = integrity['outlier_detected'],
        raw_input   = json.dumps(raw_input),
        missing_pct = integrity['missing_feature_pct'],
    )
    db.add(health_log)

    # ── Step 8: Save PredictionRecord ──────────
    pred_record = PredictionRecord(
        upid             = upid,
        model_version_id = MODEL_VERSION,
        timestamp        = datetime.now(timezone.utc),
        probability      = result['probability'],
        risk_level       = result['risk_level'],
        risk_level_dm    = result['prediction'],
        confidence_score = result['confidence'],
        safe_fail_triggered = False,
        explanation_json = json.dumps(result.get('explanation', {})),
        subtype          = result.get('subtype_classification', {}).get('subtype'),
        threshold_used   = result['threshold_used'],
    )
    db.add(pred_record)

    # ── Step 9: Save ModelPerformanceLog ───────
    perf_log = ModelPerformanceLog(
        model_version_id    = MODEL_VERSION,
        upid                = upid,
        missing_feature_pct = integrity['missing_feature_pct'],
        outlier_detected    = integrity['outlier_detected'],
        drift_score         = integrity['drift_score'],
        safe_fail_fired     = False,
        notes               = f"Prediction successful. Risk={result['risk_level']}, "
                              f"Prob={result['probability']:.4f}"
    )
    db.add(perf_log)
    db.commit()

    # ── Step 10: Longitudinal analysis ─────────
    prior_records = db.query(PredictionRecord).filter(
        PredictionRecord.upid == upid
    ).order_by(PredictionRecord.timestamp.asc()).all()

    longitudinal = compute_longitudinal(prior_records) \
                   if len(prior_records) > 1 else {
                       'message': 'First prediction for this UPID — '
                                  'longitudinal analysis available after 2+ assessments'
                   }

    logger.info(
        f"Prediction complete | UPID={upid} | "
        f"Risk={result['risk_level']} | Prob={result['probability']:.4f}"
    )

    # ── Step 11: Build complete response ────────
    return {
        # ── Core prediction ─────────────────
        "status":           result['status'],
        "prediction":       result['prediction'],
        "probability":      result['probability'],
        "risk_level":       result['risk_level'],
        "confidence":       result['confidence'],
        "threshold_used":   result['threshold_used'],
        "model_version":    MODEL_VERSION,
        "timestamp":        datetime.now(timezone.utc).isoformat(),

        # ── Clinical explanation ─────────────
        "explanation":      result.get('explanation', {}),

        # ── T2D subtype (if diabetic) ────────
        "subtype_classification": result.get('subtype_classification', {}),

        # ── Distribution plot data ───────────
        "distribution_data": distribution_data,

        # ── Data integrity report ────────────
        "data_integrity": integrity,

        # ── Longitudinal trend ───────────────
        "longitudinal": longitudinal,
    }


# ── 13f. LONGITUDINAL HISTORY ─────────────────
@app.get("/history", tags=["Longitudinal"])
def get_history(
    limit: int    = 20,
    upid:  str    = Depends(verify_token),
    db:    Session = Depends(get_db)
):
    """
    Returns full prediction history for a UPID with
    longitudinal trend analysis.
    Satisfies: 'longitudinal analysis categorising risk journey.'
    """
    verify_consent(upid, db)

    records = db.query(PredictionRecord).filter(
        PredictionRecord.upid == upid
    ).order_by(PredictionRecord.timestamp.asc()).limit(limit).all()

    if not records:
        return {"upid": upid, "message": "No prediction history found",
                "records": [], "longitudinal": {}}

    longitudinal = compute_longitudinal(records)

    history = []
    for r in records:
        history.append({
            "timestamp":        r.timestamp.isoformat(),
            "probability":      r.probability,
            "risk_level":       r.risk_level,
            "prediction":       r.risk_level_dm,
            "confidence":       r.confidence_score,
            "safe_fail":        r.safe_fail_triggered,
            "subtype":          r.subtype,
            "model_version":    r.model_version_id,
        })

    return {
        "upid":        upid,
        "total":       len(history),
        "records":     history,
        "longitudinal": longitudinal,
    }


# ── 13g. DATA INTEGRITY REPORT ────────────────
@app.get("/integrity/report", tags=["Data Integrity"])
def get_integrity_report(
    upid: str     = Depends(verify_token),
    db:   Session = Depends(get_db)
):
    """
    Returns a Data Integrity Report covering:
      - missing feature rates across all submissions
      - outlier frequency
      - drift scores over time
      - safe-fail trigger history
    Satisfies the Data Integrity Engine requirement.
    """
    verify_consent(upid, db)

    logs = db.query(ModelPerformanceLog).filter(
        ModelPerformanceLog.upid == upid
    ).order_by(ModelPerformanceLog.log_date.asc()).all()

    if not logs:
        return {"upid": upid, "message": "No integrity logs found"}

    missing_rates = [l.missing_feature_pct for l in logs]
    drift_scores  = [l.drift_score for l in logs if l.drift_score is not None]
    outlier_count = sum(1 for l in logs if l.outlier_detected)
    safe_fail_count = sum(1 for l in logs if l.safe_fail_fired)

    return {
        "upid":               upid,
        "total_submissions":  len(logs),
        "missing_features": {
            "average_pct": round(float(np.mean(missing_rates)), 1),
            "max_pct":     round(float(np.max(missing_rates)), 1),
            "min_pct":     round(float(np.min(missing_rates)), 1),
        },
        "drift": {
            "average_score":   round(float(np.mean(drift_scores)), 4) if drift_scores else None,
            "latest_score":    drift_scores[-1] if drift_scores else None,
            "interpretation":  (
                "Input data is consistent with training population"
                if drift_scores and np.mean(drift_scores) < 0.3 else
                "Some drift detected — predictions may be less reliable"
            )
        },
        "outliers": {
            "total_detected":  outlier_count,
            "rate_pct":        round(outlier_count / len(logs) * 100, 1),
        },
        "safe_fail": {
            "total_triggered": safe_fail_count,
            "rate_pct":        round(safe_fail_count / len(logs) * 100, 1),
        },
        "compliance_status": (
            "PASS — data quality is within acceptable bounds"
            if np.mean(missing_rates) < 60 and outlier_count / len(logs) < 0.1
            else "REVIEW — data quality issues detected"
        ),
        "generated_at": datetime.now(timezone.utc).isoformat(),
    }


# ── 13h. PDF REPORT GENERATION ────────────────
@app.post("/report/generate", tags=["Reporting"])
def generate_pdf_report(
    request: ReportRequest,
    upid:    str     = Depends(verify_token),
    db:      Session = Depends(get_db)
):
    """
    Generates a non-editable PDF report for a date range.
    Includes: latest assessment, risk trend, written summary,
    biomarker distribution vs clinical thresholds.
    """
    verify_consent(upid, db)

    try:
        date_from = datetime.strptime(request.date_from, "%Y-%m-%d")
        date_to   = datetime.strptime(request.date_to,   "%Y-%m-%d")
    except ValueError:
        raise HTTPException(
            status_code=400,
            detail="Invalid date format. Use YYYY-MM-DD."
        )

    if date_from > date_to:
        raise HTTPException(
            status_code=400,
            detail="date_from must be before date_to"
        )

    # Fetch prediction records for the date range
    records = db.query(PredictionRecord).filter(
        PredictionRecord.upid      == upid,
        PredictionRecord.timestamp >= date_from,
        PredictionRecord.timestamp <= date_to
    ).order_by(PredictionRecord.timestamp.asc()).all()

    # Build history list for report builder
    history = []
    for r in records:
        exp = {}
        try:
            exp = json.loads(r.explanation_json) if r.explanation_json else {}
        except Exception:
            pass

        # Fetch matching HealthLog for raw_input
        hlog = db.query(HealthLog).filter(
          HealthLog.upid == upid,
          func.abs(
          func.timestampdiff(
             text('SECOND'),
             HealthLog.timestamp,
            r.timestamp
            )
          ) < 900  # within 15 minutes
        ).first()

        raw_input = {}
        if hlog and hlog.raw_input:
            try:
                raw_input = json.loads(hlog.raw_input)
            except Exception:
                pass

        history.append({
            'timestamp':        r.timestamp.isoformat(),
            'probability':      r.probability,
            'risk_level':       r.risk_level,
            'prediction':       r.risk_level_dm,
            'confidence':       r.confidence_score,
            'safe_fail_triggered': r.safe_fail_triggered,
            'explanation':      exp,
            'subtype_classification': {
                'subtype': r.subtype,
                'subtype_description': ''
            },
            'raw_input': raw_input,
            'status': 'success',
        })

    if not history:
        raise HTTPException(
            status_code=404,
            detail=f"No predictions found for UPID {upid} between "
                   f"{request.date_from} and {request.date_to}"
        )

    latest = history[-1] if history else None

    # Build PDF
    pdf_bytes = build_report(
        upid               = upid,
        prediction_history = history,
        date_from          = request.date_from,
        date_to            = request.date_to,
        latest_prediction  = latest,
    )

    filename = (
        f"tibyan_report_{upid[:8]}_"
        f"{request.date_from}_{request.date_to}.pdf"
    )
    logger.info(f"PDF report generated | UPID={upid} | {len(pdf_bytes):,} bytes")

    return Response(
        content     = pdf_bytes,
        media_type  = "application/pdf",
        headers     = {
            "Content-Disposition":  f"attachment; filename={filename}",
            "Content-Type":         "application/pdf",
            "X-Content-Type-Options": "nosniff",
        }
    )


# ── 13i. DISTRIBUTION DATA (standalone) ───────
@app.post("/distribution", tags=["Reporting"])
def get_distribution_data(
    data: PredictionInput,
    upid: str     = Depends(verify_token),
    db:   Session = Depends(get_db)
):
    """
    Returns distribution plot data for frontend chart rendering.
    Compares each biomarker to clinical normal ranges.
    Does NOT run the ML model — pure data comparison.
    Satisfies: 'display distribution plot overlaying patient data
    against standard clinical thresholds.'
    """
    verify_consent(upid, db)
    raw_input = {k: v for k, v in data.dict().items() if v is not None}
    distribution = build_distribution_data(raw_input)

    above_normal = [d for d in distribution if d['status'] == 'above_normal']
    below_normal = [d for d in distribution if d['status'] == 'below_normal']

    return {
        "upid":             upid,
        "biomarkers":       distribution,
        "summary": {
            "total_measured":     len(distribution),
            "above_normal_count": len(above_normal),
            "below_normal_count": len(below_normal),
            "normal_count":       len(distribution) - len(above_normal) - len(below_normal),
            "above_normal":       [d['label'] for d in above_normal],
            "below_normal":       [d['label'] for d in below_normal],
        },
        "timestamp": datetime.now(timezone.utc).isoformat(),
    }


# ─────────────────────────────────────────────
#  14. STARTUP EVENT
# ─────────────────────────────────────────────
@app.on_event("startup")
async def startup():
    logger.info("=" * 50)
    logger.info("  Tibyān API starting up")
    logger.info(f"  Model version : {MODEL_VERSION}")
    logger.info(f"  Database      : {DB_URL}")
    logger.info("  All tables created")
    logger.info("=" * 50)


# ─────────────────────────────────────────────
#  15. RUN
# ─────────────────────────────────────────────
if __name__ == "__main__":
    import uvicorn
    uvicorn.run("api:app", host="0.0.0.0", port=8000, reload=True)