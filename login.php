<?php
/**
 * Login Page
 * CarDeal: Web-Based Car Dealership Management System
 */

require_once __DIR__ . '/autoload.php';

use App\Services\AuthService;
use App\Config\Database;

AuthService::startSession();

// If already logged in, redirect to dashboard
if (AuthService::check()) {
    header("Location: index.php");
    exit;
}

$error = $_GET['error'] ?? null;
$usernameVal = '';

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameVal = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = AuthService::login($usernameVal, $password);
    if ($result['success']) {
        header("Location: index.php?success=" . urlencode("Welcome back, " . $result['user']->getFullName() . "!"));
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CarDeal Dealership System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/assets/css/style.css">
    <style>
        body {
            background: radial-gradient(circle at top right, #1d2c44, #0f1b2d 60%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #142236;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 36px 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), #ff6b6b);
        }
        .login-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        .login-logo-icon {
            width: 48px;
            height: 48px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 4px 14px var(--primary-glow);
        }
        .demo-credentials {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 14px;
            margin-top: 24px;
            font-size: 12px;
        }
        .demo-credentials h5 {
            color: var(--primary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        .demo-btn-group {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-logo">
        <div class="login-logo-icon">🚘</div>
        <div>
            <h2 style="font-size: 24px; font-weight: 800; line-height: 1.1;">Car<span style="color:var(--primary);">Deal</span></h2>
            <p style="font-size: 12px; color: var(--text-muted);">Dealership Management System</p>
        </div>
    </div>

    <h3 style="font-size: 18px; margin-bottom: 6px;">Staff Authorization</h3>
    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px;">
        Please sign in with your authorized dealership credentials.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="padding: 10px 14px; font-size: 13px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" 
                   value="<?= htmlspecialchars($usernameVal) ?>" required placeholder="Enter username" autofocus>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" 
                   required placeholder="Enter your password">
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 8px;">
            Sign In to System
        </button>
    </form>

    <div class="demo-credentials">
        <h5>Quick Demo Credentials:</h5>
        <div style="color: var(--text-muted); line-height: 1.6;">
            <div><strong>Administrator:</strong> admin / admin123</div>
            <div><strong>Dealership Staff:</strong> staff1 / staff123</div>
        </div>
        <div class="demo-btn-group">
            <button type="button" class="btn btn-secondary btn-sm" onclick="fillCreds('admin', 'admin123')">
                Fill Admin
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="fillCreds('staff1', 'staff123')">
                Fill Staff
            </button>
        </div>
    </div>
</div>

<script>
function fillCreds(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
}
</script>
</body>
</html>
