<?php
session_start();
$isCli = (php_sapi_name() === 'cli');
$isAdmin = (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true);

if (!$isCli && !$isAdmin) {
    http_response_code(403);
    echo "Access denied.";
    exit();
}

$password = 'admin#123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Password Hash Generator</h2>";
echo "<p><strong>Generated Hash:</strong></p>";
echo "<textarea rows='3' cols='80' readonly>" . htmlspecialchars($hash, ENT_QUOTES, 'UTF-8') . "</textarea>";
?>
