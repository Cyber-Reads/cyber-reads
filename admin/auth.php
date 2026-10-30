<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require '../src/config/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!empty($_SESSION['csrf_token'])) {
        $submitted_token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
        if ($submitted_token !== '' && !hash_equals($_SESSION['csrf_token'], $submitted_token)) {
            header("Location: admin.php?error=1");
            exit();
        }
    }

    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: admin.php?error=1");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, email, password FROM admins WHERE email = ? LIMIT 1");
    if (!$stmt) {
        error_log('Prepare failed in admin/auth.php: ' . $conn->error);
        header("Location: admin.php?error=1");
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $hashed_password = $user['password'];

        if (password_verify($password, $hashed_password)) {
            session_regenerate_id(true);
            $_SESSION["admin_loggedin"] = true;
            $_SESSION["admin_id"] = (int)$user['id'];
            $_SESSION["admin_email"] = $user['email'];
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

            $stmt->close();
            $conn->close();
            header("Location: dashboard.php");
            exit();
        }
    }

    $stmt->close();
    $conn->close();
    header("Location: admin.php?error=1");
    exit();
} else {
    header("Location: admin.php");
    exit();
}
?>
