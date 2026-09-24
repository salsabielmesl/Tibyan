<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['logged_in'])) {
    header('Location: signin.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: labtests.php'); exit;
}

require_once 'common/api.php';

// Extend timeout for this page — it makes 3 API calls
set_time_limit(120);

$upid = $_SESSION['upid'] ?? '';

// If upid missing from session, fetch from profile
if (empty($upid) || $upid === 'UNKNOWN') {
    $pr   = api_call('user/profile', 'GET');
    $upid = $pr['body']['payload']['upid'] ?? '';
    if (!empty($upid)) $_SESSION['upid'] = $upid;
}

// ── 1. Save health log ────────────────────────────────────────────────────
$log_response = api_call('health/log-data', 'POST', [
    'upid'            => $upid,
    'glucose'         => $_POST['glucose']       !== '' ? (float)$_POST['glucose']       : null,
    'insulin'         => $_POST['insulin']       !== '' ? (float)$_POST['insulin']       : null,
    'circumference'   => $_POST['circumfirance'] !== '' ? (float)$_POST['circumfirance'] : null,
    'bmi'             => $_POST['bmi']           !== '' ? (float)$_POST['bmi']           : null,
    'cpeptide'        => $_POST['cpeptide']      !== '' ? (float)$_POST['cpeptide']      : null,
    'cholesterol_hdl' => $_POST['hdl']           !== '' ? (float)$_POST['hdl']           : null,
    'ldl'             => $_POST['ldl']           !== '' ? (float)$_POST['ldl']           : null,
    'triglycerides'   => $_POST['triglycerides'] !== '' ? (float)$_POST['triglycerides'] : null,
    'albumin'         => $_POST['albumin']       !== '' ? (float)$_POST['albumin']       : null,
    'bicarbonate'     => $_POST['bicarbonate']   !== '' ? (float)$_POST['bicarbonate']   : null,
    'alt'             => $_POST['alt']           !== '' ? (float)$_POST['alt']           : null,
    'ualbumin'        => $_POST['ualbumin']      !== '' ? (float)$_POST['ualbumin']      : null,
    'nitrogen'        => $_POST['nitrogen']      !== '' ? (float)$_POST['nitrogen']      : null,
    'systolic_bp'     => $_POST['systolic']      !== '' ? (float)$_POST['systolic']      : null,
    'diastolic_bp'    => $_POST['diastolic']     !== '' ? (float)$_POST['diastolic']     : null,
    'waist_hip_ratio' => $_POST['waist-hip']     !== '' ? (float)$_POST['waist-hip']     : null,
    'sagittal'        => $_POST['sagittal']      !== '' ? (float)$_POST['sagittal']      : null,
]);

$log_ok = ($log_response['status'] === 201);

// ── 2. Call ML prediction ─────────────────────────────────────────────────
$pred_response = api_call('predict/run', 'POST', [
    'upid'        => $upid,
    'direct_vals' => [
        'glucose'         => $_POST['glucose']       !== '' ? (float)$_POST['glucose']       : null,
        'insulin'         => $_POST['insulin']       !== '' ? (float)$_POST['insulin']       : null,
        'circumference'   => $_POST['circumfirance'] !== '' ? (float)$_POST['circumfirance'] : null,
        'bmi'             => $_POST['bmi']           !== '' ? (float)$_POST['bmi']           : null,
        'cpeptide'        => $_POST['cpeptide']      !== '' ? (float)$_POST['cpeptide']      : null,
        'cholesterol_hdl' => $_POST['hdl']           !== '' ? (float)$_POST['hdl']           : null,
        'ldl'             => $_POST['ldl']           !== '' ? (float)$_POST['ldl']           : null,
        'triglycerides'   => $_POST['triglycerides'] !== '' ? (float)$_POST['triglycerides'] : null,
        'albumin'         => $_POST['albumin']       !== '' ? (float)$_POST['albumin']       : null,
        'bicarbonate'     => $_POST['bicarbonate']   !== '' ? (float)$_POST['bicarbonate']   : null,
        'alt'             => $_POST['alt']           !== '' ? (float)$_POST['alt']           : null,
        'ualbumin'        => $_POST['ualbumin']      !== '' ? (float)$_POST['ualbumin']      : null,
        'nitrogen'        => $_POST['nitrogen']      !== '' ? (float)$_POST['nitrogen']      : null,
        'systolic_bp'     => $_POST['systolic']      !== '' ? (float)$_POST['systolic']      : null,
        'diastolic_bp'    => $_POST['diastolic']     !== '' ? (float)$_POST['diastolic']     : null,
        'waist_hip_ratio' => $_POST['waist-hip']     !== '' ? (float)$_POST['waist-hip']     : null,
        'sagittal'        => $_POST['sagittal']      !== '' ? (float)$_POST['sagittal']      : null,
    ]
]);
$pred_ok = ($pred_response['status'] === 200) && !empty($pred_response['body']['payload']['risk_level']);
$ml      = $pred_response['body']['payload'] ?? [];

// Fetch health logs for longitudinal chart (after prediction to avoid blocking)
$logs_resp = api_call('health/logs/' . $upid, 'GET');
$all_logs  = $logs_resp['body']['payload'] ?? [];
if (!empty($all_logs) && isset($all_logs['upid'])) $all_logs = [$all_logs];
$all_logs  = array_values(array_filter((array)$all_logs));
usort($all_logs, fn($a, $b) => strtotime($a['created_at'] ?? 0) - strtotime($b['created_at'] ?? 0));

// Core
$risk_level    = $ml['risk_level']      ?? null;
$probability   = $ml['probability']     ?? null;
$confidence    = $ml['confidence']      ?? null;
$prediction    = $ml['prediction']      ?? null;  // 0 or 1
$model_version = $ml['model_version']   ?? null;
$threshold     = $ml['threshold_used']  ?? null;

// Explanation (SHAP)
$explanation   = $ml['explanation']     ?? [];
$summary_text  = $explanation['summary_text']           ?? '';
$top_factors   = $explanation['contributing_factors']   ?? [];
$top_risk      = $explanation['top_risk_features']      ?? [];
$top_protect   = $explanation['top_protective_features'] ?? [];

// Subtype
$subtype       = $ml['subtype']         ?? [];
$subtype_label = $subtype['predicted_subtype']    ?? null;
$subtype_conf  = $subtype['confidence_score']     ?? null;
$subtype_probs = $subtype['probabilities']        ?? [];

// Distribution
$distribution  = $ml['distribution']   ?? [];

// Integrity
$integrity     = $ml['integrity']      ?? [];
$missing_pct   = $integrity['missing_feature_pct'] ?? null;
$outlier       = $integrity['outlier_detected']    ?? false;
$drift_score   = $integrity['drift_score']         ?? null;
$drift_msg     = $integrity['drift_message']       ?? '';

// Longitudinal — built from our own health logs (ML service may not return this)
$ml_longitudinal = $ml['longitudinal'] ?? [];

// Compute from health logs if ML didn't provide it
if (empty($ml_longitudinal) && count($all_logs) >= 2) {
    $n     = count($all_logs);
    $first = $all_logs[0];
    $last  = $all_logs[$n - 1];

    // Compute average glucose trend
    $glucose_vals = array_filter(array_column($all_logs, 'glucose'), fn($v) => $v !== null && $v !== '');
    $avg_glucose  = count($glucose_vals) ? array_sum($glucose_vals) / count($glucose_vals) : null;

    // Simple trend: compare first half avg vs second half avg for glucose
    $trend_label = 'Stable';
    if (count($glucose_vals) >= 2) {
        $half    = (int)ceil(count($glucose_vals) / 2);
        $first_h = array_slice(array_values($glucose_vals), 0, $half);
        $last_h  = array_slice(array_values($glucose_vals), $half);
        $avg1    = array_sum($first_h) / count($first_h);
        $avg2    = count($last_h) ? array_sum($last_h) / count($last_h) : $avg1;
        $delta   = $avg2 - $avg1;
        if ($delta >  5) $trend_label = 'Worsening ↑';
        elseif ($delta < -5) $trend_label = 'Improving ↓';
    }

    $ml_longitudinal = [
        'total_predictions'   => $n,
        'average_probability' => null,
        'trend'               => $trend_label,
        'risk_progression'    => $n >= 3 ? 'Tracked' : 'Early tracking',
    ];
}

$longitudinal  = $ml_longitudinal;
$long_message  = (!empty($longitudinal) || count($all_logs) < 2)
    ? (count($all_logs) < 2 ? 'Submit at least 2 lab tests to see your longitudinal trend.' : null)
    : null;
$trend         = $longitudinal['trend'] ?? null;

// Risk colours
$risk_color = match($risk_level) {
    'High'   => 'danger',
    'Medium' => 'warning',
    'Low'    => 'success',
    default  => 'secondary'
};
$risk_icon = match($risk_level) {
    'High'   => 'fa-triangle-exclamation',
    'Medium' => 'fa-circle-exclamation',
    'Low'    => 'fa-circle-check',
    default  => 'fa-circle-question'
};

// Submitted metrics for display
$metrics = [
    'Fasting Glucose (mg/dL)'       => ['val' => $_POST['glucose']       ?? '-', 'required' => true],
    'Serum Insulin (μU/mL)'         => ['val' => $_POST['insulin']       ?? '-', 'required' => true],
    'Waist Circumference (cm)'      => ['val' => $_POST['circumfirance'] ?? '-', 'required' => true],
    'BMI (kg/m²)'                   => ['val' => $_POST['bmi']           ?? '-', 'required' => true],
    'C-Peptide (pmol/mL)'           => ['val' => $_POST['cpeptide']      ?? '-', 'required' => false],
    'HDL Cholesterol (mg/dL)'       => ['val' => $_POST['hdl']           ?? '-', 'required' => false],
    'LDL Cholesterol (mg/dL)'       => ['val' => $_POST['ldl']           ?? '-', 'required' => false],
    'Triglycerides (mg/dL)'         => ['val' => $_POST['triglycerides'] ?? '-', 'required' => false],
    'Serum Albumin (g/dL)'          => ['val' => $_POST['albumin']       ?? '-', 'required' => false],
    'Bicarbonate (mmol/L)'          => ['val' => $_POST['bicarbonate']   ?? '-', 'required' => false],
    'ALT (U/L)'                     => ['val' => $_POST['alt']           ?? '-', 'required' => false],
    'Urinary Albumin (μg/mL)'       => ['val' => $_POST['ualbumin']      ?? '-', 'required' => false],
    'Blood Urea Nitrogen (mmol/L)'  => ['val' => $_POST['nitrogen']      ?? '-', 'required' => false],
    'Systolic BP (mmHg)'            => ['val' => $_POST['systolic']      ?? '-', 'required' => false],
    'Diastolic BP (mmHg)'           => ['val' => $_POST['diastolic']     ?? '-', 'required' => false],
    'Waist-Hip Ratio'               => ['val' => $_POST['waist-hip']     ?? '-', 'required' => false],
    'Sagittal Abdominal Diam. (cm)' => ['val' => $_POST['sagittal']      ?? '-', 'required' => false],
];
?>
<?php require "common/head.php" ?>
<?php require "common/script.php" ?>
<?php require "common/navbar2.php" ?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-xl-10">

      <?php if ($pred_ok && $risk_level): ?>

      <!-- ── HERO RISK CARD ─────────────────────────────────────── -->
      <div class="card border-0 shadow rounded-4 p-4 p-md-5 mb-4 text-center bg-white">
        <div class="d-inline-flex align-items-center justify-content-center
                    bg-<?= $risk_color ?> bg-opacity-10 text-<?= $risk_color ?>
                    rounded-circle mx-auto mb-3" style="width:80px;height:80px;font-size:2.2rem;">
          <i class="fa-solid <?= $risk_icon ?>"></i>
        </div>
        <h2 class="fw-bold text-dark mb-1">
          <?= htmlspecialchars($risk_level) ?> Risk of Diabetes
        </h2>
        <p class="text-muted mb-3">
          <?= $prediction == 1 ? 'Positive prediction — diabetes risk detected.' : 'Negative prediction — no diabetes risk detected.' ?>
        </p>

        <div class="row g-3 justify-content-center mb-3">
          <!-- Probability -->
          <div class="col-md-3">
            <div class="bg-light rounded-3 p-3">
              <p class="text-muted small fw-bold mb-1">Probability</p>
              <p class="fw-bold fs-4 mb-0 text-<?= $risk_color ?>">
                <?= $probability !== null ? round($probability * 100, 1) . '%' : '—' ?>
              </p>
            </div>
          </div>
          <!-- Confidence -->
          <div class="col-md-3">
            <div class="bg-light rounded-3 p-3">
              <p class="text-muted small fw-bold mb-1">Confidence</p>
              <p class="fw-bold fs-5 mb-0"><?= htmlspecialchars($confidence ?? '—') ?></p>
            </div>
          </div>
          <!-- Threshold -->
          <div class="col-md-3">
            <div class="bg-light rounded-3 p-3">
              <p class="text-muted small fw-bold mb-1">Decision Threshold</p>
              <p class="fw-bold fs-5 mb-0"><?= $threshold !== null ? round($threshold * 100, 1) . '%' : '—' ?></p>
            </div>
          </div>
          <!-- Model -->
          <div class="col-md-3">
            <div class="bg-light rounded-3 p-3">
              <p class="text-muted small fw-bold mb-1">Model</p>
              <p class="fw-bold fs-6 mb-0"><?= htmlspecialchars($model_version ?? '—') ?></p>
            </div>
          </div>
        </div>

        <!-- Probability bar -->
        <?php if ($probability !== null): ?>
        <div class="mt-2 px-md-5">
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span>0%</span><span>Threshold <?= round($threshold * 100, 1) ?>%</span><span>100%</span>
          </div>
          <div class="progress" style="height:12px;border-radius:99px;">
            <div class="progress-bar bg-<?= $risk_color ?>" role="progressbar"
                 style="width:<?= round($probability * 100, 1) ?>%">
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── T2D SUBTYPE ─────────────────────────────────────────── -->
      <?php if (!empty($subtype_label)): ?>
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-dna me-2 text-primary"></i>T2D Subtype Classification</h5>
        <div class="row g-3 align-items-center">
          <div class="col-md-4 text-center">
            <div class="rounded-3 p-3" style="background:#1a56db;">
              <p class="small fw-bold mb-1" style="color:#fff;opacity:0.85;">Predicted Subtype</p>
              <p class="fw-bold fs-5 mb-0" style="color:#fff;"><?= htmlspecialchars($subtype_label) ?></p>
              <?php if ($subtype_conf !== null): ?>
              <p class="small mb-0 mt-1" style="color:#fff;opacity:0.85;">Confidence: <?= round($subtype_conf * 100, 1) ?>%</p>
              <?php endif; ?>
            </div>
          </div>
          <?php if (!empty($subtype_probs)): ?>
          <div class="col-md-8">
            <?php foreach ($subtype_probs as $cls => $prob): ?>
            <div class="mb-2">
              <div class="d-flex justify-content-between small mb-1">
                <span class="fw-bold text-dark"><?= htmlspecialchars($cls) ?></span>
                <span class="text-muted"><?= round($prob * 100, 1) ?>%</span>
              </div>
              <div class="progress" style="height:8px;border-radius:99px;">
                <div class="progress-bar <?= $cls === $subtype_label ? 'bg-primary' : 'bg-secondary bg-opacity-25' ?>"
                     style="width:<?= round($prob * 100, 1) ?>%"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── SHAP EXPLANATION ────────────────────────────────────── -->
      <?php if (!empty($top_factors)): ?>
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold mb-1"><i class="fa-solid fa-magnifying-glass-chart me-2 text-primary"></i>Clinical Explanation</h5>
        <?php if ($summary_text): ?>
        <p class="text-muted small mb-3"><?= htmlspecialchars($summary_text) ?></p>
        <?php endif; ?>

        <div class="row g-2">
          <?php foreach ($top_factors as $f): ?>
          <?php
            $dir_color = $f['direction'] === 'increases' ? 'danger' : 'success';
            $dir_icon  = $f['direction'] === 'increases' ? 'fa-arrow-up' : 'fa-arrow-down';
            $impact_w  = match($f['impact_level'] ?? '') {
                'High'   => 100,
                'Medium' => 60,
                default  => 30
            };
          ?>
          <div class="col-md-6">
            <div class="border rounded-3 p-3">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <span class="fw-bold small"><?= htmlspecialchars($f['label'] ?? $f['feature']) ?></span>
                <span class="badge bg-<?= $dir_color ?> bg-opacity-10 text-<?= $dir_color ?> small">
                  <i class="fa-solid <?= $dir_icon ?> me-1"></i><?= ucfirst($f['direction']) ?> risk
                </span>
              </div>
              <?php if (!empty($f['raw_value'])): ?>
              <p class="text-muted small mb-1">Value: <strong><?= htmlspecialchars($f['raw_value']) ?></strong></p>
              <?php endif; ?>
              <?php if (!empty($f['context'])): ?>
              <p class="text-muted small mb-1 fst-italic"><?= htmlspecialchars($f['context']) ?></p>
              <?php endif; ?>
              <div class="d-flex align-items-center gap-2 mt-1">
                <div class="progress flex-grow-1" style="height:6px;border-radius:99px;">
                  <div class="progress-bar bg-<?= $dir_color ?>" style="width:<?= $impact_w ?>%"></div>
                </div>
                <span class="text-muted small"><?= htmlspecialchars($f['impact_level'] ?? '') ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── DATA INTEGRITY ──────────────────────────────────────── -->
      <?php if (!empty($integrity)): ?>
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Data Integrity Report</h5>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="bg-light rounded-3 p-3 text-center">
              <p class="text-muted small fw-bold mb-1">Missing Features</p>
              <p class="fw-bold fs-5 mb-0 <?= ($missing_pct ?? 0) > 30 ? 'text-warning' : 'text-success' ?>">
                <?= $missing_pct !== null ? round($missing_pct, 1) . '%' : '—' ?>
              </p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="bg-light rounded-3 p-3 text-center">
              <p class="text-muted small fw-bold mb-1">Outlier Detected</p>
              <p class="fw-bold fs-5 mb-0 <?= $outlier ? 'text-warning' : 'text-success' ?>">
                <?= $outlier ? 'Yes' : 'No' ?>
              </p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="bg-light rounded-3 p-3 text-center">
              <p class="text-muted small fw-bold mb-1">Drift Score</p>
              <p class="fw-bold fs-5 mb-0"><?= $drift_score !== null ? round($drift_score, 3) : '—' ?></p>
              <?php if ($drift_msg): ?>
              <p class="text-muted" style="font-size:0.65rem;"><?= htmlspecialchars($drift_msg) ?></p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── LONGITUDINAL ────────────────────────────────────────── -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-chart-line me-2 text-primary"></i>Longitudinal Trend</h5>
        <?php if ($long_message): ?>
          <p class="text-muted small mb-2 fst-italic"><?= htmlspecialchars($long_message) ?></p>
        <?php endif; ?>
        <?php if (!empty($longitudinal)): ?>
        <div class="row g-3 mb-4">
          <div class="col-6 col-md-3">
            <div class="bg-light rounded-3 p-3 text-center">
              <p class="text-muted small fw-bold mb-1">Total Submissions</p>
              <p class="fw-bold fs-5 mb-0"><?= $longitudinal['total_predictions'] ?? count($all_logs) ?></p>
            </div>
          </div>
          <?php if (!empty($longitudinal['average_probability'])): ?>
          <div class="col-6 col-md-3">
            <div class="bg-light rounded-3 p-3 text-center">
              <p class="text-muted small fw-bold mb-1">Avg Probability</p>
              <p class="fw-bold fs-5 mb-0"><?= round($longitudinal['average_probability'] * 100, 1) ?>%</p>
            </div>
          </div>
          <?php endif; ?>
          <?php if (!empty($longitudinal['trend'])): ?>
          <div class="col-6 col-md-3">
            <div class="bg-light rounded-3 p-3 text-center">
              <p class="text-muted small fw-bold mb-1">Glucose Trend</p>
              <p class="fw-bold fs-6 mb-0 <?= str_contains($longitudinal['trend'], 'Wors') ? 'text-danger' : (str_contains($longitudinal['trend'], 'Impr') ? 'text-success' : 'text-secondary') ?>">
                <?= htmlspecialchars($longitudinal['trend']) ?>
              </p>
            </div>
          </div>
          <?php endif; ?>
          <?php if (!empty($longitudinal['risk_progression'])): ?>
          <div class="col-6 col-md-3">
            <div class="bg-light rounded-3 p-3 text-center">
              <p class="text-muted small fw-bold mb-1">Progression</p>
              <p class="fw-bold fs-6 mb-0"><?= htmlspecialchars($longitudinal['risk_progression']) ?></p>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if (count($all_logs) >= 2): ?>
        <!-- Metric selector -->
        <div class="d-flex flex-wrap gap-2 mb-3" id="metricBtns">
          <button class="btn btn-sm btn-primary trend-btn active"   data-metric="glucose"         data-label="Glucose (mg/dL)">Glucose</button>
          <button class="btn btn-sm btn-outline-secondary trend-btn" data-metric="insulin"         data-label="Insulin (µU/mL)">Insulin</button>
          <button class="btn btn-sm btn-outline-secondary trend-btn" data-metric="bmi"             data-label="BMI (kg/m²)">BMI</button>
          <button class="btn btn-sm btn-outline-secondary trend-btn" data-metric="cholesterol_hdl" data-label="HDL (mg/dL)">HDL</button>
          <button class="btn btn-sm btn-outline-secondary trend-btn" data-metric="ldl"             data-label="LDL (mg/dL)">LDL</button>
          <button class="btn btn-sm btn-outline-secondary trend-btn" data-metric="triglycerides"   data-label="Triglycerides (mg/dL)">Triglycerides</button>
          <button class="btn btn-sm btn-outline-secondary trend-btn" data-metric="systolic_bp"     data-label="Systolic BP (mmHg)">Systolic BP</button>
          <button class="btn btn-sm btn-outline-secondary trend-btn" data-metric="diastolic_bp"    data-label="Diastolic BP (mmHg)">Diastolic BP</button>
        </div>
        <div style="position:relative;height:280px;">
          <canvas id="trendChart"></canvas>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
        (function() {
          const logs = <?= json_encode(array_values($all_logs)) ?>;
          if (!logs || logs.length < 2) return;

          const labels = logs.map(l => {
            const d = new Date(l.created_at);
            return d.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'2-digit'});
          });

          let chart;

          function render(metric, label) {
            if (chart) chart.destroy();
            const ctx = document.getElementById('trendChart').getContext('2d');
            chart = new Chart(ctx, {
              type: 'line',
              data: {
                labels,
                datasets: [{
                  label,
                  data: logs.map(l => (l[metric] !== null && l[metric] !== undefined) ? parseFloat(l[metric]) : null),
                  borderColor: '#1a56db',
                  backgroundColor: 'rgba(26,86,219,.07)',
                  borderWidth: 2.5,
                  pointBackgroundColor: '#1a56db',
                  pointRadius: 5,
                  pointHoverRadius: 7,
                  tension: 0.35,
                  fill: true,
                  spanGaps: true,
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: { display: false },
                  tooltip: {
                    callbacks: {
                      title: i => i[0].label,
                      label: i => ' ' + label + ': ' + (i.raw ?? 'N/A'),
                    }
                  }
                },
                scales: {
                  x: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 }, color: '#64748b' } },
                  y: {
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { size: 11 }, color: '#64748b' },
                    title: { display: true, text: label, font: { size: 11 }, color: '#64748b' }
                  }
                }
              }
            });
          }

          render('glucose', 'Glucose (mg/dL)');

          document.getElementById('metricBtns').addEventListener('click', function(e) {
            const btn = e.target.closest('.trend-btn');
            if (!btn) return;
            document.querySelectorAll('.trend-btn').forEach(b => {
              b.classList.remove('btn-primary', 'active');
              b.classList.add('btn-outline-secondary');
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-primary', 'active');
            render(btn.dataset.metric, btn.dataset.label);
          });

          // Hide buttons with no data
          document.querySelectorAll('.trend-btn').forEach(btn => {
            const hasData = logs.some(l => l[btn.dataset.metric] !== null && l[btn.dataset.metric] !== undefined && l[btn.dataset.metric] !== '');
            if (!hasData) btn.style.display = 'none';
          });
        })();
        </script>
        <?php elseif (count($all_logs) === 1): ?>
          <p class="text-muted small fst-italic">Only 1 submission — submit more lab tests to see your trend chart.</p>
        <?php else: ?>
          <p class="text-muted small fst-italic">No submissions found.</p>
        <?php endif; ?>
      </div>

      <?php elseif (!$pred_ok): ?>
      <!-- ── PREDICTION ERROR ────────────────────────────────────── -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white text-center">
        <i class="fa-solid fa-triangle-exclamation text-danger fs-1 mb-3"></i>
        <h4 class="fw-bold text-dark">Prediction Failed</h4>
        <?php $issues = $pred_response['body']['payload']['issues'] ?? []; ?>
        <?php if (!empty($issues)): ?>
          <?php foreach ($issues as $issue): ?>
          <p class="text-muted small"><?= htmlspecialchars($issue) ?></p>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted">The ML service could not generate a prediction. Please try again.</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- ── SUBMITTED METRICS ───────────────────────────────────── -->
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-clipboard-list me-2 text-primary"></i>Submitted Lab Values</h5>
        <div class="row g-2">
          <?php foreach ($metrics as $label => $info): ?>
          <div class="col-md-6 col-lg-4">
            <div class="bg-light rounded-3 p-2 px-3 <?= $info['required'] ? 'border border-primary border-opacity-25' : '' ?>">
              <p class="text-muted small mb-0">
                <?= htmlspecialchars($label) ?>
                <?= $info['required'] ? '<span class="badge bg-primary" style="font-size:0.6rem;">Required</span>' : '' ?>
              </p>
              <p class="fw-bold mb-0"><?= htmlspecialchars($info['val']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <!-- ── ACTIONS ─────────────────────────────────────────────── -->
      <div class="text-center">
        <a href="labtests.php" class="btn btn-primary px-5 py-2 fw-bold rounded-3 me-2">
          <i class="fa-solid fa-arrow-left me-2"></i>New Test
        </a>
        <a href="profile.php" class="btn btn-outline-secondary px-5 py-2 fw-bold rounded-3">
          <i class="fa-solid fa-user me-2"></i>My Profile
        </a>
      </div>

    </div>
  </div>
</div>

<?php require "common/footer.php"; ?>
</body>
</html>