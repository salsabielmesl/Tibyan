<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PermissionCheck
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('url');
        $this->CI->load->library('session');
    }

    public function check_permission()
    {
        $controller = strtolower($this->CI->router->fetch_class());
        $method     = strtolower($this->CI->router->fetch_method());

        // Public controllers (no login required)
        $public_controllers = ['auth', 'login', 'register'];

        if (in_array($controller, $public_controllers)) {
            return;
        }

        // If user not logged in → redirect to login
        if (!$this->CI->session->userdata('logged_in')) {
            $this->CI->session->set_userdata('redirect_url', current_url());
            redirect('auth/index');
        }

        // Get role from session (optional)
        $role = $this->CI->session->userdata('role');

        // If you DON'T want roles, comment this block
        if ($role && !$this->has_access($controller, $role)) {
            $this->access_denied();
        }
    }

    private function has_access($controller, $role)
    {
        // Define allowed controllers per role (EMPTY TEMPLATE)
        $permissions = [
            'admin' => [],
            'user'  => [],
        ];

        // If role not defined → deny
        if (!isset($permissions[$role])) {
            return false;
        }

        // If role has no restrictions → allow everything
        if (empty($permissions[$role])) {
            return true;
        }

        return in_array($controller, $permissions[$role]);
    }

    private function access_denied()
    {
        echo '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Access Denied</title>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: "error",
                        title: "Access Denied",
                        text: "You do not have permission to access this page.",
                        confirmButtonText: "Go Back"
                    }).then(() => {
                        window.history.back();
                    });
                });
            </script>
        </body>
        </html>';
        exit;
    }
}