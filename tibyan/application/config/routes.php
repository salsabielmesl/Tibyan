<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'auth';


//tibyan_pii
// User API endpoints
$route['user/test'] = 'user/test';
$route['user/create'] = 'user/create';
$route['user/update/(:num)'] = 'user/update/$1';
$route['user/delete/(:num)'] = 'user/delete/$1';
$route['auth/register'] = 'auth/register';
$route['user/profile'] = 'user/profile';
$route['user/update']  = 'user/update';
$route['user/debug_input'] = 'user/debug_input';

$route['subscription/create'] = 'subscription/create';
$route['subscription/(:num)'] = 'subscription/index/$1';
$route['subscription'] = 'subscription/index';

$route['sessions/create'] = 'sessions/create';
$route['sessions/(:any)'] = 'sessions/index/$1';
$route['sessions'] = 'sessions/index';

// Routes for Password Reset Tokens
$route['password-resets/create'] = 'passwordresets/create';
$route['password-resets/verify/(:any)'] = 'passwordresets/verify/$1'; // Optional: for checking if a token is valid

$route['consent/create'] = 'consent/create';
$route['consent/(:num)'] = 'consent/index/$1'; // Get consent by user_id
$route['consent'] = 'consent/index';

//tibyan_analytical
// Upid Links (The Bridge)
$route['upidlinks/create']         = 'upidlinks/create';
$route['upidlinks/(:any)']         = 'upidlinks/index/$1';
$route['upidlinks']                = 'upidlinks/index';

$route['predict/report'] = 'predict/report';
$route['predict/run'] = 'predict/run';
$route['predictions/create']           = 'predictions/create';
$route['predictions/user/(:any)']      = 'predictions/get_by_user/$1';



$route['model-versions/create'] = 'modelversions/create';

$route['api/v1/model-log'] = 'ModelPerformance/log_accuracy';

$route['health/log-data'] = 'HealthLogs/create';
$route['health/logs/(:any)'] = 'HealthLogs/index/$1';
$route['health/logs']        = 'HealthLogs/index';

$route['api/v1/feature-importance'] = 'FeatureImportance/create';

// General routes come last
$route['user/(:num)'] = 'user/index/$1';
$route['user'] = 'user/index';

$route['auth/reset_password'] = 'auth/reset_password';

$route['api/metrics/add'] = 'AnalyticalTables/add_metrics';

// Routes for logged-in users (CodeIgniter 3 Compliant)
$route['profile/change-password']  = 'authController/showChangePassword';
$route['profile/update-password']  = 'authController/updatePassword';

$route['cms/update_section/(:any)'] = 'cms/update_section/$1';

$route['dbtest2'] = 'dbtest2/index';