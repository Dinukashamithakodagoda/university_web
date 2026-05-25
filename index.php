<?php
require_once 'includes/db.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$timeout = isset($_GET['timeout']) ? 'Your session expired. Please sign in again.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Refresh the page and try again.';
    } elseif (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');

        if ($stmt instanceof mysqli_stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result instanceof mysqli_result && ($admin = $result->fetch_assoc())) {
                $stored_password = $admin['password'];

                if (password_verify($password, $stored_password)) {
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int) $admin['id'];
                    $_SESSION['admin_name'] = $admin['username'];
                    $_SESSION['last_activity'] = time();

                    header('Location: dashboard.php');
                    exit();
                }

                if ($password === $stored_password) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare('UPDATE admins SET password = ? WHERE id = ?');

                    if ($update_stmt instanceof mysqli_stmt) {
                        $admin_id = (int) $admin['id'];
                        $update_stmt->bind_param('si', $new_hash, $admin_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }

                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int) $admin['id'];
                    $_SESSION['admin_name'] = $admin['username'];
                    $_SESSION['last_activity'] = time();

                    header('Location: dashboard.php');
                    exit();
                }

                $error = 'Incorrect password. Please try again.';
            } else {
                $error = 'Username not found. Please check your credentials.';
            }

            $stmt->close();
        } else {
            $error = 'Database query failed.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – UniPortal</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="login-page">
    <div class="login-bg-pattern"></div>

    <div class="login-card">

        <div class="login-logo">
            <div class="login-logo-icon">U</div>

            <h1>UniPortal</h1>

            <p>Student Management System</p>
        </div>

        <?php if ($timeout): ?>
            <div class="alert alert-info">
                <?= e($timeout) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="username">Username</label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button type="submit" class="btn">
                Sign In
            </button>

        </form>

        <div class="divider"></div>

        <div class="footer-text" style="margin-top:0;">
            Admin accounts are managed inside the dashboard.
        </div>

        <div class="footer-text">
            Institute of Management & Business Studies<br>
            <strong>IMBS Green Campus</strong>
        </div>

    </div>

</div>

</body>
</html>