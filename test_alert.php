<?php
session_start();
$_SESSION['login_success'] = true;
$_SESSION['user_name'] = 'Test User';
header('Location: index.php');
exit;
?>
