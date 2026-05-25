<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800);
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return !empty($_SESSION['admin_id']);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token): bool
    {
        return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('destroy_session')) {
    function destroy_session(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}

if (!function_exists('course_options')) {
    function course_options(mysqli $conn): array
    {
        $options = [];
        $result = $conn->query('SELECT id, course_name FROM courses WHERE is_active = 1 ORDER BY course_name ASC');

        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $options[] = $row;
            }
        }

        return $options;
    }
}

if (!function_exists('table_exists')) {
    function table_exists(mysqli $conn, string $table): bool
    {
        $table = $conn->real_escape_string($table);
        $result = $conn->query("SHOW TABLES LIKE '$table'");

        return $result instanceof mysqli_result && $result->num_rows > 0;
    }
}

if (!function_exists('generate_student_id')) {
    function generate_student_id(mysqli $conn): string
    {
        $prefix = 'STU';
        $year = date('Y');
        $base = $prefix . $year;
        $result = $conn->query("SELECT student_id FROM students WHERE student_id LIKE '" . $conn->real_escape_string($base) . "%' ORDER BY id DESC LIMIT 1");
        $next = 1;

        if ($result instanceof mysqli_result && ($row = $result->fetch_assoc())) {
            $next = ((int) substr($row['student_id'], -4)) + 1;
        }

        return sprintf('%s%04d', $base, $next);
    }
}

if (!function_exists('student_photo_url')) {
    function student_photo_url(?string $path): string
    {
        if (!$path) {
            return 'https://via.placeholder.com/120x120.png?text=Student';
        }

        return $path;
    }
}

if (!empty($_SESSION['last_activity']) && is_logged_in() && (time() - (int) $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    destroy_session();
    header('Location: index.php?timeout=1');
    exit();
}

if (is_logged_in()) {
    $_SESSION['last_activity'] = time();
}

if (empty($_SESSION['csrf_token'])) {
    csrf_token();
}
