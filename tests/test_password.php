
<?php
// ඔබගේ දත්ත සමුදායේ ඇති hash එක (ඔබගේ screenshot එකෙන් ලබාගත්තේ)
$database_hash = '$2y$10$3P.p8.P/gG4U2Y5yL4aJc.flBSM2s5o5t3H8E7i.mY.OLCWLEvB.O';

// අපි පරීක්ෂා කරන මුරපදය
$password_to_check = 'admin123';

echo "Checking password against the hash...<br><br>";
echo "Password to check: " . $password_to_check . "<br>";
echo "Hash from database: " . $database_hash . "<br><br>";

// මුරපදය සහ hash එක සසඳා බැලීම
if (password_verify($password_to_check, $database_hash)) {
    echo '<h1 style="color: green;">SUCCESS: The password matches the hash!</h1>';
    echo 'The problem is likely a typo when you are logging in. Please try typing the password very carefully again.';
} else {
    echo '<h1 style="color: red;">FAILURE: The password does NOT match the hash!</h1>';
    echo 'This means the hash in your database is corrupted or incorrect. Please run the UPDATE SQL query again.';
}
?>

