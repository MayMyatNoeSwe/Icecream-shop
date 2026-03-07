<?php
session_start();

$success = '';
$error = '';

// Prefill from session for logged-in users
$name    = $_SESSION['user_name']  ?? '';
$email   = $_SESSION['user_email'] ?? '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();

    // Handle Contact Form Submission
    if (isset($_POST['name']) && !isset($_POST['is_catering'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'All fields are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $subject, $message]);
                $success = 'Thank you for contacting us! We\'ll get back to you soon.';
                // Clear form
                $name = $email = $subject = $message = '';
            } catch (Exception $e) {
                $error = 'Failed to send message. Please try again later.';
            }
        }
    }

    // Handle Catering Inquiry (AJAX)
    if (isset($_POST['is_catering'])) {
        header('Content-Type: application/json');
        try {
            $type = $_POST['type'] ?? '';
            $guests = $_POST['guests'] ?? 0;
            $userId = $_SESSION['user_id'] ?? null;
            $userName = $_SESSION['user_name'] ?? 'Guest';
            $userEmail = $_SESSION['user_email'] ?? 'Not Provided';

            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Please login to send a catering inquiry.']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO catering_inquiries (user_id, name, email, event_type, guests) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $userName, $userEmail, $type, $guests]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
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
            color: white;
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

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44, 41, 109, 0.2);
            filter: brightness(1.1);
        }

        /* Catering Section */
        .catering-section {
            margin: 60px 0;
            padding: 50px;
            background: linear-gradient(135deg, var(--primary-text) 0%, #4c44a1 100%);
            border-radius: 30px;
            color: white;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        [data-theme="dark"] .catering-section {
            background: linear-gradient(135deg, #2e1065 0%, #4c1d95 100%);
        }

        .catering-section::before {
            content: '🍦';
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 10rem;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        .catering-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            margin-bottom: 15px;
            font-weight: 900;
        }

        .catering-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .catering-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: white;
            color: #2c296d;
            padding: 14px 28px;
            border-radius: 100px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .catering-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            background: var(--accent-color);
            color: white;
        }

        .catering-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .c-feature {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .c-feature i {
            font-size: 1.5rem;
            margin-bottom: 10px;
            display: block;
        }

        .c-feature span {
            font-weight: 700;
            font-size: 0.9rem;
        }

        /* Direct Messaging */
        .dm-links {
            display: flex;
            gap: 12px;
            margin-top: 15px;
        }

        .dm-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .dm-btn.whatsapp {
            background: rgba(37, 211, 102, 0.1);
            color: #25d366;
            border: 1px solid rgba(37, 211, 102, 0.2);
        }

        .dm-btn.whatsapp:hover {
            background: #25d366;
            color: white;
        }

        .dm-btn.messenger {
            background: rgba(0, 132, 255, 0.1);
            color: #0084ff;
            border: 1px solid rgba(0, 132, 255, 0.2);
        }

        .dm-btn.messenger:hover {
            background: #0084ff;
            color: white;
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
            @media (max-width: 900px) {
                .catering-section {
                    grid-template-columns: 1fr;
                    padding: 35px;
                    text-align: center;
                }
                .catering-content h2 { font-size: 1.8rem; }
            }

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
                    <h3>Contact Directly</h3>
                    <p style="margin-bottom: 10px;">For instant support, message us on your favorite platform:</p>
                    <div class="dm-links">
                        <a href="https://wa.me/959123456789" class="dm-btn whatsapp" target="_blank">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                        <a href="https://m.me/scoopscreamery" class="dm-btn messenger" target="_blank">
                            <i class="bi bi-messenger"></i> Messenger
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catering Section -->
        <div class="catering-section">
            <div class="catering-content">
                <h2>Host Your Event with Scoops</h2>
                <p>From luxury weddings to corporate celebrations, we bring the premium Scoops experience to your venue with our artisan catering cart.</p>
                <a href="#" class="catering-btn" onclick="openCateringModal(); return false;">
                    Plan My Event <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="catering-features">
                <div class="c-feature">
                    <i class="bi bi-calendar-check-fill"></i>
                    <span>Full Service Cart</span>
                </div>
                <div class="c-feature">
                    <i class="bi bi-stars"></i>
                    <span>Custom Flavors</span>
                </div>
                <div class="c-feature">
                    <i class="bi bi-people-fill"></i>
                    <span>Artisan Servers</span>
                </div>
                <div class="c-feature">
                    <i class="bi bi-balloon-heart-fill"></i>
                    <span>Party Packages</span>
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
        function openCateringModal() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            Swal.fire({
                title: '<div style="font-family:\'Playfair Display\', serif; font-size: 2rem; font-weight: 900; margin-bottom: 5px;">Catering Inquiry</div>',
                html: `
                    <div style="text-align: left; padding: 10px 5px;">
                        <p style="font-family:\'Plus Jakarta Sans\', sans-serif; font-size: 0.95rem; color: var(--secondary-text); margin-bottom: 25px; line-height: 1.5;">
                            Tell us about your event and we\'ll create a custom artisan package just for you.
                        </p>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-family:\'Plus Jakarta Sans\', sans-serif; font-weight: 800; font-size: 0.75rem; letter-spacing: 1px; color: var(--primary-text); margin-bottom: 10px; text-transform: uppercase;">Event Type</label>
                            <select id="swalEventType" style="width:100%; padding: 14px; border-radius: 12px; border: 1px solid ${isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)'}; background: ${isDark ? '#2a2a3d' : '#f8f9fa'}; color: var(--primary-text); font-family:\'Plus Jakarta Sans\', sans-serif; font-size: 0.95rem; outline: none; appearance: none; background-image: url(\'data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22currentColor%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C/polyline%3E%3C/svg%3E\'); background-repeat: no-repeat; background-position: right 14px center; background-size: 16px;">
                                <option>Wedding Reception</option>
                                <option>Corporate Gala</option>
                                <option>Birthday Celebration</option>
                                <option>Artisanal Workshop</option>
                                <option>Other Special Event</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; font-family:\'Plus Jakarta Sans\', sans-serif; font-weight: 800; font-size: 0.75rem; letter-spacing: 1px; color: var(--primary-text); margin-bottom: 10px; text-transform: uppercase;">Expected Guests</label>
                            <input type="number" id="swalGuests" placeholder="e.g. 50" style="width:100%; padding: 14px; border-radius: 12px; border: 1px solid ${isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)'}; background: ${isDark ? '#2a2a3d' : '#f8f9fa'}; color: var(--primary-text); font-family:\'Plus Jakarta Sans\', sans-serif; font-size: 0.95rem; outline: none;">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Send Inquiry',
                cancelButtonText: 'Maybe Later',
                confirmButtonColor: '#6c5dfc',
                cancelButtonColor: 'transparent',
                background: isDark ? '#1e1e2f' : '#ffffff',
                color: isDark ? '#f0f0f5' : '#2c296d',
                width: '480px',
                padding: '2.5rem',
                customClass: {
                    popup: 'premium-swal-popup',
                    confirmButton: 'premium-confirm-btn',
                    cancelButton: 'premium-cancel-btn'
                },
                didOpen: () => {
                   const container = Swal.getPopup();
                   container.style.borderRadius = '32px';
                   const cancelBtn = container.querySelector('.premium-cancel-btn');
                   if (cancelBtn) {
                       cancelBtn.style.color = isDark ? '#c4c4d9' : '#6b6b8d';
                       cancelBtn.style.fontWeight = '700';
                       cancelBtn.style.fontSize = '0.9rem';
                   }
                },
                preConfirm: () => {
                    const type = document.getElementById('swalEventType').value;
                    const guests = document.getElementById('swalGuests').value;
                    if (!guests) {
                        Swal.showValidationMessage('Please entering estimated guests count');
                        return false;
                    }
                    
                    // Send AJAX request
                    const formData = new FormData();
                    formData.append('is_catering', '1');
                    formData.append('type', type);
                    formData.append('guests', guests);

                    return fetch('contact.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to send inquiry');
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Error: ${error.message}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Inquiry Sent!',
                        text: 'Our events coordinator will contact you shortly.',
                        icon: 'success',
                        confirmButtonColor: '#6c5dfc',
                        background: isDark ? '#1e1e2f' : '#ffffff',
                        color: isDark ? '#f0f0f5' : '#2c296d'
                    });
                }
            });
        }

        const isDarkTheme = document.documentElement.getAttribute('data-theme') === 'dark';

        <?php if ($success): ?>
        Swal.fire({
            title: '<div style="font-family:\'Playfair Display\', serif; font-size: 2.2rem; font-weight: 900; margin-bottom: 5px;">Thank You!</div>',
            html: `
                <div style="font-family:\'Plus Jakarta Sans\', sans-serif; font-size: 1.05rem; color: var(--secondary-text); line-height: 1.6; padding: 0 10px;">
                    <?= addslashes($success) ?>
                </div>
            `,
            icon: 'success',
            iconColor: '#6c5dfc',
            confirmButtonText: 'Great',
            confirmButtonColor: '#6c5dfc',
            background: isDarkTheme ? '#1e1e2f' : '#ffffff',
            color: isDarkTheme ? '#f0f0f5' : '#2c296d',
            width: '450px',
            padding: '2.5rem',
            didOpen: () => {
                const container = Swal.getPopup();
                container.style.borderRadius = '32px';
                const confirmBtn = container.querySelector('.swal2-confirm');
                if (confirmBtn) {
                    confirmBtn.style.padding = '12px 40px';
                    confirmBtn.style.borderRadius = '14px';
                    confirmBtn.style.fontWeight = '800';
                    confirmBtn.style.fontSize = '0.95rem';
                    confirmBtn.style.textTransform = 'uppercase';
                    confirmBtn.style.letterSpacing = '1px';
                }
            }
        });
        <?php endif; ?>

        <?php if ($error): ?>
        Swal.fire({
            title: '<div style="font-family:\'Playfair Display\', serif; font-size: 2rem; font-weight: 900; margin-bottom: 5px;">Oops...</div>',
            html: `
                <div style="font-family:\'Plus Jakarta Sans\', sans-serif; font-size: 1rem; color: var(--secondary-text); line-height: 1.6;">
                    <?= addslashes($error) ?>
                </div>
            `,
            icon: 'error',
            iconColor: '#ef4444',
            confirmButtonText: 'Try Again',
            confirmButtonColor: '#6c5dfc',
            background: isDarkTheme ? '#1e1e2f' : '#ffffff',
            color: isDarkTheme ? '#f0f0f5' : '#2c296d',
            width: '420px',
            padding: '2rem',
            didOpen: () => {
                const container = Swal.getPopup();
                container.style.borderRadius = '32px';
                const confirmBtn = container.querySelector('.swal2-confirm');
                if (confirmBtn) {
                    confirmBtn.style.borderRadius = '14px';
                    confirmBtn.style.fontWeight = '700';
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
