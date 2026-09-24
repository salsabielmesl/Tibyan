<script src="https://kit.fontawesome.com/dd9780e71f.js" crossorigin="anonymous"></script>

    <div class="sidebar pe-4 pb-3">
            <nav class="navbar bg-light navbar-light">
                <div>
                <a href="main.php" class="navbar-brand text-start px-4 px-lg-5 w-100">
                    <h2 class="m-0 text text-start text-primary">
                        <i class="fa-solid fa-laptop-medical me-3 ms-1"></i>Tebyān
                    </h2>
                </a></div>
                <div class="d-flex align-items-center ms-4 mb-4">
                    <div class="position-relative">
                        <img class="rounded-circle" src="img/logo.png" alt="" style="width: 40px; height: 40px;">
                        <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-0"> <?php echo $_SESSION['username'] ?? 'Guest'; ?> </h6>
                        <span> <?php echo $_SESSION['user_type'] ?? ''; ?> </span>
                    </div>
                </div>
                <div class="navbar-nav w-100">
                    <a href="main.php" class="nav-item nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'main.php' ? 'active' : ''; ?>" > <i class="fa fa-tachometer-alt me-2" ></i>Dashboard</a>
                    <a href="carousel.php" class="nav-item nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'carousel.php' ? 'active' : ''; ?>"><i class="fa fa-tachometer-alt me-2" ></i>Carousel</a>
                    <a href="about.php" class="nav-item nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>"><i class="fa fa-tachometer-alt me-2" ></i>About Us</a>
                    <a href="services.php" class="nav-item nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>"><i class="fa fa-tachometer-alt me-2" ></i>Services</a>
                    <a href="features.php" class="nav-item nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'features.php' ? 'active' : ''; ?>"><i class="fa fa-tachometer-alt me-2" ></i>Features</a>
                    <a href="about2.php" class="nav-item nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about2.php' ? 'active' : ''; ?>"><i class="fa fa-tachometer-alt me-2" ></i>About Page</a>
                    <a href="contact.php" class="nav-item nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>"><i class="fa fa-tachometer-alt me-2" ></i>Contact Page</a>
                </div>
            </nav>
        </div>