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
    <link rel="icon" type="image/png" href="images/logo-removebg-preview.png">
    <link rel="shortcut icon" type="image/png" href="images/logo-removebg-preview.png">
    <link href="https://fonts.googleapis.com/css2?family=Boogaloo&family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&family=Slabo+27px&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="public/index.css">
    <style>
        /* Compact layout adjustments */
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.5;
        }

        .contact-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: calc(var(--nav-height) + 40px) 20px 60px;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .contact-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary-text);
            margin-bottom: 12px;
            letter-spacing: -1px;
        }

        .contact-header p {
            font-size: 0.95rem;
            color: var(--secondary-text);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }

        /* Contact Form */
        .contact-form {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            padding: 35px;
            border-radius: 24px;
        }

        .contact-form h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            color: var(--primary-text);
            margin-bottom: 25px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-text);
            font-weight: 600;
            font-size: 0.85rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
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
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            width: 100%;
            background: var(--btn-bg);
            color: var(--white);
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: var(--transition);
        }

        /* Contact Info */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            padding: 25px;
            border-radius: 20px;
            transition: var(--transition);
        }

        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(108, 93, 252, 0.1);
        }

        .info-card-icon {
            font-size: 1.8rem;
            margin-bottom: 12px;
        }

        .info-card h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.2rem;
            color: var(--primary-text);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .info-card p {
            color: var(--secondary-text);
            font-size: 0.9rem;
            line-height: 1.6;
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
            margin-top: 60px;
            padding: 40px 30px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
        }

        .social-section h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.8rem;
            color: var(--primary-text);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .social-section p {
            font-size: 0.9rem;
            color: var(--secondary-text);
            margin-bottom: 30px;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .social-link {
            width: 45px;
            height: 45px;
            background: var(--btn-bg);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .social-link:hover {
            transform: translateY(-3px);
            filter: brightness(1.2);
        }

        /* Map Section */
        .map-section {
            margin-top: 60px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        }

        .map-section iframe {
            width: 100%;
            height: 350px;
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
    <?php include 'navbar.php'; ?>
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

    <?php include 'footer.php'; ?>

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
