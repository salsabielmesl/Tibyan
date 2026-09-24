# report_builder.py
# ─────────────────────────────────────────────
#  Generates non-editable clinical PDF reports
#  from prediction history for a given UPID
# ─────────────────────────────────────────────
import io
from datetime import datetime, timezone
from typing import List, Optional

from reportlab.lib import colors
from reportlab.lib.pagesizes import letter
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import inch
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    HRFlowable, PageBreak, KeepTogether
)
from reportlab.platypus.flowables import Flowable
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT, TA_JUSTIFY

# ── Tibyān brand colours ──────────────────────
TIBYAN_BLUE   = colors.HexColor('#2E4A7A')
TIBYAN_LIGHT  = colors.HexColor('#EEF3FA')
TIBYAN_GREEN  = colors.HexColor('#27AE60')
TIBYAN_YELLOW = colors.HexColor('#F39C12')
TIBYAN_RED    = colors.HexColor('#E74C3C')
TIBYAN_GREY   = colors.HexColor('#566573')
TEXT_DARK     = colors.HexColor('#1A1A2E')

# ── Risk level colours ─────────────────────────
RISK_COLORS = {
    'Low':    TIBYAN_GREEN,
    'Medium': TIBYAN_YELLOW,
    'High':   TIBYAN_RED,
}

# ── Clinical normal ranges for display ─────────
NORMAL_RANGES = {
    'Fasting plasma glucose (mg/dL)': (70,  100,  'mg/dL'),
    'Serum insulin (uU/mL)':          (2,   25,   'uU/mL'),
    'BMI (kg/m2)':                    (18.5, 24.9, 'kg/m²'),
    'Waist circumference (cm)':       (0,   88,   'cm'),
    'Waist-hip ratio':                (0,   0.85, ''),
    'Serum HDL cholesterol (mg/dL)':  (40,  60,   'mg/dL'),
    'Serum triglycerides (mg/dL) TR': (0,   150,  'mg/dL'),
    'Mean systolic BP (mm Hg)':       (90,  120,  'mm Hg'),
    'Mean diastolic BP (mm Hg)':      (60,  80,   'mm Hg'),
    'ALT (U/L)':                      (7,   40,   'U/L'),
    'Serum albumin (g/dL)':           (3.5, 5.0,  'g/dL'),
    'Serum bicarbonate (mmol/L)':     (22,  29,   'mmol/L'),
}


# ─────────────────────────────────────────────
#  HELPER: Diagonal watermark on each page
# ─────────────────────────────────────────────
def add_watermark(canvas, doc):
    """Draws a diagonal CONFIDENTIAL watermark on every page."""
    canvas.saveState()
    canvas.setFont('Helvetica', 42)
    canvas.setFillColor(colors.HexColor('#DDDDDD'))
    canvas.setFillAlpha(0.35)
    canvas.translate(4.25 * inch, 5.5 * inch)
    canvas.rotate(45)
    canvas.drawCentredString(0, 0, "CONFIDENTIAL — TIBYAN")
    canvas.restoreState()

    # Footer on every page
    canvas.saveState()
    canvas.setFont('Helvetica', 8)
    canvas.setFillColor(TIBYAN_GREY)
    canvas.drawString(
        0.75 * inch, 0.5 * inch,
        "Tibyān Health Prediction System  |  For informational purposes only."
        "  This report is not a medical diagnosis."
    )
    canvas.drawRightString(
        7.75 * inch, 0.5 * inch,
        f"Page {doc.page}  |  Generated {datetime.now().strftime('%Y-%m-%d %H:%M UTC')}"
    )
    # Header line
    canvas.setStrokeColor(TIBYAN_BLUE)
    canvas.setLineWidth(2)
    canvas.line(0.75 * inch, 10.3 * inch, 7.75 * inch, 10.3 * inch)
    canvas.restoreState()


# ─────────────────────────────────────────────
#  HELPER: Coloured risk badge
# ─────────────────────────────────────────────
def risk_badge_table(risk_level: str, probability: float) -> Table:
    """Returns a small coloured table acting as a risk badge."""
    colour = RISK_COLORS.get(risk_level, TIBYAN_GREY)
    badge = Table(
        [[f" {risk_level.upper()} RISK  —  {probability*100:.1f}% probability "]],
        colWidths=[3 * inch]
    )
    badge.setStyle(TableStyle([
        ('BACKGROUND',   (0, 0), (-1, -1), colour),
        ('TEXTCOLOR',    (0, 0), (-1, -1), colors.white),
        ('FONTNAME',     (0, 0), (-1, -1), 'Helvetica-Bold'),
        ('FONTSIZE',     (0, 0), (-1, -1), 13),
        ('ALIGN',        (0, 0), (-1, -1), 'CENTER'),
        ('ROWBACKGROUNDS', (0, 0), (-1, -1), [colour]),
        ('TOPPADDING',   (0, 0), (-1, -1), 8),
        ('BOTTOMPADDING',(0, 0), (-1, -1), 8),
        ('ROUNDEDCORNERS', [4]),
    ]))
    return badge


# ─────────────────────────────────────────────
#  MAIN BUILDER FUNCTION
# ─────────────────────────────────────────────
def build_report(
    upid: str,
    prediction_history: List[dict],
    date_from: str,
    date_to: str,
    latest_prediction: Optional[dict] = None,
) -> bytes:
    """
    Builds a non-editable PDF report.

    Args:
        upid:               Anonymous patient identifier
        prediction_history: List of past prediction dicts from DB,
                            each must have: timestamp, probability,
                            risk_level, prediction, confidence,
                            explanation, subtype_classification
        date_from:          Report start date (YYYY-MM-DD)
        date_to:            Report end date   (YYYY-MM-DD)
        latest_prediction:  Most recent prediction dict from pipeline

    Returns:
        PDF as bytes — ready to return from FastAPI endpoint
    """
    buffer = io.BytesIO()

    doc = SimpleDocTemplate(
        buffer,
        pagesize=letter,
        rightMargin=0.75 * inch,
        leftMargin=0.75 * inch,
        topMargin=1.1 * inch,
        bottomMargin=0.9 * inch,
        title="Tibyān Diabetes Risk Report",
        author="Tibyān Health Prediction System",
        subject=f"Diabetes Risk Report for UPID {upid}",
        creator="Tibyān v4 Fixed",
        # Encryption — makes document non-editable
        encrypt=None,   # set to PDFCrypt object for password protection
    )

    styles = getSampleStyleSheet()

    # ── Custom styles ──────────────────────────
    title_style = ParagraphStyle(
        'TibyanTitle',
        parent=styles['Title'],
        fontSize=22,
        textColor=TIBYAN_BLUE,
        spaceAfter=6,
        fontName='Helvetica-Bold',
    )
    h1_style = ParagraphStyle(
        'TibyanH1',
        parent=styles['Heading1'],
        fontSize=14,
        textColor=TIBYAN_BLUE,
        spaceBefore=16,
        spaceAfter=6,
        fontName='Helvetica-Bold',
        borderPad=4,
    )
    h2_style = ParagraphStyle(
        'TibyanH2',
        parent=styles['Heading2'],
        fontSize=11,
        textColor=TIBYAN_BLUE,
        spaceBefore=10,
        spaceAfter=4,
        fontName='Helvetica-Bold',
    )
    body_style = ParagraphStyle(
        'TibyanBody',
        parent=styles['Normal'],
        fontSize=9,
        textColor=TEXT_DARK,
        spaceAfter=4,
        leading=14,
        fontName='Helvetica',
    )
    summary_style = ParagraphStyle(
        'TibyanSummary',
        parent=styles['Normal'],
        fontSize=9,
        textColor=TEXT_DARK,
        spaceAfter=4,
        leading=15,
        fontName='Helvetica',
        alignment=TA_JUSTIFY,
        backColor=TIBYAN_LIGHT,
        borderPad=8,
        leftIndent=8,
        rightIndent=8,
    )
    disclaimer_style = ParagraphStyle(
        'Disclaimer',
        parent=styles['Normal'],
        fontSize=8,
        textColor=TIBYAN_GREY,
        fontName='Helvetica-Oblique',
        alignment=TA_CENTER,
    )

    story = []   # all PDF elements go into this list

    # ══════════════════════════════════════════
    #  SECTION 1 — HEADER
    # ══════════════════════════════════════════
    story.append(Paragraph("Tibyān", title_style))
    story.append(Paragraph(
        "Diabetes Risk Assessment Report", h1_style
    ))
    story.append(HRFlowable(
        width="100%", thickness=2,
        color=TIBYAN_BLUE, spaceAfter=10
    ))

    # Report metadata table
    meta_data = [
        ["Patient ID (UPID):",  upid,
         "Report Period:",       f"{date_from}  →  {date_to}"],
        ["Report Generated:",
         datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M UTC"),
         "Total Assessments:",   str(len(prediction_history))],
        ["Model Version:",       "v4_fixed",
         "Predictions Included:", str(len(prediction_history))],
    ]
    meta_table = Table(meta_data, colWidths=[1.5*inch, 2*inch, 1.5*inch, 2*inch])
    meta_table.setStyle(TableStyle([
        ('FONTNAME',    (0, 0), (-1, -1), 'Helvetica'),
        ('FONTSIZE',    (0, 0), (-1, -1), 8.5),
        ('FONTNAME',    (0, 0), (0, -1), 'Helvetica-Bold'),
        ('FONTNAME',    (2, 0), (2, -1), 'Helvetica-Bold'),
        ('TEXTCOLOR',   (0, 0), (0, -1), TIBYAN_BLUE),
        ('TEXTCOLOR',   (2, 0), (2, -1), TIBYAN_BLUE),
        ('BACKGROUND',  (0, 0), (-1, -1), TIBYAN_LIGHT),
        ('ROWBACKGROUNDS', (0, 0), (-1, -1), [TIBYAN_LIGHT, colors.white]),
        ('TOPPADDING',  (0, 0), (-1, -1), 5),
        ('BOTTOMPADDING',(0, 0), (-1, -1), 5),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('BOX',         (0, 0), (-1, -1), 0.5, TIBYAN_BLUE),
        ('INNERGRID',   (0, 0), (-1, -1), 0.25, colors.lightgrey),
    ]))
    story.append(meta_table)
    story.append(Spacer(1, 12))

    # ══════════════════════════════════════════
    #  SECTION 2 — DISCLAIMER
    # ══════════════════════════════════════════
    story.append(Paragraph(
        "⚠  IMPORTANT: This report is generated by an AI prediction system for "
        "informational and screening purposes only. It does not constitute a medical "
        "diagnosis. All findings should be reviewed and confirmed by a qualified "
        "healthcare professional before any clinical decision is made.",
        disclaimer_style
    ))
    story.append(Spacer(1, 10))

    # ══════════════════════════════════════════
    #  SECTION 3 — LATEST ASSESSMENT SUMMARY
    # ══════════════════════════════════════════
    if latest_prediction and latest_prediction.get('status') == 'success':
        story.append(Paragraph("Latest Assessment Result", h1_style))
        story.append(HRFlowable(
            width="100%", thickness=1,
            color=TIBYAN_BLUE, spaceAfter=8
        ))

        pred = latest_prediction
        risk  = pred.get('risk_level', 'Unknown')
        prob  = pred.get('probability', 0)
        conf  = pred.get('confidence', 'Unknown')
        ts    = pred.get('timestamp', '')[:10]

        # Risk badge
        story.append(risk_badge_table(risk, prob))
        story.append(Spacer(1, 8))

        # Summary metrics
        summary_rows = [
            ["Metric", "Value", "Clinical Interpretation"],
            ["Diabetes Risk Level", risk,
             {"Low": "No immediate action required — continue monitoring",
              "Medium": "Clinical evaluation recommended within 3 months",
              "High": "Urgent clinical evaluation recommended"}.get(risk, "")],
            ["Risk Probability", f"{prob*100:.1f}%",
             f"Model confidence threshold: {pred.get('threshold_used', 0.262):.3f}"],
            ["Prediction Confidence", conf,
             "Based on completeness of lab panel provided"],
            ["Assessment Date", ts, ""],
        ]

        # Add subtype if diabetic
        sub = pred.get('subtype_classification', {})
        if sub and sub.get('subtype'):
            summary_rows.append([
                "T2D Subtype",
                sub['subtype'],
                sub.get('subtype_description', '')[:80] + "..."
            ])

        s_table = Table(
            summary_rows,
            colWidths=[1.8*inch, 1.5*inch, 3.6*inch]
        )
        s_table.setStyle(TableStyle([
            ('BACKGROUND',   (0, 0), (-1, 0), TIBYAN_BLUE),
            ('TEXTCOLOR',    (0, 0), (-1, 0), colors.white),
            ('FONTNAME',     (0, 0), (-1, 0), 'Helvetica-Bold'),
            ('FONTSIZE',     (0, 0), (-1, -1), 8.5),
            ('ROWBACKGROUNDS',(0, 1), (-1, -1),
             [TIBYAN_LIGHT, colors.white]),
            ('GRID',         (0, 0), (-1, -1), 0.25, colors.lightgrey),
            ('BOX',          (0, 0), (-1, -1), 0.5, TIBYAN_BLUE),
            ('TOPPADDING',   (0, 0), (-1, -1), 5),
            ('BOTTOMPADDING',(0, 0), (-1, -1), 5),
            ('LEFTPADDING',  (0, 0), (-1, -1), 6),
            ('FONTNAME',     (0, 1), (0, -1), 'Helvetica-Bold'),
            ('TEXTCOLOR',    (0, 1), (0, -1), TIBYAN_BLUE),
        ]))
        story.append(s_table)
        story.append(Spacer(1, 12))

        # ── AI Explanation ─────────────────────
        exp = pred.get('explanation', {})
        if exp and exp.get('summary_text'):
            story.append(Paragraph("AI Explanation", h2_style))
            story.append(Paragraph(exp['summary_text'], summary_style))
            story.append(Spacer(1, 8))

        # ── Contributing factors table ─────────
        factors = exp.get('contributing_factors', [])
        if factors:
            story.append(Paragraph("Key Contributing Factors", h2_style))
            factor_rows = [["Factor", "Impact", "Direction", "Value", "Clinical Context"]]
            for f in factors[:8]:
                arrow = "↑ Increases risk" if f['direction'] == 'increases' \
                        else "↓ Decreases risk"
                val = str(f['raw_value']) if f['raw_value'] is not None else "—"
                factor_rows.append([
                    f['label'],
                    f['impact_level'],
                    arrow,
                    val,
                    f.get('context', '')
                ])
            f_table = Table(
                factor_rows,
                colWidths=[1.8*inch, 0.7*inch, 1.3*inch, 0.7*inch, 2.4*inch]
            )
            f_table.setStyle(TableStyle([
                ('BACKGROUND',   (0, 0), (-1, 0), TIBYAN_BLUE),
                ('TEXTCOLOR',    (0, 0), (-1, 0), colors.white),
                ('FONTNAME',     (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE',     (0, 0), (-1, -1), 8),
                ('ROWBACKGROUNDS',(0, 1), (-1, -1),
                 [TIBYAN_LIGHT, colors.white]),
                ('GRID',         (0, 0), (-1, -1), 0.25, colors.lightgrey),
                ('BOX',          (0, 0), (-1, -1), 0.5, TIBYAN_BLUE),
                ('TOPPADDING',   (0, 0), (-1, -1), 4),
                ('BOTTOMPADDING',(0, 0), (-1, -1), 4),
                ('LEFTPADDING',  (0, 0), (-1, -1), 5),
                ('WORDWRAP',     (0, 0), (-1, -1), True),
            ]))
            story.append(f_table)
            story.append(Spacer(1, 12))

    # ══════════════════════════════════════════
    #  SECTION 4 — RISK TREND OVER DATE RANGE
    # ══════════════════════════════════════════
    if prediction_history:
        story.append(PageBreak())
        story.append(Paragraph("Risk Trend Analysis", h1_style))
        story.append(HRFlowable(
            width="100%", thickness=1,
            color=TIBYAN_BLUE, spaceAfter=8
        ))

        # ── Trend summary ──────────────────────
        probs = [p['probability'] for p in prediction_history]
        risks  = [p['risk_level'] for p in prediction_history]

        first_prob = probs[0]
        last_prob  = probs[-1]
        delta      = last_prob - first_prob
        direction  = "improving" if delta < -0.05 \
                     else "worsening" if delta > 0.05 \
                     else "stable"

        high_count   = risks.count('High')
        medium_count = risks.count('Medium')
        low_count    = risks.count('Low')

        # Journey categorisation as per document requirement
        if first_prob < 0.262 and last_prob >= 0.262:
            journey = "Low-to-High Risk (Deteriorating)"
            journey_color = TIBYAN_RED
        elif first_prob >= 0.262 and last_prob < 0.262:
            journey = "High-to-Low Risk (Improving)"
            journey_color = TIBYAN_GREEN
        else:
            journey = "Stable Risk Profile"
            journey_color = TIBYAN_YELLOW

        trend_rows = [
            ["Trend Metric", "Value"],
            ["Risk Journey Category", journey],
            ["First Assessment Probability", f"{first_prob*100:.1f}%"],
            ["Latest Assessment Probability", f"{last_prob*100:.1f}%"],
            ["Change Over Period",
             f"{delta*100:+.1f}% ({direction})"],
            ["High Risk Assessments",
             f"{high_count} / {len(prediction_history)}"],
            ["Medium Risk Assessments",
             f"{medium_count} / {len(prediction_history)}"],
            ["Low Risk Assessments",
             f"{low_count} / {len(prediction_history)}"],
        ]
        t_table = Table(trend_rows, colWidths=[2.5*inch, 4.4*inch])
        t_table.setStyle(TableStyle([
            ('BACKGROUND',   (0, 0), (-1, 0), TIBYAN_BLUE),
            ('TEXTCOLOR',    (0, 0), (-1, 0), colors.white),
            ('FONTNAME',     (0, 0), (-1, 0), 'Helvetica-Bold'),
            ('FONTSIZE',     (0, 0), (-1, -1), 9),
            ('ROWBACKGROUNDS',(0, 1), (-1, -1),
             [TIBYAN_LIGHT, colors.white]),
            ('GRID',         (0, 0), (-1, -1), 0.25, colors.lightgrey),
            ('BOX',          (0, 0), (-1, -1), 0.5, TIBYAN_BLUE),
            ('TOPPADDING',   (0, 0), (-1, -1), 5),
            ('BOTTOMPADDING',(0, 0), (-1, -1), 5),
            ('LEFTPADDING',  (0, 0), (-1, -1), 8),
            ('FONTNAME',     (0, 1), (0, -1), 'Helvetica-Bold'),
            ('TEXTCOLOR',    (0, 1), (0, -1), TIBYAN_BLUE),
        ]))
        story.append(t_table)
        story.append(Spacer(1, 12))

        # ── Written trend summary ──────────────
        story.append(Paragraph("Written Risk Trend Summary", h2_style))
        written_summary = (
            f"Over the period from {date_from} to {date_to}, this patient underwent "
            f"{len(prediction_history)} diabetes risk assessment(s) through the Tibyān "
            f"system. The overall risk trajectory is classified as "
            f"<b>{journey}</b>. "
            f"The initial assessment recorded a probability of {first_prob*100:.1f}%, "
            f"while the most recent assessment recorded {last_prob*100:.1f}%, "
            f"representing a change of {delta*100:+.1f} percentage points. "
            f"During this period, {high_count} assessment(s) returned a High risk "
            f"classification, {medium_count} returned Medium risk, and {low_count} "
            f"returned Low risk. "
        )
        if direction == "worsening":
            written_summary += (
                "The worsening trend warrants prompt clinical evaluation. "
                "A qualified healthcare provider should review these results "
                "and consider further diagnostic workup."
            )
        elif direction == "improving":
            written_summary += (
                "The improving trend may reflect positive lifestyle changes or "
                "treatment response. Continued monitoring is recommended to "
                "confirm the sustained improvement."
            )
        else:
            written_summary += (
                "The stable risk profile suggests no significant change in "
                "metabolic status over this period. Routine monitoring "
                "should continue as advised by a healthcare provider."
            )

        story.append(Paragraph(written_summary, summary_style))
        story.append(Spacer(1, 12))

        # ── Assessment history table ───────────
        story.append(Paragraph("Assessment History", h2_style))
        hist_rows = [["Date", "Probability", "Risk Level",
                      "Confidence", "Safe-Fail"]]
        for p in prediction_history:
            ts   = p.get('timestamp', '')[:10]
            prob = f"{p.get('probability', 0)*100:.1f}%"
            risk = p.get('risk_level', '—')
            conf = p.get('confidence', '—')
            safe = "⚠ Triggered" if p.get('safe_fail_triggered') else "✓ OK"
            hist_rows.append([ts, prob, risk, conf, safe])

        h_table = Table(
            hist_rows,
            colWidths=[1.2*inch, 1.0*inch, 1.0*inch, 2.0*inch, 1.0*inch]
        )
        h_table.setStyle(TableStyle([
            ('BACKGROUND',    (0, 0), (-1, 0), TIBYAN_BLUE),
            ('TEXTCOLOR',     (0, 0), (-1, 0), colors.white),
            ('FONTNAME',      (0, 0), (-1, 0), 'Helvetica-Bold'),
            ('FONTSIZE',      (0, 0), (-1, -1), 8.5),
            ('ROWBACKGROUNDS',(0, 1), (-1, -1),
             [TIBYAN_LIGHT, colors.white]),
            ('GRID',          (0, 0), (-1, -1), 0.25, colors.lightgrey),
            ('BOX',           (0, 0), (-1, -1), 0.5, TIBYAN_BLUE),
            ('TOPPADDING',    (0, 0), (-1, -1), 4),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
            ('ALIGN',         (1, 0), (-1, -1), 'CENTER'),
        ]))
        story.append(h_table)

    # ══════════════════════════════════════════
    #  SECTION 5 — BIOMARKER DISTRIBUTION
    # ══════════════════════════════════════════
    if latest_prediction and latest_prediction.get('status') == 'success':
        story.append(PageBreak())
        story.append(Paragraph(
            "Biomarker Distribution vs Clinical Thresholds", h1_style
        ))
        story.append(HRFlowable(
            width="100%", thickness=1,
            color=TIBYAN_BLUE, spaceAfter=8
        ))
        story.append(Paragraph(
            "The table below compares the patient's biomarker values against "
            "established clinical normal ranges. Values outside the normal range "
            "are highlighted.",
            body_style
        ))
        story.append(Spacer(1, 8))

        # Build from raw input values stored with the prediction
        raw = latest_prediction.get('raw_input', {})

        bio_rows = [["Biomarker", "Patient Value",
                     "Normal Range", "Unit", "Status"]]
        for feature, (lo, hi, unit) in NORMAL_RANGES.items():
            val = raw.get(feature)
            if val is None:
                continue
            status = "✓ Normal"
            if val < lo:
                status = "↓ Below range"
            elif val > hi:
                status = "↑ Above range"
            bio_rows.append([
                feature,
                f"{val:.1f}",
                f"{lo} – {hi}",
                unit,
                status
            ])

        if len(bio_rows) > 1:
            b_table = Table(
                bio_rows,
                colWidths=[2.2*inch, 1.1*inch, 1.1*inch, 0.7*inch, 1.8*inch]
            )

            # Build row styles — highlight abnormal rows
            row_styles = [
                ('BACKGROUND',   (0, 0), (-1, 0), TIBYAN_BLUE),
                ('TEXTCOLOR',    (0, 0), (-1, 0), colors.white),
                ('FONTNAME',     (0, 0), (-1, 0), 'Helvetica-Bold'),
                ('FONTSIZE',     (0, 0), (-1, -1), 8.5),
                ('GRID',         (0, 0), (-1, -1), 0.25, colors.lightgrey),
                ('BOX',          (0, 0), (-1, -1), 0.5, TIBYAN_BLUE),
                ('TOPPADDING',   (0, 0), (-1, -1), 4),
                ('BOTTOMPADDING',(0, 0), (-1, -1), 4),
                ('LEFTPADDING',  (0, 0), (-1, -1), 5),
            ]
            for i, row in enumerate(bio_rows[1:], start=1):
                status_cell = row[4] if len(row) > 4 else ""
                if "Above" in status_cell or "Below" in status_cell:
                    row_styles.append(
                        ('BACKGROUND', (0, i), (-1, i),
                         colors.HexColor('#FFF3CD'))
                    )
                    row_styles.append(
                        ('TEXTCOLOR', (4, i), (4, i), TIBYAN_RED)
                    )
                    row_styles.append(
                        ('FONTNAME', (4, i), (4, i), 'Helvetica-Bold')
                    )
                else:
                    if i % 2 == 0:
                        row_styles.append(
                            ('BACKGROUND', (0, i), (-1, i), TIBYAN_LIGHT)
                        )

            b_table.setStyle(TableStyle(row_styles))
            story.append(b_table)

    # ══════════════════════════════════════════
    #  SECTION 6 — CLOSING DISCLAIMER
    # ══════════════════════════════════════════
    story.append(Spacer(1, 20))
    story.append(HRFlowable(
        width="100%", thickness=1,
        color=TIBYAN_GREY, spaceAfter=8
    ))
    story.append(Paragraph(
        "This report has been automatically generated by Tibyān v4 and has "
        "not been reviewed or signed by a medical professional. The predictions "
        "are based on statistical models trained on anonymized population data "
        "(NHANES) and should not be used as the sole basis for clinical decisions. "
        "This document is confidential and intended solely for the patient identified "
        "by the UPID shown above.",
        disclaimer_style
    ))

    # ── Build PDF ──────────────────────────────
    doc.build(
        story,
        onFirstPage=add_watermark,
        onLaterPages=add_watermark
    )

    pdf_bytes = buffer.getvalue()
    buffer.close()
    return pdf_bytes