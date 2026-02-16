<?php
session_start();
require_once '../config/database.php';

// If already logged in as admin, redirect to admin panel
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Admin credentials (you can store this in database later)
    // Default admin: username = admin, password = admin123
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_name'] = 'Administrator';
        $_SESSION['is_admin'] = true;
        
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Ice Cream Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #7c3aed 50%, #ec4899 100%);
            min-height: 100vh;
            padding: 20px 20px 50px 20px;
            margin: 0;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px 30px;
            border-radius: 28px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
            max-width: 480px;
            width: 100%;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin: 20px auto;
        }
        
        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #7c3aed 0%, #ec4899 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        
        .logo {
            text-align: center;
            font-size: 56px;
            margin-bottom: 15px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        h1 {
            font-family: 'Playfair Display', serif;
            text-align: center;
            color: #1e293b;
            margin-bottom: 10px;
            font-size: 36px;
            font-weight: 700;
        }
        
        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 40px;
            font-size: 15px;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 28px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: #1e293b;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            background: white;
        }
        
        input:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
            transform: translateY(-2px);
        }
        
        .error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #fca5a5;
        }
        
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #7c3aed 0%, #ec4899 100%);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.4);
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(124, 58, 237, 0.5);
        }
        
        .login-btn:active {
            transform: translateY(-1px);
        }
        
        .demo-info {
            margin-top: 35px;
            padding: 24px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0e7ff 100%);
            border-radius: 14px;
            border-left: 4px solid #7c3aed;
        }
        
        .demo-info h3 {
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .demo-info p {
            font-size: 14px;
            color: #475569;
            margin: 8px 0;
            font-weight: 500;
        }
        
        .demo-info strong {
            color: #1e293b;
            font-weight: 700;
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-link a {
            color: #7c3aed;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: #6d28d9;
            text-decoration: underline;
        }
        
        .security-note {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background: rgba(124, 58, 237, 0.1);
            border-radius: 10px;
            font-size: 13px;
            color: #64748b;
        }
        
        .security-note strong {
            color: #7c3aed;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div style="text-align: center;">
            <span class="admin-badge">🔐 Admin Access</span>
        </div>
        <div class="logo">👨‍💼</div>
        <h1>Admin Panel</h1>
        <p class="subtitle">Secure Administrator Login</p>
        
        <?php if ($error): ?>
            <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">👤 Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter admin username" autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">🔑 Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter admin password" autocomplete="current-password">
            </div>
            
            <button type="submit" class="login-btn">🚀 Login to Dashboard</button>
        </form>
        
        <!-- <div class="demo-info">
            <h3>🔐 Demo Admin Credentials</h3>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> admin123</p>
        </div> -->
        
        <div class="security-note">
            <strong>🛡️ Security Notice:</strong> This is an admin-only area. Unauthorized access is prohibited.
        </div>
        
        <div class="back-link">
            <a href="../index.php">← Back to Shop</a>
        </div>
    </div>
</body>
</html>
