<?php
session_start();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        // Here you would typically send an email or save to database
        // For now, we'll just show a success message
        $success = 'Thank you for contacting us! We\'ll get back to you soon.';
        
        // Clear form
        $name = $email = $subject = $message = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Scoops Premium Artisan Ice Cream</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-color: #f7f3ff;
            --primary-text: #2c296d;
            --accent-color: #6c5dfc;
            --secondary-text: #6b6b8d;
            --white: #ffffff;
            --btn-bg: #2c296d;
            --nav-height: 90px;
            --transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            --card-bg: rgba(255, 255, 255, 0.4);
            --card-border: rgba(255, 255, 255, 0.3);
            --nav-bg: rgba(247, 243, 255, 0.8);
        }

        [data-theme="dark"] {
            --bg-color: #0f0f1e;
            --primary-text: #f0f0f5;
            --accent-color: #a78bfa;
            --secondary-text: #c4c4d9;
            --white: #1e1e2f;
            --btn-bg: #7c3aed;
            --card-bg: rgba(30, 30, 47, 0.7);
            --card-border: rgba(167, 139, 250, 0.2);
            --nav-bg: rgba(15, 15, 30, 0.95);
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-color);
            color: var(--primary-text);
            min-height: 100vh;
            line-height: 1.6;
            transition: var(--transition);
        }

        /* Navigation */
        nav {
            height: var(--nav-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(15px);
            background: var(--nav-bg);
            border-bottom: 1px solid rgba(44, 41, 109, 0.05);
        }

        .nav-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--primary-text);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 3rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--primary-text);
            font-weight: 700;
            font-size: 0.95rem;
            opacity: 0.7;
            transition: var(--transition);
        }

        .nav-links a:hover {
            opacity: 1;
            color: var(--accent-color);
        }

        .back-btn {
            background: var(--btn-bg);
            color: var(--white);
            padding: 0.9rem 2rem;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: var(--transition);
            box-shadow: 0 10px 25px rgba(44, 41, 109, 0.2);
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(44, 41, 109, 0.3);
        }

        /* Contact Container */
        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: calc(var(--nav-height) + 60px) 20px 80px;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .contact-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 4rem;
            font-weight: 900;
            color: var(--primary-text);
            margin-bottom: 20px;
        }

        .contact-header p {
            font-size: 1.3rem;
            color: var(--secondary-text);
            max-width: 700px;
            margin: 0 auto;
        }

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-bottom: 80px;
        }

        /* Contact Form */
        .contact-form {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            padding: 50px;
            border-radius: 30px;
        }

        .contact-form h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--primary-text);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: var(--primary-text);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid var(--card-border);
            border-radius: 14px;
            font-size: 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--white);
            color: var(--primary-text);
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(108, 93, 252, 0.1);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .submit-btn {
            width: 100%;
            background: var(--btn-bg);
            color: var(--white);
            border: none;
            padding: 18px;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 10px 25px rgba(44, 41, 109, 0.2);
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(44, 41, 109, 0.3);
        }

        /* Contact Info */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .info-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            padding: 35px;
            border-radius: 24px;
            transition: var(--transition);
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(108, 93, 252, 0.15);
        }

        .info-card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .info-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--primary-text);
            margin-bottom: 10px;
        }

        .info-card p {
            color: var(--secondary-text);
            font-size: 1.05rem;
            line-height: 1.8;
        }

        .info-card a {
            color: var(--accent-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .info-card a:hover {
            text-decoration: underline;
        }

        /* Social Media */
        .social-section {
            text-align: center;
            margin-top: 80px;
            padding: 60px 40px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 30px;
        }

        .social-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-text);
            margin-bottom: 20px;
        }

        .social-section p {
            font-size: 1.1rem;
            color: var(--secondary-text);
            margin-bottom: 40px;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .social-link {
            width: 60px;
            height: 60px;
            background: var(--btn-bg);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            text-decoration: none;
            transition: var(--transition);
            box-shadow: 0 8px 20px rgba(44, 41, 109, 0.2);
        }

        .social-link:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 12px 30px rgba(44, 41, 109, 0.3);
        }

        /* Map Section */
        .map-section {
            margin-top: 80px;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }

        .map-section iframe {
            width: 100%;
            height: 450px;
            border: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .contact-header h1 {
                font-size: 2.5rem;
            }

            .contact-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .contact-form {
                padding: 30px;
            }

            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="images/logo-removebg-preview.png" alt="Scoops Logo" style="height: 55px; vertical-align: middle;">
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#flavors">Flavors</a></li>
                <li><a href="story.php">Our Story</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
            <a href="index.php" class="back-btn">← Back to Shop</a>
        </div>
    </nav>

    <!-- Contact Container -->
    <div class="contact-container">
        <!-- Header -->
        <div class="contact-header">
            <h1>Get In Touch</h1>
            <p>Have a question or want to learn more? We'd love to hear from you!</p>
        </div>

        <!-- Contact Grid -->
        <div class="contact-grid">
            <!-- Contact Form -->
            <div class="contact-form">
                <h2>Send Us a Message</h2>
                <form method="POST" id="contactForm">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($name ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required value="<?= htmlspecialchars($subject ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required><?= htmlspecialchars($message ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="contact-info">
                <div class="info-card">
                    <div class="info-card-icon">📍</div>
                    <h3>Visit Us</h3>
                    <p>123 Sweet Street<br>Ice Cream District<br>Yangon, Myanmar</p>
                </div>

                <div class="info-card">
                    <div class="info-card-icon">📞</div>
                    <h3>Call Us</h3>
                    <p>
                        Phone: <a href="tel:+959123456789">+95 9 123 456 789</a><br>
                        Open: Mon-Sun, 10AM - 10PM
                    </p>
                </div>

                <div class="info-card">
                    <div class="info-card-icon">✉️</div>
                    <h3>Email Us</h3>
                    <p>
                        General: <a href="mailto:hello@scoops.com">hello@scoops.com</a><br>
                        Support: <a href="mailto:support@scoops.com">support@scoops.com</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Social Media Section -->
        <div class="social-section">
            <h2>Follow Us</h2>
            <p>Stay connected and get the latest updates on new flavors and special offers</p>
            <div class="social-links">
                <a href="#" class="social-link" title="Facebook">📘</a>
                <a href="#" class="social-link" title="Instagram">📷</a>
                <a href="#" class="social-link" title="Twitter">🐦</a>
                <a href="#" class="social-link" title="TikTok">🎵</a>
                <a href="#" class="social-link" title="YouTube">📺</a>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3819.7267742469845!2d96.15640931484158!3d16.80528888842892!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30c1eb2c5d94c5b3%3A0x5e5e5e5e5e5e5e5e!2sYangon%2C%20Myanmar!5e0!3m2!1sen!2s!4v1234567890123!5m2!1sen!2s" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>

    <script>
        // Theme support (sync with main site)
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);

        <?php if ($success): ?>
        Swal.fire({
            title: 'Success!',
            text: '<?= addslashes($success) ?>',
            icon: 'success',
            confirmButtonColor: '#2c296d',
            background: 'var(--card-bg)',
            color: 'var(--primary-text)'
        });
        <?php endif; ?>

        <?php if ($error): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?= addslashes($error) ?>',
            icon: 'error',
            confirmButtonColor: '#2c296d',
            background: 'var(--card-bg)',
            color: 'var(--primary-text)'
        });
        <?php endif; ?>
    </script>
</body>
</html>
