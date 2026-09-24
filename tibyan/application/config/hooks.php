<?php
defined('BASEPATH') or exit('No direct script access allowed');

// CORS must run first - before any output
$hook['pre_controller'][] = array(
    'class'    => 'Cors',
    'function' => 'set_cors_headers',
    'filename' => 'Cors.php',
    'filepath' => 'hooks'
);

// Permission check runs after controller is constructed
$hook['post_controller_constructor'][] = array(
    'class'    => 'PermissionCheck',
    'function' => 'check_permission',
    'filename' => 'PermissionCheck.php',
    'filepath' => 'hooks'
);