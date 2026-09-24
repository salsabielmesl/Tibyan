<?php include("comm/script.php"); ?>
<nav class="navbar navbar-expand bg-light navbar-light sticky-top px-4 py-0">
    <a href="#" class="sidebar-toggler flex-shrink-0">
        <i class="fa fa-bars"></i>
    </a>
    
    <div class="navbar-nav align-items-center ms-auto">
        <div class="nav-item dropdown">
            </div>
      
        <div class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img class="rounded-circle me-lg-2" src="img/logo.png" alt="" style="width: 40px; height: 40px;">
                <span class="d-none d-lg-inline-flex"><?php echo $_SESSION['username'] ?? 'Guest'; ?></span>
            </a>
            <?php include("comm/script.php"); ?>
            <div class="dropdown-menu dropdown-menu-end bg-light border-0 rounded-0 rounded-bottom m-0">
                <a href="logout.php" class="dropdown-item">
                    <i class="fa fa-sign-out-alt me-2"></i> Log Out
                </a>
            </div>
        </div>
    </div>
</nav>