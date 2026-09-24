<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Must be logged in to access this page
if (empty($_SESSION['logged_in'])) {
    header('Location: signin.php'); exit;
}
?>
<body>
<?php require "common/head.php"; ?>
<?php require "common/script.php"; ?>
<?php require "common/navbar2.php"; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                
                <div class="text-center mb-5">
                    <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle mb-3" style="width: 60px; height: 60px; font-size: 1.75rem;">
                        <i class="fa-solid fa-square-poll-horizontal"></i>
                    </div>
                    <h2 class="fw-bold m-0 text-dark">Lab Test Input Portal</h2>
                    <p class="text-muted small mt-1">Please populate clinical metrics below to run the diagnostic system analyzer.</p>
                </div>

                <form method="POST" action="result.php">

                    <div class="mb-4">
                        <h5 class="text-primary border-bottom pb-2 mb-3 fw-bold"><i class="fa-solid fa-droplet me-2"></i>Metabolic & Glycemic Indicators</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Fasting Plasma Glucose (mg/dL)</label>
                                <input type="number" step="0.01" name="glucose" class="form-control bg-light border-0 py-2" placeholder="e.g. 95" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Serum Insulin (μU/mL)</label>
                                <input type="number" step="0.01" min="0.1" name="insulin" class="form-control bg-light border-0 py-2" placeholder="e.g. 6.0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">C-peptide (pmol/mL)</label>
                                <input type="number" step="0.01" name="cpeptide" class="form-control bg-light border-0 py-2" placeholder="e.g. 1.1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">HbA1c / Serum Bicarbonate (mmol/L)</label>
                                <input type="number" step="0.01" name="bicarbonate" class="form-control bg-light border-0 py-2" placeholder="e.g. 24">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="text-primary border-bottom pb-2 mb-3 fw-bold"><i class="fa-solid fa-heart-pulse me-2"></i>Lipid & Cardiovascular Panel</h5>
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label text-muted small fw-bold">HDL Cholesterol (mg/dL)</label>
                                <input type="number" step="0.01" name="hdl" class="form-control bg-light border-0 py-2" placeholder="e.g. 50">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label text-muted small fw-bold">LDL Cholesterol (mg/dL)</label>
                                <input type="number" step="0.01" name="ldl" class="form-control bg-light border-0 py-2" placeholder="e.g. 100">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label text-muted small fw-bold">Triglycerides (mg/dL)</label>
                                <input type="number" step="0.01" name="triglycerides" class="form-control bg-light border-0 py-2" placeholder="e.g. 150">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label text-muted small fw-bold">ALT Liver Enzyme (U/L)</label>
                                <input type="number" step="0.01" name="alt" class="form-control bg-light border-0 py-2" placeholder="e.g. 25">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Systolic Blood Pressure (mm Hg)</label>
                                <input type="number" step="0.01" name="systolic" class="form-control bg-light border-0 py-2" placeholder="e.g. 120">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Diastolic Blood Pressure (mm Hg)</label>
                                <input type="number" step="0.01" name="diastolic" class="form-control bg-light border-0 py-2" placeholder="e.g. 80">
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <h5 class="text-primary border-bottom pb-2 mb-3 fw-bold"><i class="fa-solid fa-weight-scale me-2"></i>Renal & Body Anthropometry</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">Waist Circumference (cm)</label>
                                <input type="number" step="0.01" name="circumfirance" class="form-control bg-light border-0 py-2" placeholder="e.g. 88" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">Body Mass Index (BMI)</label>
                                <input type="number" step="0.01" name="bmi" class="form-control bg-light border-0 py-2" placeholder="e.g. 24.5" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">Waist-Hip Ratio</label>
                                <input type="number" step="0.01" name="waist-hip" class="form-control bg-light border-0 py-2" placeholder="e.g. 0.85">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">Serum Albumin (g/dL)</label>
                                <input type="number" step="0.01" name="albumin" class="form-control bg-light border-0 py-2" placeholder="e.g. 4.2">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">Urinary Albumin (μg/mL)</label>
                                <input type="number" step="0.01" name="ualbumin" class="form-control bg-light border-0 py-2" placeholder="e.g. 12">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">Blood Urea Nitrogen (mmol/L)</label>
                                <input type="number" step="0.01" name="nitrogen" class="form-control bg-light border-0 py-2" placeholder="e.g. 4.5">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted small fw-bold">Sagittal Abdominal Diameter (cm)</label>
                                <input type="number" step="0.01" name="sagittal" class="form-control bg-light border-0 py-2" placeholder="e.g. 20">
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-2">
                        <button type="submit" class="btn btn-primary btn-lg px-5 py-3 fw-bold shadow-sm rounded-3 w-100 w-md-auto">
                            <i class="fa-solid fa-chart-line me-2"></i>Generate Analytics Report
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<div>
<?php require "common/footer.php"; ?>
</div>

</body>
</html>