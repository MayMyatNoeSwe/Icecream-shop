<?php
session_start();
require_once 'config/database.php';

// If already logged in with the same role, redirect appropriately
if (isset($_SESSION['user_id'])) {
    $currentRole = $_SESSION['user_role'];
    $requestedRole = $_GET['role'] ?? 'customer';
    
    // Only redirect if we are already logged in as the requested role
    if ($currentRole === $requestedRole) {
        $redirect = $_GET['redirect'] ?? 'index.php';
        header('Location: ' . $redirect);
        exit;
    }
    // If roles differ, we stay on login.php to allow switching (logging into the new role will overwrite session)
}

$error = '';
$success = '';
$active_role = 'customer';

if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = 'Registration successful! Please login with your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Both email and password are required.';
    } else {
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
                // Clear all existing session data to prevent role-bleeding
                $_SESSION = array();
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_success'] = true;

                if ($user['role'] === 'admin') {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['is_admin'] = true;
                    $_SESSION['admin_username'] = explode('@', $user['email'])[0];
                    $_SESSION['admin_name'] = $user['name'];
                    
                    $redirect = $_GET['redirect'] ?? 'admin/index.php';
                    header('Location: ' . $redirect);
                } else {
                    $redirect = $_GET['redirect'] ?? 'index.php';
                    header('Location: ' . $redirect);
                }
                exit;
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Scoops Ice Cream</title>
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
            transition: background 0.5s ease;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 32px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 450px;
            width: 100%;
            position: relative;
            overflow: hidden;
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
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        
        .error, .success {
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 14px;
            text-align: center;
            font-weight: 600;
            animation: shake 0.5s ease;
        }

        .error {
            background: #fff1f2;
            color: #e11d48;
            border: 1px solid #fecdd3;
        }

        .success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .login-btn {
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
        
        .login-btn:hover {
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
    <div class="login-container">
        <div class="logo">
            <img src="images/logo-removebg-preview.png" alt="Scoops Logo">
        </div>
        <h1>Welcome Back</h1>
        <p class="subtitle" id="login-subtitle">
            Start shopping for delicious treats
        </p>
        
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
        
        <?php if ($success): ?>
            <div class="success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($email ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrapper">
                    <i class="bi bi-shield-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••">
                </div>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="bi bi-box-arrow-in-right"></i> <span id="btn-text">Sign In</span>
            </button>
        </form>
        
        <div class="links-container" id="extra-links">
            <p>New to Scoops? <a href="register.php">Create an Account</a></p>
            <a href="index.php"><i class="bi bi-arrow-left"></i> Back to Shop</a>
        </div>
    </div>
</body>
</html>