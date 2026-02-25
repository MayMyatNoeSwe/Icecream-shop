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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 24px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 400px;
            width: 100%;
        }
        
        .logo {
            text-align: center;
            font-size: 32px;
            margin-bottom: 2px;
        }

        .logo img {
            height: 45px !important;
        }
        
        h1 {
            font-family: 'Slabo 27px', serif;
            text-align: center;
            color: #2d3748;
            margin-bottom: 2px;
            font-size: 24px;
            font-weight: 700;
        }
        
        .subtitle {
            text-align: center;
            color: rgba(102, 126, 234, 0.8);
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .form-group {
            margin-bottom: 12px;
        }
        
        label {
            display: none;
        }
        
        input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(168, 85, 247, 0.2);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: rgba(255, 255, 255, 0.8);
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .error {
            background: rgba(254, 215, 215, 0.9);
            color: #c53030;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 1px solid rgba(254, 215, 215, 1);
        }
        
        .register-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: rgba(102, 126, 234, 0.8);
            font-size: 14px;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            text-decoration: underline;
            color: #5a4fcf;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: rgba(102, 126, 234, 0.8);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #667eea;
        }
        
        .password-strength {
            font-size: 12px;
            margin-top: 6px;
            color: rgba(102, 126, 234, 0.7);
            padding-left: 2px;
        }
    </style>
</head>
<body>
    <div class="register-container">
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
                <div class="password-strength">Minimum 6 characters required</div>
            </div>
            
            <button type="submit" class="register-btn">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        
        <div class="back-link">
            <a href="index.php">← Back to Shop</a>
        </div>
    </div>
</body>
</html>
