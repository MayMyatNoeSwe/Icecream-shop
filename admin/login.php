<?php
session_name('SCOOPS_ADMIN_SESSION');
session_start();
require_once '../config/database.php';

// If already logged in as admin via the dedicated admin session, redirect to dashboard
if (isset($_SESSION['is_admin']) && isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

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
                $error = 'Invalid credentials.';
            } elseif (!password_verify($password, $user['password'])) {
                $error = 'Invalid credentials.';
            } elseif ($user['role'] !== 'admin') {
                $error = "Access denied. Administrative privileges required.";
            } else {
                // Clear all existing session data
                $_SESSION = array();
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_success'] = true;

                // Admin specific session flags
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_username'] = explode('@', $user['email'])[0];
                $_SESSION['admin_name'] = $user['name'];
                
                header('Location: index.php');
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
    <title>Admin Login - Scoops Ice Cream</title>
    <link href="https://fonts.googleapis.com/css2?family=Slabo+27px&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --admin-primary: #1e293b;
            --admin-secondary: #334155;
            --accent: #6c5dfc;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --admin-bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--admin-bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 32px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.5);
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
            border-color: var(--admin-primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(30, 41, 59, 0.1);
        }
        
        .login-btn {
            width: 100%;
            background: var(--admin-primary);
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
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            background: #0f172a;
            box-shadow: 0 15px 30px rgba(15, 23, 42, 0.4);
        }
        
        .links-container {
            margin-top: 30px;
            text-align: center;
            border-top: 1px solid #eef2f7;
            padding-top: 25px;
        }

        .links-container a { color: var(--text-light); text-decoration: none; font-weight: 700; transition: all 0.3s; font-size: 14px; }
        .links-container a:hover { color: var(--admin-primary); text-decoration: underline; }

        .admin-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #fee2e2;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 800;
            color: #ef4444;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="admin-badge">🔐 Restricted</div>
        <div class="logo">
            <img src="../images/logo-removebg-preview.png" alt="Scoops Logo">
        </div>
        <h1>Staff Portal</h1>
        <p class="subtitle">Secure Login for Administrators</p>
        
        <?php if ($error): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: '<?= htmlspecialchars($error) ?>',
                    background: '#1e293b',
                    color: '#f0f0f5',
                    confirmButtonColor: '#334155',
                    confirmButtonText: 'Try Again'
                });
            </script>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Admin Email</label>
                <div class="input-wrapper">
                    <i class="bi bi-person-badge input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="admin@scoops.com" value="<?= htmlspecialchars($email) ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrapper">
                    <i class="bi bi-shield-lock input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="bi bi-speedometer2"></i> Access Dashboard
            </button>
        </form>
        
        <div class="links-container">
            <a href="../index.php"><i class="bi bi-arrow-left"></i> Back to Public Shop</a>
        </div>
    </div>
</body>
</html>
