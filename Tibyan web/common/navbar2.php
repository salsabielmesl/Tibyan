<script src="https://kit.fontawesome.com/dd9780e71f.js" crossorigin="anonymous"></script>
    <nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0 wow fadeIn" data-wow-delay="0.1s">
        <a href="index.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <h1 class="m-0 text-primary"><i class="fa-solid fa-laptop-medical"></i>Tibyān</h1>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="index.php" class="nav-item nav-link">Home</a>
                <a href="about.php" class="nav-item nav-link">About</a>
                <a href="contact.php" class="nav-item nav-link">Contact</a>
                <?php if (!empty($_SESSION['logged_in'])): ?>
                <a href="labtests.php" class="nav-item nav-link fw-bold text-primary">
                    <i class="fa-solid fa-flask-vial me-1"></i>Lab Tests
                </a>
                <a href="profile.php" class="nav-item nav-link"><i class="fa-solid fa-user me-1"></i>Profile</a>
                <a href="logout.php" class="nav-item nav-link text-danger"><i class="fa-solid fa-power-off me-1"></i>Logout</a>
                <?php else: ?>
                <a href="signin.php" class="nav-item nav-link"><i class="fa-solid fa-right-to-bracket me-1"></i>Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>