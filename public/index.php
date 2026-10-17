<?php

use App\Admission\AdmissionHttp;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Bound dynamic work before Composer, database sessions and application queries.
require_once __DIR__.'/../app/Admission/FileAdmissionGate.php';
require_once __DIR__.'/../app/Admission/AdmissionHttp.php';
AdmissionHttp::enter();

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
