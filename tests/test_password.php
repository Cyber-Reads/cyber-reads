<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Access denied.');
}

$database_hash = '$2y$10$3P.p8.P/gG4U2Y5yL4aJc.flBSM2s5o5t3H8E7i.mY.OLCWLEvB.O';
$password_to_check = 'admin123';

echo "Checking password against sample hash...\n";
if (password_verify($password_to_check, $database_hash)) {
    echo "SUCCESS: The password matches the hash.\n";
} else {
    echo "NOTICE: Sample password does not match sample hash.\n";
}
?>
