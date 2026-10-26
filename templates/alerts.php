<?php
/**
 * Alerts & Flash Messages Component
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$successMsg = $_SESSION['flash_success'] ?? $_GET['success'] ?? null;
$errorMsg = $_SESSION['flash_error'] ?? $_GET['error'] ?? null;
$infoMsg = $_SESSION['flash_info'] ?? $_GET['info'] ?? null;
$warningMsg = $_SESSION['flash_warning'] ?? $_GET['warning'] ?? null;

unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['flash_info'], $_SESSION['flash_warning']);
?>

<?php if ($successMsg): ?>
    <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <div><?= htmlspecialchars($successMsg) ?></div>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="alert alert-danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
        <div><?= htmlspecialchars($errorMsg) ?></div>
    </div>
<?php endif; ?>

<?php if ($infoMsg): ?>
    <div class="alert alert-info">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <div><?= htmlspecialchars($infoMsg) ?></div>
    </div>
<?php endif; ?>

<?php if ($warningMsg): ?>
    <div class="alert alert-warning">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        <div><?= htmlspecialchars($warningMsg) ?></div>
    </div>
<?php endif; ?>
