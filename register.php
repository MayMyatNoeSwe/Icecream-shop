<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match. Please reconfirm.';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                $userId = bin2hex(random_bytes(16));
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (id, name, email, password, role) VALUES (?, ?, ?, ?, 'customer')");
                $stmt->execute([$userId, $name, $email, $hashed]);
                header('Location: login.php?registered=1');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Ice Cream Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Slabo+27px&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <img src="images/logo-removebg-preview.png" alt="Scoops Logo" style="height: 60px;">
        </div>
        <h1>Create Account</h1>
        <p class="subtitle">Join us and start shopping</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" autocomplete="off">
            <!-- Dummy inputs to catch browser auto-fill -->
            <input type="text" style="display:none">
            <input type="password" style="display:none">

            <div class="form-group">
                <input type="text" id="name" name="name" placeholder="Full Name" autocomplete="new-password" value="<?= htmlspecialchars($name ?? '') ?>">
            </div>
            
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder="Email Address" autocomplete="new-password" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Password" autocomplete="new-password">
            </div>

            <div class="form-group">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" autocomplete="new-password">
                <div class="password-strength" style="font-size: 12px; margin-top: 6px; color: rgba(102, 126, 234, 0.7); padding-left: 2px;">Minimum 6 characters required</div>
            </div>
            
            <button type="submit" class="auth-btn">Create Account</button>
        </form>
        
        <div class="auth-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        
        <div class="back-link">
            <a href="index.php">← Back to Shop</a>
        </div>
    </div>
</body>
</html>
