<?php
require_once 'includes/db.php';

$admin_count = (int) ($conn->query('SELECT COUNT(*) AS c FROM admins')->fetch_assoc()['c'] ?? 0);
$allow_public_access = $admin_count === 0;

if (!is_logged_in() && !$allow_public_access) {
    header('Location: index.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Refresh the page and try again.';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'Admin';

        if ($full_name !== '' && $username !== '' && $email !== '' && $password !== '') {
            if (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO admins (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)');

                if ($stmt instanceof mysqli_stmt) {
                    $stmt->bind_param('sssss', $full_name, $username, $email, $password_hash, $role);
                    if ($stmt->execute()) {
                        $success = 'Admin account created successfully.';
                        $_POST = [];
                    } else {
                        if ($conn->errno === 1062) {
                            $error = 'That username or email already exists.';
                        } else {
                            $error = 'Database error: ' . e($conn->error);
                        }
                    }
                    $stmt->close();
                } else {
                    $error = 'Database query failed.';
                }
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
}

$page_title = 'Admin Registration';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-bg-pattern"></div>
    <div class="login-card" style="max-width:520px;">
        <div class="login-logo">
            <div class="login-logo-icon">A</div>
            <h1>Admin Registration</h1>
            <p>Create a new administrator account</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-grid single">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="Admin" <?= (($_POST['role'] ?? '') === 'Admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="Super Admin" <?= (($_POST['role'] ?? '') === 'Super Admin') ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:1.5rem; display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                <?php if (is_logged_in()): ?>
                    <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-outline">Back to Login</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-gold">Create Admin</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
