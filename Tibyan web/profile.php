<body>
<?php require "common/head.php"; ?>
<?php require "common/script.php"; ?>
<?php require "common/navbar2.php"; ?>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in'])) { header('Location: signin.php'); exit; }
require_once 'common/api.php';

// ── Fetch profile ────────────────────────────────────────────────────────────
$pr = api_call('user/profile', 'GET');
$profile = $pr['body']['payload']['result'] ?? $pr['body']['payload'] ?? $pr['body'] ?? [];
if (isset($profile['status'])) $profile = []; // error response

$user_email = $profile['email']         ?? $_SESSION['user_email'] ?? '';
$user_name  = $profile['full_name']     ?? $_SESSION['user_name']  ?? 'User';
$user_role  = $profile['role']          ?? $_SESSION['role']       ?? 'patient';
$user_dob   = $profile['date_of_birth'] ?? $_SESSION['date_of_birth'] ?? '';
$user_phone = $profile['contact_info']  ?? $_SESSION['contact_info']  ?? '';
$user_upid  = $profile['upid']          ?? $_SESSION['upid']       ?? '';

// ── Fetch consent ────────────────────────────────────────────────────────────
$cr = api_call('consent', 'GET');
$consent = $cr['body']['payload'] ?? $cr['body'] ?? [];
if (isset($consent['status'])) $consent = [];

$has_consent     = !empty($consent);
$research_opt_in = $has_consent ? (bool)($consent['research_opt_in'] ?? false) : false;
$policy_version  = $has_consent ? ($consent['policy_agreed_version'] ?? '1') : null;
$agreed_at       = $has_consent ? ($consent['agreed_at'] ?? '') : '';
$withdrawn       = $has_consent ? ($consent['withdrawn_requested_at'] ?? null) : null;

// ── Fetch subscription ───────────────────────────────────────────────────────
$sr = api_call('subscription', 'GET');
$sub_raw = $sr['body']['payload'] ?? $sr['body'] ?? [];
if (isset($sub_raw['status'])) $sub_raw = [];
$subscription = !empty($sub_raw) ? (isset($sub_raw[0]) ? end($sub_raw) : $sub_raw) : null;
$sub_status   = $subscription['status']       ?? 'none';
$sub_renewal  = $subscription['renewal_date'] ?? '';

// ── Flash messages ───────────────────────────────────────────────────────────
$success_msg = $_GET['success'] ?? '';
$error_msg   = $_GET['error']   ?? '';
?>

<style>
:root {
    --teal:   #0463FA;
    --teal-d: #0f766e;
    --teal-l: #ccfbf1;
    --slate:  #1e293b;
    --muted:  #64748b;
    --border: #e2e8f0;
    --bg:     #f1f5f9;
}
body { background: var(--bg); font-family: 'Open Sans', sans-serif; }

/* ── Hero ── */
.profile-hero {
    background: linear-gradient(135deg, #0463FA, #0d9488 55%, #14b8a6);
    padding: 3.5rem 0 5.5rem;
    position: relative; overflow: hidden; text-align: center; color: #fff;
}
.profile-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:260px; height:260px; border-radius:50%;
    background:rgba(255,255,255,.06);
}
.profile-hero::after {
    content:''; position:absolute; bottom:-1px; left:0; right:0;
    height:56px; background:var(--bg);
    clip-path: ellipse(55% 100% at 50% 100%);
}
.avatar-ring {
    width:88px; height:88px; border-radius:50%;
    background:rgba(255,255,255,.18);
    border:3px solid rgba(255,255,255,.45);
    display:flex; align-items:center; justify-content:center;
    font-size:2.1rem; color:#fff; margin:0 auto 1rem;
}
.upid-tag {
    display:inline-block;
    background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.3);
    color:#fff; font-family:'Courier New',monospace;
    font-size:.75rem; letter-spacing:.1em;
    padding:.22rem .75rem; border-radius:20px; margin-top:.3rem;
}

/* ── Cards ── */
.p-card {
    background:#fff; border:1px solid var(--border);
    border-radius:14px; padding:1.6rem;
    margin-bottom:1.3rem;
    box-shadow:0 1px 8px rgba(0,0,0,.05);
}
.card-label {
    font-size:.68rem; font-weight:700; letter-spacing:.13em;
    text-transform:uppercase; color:var(--teal);
    display:flex; align-items:center; gap:.45rem; margin-bottom:1rem;
}
.info-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:.55rem 0; border-bottom:1px solid var(--border);
    font-size:.88rem;
}
.info-row:last-of-type { border-bottom:none; }
.lbl { color:var(--muted); font-size:.8rem; }
.val { font-weight:600; color:var(--slate); }

/* ── Status pills ── */
.pill {
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.25rem .8rem; border-radius:20px;
    font-size:.78rem; font-weight:600;
}
.pill-active  { background:#dcfce7; color:#15803d; }
.pill-trial   { background:#fef9c3; color:#854d0e; }
.pill-expired { background:#fee2e2; color:#b91c1c; }
.pill-none    { background:#f1f5f9; color:var(--muted); }
.pill-dot { width:7px; height:7px; border-radius:50%; background:currentColor; }

/* ── Consent toggle ── */
.tog { position:relative; width:44px; height:24px; flex-shrink:0; }
.tog input { opacity:0; width:0; height:0; }
.tog-track {
    position:absolute; inset:0; background:#cbd5e1;
    border-radius:24px; cursor:pointer; transition:background .25s;
}
.tog-track::before {
    content:''; position:absolute;
    width:18px; height:18px; left:3px; bottom:3px;
    background:#fff; border-radius:50%;
    box-shadow:0 1px 3px rgba(0,0,0,.2);
    transition:transform .25s;
}
.tog input:checked ~ .tog-track { background:var(--teal); }
.tog input:checked ~ .tog-track::before { transform:translateX(20px); }

/* ── Buttons ── */
.btn-t {
    background:var(--teal); color:#fff; border:none;
    padding:.52rem 1.25rem; border-radius:8px;
    font-size:.86rem; font-weight:600;
    cursor:pointer; transition:background .2s, transform .15s;
    display:inline-flex; align-items:center; gap:.4rem;
}
.btn-t:hover { background:var(--teal-d); transform:translateY(-1px); color:#fff; }
.btn-o {
    background:transparent; color:var(--teal);
    border:1.5px solid var(--teal);
    padding:.5rem 1.2rem; border-radius:8px;
    font-size:.86rem; font-weight:600;
    cursor:pointer; transition:all .2s;
    display:inline-flex; align-items:center; gap:.4rem;
}
.btn-o:hover { background:var(--teal-l); }
.btn-danger-o {
    background:transparent; color:#b91c1c;
    border:1.5px solid #b91c1c;
    padding:.5rem 1.2rem; border-radius:8px;
    font-size:.86rem; font-weight:600;
    cursor:pointer; transition:all .2s;
    display:inline-flex; align-items:center; gap:.4rem;
}
.btn-danger-o:hover { background:#fee2e2; }

/* ── Alerts ── */
.flash {
    padding:.7rem 1rem; border-radius:8px;
    font-size:.87rem; margin-bottom:1.1rem;
    display:flex; align-items:center; gap:.5rem;
}
.flash-ok  { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
.flash-err { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

/* ── Form ── */
.form-control, .form-select {
    border-radius:8px; border:1px solid var(--border);
    font-size:.88rem; padding:.55rem .85rem;
}
.form-control:focus, .form-select:focus {
    border-color:var(--teal); box-shadow:0 0 0 3px rgba(13,148,136,.12);
}
.form-label { font-size:.78rem; font-weight:700; color:var(--muted); margin-bottom:.35rem; }
</style>

<!-- ── Hero ──────────────────────────────────────────────────── -->
<div class="profile-hero">
    <div class="avatar-ring"><i class="fa fa-user"></i></div>
    <h4 class="fw-bold mb-1"><?= htmlspecialchars($user_name) ?></h4>
    <p style="opacity:.85;font-size:.9rem;margin-bottom:.25rem"><?= htmlspecialchars($user_email) ?></p>
    <?php if ($user_upid): ?>
        <div><span class="upid-tag"><?= htmlspecialchars($user_upid) ?></span></div>
    <?php endif; ?>
</div>

<!-- ── Body ──────────────────────────────────────────────────── -->
<div class="container" style="max-width:680px;margin-top:-2.8rem;padding-bottom:3rem;position:relative;z-index:10;">

    <?php if ($success_msg): ?>
        <div class="flash flash-ok"><i class="fa fa-check-circle"></i><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="flash flash-err"><i class="fa fa-exclamation-circle"></i><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <!-- Personal Info -->
    <div class="p-card">
        <div class="card-label"><i class="fa fa-id-card"></i> Personal Information</div>
        <div class="info-row"><span class="lbl">Full Name</span><span class="val"><?= htmlspecialchars($user_name) ?></span></div>
        <div class="info-row"><span class="lbl">Email</span><span class="val"><?= htmlspecialchars($user_email) ?></span></div>
        <?php if ($user_dob): ?>
        <div class="info-row"><span class="lbl">Date of Birth</span><span class="val"><?= htmlspecialchars($user_dob) ?></span></div>
        <?php endif; ?>
        <?php if ($user_phone): ?>
        <div class="info-row"><span class="lbl">Contact</span><span class="val"><?= htmlspecialchars($user_phone) ?></span></div>
        <?php endif; ?>
        
        <div class="mt-3 d-flex gap-2 flex-wrap">
            <button class="btn-t" data-bs-toggle="modal" data-bs-target="#pwModal">
                <i class="fa fa-key"></i> Change Password
            </button>
            <button class="btn-o" data-bs-toggle="modal" data-bs-target="#editModal">
                <i class="fa fa-edit"></i> Edit Profile
            </button>
        </div>
    </div>

    <!-- Consent -->
    <div class="p-card">
        <div class="card-label"><i class="fa fa-shield-alt"></i> Consent &amp; Privacy</div>
        <?php if (!$has_consent): ?>
            <p style="color:var(--muted);font-size:.87rem;margin-bottom:.8rem">You have not signed a consent policy yet.</p>
            <button class="btn-t" data-bs-toggle="modal" data-bs-target="#consentModal">
                <i class="fa fa-file-signature"></i> Review &amp; Sign
            </button>
        <?php else: ?>
            <div class="info-row"><span class="lbl">Policy Version</span><span class="val">v<?= htmlspecialchars($policy_version) ?></span></div>
            <div class="info-row"><span class="lbl">Agreed On</span><span class="val"><?= htmlspecialchars($agreed_at) ?></span></div>
            <div class="info-row">
                <span class="lbl">Research Data Sharing</span>
                <form method="POST" action="handlers/update_consent.php" id="toggleForm" style="margin:0">
                    <input type="hidden" name="policy_agreed_version" value="<?= htmlspecialchars($policy_version) ?>">
                    <input type="hidden" name="research_opt_in" value="0" id="hiddenOptIn">
                    <label class="tog" title="Toggle research opt-in">
                        <input type="checkbox" id="researchToggle" <?= $research_opt_in ? 'checked' : '' ?>>
                        <span class="tog-track"></span>
                    </label>
                </form>
            </div>
            <?php if ($withdrawn): ?>
            <div class="info-row"><span class="lbl">Withdrawal Requested</span><span class="val" style="color:#b91c1c"><?= htmlspecialchars($withdrawn) ?></span></div>
            <?php endif; ?>
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <button class="btn-o" data-bs-toggle="modal" data-bs-target="#consentModal">
                    <i class="fa fa-edit"></i> Update Consent
                </button>
                <?php if (!$withdrawn): ?>
                <button class="btn-danger-o" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                    <i class="fa fa-times-circle"></i> Withdraw Consent
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Subscription -->
    <div class="p-card">
        <div class="card-label"><i class="fa fa-credit-card"></i> Subscription</div>
        <?php
        $pc = 'none'; $pl = 'No Active Plan';
        if ($sub_status === 'active')  { $pc = 'active';  $pl = 'Active'; }
        if ($sub_status === 'trial')   { $pc = 'trial';   $pl = 'Trial'; }
        if ($sub_status === 'expired') { $pc = 'expired'; $pl = 'Expired'; }
        ?>
        <?php if ($sub_status === 'none' || empty($subscription)): ?>
        <div style="background:#f8faff;border:1px dashed #c7d7f0;border-radius:10px;padding:1.2rem;text-align:center;margin-bottom:1rem;">
            <div style="font-size:2rem;margin-bottom:.3rem;">🌱</div>
            <p class="fw-bold mb-1">No active subscription</p>
            <p class="text-muted small mb-0">Activate a plan to access predictions, history and PDF reports.</p>
        </div>
        <button class="btn-t w-100" data-bs-toggle="modal" data-bs-target="#subModal">
            <i class="fa fa-star me-1"></i> Activate Plan
        </button>
        <?php elseif ($sub_status === 'expired'): ?>
        <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:10px;padding:1rem;margin-bottom:1rem;">
            <span class="pill pill-expired"><span class="pill-dot"></span>Expired</span>
            <p class="text-muted small mb-0 mt-2">Your access has ended. Reactivate to continue using Tibyan.</p>
        </div>
        <button class="btn-t w-100" data-bs-toggle="modal" data-bs-target="#subModal">
            <i class="fa fa-rotate me-1"></i> Reactivate Plan
        </button>
        <?php else: ?>
        <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:10px;padding:1rem;margin-bottom:1rem;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="pill pill-<?= $pc ?>"><span class="pill-dot"></span><?= $pl ?></span>
                    <p class="fw-bold mb-0 mt-2">Tibyan <?= $sub_status === 'trial' ? 'Trial' : 'Premium' ?></p>
                    <p class="text-muted small mb-0">Full access to predictions, history &amp; PDF reports</p>
                </div>
                <div style="font-size:2rem">🌿</div>
            </div>
            <?php if ($sub_renewal): ?>
            <div class="mt-2 pt-2 border-top border-light">
                <span class="text-muted small">
                    <?= $sub_status === 'trial' ? 'Trial ends' : 'Renews' ?>:
                    <strong><?= htmlspecialchars(date('M j, Y', strtotime($sub_renewal))) ?></strong>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <button class="btn-o flex-grow-1" data-bs-toggle="modal" data-bs-target="#subModal">
                <i class="fa fa-sync me-1"></i> Manage
            </button>
            <button class="btn-danger-o" data-bs-toggle="modal" data-bs-target="#cancelSubModal">
                <i class="fa fa-times me-1"></i> Cancel
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════ MODALS ══════════════ -->

<!-- Change Password -->
<div class="modal fade" id="pwModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Change Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="handlers/update_password.php" id="pwForm">
        <div class="modal-body pt-3">
          <div id="pw-alert" class="flash flash-err" style="display:none"></div>
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" class="form-control" name="old_password" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password <span style="color:var(--muted);font-weight:400">(min 8 chars)</span></label>
            <input type="password" class="form-control" name="new_password" id="np" minlength="8" required>
          </div>
          <div class="mb-1">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" id="cp" minlength="8" required>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-t">Update Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Profile -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Edit Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="handlers/update_profile.php">
        <div class="modal-body pt-3">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($user_name) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user_email) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Date of Birth</label>
            <input type="date" class="form-control" name="date_of_birth" value="<?= htmlspecialchars($user_dob) ?>">
          </div>
          <div class="mb-1">
            <label class="form-label">Contact Info</label>
            <input type="text" class="form-control" name="contact_info" value="<?= htmlspecialchars($user_phone) ?>">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-t">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Consent -->
<div class="modal fade" id="consentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Consent Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="handlers/update_consent.php">
        <div class="modal-body pt-3">
          <div style="background:#f8fafc;border-radius:8px;padding:1rem;font-size:.83rem;color:var(--muted);max-height:160px;overflow-y:auto;margin-bottom:1rem;line-height:1.6">
            <strong style="color:var(--slate)">Tibyan Data &amp; Privacy Policy — v1</strong><br><br>
            By using Tibyan's health analysis features, you agree that your anonymised lab data
            may be stored securely and used to generate personalised health insights.
            You may opt in to share anonymised data with our research partners to help advance
            diabetes and metabolic syndrome research. You can withdraw consent at any time.
          </div>
          <input type="hidden" name="policy_agreed_version" value="1">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="research_opt_in" value="1"
                   id="consentCheck" <?= $research_opt_in ? 'checked' : '' ?>>
            <label class="form-check-label" for="consentCheck" style="font-size:.86rem">
              I agree to share anonymised data for research purposes
            </label>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-t">Save Consent</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Withdraw Consent -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-danger">Withdraw Consent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="handlers/withdraw_consent.php">
        <div class="modal-body pt-2">
          <p style="font-size:.88rem;color:var(--muted)">
            This will mark your consent as withdrawn and stop your data from being used in research.
            Your existing health logs will be retained but anonymised. This action can be reversed
            by signing the consent policy again.
          </p>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger px-4">Confirm Withdrawal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Subscription Modal -->
<div class="modal fade" id="subModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">
          <?php if ($sub_status === 'active' || $sub_status === 'trial'): ?>Manage Subscription<?php else: ?>Activate Subscription<?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="handlers/update_subscription.php">
        <div class="modal-body pt-2">
          <?php if ($sub_status === 'active' || $sub_status === 'trial'): ?>
          <div style="background:#f0fdf4;border-radius:8px;padding:.8rem 1rem;margin-bottom:1rem;font-size:.85rem;">
            <i class="fa fa-circle-check text-success me-1"></i>
            You currently have an <strong><?= $pl ?></strong> subscription.
            <?php if ($sub_renewal): ?> It <?= $sub_status === 'trial' ? 'ends' : 'renews' ?> on <strong><?= htmlspecialchars(date('M j, Y', strtotime($sub_renewal))) ?></strong>.<?php endif; ?>
          </div>
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label fw-bold small">Status</label>
            <select class="form-select" name="status">
              <option value="trial"   <?= $sub_status==='trial'   ? 'selected':'' ?>>Trial — Free access for evaluation</option>
              <option value="active"  <?= $sub_status==='active'  ? 'selected':'' ?>>Active — Full access</option>
              <option value="expired" <?= $sub_status==='expired' ? 'selected':'' ?>>Expired — No access</option>
            </select>
          </div>
          <div class="mb-1">
            <label class="form-label fw-bold small"><?= ($sub_status === 'trial') ? 'Trial End Date' : 'Renewal Date' ?></label>
            <input type="date" class="form-control" name="renewal_date" value="<?= htmlspecialchars($sub_renewal) ?>" min="<?= date('Y-m-d') ?>">
            <div class="form-text">Leave blank for open-ended access.</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn-t">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Cancel Subscription Modal -->
<div class="modal fade" id="cancelSubModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-danger"><i class="fa fa-triangle-exclamation me-2"></i>Cancel Subscription</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="handlers/update_subscription.php">
        <input type="hidden" name="status" value="expired">
        <input type="hidden" name="renewal_date" value="">
        <div class="modal-body pt-2">
          <p class="text-muted small mb-3">Cancelling will immediately set your account to <strong>Expired</strong>. You will lose access to:</p>
          <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:8px;padding:.8rem 1rem;font-size:.84rem;">
            <ul class="mb-0 ps-3">
              <li>New lab test submissions</li>
              <li>ML predictions</li>
              <li>PDF health reports</li>
              <li>Prediction history</li>
            </ul>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Keep My Plan</button>
          <button type="submit" class="btn btn-danger px-4">Yes, Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>>

<!-- Withdraw Consent -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-danger">Withdraw Consent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="handlers/withdraw_consent.php">
        <div class="modal-body pt-2">
          <p style="font-size:.88rem;color:var(--muted)">
            This will mark your consent as withdrawn and stop your data from being used in research.
            Your existing health logs will be retained but anonymised. This action can be reversed
            by signing the consent policy again.
          </p>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger px-4">Confirm Withdrawal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// ── Password confirm validation ──────────────────────────────────────────────
document.getElementById('pwForm').addEventListener('submit', function(e) {
    const np = document.getElementById('np').value;
    const cp = document.getElementById('cp').value;
    const al = document.getElementById('pw-alert');
    if (np !== cp) {
        e.preventDefault();
        al.textContent = 'New passwords do not match.';
        al.style.display = 'flex';
    }
});

// ── Research toggle auto-submit ──────────────────────────────────────────────
const toggle = document.getElementById('researchToggle');
if (toggle) {
    toggle.addEventListener('change', function() {
        document.getElementById('hiddenOptIn').value = this.checked ? '1' : '0';
        document.getElementById('toggleForm').submit();
    });
}
</script>

<?php require "common/footer.php"; ?>
</body>
</html

</div>
>