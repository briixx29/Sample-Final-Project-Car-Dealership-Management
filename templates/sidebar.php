<?php
/**
 * Sidebar Navigation Template
 * CarDeal Dealership Management System
 */

use App\Services\AuthService;
use App\Services\TestDriveService;
use App\Services\InquiryService;

$currentScript = basename($_SERVER['PHP_SELF']);
$currentUser = AuthService::getCurrentUser();

// Get counts for sidebar badges
$pendingQueueSize = 0;
$pendingInquiriesCount = 0;

try {
    $pendingQueue = TestDriveService::getPendingQueue();
    $pendingQueueSize = $pendingQueue->getSize();
    
    $inqStats = InquiryService::getCountByStatus();
    $pendingInquiriesCount = (int)($inqStats['pending_count'] ?? 0);
} catch (\Exception $e) {
    // Fail silently if DB not yet loaded
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="brand-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H7.5a1 1 0 0 0-.8.4L4 11l-5.16.86a1 1 0 0 0-.84.99V16h3m10 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm-10 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/>
            </svg>
        </div>
        <div>
            <div class="brand-title">Car<span>Deal</span></div>
            <div class="brand-subtitle">Dealership System</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Core Operations</div>
        
        <a href="index.php" class="nav-item <?= $currentScript === 'index.php' ? 'active' : '' ?>">
            <span class="icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="vehicles.php" class="nav-item <?= in_array($currentScript, ['vehicles.php', 'vehicle-details.php']) ? 'active' : '' ?>">
            <span class="icon">🚘</span>
            <span>Vehicle Inventory</span>
        </a>

        <a href="inquiries.php" class="nav-item <?= $currentScript === 'inquiries.php' ? 'active' : '' ?>">
            <span class="icon">💬</span>
            <span>Customer Inquiries</span>
            <?php if ($pendingInquiriesCount > 0): ?>
                <span class="nav-badge" title="Pending Inquiries"><?= $pendingInquiriesCount ?></span>
            <?php endif; ?>
        </a>

        <a href="test-drives.php" class="nav-item <?= $currentScript === 'test-drives.php' ? 'active' : '' ?>">
            <span class="icon">⏱️</span>
            <span>Test-Drive Queue</span>
            <?php if ($pendingQueueSize > 0): ?>
                <span class="nav-badge" title="FIFO Queue Size"><?= $pendingQueueSize ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section-title">Algorithms & Verification</div>

        <a href="dsa-showcase.php" class="nav-item <?= $currentScript === 'dsa-showcase.php' ? 'active' : '' ?>">
            <span class="icon">⚡</span>
            <span>DSA Engine & Audit</span>
        </a>

        <?php if (AuthService::isAdmin()): ?>
            <div class="nav-section-title">Administration</div>
            <a href="users.php" class="nav-item <?= $currentScript === 'users.php' ? 'active' : '' ?>">
                <span class="icon">👥</span>
                <span>Staff Accounts</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($currentUser['full_name'] ?? 'Staff') ?></div>
                <div class="user-role"><?= htmlspecialchars(strtoupper($currentUser['role'] ?? 'Staff')) ?></div>
            </div>
            <a href="logout.php" title="Logout" class="btn btn-outline btn-sm" style="padding: 6px 9px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </div>
</aside>
