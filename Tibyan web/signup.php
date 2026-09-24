<?php require "common/head.php" ?>
<?php require "common/script.php" ?>

<style>
    .login-container-wrapper {
        background-color: #0463FA; 
        min-height: 100vh;
        overflow-y: auto;
    }

    .custom-login-card {
        background: rgba(255, 255, 255, 0.15); 
        backdrop-filter: blur(12px); 
        -webkit-backdrop-filter: blur(12px); 
        border-radius: 16px;
        width: 100%;
        max-width: 460px; 
        padding: 20px 22px;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2); 
        border: 1px solid rgba(255, 255, 255, 0.25); 
    }

    .logo-frame {
        background-color: #ffffff; 
        border: 3px solid rgba(255, 255, 255, 0.3); 
        border-radius: 50%;
        width: 70px; 
        height: 70px; 
        margin: 0 auto 8px auto;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        overflow: hidden;
    }

    .logo-frame img {
        width: 95%;
        height: auto;
    }

    .welcome-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.35rem;
        letter-spacing: -0.5px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    .custom-divider {
        border-top: 1px solid rgba(255, 255, 255, 0.25);
        margin: 10px 0;
    }

    .custom-input {
        border-radius: 8px !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        padding: 8px 12px !important; 
        background-color: rgba(255, 255, 255, 0.95) !important; 
        color: #212529 !important;
        font-size: 0.85rem;
    }
    
    .custom-input:focus {
        background-color: #ffffff !important;
        border-color: #ffffff !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.2) !important;
    }

    .btn-custom-white {
        background-color: #ffffff !important;
        border-color: #ffffff !important;
        color: #0463FA !important;
        font-weight: 700 !important;
        border-radius: 8px !important;
        padding: 9px !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    /* --- Payment Modal Custom Styling --- */
    .payment-modal-content {
        border-radius: 16px;
        border: none;
        padding: 10px;
    }
    
    .payment-option-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        background: #fff;
    }

    .payment-option-card:hover {
        border-color: #0463FA;
        background-color: #f4f8ff;
    }

    /* Radio button hidden inside option card */
    .payment-option-card input[type="radio"] {
        display: none;
    }

    /* Active styling state when selected */
    .payment-option-card.selected {
        border: 2px solid #0463FA;
        background-color: #f4f8ff;
    }

    .payment-option-card i, 
    .payment-option-card font {
        font-size: 1.4rem;
        display: block;
        margin-bottom: 4px;
    }
</style>

<body>

<div class="wrapper login-3 login-container-wrapper d-flex align-items-center justify-content-center p-2">
    <div class="custom-login-card text-center">
        
        <div class="logo-frame">
            <img src="img/logo.png" alt="Logo">
        </div>
        
        <h2 class="welcome-title mb-1">Create Account</h2>
        <div class="custom-divider"></div>

        <form id="signupForm" class="text-start w-100" action="index.php">
            
            <div class="mb-2">
                <input type="text" class="form-control custom-input" placeholder="Full Name" required>
            </div>

            <div class="mb-2">
                <input type="email" class="form-control custom-input" placeholder="Email Address" required>
            </div>

            <div class="mb-2">
                <input type="password" class="form-control custom-input" placeholder="Password" required>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <input type="date" class="form-control custom-input" required>
                </div>
                <div class="col-6">
                    <input type="tel" class="form-control custom-input" placeholder="Contact Info" required>
                </div>
            </div>

            <button type="submit" class="btn btn-custom-white w-100 mb-1">Sign Up</button>

            <div class="text-center mt-2 small" style="font-size: 0.825rem;">
                <span class="text-white-50">Already have an account?</span>
                <a href="signin.php" class="text-white Paradox fw-bold text-decoration-none ms-1">Login here</a>
            </div>

        </form>
    </div>
</div>

<div class="modal fade" id="paymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content payment-modal-content">
            <div class="modal-body text-center p-4">
                
                <div class="text-primary mb-2" style="font-size: 2.5rem;">
                    💳
                </div>
                
                <h4 class="fw-bold text-dark mb-1">Payment Method</h4>
                <p class="text-muted small px-3">Select your preferred payment method for the $5 monthly subscription</p>
                
                <form id="paymentForm" action="index.php" method="POST">
                    <div class="row g-2 my-3">
                        <div class="col-6">
                            <label class="w-100">
                                <input type="radio" name="payment_method" value="mastercard" required>
                                <div class="payment-option-card">
                                    <span>🌐</span>
                                    <div class="small fw-bold mt-1">MasterCard</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="w-100">
                                <input type="radio" name="payment_method" value="visa">
                                <div class="payment-option-card">
                                    <span>💳</span>
                                    <div class="small fw-bold mt-1">Visa</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="w-100">
                                <input type="radio" name="payment_method" value="paypal">
                                <div class="payment-option-card">
                                    <span>🅿️</span>
                                    <div class="small fw-bold mt-1">PayPal</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="w-100">
                                <input type="radio" name="payment_method" value="omt">
                                <div class="payment-option-card">
                                    <span>💵</span>
                                    <div class="small fw-bold mt-1">OMT</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="w-100">
                                <input type="radio" name="payment_method" value="wishmoney">
                                <div class="payment-option-card">
                                    <span>📱</span>
                                    <div class="small fw-bold mt-1">Wish Money</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="w-100">
                                <input type="radio" name="payment_method" value="other">
                                <div class="payment-option-card">
                                    <span>💼</span>
                                    <div class="small fw-bold mt-1">Other Methods</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-success py-2 fw-bold" style="background-color: #28a745; border:none; border-radius: 8px;">
                            ✓ Complete Payment
                        </button>
                        <button type="button" class="btn btn-primary py-2 fw-bold" data-bs-dismiss="modal" style="background-color: #17a2b8; border:none; border-radius: 8px;">
                            ← Back
                        </button>
                    </div>

                    <div class="mt-3">
                        <a href="signin.php" class="text-decoration-none small text-secondary">Cancel and return to login</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const signupForm = document.getElementById('signupForm');
    // Initialize Bootstrap Modal context object safely
    const paymentModalElement = document.getElementById('paymentModal');
    const paymentModal = new bootstrap.Modal(paymentModalElement);

    // 1. Intercept Sign up Action
    signupForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Stop page from parsing immediately
        
        // Collect all form fields
        const fullName  = document.querySelector('input[placeholder="Full Name"]').value.trim();
        const email     = document.querySelector('input[placeholder="Email Address"]').value.trim();
        const password  = document.querySelector('input[placeholder="Password"]').value;
        const dob       = document.querySelector('input[type="date"]').value;
        const contact   = document.querySelector('input[placeholder="Contact Info"]').value.trim();

        // Send to backend API
        fetch('handlers/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                full_name:     fullName,
                email:         email,
                password:      password,
                date_of_birth: dob,
                contact_info:  contact
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                paymentModal.show();
            } else {
                const msg = data.errors
                    ? Object.values(data.errors).join('\n')
                    : (data.message || 'Registration failed');
                alert('Error: ' + msg);
            }
        })
        .catch(err => alert('Could not reach server. Please try again.'));
    });

    // 2. Select Option Visual Scripting (Adds UX outline borders matching selection)
    const radioCards = document.querySelectorAll('.payment-option-card');
    radioCards.forEach(card => {
        card.addEventListener('click', function() {
            // Clear current choices setup
            radioCards.forEach(c => c.classList.remove('selected'));
            // Select targeted setup
            this.classList.add('selected');
            this.closest('label').querySelector('input[type="radio"]').checked = true;
        });
    });
});
</script>

<?php require "common/script.php" ?>
</body>
</html>