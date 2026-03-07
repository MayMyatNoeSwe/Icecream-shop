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
    <title>Register - Scoops Ice Cream</title>
    <link href="https://fonts.googleapis.com/css2?family=Slabo+27px&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 32px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 450px;
            width: 100%;
            position: relative;
        }

        .logo {
            text-align: center;
            margin-bottom: 5px;
        }

        .logo img { height: 50px; }
        
        h1 {
            font-family: 'Slabo 27px', serif;
            text-align: center;
            color: var(--text-dark);
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .subtitle {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: none;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
        }
        
        input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #eef2f7;
            border-radius: 14px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            color: var(--text-dark);
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .password-hint {
            font-size: 12px;
            margin-top: 8px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .auth-btn {
            width: 100%;
            background: var(--bg-gradient);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3);
        }
        
        .auth-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 15px 30px rgba(118, 75, 162, 0.4);
        }

        .links-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
            text-align: center;
            border-top: 1px solid #eef2f7;
            padding-top: 25px;
        }

        .links-container p { font-size: 14px; color: var(--text-light); }
        .links-container a { color: var(--primary); text-decoration: none; font-weight: 700; transition: all 0.3s; }
        .links-container a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <img src="images/logo-removebg-preview.png" alt="Scoops Logo">
        </div>
        <h1>Create Account</h1>
        <p class="subtitle">Join us and start shopping for delicious treats</p>
        
        <?php if ($error): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?= htmlspecialchars($error) ?>',
                    background: '#ffffff',
                    color: '#1e293b',
                    confirmButtonColor: '#667eea',
                    confirmButtonText: 'Got it',
                    showClass: {
                        popup: 'animate__animated animate__shakeX'
                    }
                });
            </script>
        <?php endif; ?>
        
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <div class="input-wrapper">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" id="name" name="name" placeholder="Full Name" value="<?= htmlspecialchars($name ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="Email Address" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-wrapper">
                    <i class="bi bi-shield-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Password" minlength="6" required>
                </div>
            </div>

            <div class="form-group">
                <div class="input-wrapper">
                    <i class="bi bi-shield-check input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" minlength="6" required>
                </div>
                <div class="password-hint">
                    <i class="bi bi-info-circle"></i> Minimum 6 characters required
                </div>
            </div>
            
            <button type="submit" class="auth-btn">
                <i class="bi bi-person-plus"></i> Create Account
            </button>
        </form>
        
        <div class="links-container">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <a href="index.php"><i class="bi bi-arrow-left"></i> Back to Shop</a>
        </div>
    </div>
</body>
</html>
