<?php

$password = 'admin#123'; 

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Password Hash Generator</h2>";

echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";

echo "<p><strong>Generated Hash:</strong></p>";

echo "<textarea rows='3' cols='100' readonly>" . $hash . "</textarea>";

echo "<hr>";

echo "<p><strong>SQL Query to insert this:</strong></p>";

echo "<textarea rows='3' cols='100' readonly>UPDATE `admins` SET `password` = '" . $hash . "' WHERE `email` = 'plain@admin.com';</textarea>";



?>
