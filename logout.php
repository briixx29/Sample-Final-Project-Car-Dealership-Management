<?php
/**
 * Logout Handler
 * CarDeal Dealership Management System
 */

require_once __DIR__ . '/autoload.php';

use App\Services\AuthService;

AuthService::logout();
header("Location: login.php?info=" . urlencode("You have been signed out successfully."));
exit;
