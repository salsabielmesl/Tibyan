<?php require "comm/header.php" ?>
<?php require "comm/script.php" ?>

<style>
    /* Full screen centering with your exact blue background color */
    .login-container-wrapper {
        background-color: #0463FA; 
        min-height: 100vh;
    }

    /* Transparent Glassmorphism Login Card */
    .custom-login-card {
        background: rgba(255, 255, 255, 0.15); /* Semi-transparent white */
        backdrop-filter: blur(12px); /* Blurred glass effect */
        -webkit-backdrop-filter: blur(12px); /* Safari support */
        border-radius: 24px;
        width: 100%;
        max-width: 450px;
        padding: 40px 35px;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2); /* Soft depth shadow */
        border: 1px solid rgba(255, 255, 255, 0.25); /* Subtle glass border highlight */
    }

    /* Solid White Circular Logo Frame */
    .logo-frame {
        background-color: #ffffff; 
        border: 4px solid rgba(255, 255, 255, 0.3); /* Subtle outer blend */
        border-radius: 50%;
        width: 120px;
        height: 120px;
        margin: 0 auto 20px auto;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }

    .logo-frame img {
        width: 70%;
        height: auto;
    }

    /* White title text to stand out cleanly against the blue backdrop */
    .welcome-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.85rem;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    /* Subtle white divider line */
    .custom-divider {
        border-top: 1px solid rgba(255, 255, 255, 0.3);
        margin-bottom: 25px;
    }

    /* Semi-transparent white input fields to match the glass theme */
    .custom-input {
        border-radius: 10px !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        padding: 12px 15px !important;
        background-color: rgba(255, 255, 255, 0.9) !important; /* Kept mostly white for text readability */
        color: #212529 !important;
    }
    
    .custom-input:focus {
        background-color: #ffffff !important;
        border-color: #ffffff !important;
        box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25) !important;
    }

    /* Crisp solid white button with blue text to contrast perfectly with the glass design */
    .btn-custom-white {
        background-color: #ffffff !important;
        border-color: #ffffff !important;
        color: #0463FA !important;
        font-weight: 700 !important;
        border-radius: 10px !important;
        padding: 12px !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
        font-size: 1rem;
        transition: all 0.2s ease;
    }

    .btn-custom-white:hover {
        background-color: rgba(255, 255, 255, 0.9) !important;
        transform: translateY(-1px);
    }
</style>

<body>

<div class="wrapper login-3 login-container-wrapper d-flex align-items-center justify-content-center p-3">
    
    <div class="custom-login-card text-center">
        
        <div class="logo-frame">
            <img src="img/logo.png" alt="Logo">
        </div>
        
        <h2 class="welcome-title mb-3">Admin Login</h2>
        
        <div class="custom-divider"></div>

        <form class="text-start w-100" action="main.php">
            
            <div class="mb-3">
                <label class="form-label fw-semibold text-white ps-1">Email</label>
                <input type="email" class="form-control custom-input" placeholder="Enter email" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold text-white ps-1">Password</label>
                <input type="password" class="form-control custom-input" placeholder="Enter password" required>
            </div>

            <button class="btn btn-custom-white w-100 mb-3">Login</button>

            
            </div>

        </form>

    </div>

</div>

<?php require "comm/script.php" ?>

</body>
</html>