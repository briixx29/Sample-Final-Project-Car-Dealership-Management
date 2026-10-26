<?php
/**
 * Staff Accounts Management (Admin Only)
 * CarDeal Dealership System
 */

require_once __DIR__ . '/autoload.php';

use App\Services\AuthService;
use App\Services\UserService;

AuthService::requireAdmin();

$currentUser = AuthService::getCurrentUser();
$pageTitle = 'Staff Accounts';
$errors = [];

// ==========================================
// HANDLE ACTIONS (CREATE, UPDATE, TOGGLE STATUS)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create User
    if ($action === 'create') {
        $result = UserService::create($_POST);
        if ($result['success']) {
            header("Location: users.php?success=" . urlencode($result['message']));
            exit;
        } else {
            $errors = $result['errors'] ?? [];
            $errorMessage = $result['message'];
        }
    }

    // 2. Update User
    elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $result = UserService::update($id, $_POST);
        if ($result['success']) {
            header("Location: users.php?success=" . urlencode($result['message']));
            exit;
        } else {
            $errors = $result['errors'] ?? [];
            $errorMessage = $result['message'];
        }
    }

    // 3. Toggle Status
    elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$currentUser['id']) {
            header("Location: users.php?error=" . urlencode("You cannot deactivate your own administrative account."));
            exit;
        }

        $result = UserService::toggleStatus($id);
        if ($result['success']) {
            header("Location: users.php?success=" . urlencode($result['message']));
        } else {
            header("Location: users.php?error=" . urlencode($result['message']));
        }
        exit;
    }
}

$users = UserService::getAll();

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Staff Accounts Management</h1>
        </div>
        <div class="topbar-right">
            <button class="btn btn-primary btn-sm" data-modal-open="addUserModal">
                + Add Staff Account
            </button>
        </div>
    </div>

    <div class="content-area">
        <?php require_once __DIR__ . '/templates/alerts.php'; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Dealership System Users</h3>
                    <p style="font-size: 12px; color: var(--text-muted);">
                        Administrator role has permission to oversee all records and manage staff credentials.
                    </p>
                </div>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><strong>#<?= $u->getId() ?></strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="user-avatar" style="width: 32px; height: 32px; font-size: 12px;">
                                                <?= strtoupper(substr($u->getFullName(), 0, 1)) ?>
                                            </div>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($u->getFullName()) ?></div>
                                        </div>
                                    </td>
                                    <td><code class="code-pill"><?= htmlspecialchars($u->getUsername()) ?></code></td>
                                    <td><?= htmlspecialchars($u->getEmail() ?: '—') ?></td>
                                    <td>
                                        <span class="badge <?= $u->isAdmin() ? 'badge-dsa' : 'badge-approved' ?>">
                                            <?= htmlspecialchars(strtoupper($u->getRole())) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $u->isActive() ? 'badge-available' : 'badge-sold' ?>">
                                            <?= htmlspecialchars(ucfirst($u->getStatus())) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 12px; color: var(--text-muted);">
                                            <?= $u->getCreatedAt() ? date('M d, Y', strtotime($u->getCreatedAt())) : '—' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px;">
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                    onclick='populateEditUser(<?= json_encode($u->toArray()) ?>)'>
                                                Edit
                                            </button>
                                            <?php if ($u->getId() !== (int)$currentUser['id']): ?>
                                                <form method="POST" style="margin: 0;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?= $u->getId() ?>">
                                                    <button type="submit" class="btn <?= $u->isActive() ? 'btn-danger' : 'btn-success' ?> btn-sm">
                                                        <?= $u->isActive() ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- =======================================================
     MODAL: ADD NEW USER
     ======================================================= -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="card-title">Add Staff Account</h3>
            <button class="modal-close-btn" data-modal-close="addUserModal">&times;</button>
        </div>
        <form method="POST" action="users.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="add_u_fullname">Full Name *</label>
                    <input type="text" id="add_u_fullname" name="full_name" class="form-control" placeholder="e.g. Rachel Green" required>
                    <?php if (isset($errors['full_name'])): ?><div class="form-error"><?= $errors['full_name'] ?></div><?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_u_username">Username *</label>
                        <input type="text" id="add_u_username" name="username" class="form-control" placeholder="e.g. rachel.g" required>
                        <?php if (isset($errors['username'])): ?><div class="form-error"><?= $errors['username'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_u_password">Password *</label>
                        <input type="password" id="add_u_password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                        <?php if (isset($errors['password'])): ?><div class="form-error"><?= $errors['password'] ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_u_email">Email Address</label>
                    <input type="email" id="add_u_email" name="email" class="form-control" placeholder="rachel@cardeal.com">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_u_role">Role *</label>
                        <select id="add_u_role" name="role" class="form-select" required>
                            <option value="staff" selected>Dealership Staff</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_u_status">Status</label>
                        <select id="add_u_status" name="status" class="form-select">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close="addUserModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- =======================================================
     MODAL: EDIT USER
     ======================================================= -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="card-title">Edit User Account</h3>
            <button class="modal-close-btn" data-modal-close="editUserModal">&times;</button>
        </div>
        <form method="POST" action="users.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_user_id" name="id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_full_name">Full Name *</label>
                    <input type="text" id="edit_full_name" name="full_name" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_username">Username *</label>
                        <input type="text" id="edit_username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_password">New Password (Optional)</label>
                        <input type="password" id="edit_password" name="password" class="form-control" placeholder="Leave blank to keep current">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_email">Email Address</label>
                    <input type="email" id="edit_email" name="email" class="form-control">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="edit_role">Role *</label>
                        <select id="edit_role" name="role" class="form-select" required>
                            <option value="staff">Dealership Staff</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_user_status">Status</label>
                        <select id="edit_user_status" name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close="editUserModal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
