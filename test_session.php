<?php
session_start();

echo "<h1>Session Debug</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['login_success'])) {
    echo "<p style='color: green;'>✓ login_success is SET</p>";
} else {
    echo "<p style='color: red;'>✗ login_success is NOT SET</p>";
}

if (isset($_SESSION['user_name'])) {
    echo "<p style='color: green;'>✓ user_name: " . htmlspecialchars($_SESSION['user_name']) . "</p>";
} else {
    echo "<p style='color: red;'>✗ user_name is NOT SET</p>";
}

echo "<br><a href='index.php'>Go to Index</a>";
echo "<br><a href='login.php'>Go to Login</a>";
?>
