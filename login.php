<?php
session_start();
require_once 'config/database.php';

// If already logged in, redirect to shop
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Check if user was redirected from registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = 'Registration successful! Please login with your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Email not found. Please check your email or register.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Incorrect password. Please try again.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['login_success'] = true;
            
            $redirect = $_GET['redirect'] ?? 'index.php';
            header('Location: ' . $redirect);
            exit;
        }
    } catch (Exception $e) {
        $error = 'An error occurred. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ice Cream Shop</title>
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
        
        .login-container {
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
        
        .success {
            background: rgba(198, 246, 213, 0.9);
            color: #22543d;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border: 1px solid rgba(198, 246, 213, 1);
        }
        
        .login-btn {
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
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-link a {
            color: rgba(102, 126, 234, 0.8);
            text-decoration: none !important;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #667eea;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: rgba(102, 126, 234, 0.8);
            font-size: 14px;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none !important;
            font-weight: 600;
            
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="images/logo-removebg-preview.png" alt="Scoops Logo" style="height: 60px;">
        </div>
        <h1>Welcome Back</h1>
        <p class="subtitle">Customer Login - Start shopping for delicious ice cream</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" autocomplete="off">
            <!-- Dummy inputs to catch browser auto-fill -->
            <input type="text" style="display:none">
            <input type="password" style="display:none">
            
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder="Email Address" autocomplete="new-password" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Password" autocomplete="new-password">
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <div class="login-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        
        <div class="back-link">
            <a href="index.php">← Back to Shop</a>
        </div>
    </div>
</body>
</html>