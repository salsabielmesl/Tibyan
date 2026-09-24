# ─────────────────────────────────────────────
#  SECTION 1: IMPORTS AND MODEL LOADING
# ─────────────────────────────────────────────
import joblib
import numpy as np
import pandas as pd
from pathlib import Path
from xgboost import XGBClassifier
import shap
from datetime import datetime, timezone
import warnings

warnings.filterwarnings(
    'ignore',
    message='LightGBM binary classifier with TreeExplainer'
)

MODEL_DIR = Path(__file__).parent

print("Loading models...")

# Load one at a time so you can see exactly where it stalls
print("  loading lgbm...")
lgb_model = joblib.load(MODEL_DIR / "diabetes_lgbm_v4_fixed.pkl")
print("  ✓ lgbm done")

print("  loading xgb...")
import xgboost as xgb
xgb_booster = xgb.Booster()
xgb_booster.load_model(str(MODEL_DIR / "diabetes_xgb_v4_fixed.json"))
xgb_model = xgb_booster

print("  loading weights...")
weights = joblib.load(MODEL_DIR / "ensemble_weights_v4_fixed.pkl")
print("  ✓ weights done")

print("  loading threshold...")
threshold = joblib.load(MODEL_DIR / "clinical_threshold_v4_fixed.pkl")
print("  ✓ threshold done")

print("  loading feature list...")
feature_list = joblib.load(MODEL_DIR / "feature_list_v4_fixed.pkl")
print("  ✓ feature list done")

print("  loading clip bounds...")
clip_bounds = joblib.load(MODEL_DIR / "clip_bounds_v4_fixed.pkl")
print("  ✓ clip bounds done")

print("  loading feat clip bounds...")
feat_clip_bounds = joblib.load(MODEL_DIR / "feat_clip_bounds_v4_fixed.pkl")
print("  ✓ feat clip bounds done")

print("  loading log cols...")
log_cols = joblib.load(MODEL_DIR / "log_transform_cols_v4_fixed.pkl")
print("  ✓ log cols done")

print("  loading col mapping...")
col_mapping = joblib.load(MODEL_DIR / "column_mapping_v4_fixed.pkl")
print("  ✓ col mapping done")

print("  loading redundant cols...")
redundant_cols = joblib.load(MODEL_DIR / "redundant_cols_v4_fixed.pkl")
print("  ✓ redundant cols done")

#  NEW: Loading 3-class Subtype Classifier bundle
print("  loading diabetic subtypes lgbm bundle...")
subtypes_bundle = joblib.load(MODEL_DIR / "diabetic_subtypes_lgbm.pkl")
subtypes_model = subtypes_bundle["model"]
subtypes_le = subtypes_bundle["label_encoder"]
subtypes_features = subtypes_bundle["feature_names"]
print("  ✓ Diabetic subtypes model ready")

print("✓ All models loaded")

print("  loading shap explainer...")
explainer = shap.TreeExplainer(lgb_model)
print("  ✓ shap explainer ready")


# ─────────────────────────────────────────────
#  SECTION 2: FEATURE ENGINEERING
# ─────────────────────────────────────────────
def engineer_features(df: pd.DataFrame) -> pd.DataFrame:
    """
    Replicates all feature engineering from training script.
    Input:  raw clinical values (original NHANES column names)
    Output: same df with feat_ columns added
    """

    # ── v3 confirmed features ──────────────────
    if 'LBXGLU' in df.columns and 'LBXIN' in df.columns:
        df['feat_HOMA_IR'] = (df['LBXGLU'] * df['LBXIN']) / 405

    if 'LBXGLU' in df.columns and 'LBXIN' in df.columns:
        df['feat_GlucoseInsulinGap'] = (
            df['LBXGLU'] / df['LBXIN'].replace(0, np.nan)
        )

    if 'LBXGLU' in df.columns and 'LBXSAL' in df.columns:
        df['feat_AlbuminGlucose'] = (
            df['LBXGLU'] / df['LBXSAL'].replace(0, np.nan)
        )

    if 'LBXGLU' in df.columns and 'LBXSC3SI' in df.columns:
        df['feat_AcidosisRisk'] = (
            df['LBXGLU'] / df['LBXSC3SI'].replace(0, np.nan)
        )

    if 'LBXIN' in df.columns and 'LBXCP' in df.columns:
        df['feat_InsulinLoad'] = df['LBXIN'] * df['LBXCP']

    # ── v4 raw-data features ───────────────────
    # Missingness flags first — before any imputation
    df['feat_SAD_missing'] = df['BMXSAD1'].isnull().astype(int) \
        if 'BMXSAD1' in df.columns else 1
    df['feat_WHR_missing'] = df['BMPWHR'].isnull().astype(int) \
        if 'BMPWHR' in df.columns else 1

    if 'BMPWHR' in df.columns and 'LBXGLU' in df.columns:
        df['feat_WHR_Glucose'] = df['BMPWHR'] * df['LBXGLU']

    if 'BMXSAD1' in df.columns and 'BMXWAIST' in df.columns:
        df['feat_SAD_Waist'] = df['BMXSAD1'] * df['BMXWAIST']

    if 'BMPWHR' in df.columns and 'feat_HOMA_IR' in df.columns:
        df['feat_WHR_HOMA'] = df['BMPWHR'] * df['feat_HOMA_IR']

    if 'BMXSAD1' in df.columns and 'BMXBMI' in df.columns:
        df['feat_SAD_BMI_ratio'] = (
            df['BMXSAD1'] / df['BMXBMI'].replace(0, np.nan)
        )

    if 'BMPWHR' in df.columns and 'LBXTR' in df.columns:
        df['feat_WHR_TG'] = df['BMPWHR'] * df['LBXTR']

    return df


#  NEW: Custom Feature Engineering for 3-class Multi-class Pipeline
def engineer_subtype_features(df: pd.DataFrame) -> pd.DataFrame:
    """
    Applies custom feature engineering for the 3-class diabetic subtype model.
    """
    df_out = df.copy()
    
    # 1. Waist to BMI Ratio
    if 'BMXWAIST' in df_out.columns and 'BMXBMI' in df_out.columns:
        df_out['Waist_to_BMI'] = df_out['BMXWAIST'] / (df_out['BMXBMI'] + 1e-5)
        
    # 2. Log Beta-Cell Output
    if 'BetaCell_Output' in df_out.columns:
        df_out['Log_BetaCell_Output'] = np.log1p(df_out['BetaCell_Output'])
        
    # 3. Clinical Threshold Indicator
    if 'HOMA_IR' in df_out.columns:
        df_out['High_IR_Flag'] = (df_out['HOMA_IR'] > 1.9).astype(int)
        
    return df_out


# ─────────────────────────────────────────────
#  SECTION 3: PREPROCESSING
# ─────────────────────────────────────────────
NO_LOG_FEATS = {'feat_SAD_missing', 'feat_WHR_missing', 'feat_SAD_BMI_ratio'}

def preprocess(df: pd.DataFrame) -> pd.DataFrame:
    """
    Applies all preprocessing steps in training order.
    Uses saved bounds from training data — NOT recomputed on input.
    """

    # ── Step A: Clip engineered features ──────
    for feat, bounds in feat_clip_bounds.items():
        if feat in df.columns:
            df[feat] = df[feat].replace([np.inf, -np.inf], np.nan)
            df[feat] = df[feat].fillna(df[feat].median()
                               if len(df) > 1 else bounds['lower'])
            df[feat] = df[feat].clip(
                lower=bounds['lower'], upper=bounds['upper']
            )
            if feat not in NO_LOG_FEATS:
                df[feat] = np.log1p(df[feat].clip(lower=0))

    # ── Step B: Clip original clinical columns ─
    for col, bounds in clip_bounds.items():
        if col in df.columns:
            df[col] = df[col].clip(
                lower=bounds['lower'], upper=bounds['upper']
            )

    # ── Step C: Log-transform skewed biomarkers ─
    for col in log_cols:
        if col in df.columns:
            df[col] = np.log1p(df[col])

    # ── Step D: Rename columns ─────────────────
    df = df.rename(columns=col_mapping)

    # ── Step E: Drop redundant columns ─────────
    renamed_redundant = [
        col_mapping.get(c, c) for c in redundant_cols
    ]
    df = df.drop(
        columns=[c for c in renamed_redundant if c in df.columns],
        errors='ignore'
    )

    return df


# ─────────────────────────────────────────────
#  SECTION 4: SAFE FAIL VALIDATOR
# ─────────────────────────────────────────────

# Physiological ranges — values outside these are data entry errors
PHYSIOLOGICAL_RANGES = {
    'LBXGLU':   (50,  600),   # fasting glucose mg/dL
    'LBXIN':    (1,   300),   # insulin uU/mL
    'BMXBMI':   (10,  80),    # BMI kg/m2
    'BMXWAIST': (40,  200),   # waist cm
    'LBXTR':    (20,  2000),  # triglycerides mg/dL
    'LBDHDD':   (10,  150),   # HDL mg/dL
    'BMPWHR':   (0.4, 1.8),   # waist-hip ratio
    'BMXSAD1':  (5,   50),    # SAD cm
}

# Minimum required features — prediction is unreliable without these
REQUIRED_FEATURES = ['LBXGLU', 'LBXIN', 'BMXBMI', 'BMXWAIST']

def apply_safe_fail(raw_input: dict) -> list[str]:
    """
    Returns a list of issues. Empty list = safe to predict.
    """
    issues = []

    # Check required features are present and not null
    for feat in REQUIRED_FEATURES:
        if feat not in raw_input or raw_input[feat] is None:
            issues.append(
                f"Required feature '{feat}' is missing. "
                f"Cannot generate prediction."
            )

    # Check physiological ranges
    for feat, (lo, hi) in PHYSIOLOGICAL_RANGES.items():
        val = raw_input.get(feat)
        if val is not None and not (lo <= val <= hi):
            issues.append(
                f"'{feat}' value {val} is outside the physiological "
                f"range [{lo}, {hi}]. Check for data entry error."
            )

    # Check glucose unit consistency
    # LBXGLU (mg/dL) and LBXGLUSI (mmol/L) should agree within 1 mmol/L
    glu_mgdl  = raw_input.get('LBXGLU')
    glu_mmol  = raw_input.get('LBXGLUSI')
    if glu_mgdl and glu_mmol:
        expected = glu_mgdl / 18.0
        if abs(expected - glu_mmol) > 1.5:
            issues.append(
                f"Glucose unit mismatch: {glu_mgdl} mg/dL should be "
                f"~{expected:.1f} mmol/L but got {glu_mmol}. "
                f"Possible unit error."
            )

    return issues


# ─────────────────────────────────────────────
#  SECTION 5: EXPLANATION GENERATOR
# ─────────────────────────────────────────────

# Human-readable names for the UI
# Maps internal feature names → plain English labels
FEATURE_LABELS = {
    'Fasting plasma glucose (mg/dL)' : 'Fasting glucose',
    'Serum insulin (uU/mL)'          : 'Fasting insulin',
    'feat_HOMA_IR'                   : 'Insulin resistance (HOMA-IR)',
    'feat_GlucoseInsulinGap'         : 'Glucose-insulin gap',
    'feat_AlbuminGlucose'            : 'Glucose-albumin ratio',
    'feat_AcidosisRisk'              : 'Metabolic acidosis risk',
    'feat_InsulinLoad'               : 'Pancreatic load (insulin × C-peptide)',
    'Waist-hip ratio'                : 'Central fat distribution (WHR)',
    'feat_WHR_Glucose'               : 'Fat distribution × glucose',
    'feat_WHR_HOMA'                  : 'Central adiposity × insulin resistance',
    'Waist circumference (cm)'       : 'Waist circumference',
    'BMI (kg/m2)'                    : 'Body mass index',
    'Serum triglycerides (mg/dL) TR' : 'Triglycerides',
    'Serum HDL cholesterol (mg/dL)'  : 'HDL cholesterol',
    'Mean systolic BP (mm Hg)'       : 'Systolic blood pressure',
    'Serum BUN (mmol/L)'              : 'Kidney function (BUN)',
    'Urinary albumin (ug/mL)'        : 'Urinary albumin (kidney leak)',
    'ALT (U/L)'                      : 'Liver enzyme (ALT)',
    'Serum C-peptide (pmol/mL)'      : 'C-peptide (beta-cell output)',
    'Serum albumin (g/dL)'           : 'Serum albumin',
    'Serum bicarbonate (mmol/L)'     : 'Bicarbonate buffer',
    'feat_SAD_Waist'     : 'Visceral fat volume (SAD × Waist)',
    'feat_SAD_BMI_ratio' : 'Visceral fat index (SAD / BMI)',
    'feat_WHR_TG'        : 'Central fat × triglycerides',
    'feat_SAD_missing'   : 'SAD measurement availability',
    'feat_WHR_missing'   : 'WHR measurement availability',
}

# Clinical thresholds for contextual messages
# Format: (low_threshold, high_threshold, low_msg, normal_msg, high_msg)
CLINICAL_CONTEXT = {
    'Fasting plasma glucose (mg/dL)': {
        'low':    (0,   100, "Normal fasting glucose"),
        'border': (100, 126, "Pre-diabetic fasting glucose"),
        'high':   (126, 999, "Elevated fasting glucose — meets diagnostic threshold"),
    },
    'Waist-hip ratio': {
        'low':    (0,    0.85, "Healthy fat distribution"),
        'border': (0.85, 0.90, "Borderline central fat distribution"),
        'high':   (0.90, 9.99, "Central fat distribution — android pattern"),
    },
    'feat_HOMA_IR': {
        'low':    (0,  1.0, "Normal insulin sensitivity"),
        'border': (1,  2.5, "Mild insulin resistance"),
        'high':   (2.5, 99, "Significant insulin resistance"),
    },
}

def get_clinical_context(feature_name: str, raw_value: float) -> str:
    """Returns a plain-English clinical interpretation of a raw value."""
    ctx = CLINICAL_CONTEXT.get(feature_name)
    if not ctx or raw_value is None:
        return ""
    for level in ['high', 'border', 'low']:
        lo, hi, msg = ctx[level]
        if lo <= raw_value < hi:
            return msg
    return ""


def explain_prediction(X_processed: pd.DataFrame,
                        raw_input: dict,
                        top_n: int = 5) -> dict:
    """
    Generates SHAP-based explanation for a single prediction.
    """

    # ── 1. Compute SHAP values ─────────────────
    shap_vals = explainer.shap_values(X_processed)

    # Handle LightGBM output format
    if isinstance(shap_vals, list):
        shap_arr = shap_vals[1][0]   # class 1 (diabetic), first patient
    elif len(np.array(shap_vals).shape) == 3:
        shap_arr = shap_vals[0, :, 1]
    else:
        shap_arr = shap_vals[0]

    # ── 2. Build feature → shap value dict ────
    feature_names  = X_processed.columns.tolist()
    shap_dict      = dict(zip(feature_names, shap_arr))

    # ── 3. Sort by absolute impact ────────────
    sorted_features = sorted(
        shap_dict.items(),
        key=lambda x: abs(x[1]),
        reverse=True
    )

    # ── 4. Build top factors for UI ───────────
    contributing_factors = []
    for feat_name, shap_val in sorted_features[:top_n]:
        if abs(shap_val) < 0.01:   # skip near-zero contributions
            continue

        label     = FEATURE_LABELS.get(feat_name, feat_name)
        direction = 'increases' if shap_val > 0 else 'decreases'
        impact    = abs(shap_val)
        impact_level = (
            'High'   if impact > 0.3 else
            'Medium' if impact > 0.1 else
            'Low'
        )

        # Get raw value for this feature if available
        raw_val = raw_input.get(
            # reverse-map renamed columns back to original names
            next((k for k, v in col_mapping.items() if v == feat_name),
                 feat_name),
            None
        )

        context = get_clinical_context(feat_name, raw_val)

        contributing_factors.append({
            'feature'      : feat_name,
            'label'        : label,
            'shap_value'   : round(float(shap_val), 4),
            'direction'    : direction,
            'impact_level' : impact_level,
            'raw_value'    : raw_val,
            'context'      : context,
        })

    # ── 5. Generate summary text ───────────────
    risk_factors  = [f for f in contributing_factors if f['direction'] == 'increases']
    protect_factors = [f for f in contributing_factors if f['direction'] == 'decreases']

    if risk_factors:
        top_risk_labels = [f['label'] for f in risk_factors[:3]]
        risk_str = ', '.join(top_risk_labels)
        summary = (
            f"The primary drivers of elevated diabetes risk for this patient are: "
            f"{risk_str}. "
        )
    else:
        summary = "No dominant risk factors were identified. "

    if protect_factors:
        top_prot_labels = [f['label'] for f in protect_factors[:2]]
        prot_str = ' and '.join(top_prot_labels)
        summary += (
            f"Protective factors include {prot_str}, "
            f"which reduce the predicted risk."
        )

    return {
        'contributing_factors': contributing_factors,
        'shap_values'         : {k: round(float(v), 4)
                                 for k, v in shap_dict.items()},
        'summary_text'        : summary,
        'top_risk_features'   : [f['label'] for f in risk_factors[:3]],
        'top_protective_features': [f['label'] for f in protect_factors[:2]],
    }


# ─────────────────────────────────────────────
#  SECTION 5A: CONFIDENCE SCORER
# ─────────────────────────────────────────────
def compute_confidence(raw_input: dict) -> str:
    """
    Tells the UI how much to trust the prediction
    """
    recommended = [
        'LBXCP', 'LBDHDD', 'LBDLDL', 'LBXTR', 'LBXSAL',
        'LBXSC3SI', 'LBXSATSI', 'URXUMA', 'LBDSBUSI',
        'VNAVEBPXSY', 'VNLBAVEBPXDI'
    ]
    present = sum(
        1 for f in recommended
        if raw_input.get(f) is not None
    )
    if present >= 9:
        return 'High'
    elif present >= 5:
        return 'Medium'
    else:
        return 'Low — consider collecting more lab values'


# ─────────────────────────────────────────────
#  SECTION 5B: MAIN PREDICT FUNCTION (BINARY)
# ─────────────────────────────────────────────
def predict(raw_input: dict) -> dict:
    """
    Full prediction pipeline for Binary Classification.
    """
    # ── 1. Safe fail check ─────────────────────
    issues = apply_safe_fail(raw_input)
    if issues:
        return {
            'status':     'rejected',
            'prediction':  None,
            'probability': None,
            'issues':      issues,
            'timestamp':   datetime.now(timezone.utc).isoformat()
        }

    # ── 2. Build dataframe ─────────────────────
    df = pd.DataFrame([raw_input])

    # ── 3. Engineer features ───────────────────
    df = engineer_features(df)

    # ── 4. Preprocess ──────────────────────────
    df = preprocess(df)

    # ── 5. Drop non-feature columns ───────────
    drop_always = ['SEQN', 'SEQN_new', 'SDDSRVYR', 'Target']
    df = df.drop(
        columns=[c for c in drop_always if c in df.columns],
        errors='ignore'
    )

    # ── 6. Align to training feature order ────
    for col in feature_list:
        if col not in df.columns:
            df[col] = np.nan
    df = df[feature_list]

    # ── 7. Generate predictions ────────────────
    lgb_prob = float(lgb_model.predict_proba(df)[:, 1][0])
    dmatrix = xgb.DMatrix(df)
    xgb_prob = float(xgb_model.predict(dmatrix)[0])
    blended  = float(weights[0] * lgb_prob + weights[1] * xgb_prob)

    prediction = int(blended >= threshold)
    risk_level = (
        'High'   if blended >= 0.60 else
        'Medium' if blended >= threshold else
        'Low'
    )

    # ── 8. Generate explanation ────────────────
    explanation = explain_prediction(df, raw_input, top_n=5)

    # ── 9. Compute confidence ──────────────────
    confidence = compute_confidence(raw_input)
    
   # ── 11. Subtype classification (diabetic only) ──
    subtype_classification = {}
    if prediction == 1:
        try:
            subtype_classification = predict_diabetic_subtype(raw_input)
        except Exception as e:
            subtype_classification = {'error': str(e)}

    return {
        'status'                 : 'success',
        'prediction'             : prediction,
        'probability'            : round(blended, 4),
        'risk_level'             : risk_level,
        'confidence'             : confidence,
        'lgbm_probability'       : round(lgb_prob, 4),
        'xgb_probability'        : round(xgb_prob, 4),
        'threshold_used'         : round(float(threshold), 4),
        'model_version'          : 'v4_fixed',
        'timestamp'              : datetime.now(timezone.utc).isoformat(),
        'explanation'            : explanation,
        'subtype_classification' : subtype_classification,
        'issues'                 : [],
    }


# ─────────────────────────────────────────────
#   SECTION 5C: SUBTYPE PREDICT FUNCTION (3-CLASS MULTI-CLASS)
# ─────────────────────────────────────────────
def predict_diabetic_subtype(raw_input: dict) -> dict:
    """
    Takes raw inputs, extracts subtype features, aligns columns,
    and returns the predicted 3-class diabetic subtype with probabilities.
    """
    # Create DataFrame from raw input dictionary
    df = pd.DataFrame([raw_input])
    
    # Run custom multi-class engineering steps
    df = engineer_subtype_features(df)
    
    # Ensure all columns exactly match the booster configuration sequence
    for col in subtypes_features:
        if col not in df.columns:
            df[col] = 0.0  # Safe imputation fallback for missing inputs
            
    df_aligned = df[subtypes_features]
    
    # Compute class probability distribution array
    probabilities = subtypes_model.predict(df_aligned)[0]
    
    # Find index position containing the highest value
    predicted_class_idx = np.argmax(probabilities)
    
    # Convert index integer back to string label mapping
    predicted_label = subtypes_le.inverse_transform([predicted_class_idx])[0]
    
    return {
        'status': 'success',
        'predicted_subtype': str(predicted_label),
        'confidence_score': round(float(probabilities[predicted_class_idx]), 4),
        'probabilities': {
            str(cls): round(float(prob), 4) 
            for cls, prob in zip(subtypes_le.classes_, probabilities)
        },
        'timestamp': datetime.now(timezone.utc).isoformat()
    }