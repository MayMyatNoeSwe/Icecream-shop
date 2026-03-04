<!-- Footer Section -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="footer-logo">
                    <span class="logo-icon">🍦</span>
                    <span class="logo-text">Scoops</span>
                    <p class="tagline">Crafting Sweet Memories Since 2015</p>
                </div>
                <p class="brand-description">
                    At Scoops Creamery, we're dedicated to the art of traditional ice cream making. 
                    Using only the purest ingredients and time-honored techniques, we create flavors 
                    that inspire joy in every spoonful.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link facebook" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3V2z" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                    </a>
                    <a href="#" class="social-link instagram" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" width="20" height="20"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="0.5" fill="currentColor"/></svg>
                    </a>
                    <a href="#" class="social-link twitter" aria-label="Twitter">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                    </a>
                </div>
            </div>
            
            <div class="footer-nav">
                <h4>Quick Menu</h4>
                <ul>
                    <li><a href="index.php">Home Gallery</a></li>
                    <li><a href="index.php#flavors">Signature Flavors</a></li>
                    <li><a href="story.php">Our Story</a></li>
                    <li><a href="reviews.php">Verified Reviews</a></li>
                    <li><a href="contact.php">Store Locator</a></li>
                </ul>
            </div>
            
            <div class="footer-nav">
                <h4>Customer Help</h4>
                <ul>
                    <li><a href="orders.php">Track Order</a></li>
                    <li><a href="cart.php">Shopping Cart</a></li>
                    <li><a href="#">Artisan FAQ</a></li>
                    <li><a href="#">Dine-in Policy</a></li>
                    <li><a href="login.php">Member Login</a></li>
                </ul>
            </div>
            
            <div class="footer-nav contact-nav">
                <h4>Talk to Us</h4>
                <div class="contact-item">
                    <span class="contact-icon">📍</span>
                    <p>123 Artisan Way, Bahan Township,<br>Yangon, Myanmar</p>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">📞</span>
                    <p><a href="tel:+959123456789" style="color: inherit; text-decoration: none;">+95 9 123 456 789</a></p>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">✉️</span>
                    <p><a href="mailto:hello@scoopscreamery.com" style="color: inherit; text-decoration: none;">hello@scoopscreamery.com</a></p>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">🕙</span>
                    <p>Open Daily: 10AM - 11PM</p>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-info">
                <p>&copy; <?= date('Y') ?> Scoops Creamery. All rights reserved.</p>
                <div class="bottom-links">
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Cookies</a>
                </div>
            </div>
            <div class="footer-extra">
                <div class="payment-methods">
                    <span title="Visa">💳</span>
                    <span title="Mastercard">🎴</span>
                    <span title="KBZPay">📱</span>
                    <span title="WavePay">🌊</span>
                </div>
                <div class="scroll-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
                    <span>↑</span>
                </div>
            </div>
        </div>
    </div>

    <style>
        .footer {
            background: var(--nav-bg);
            backdrop-filter: blur(25px);
            border-top: 1px solid var(--card-border);
            padding: 4rem 0 0;
            color: var(--primary-text);
            position: relative;
            z-index: 100;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: radial-gradient(circle at 90% 10%, rgba(108, 93, 252, 0.05), transparent 40%),
                        radial-gradient(circle at 10% 90%, rgba(108, 93, 252, 0.05), transparent 40%);
            pointer-events: none;
        }

        .container {
            max-width: 1250px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Footer Grid */
        .footer-grid {
            display: grid;
            grid-template-columns: 2.5fr 1fr 1fr 1.8fr;
            gap: 4.5rem;
            padding-bottom: 5rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2.5rem;
        }

        .logo-icon {
            font-size: 2.8rem;
        }

        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 900;
            color: var(--primary-text);
            letter-spacing: -0.01em;
        }

        .tagline {
            width: 100%;
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 4px;
            color: var(--accent-color);
            opacity: 0.8;
            margin-top: -0.5rem;
        }

        .brand-description {
            color: var(--secondary-text);
            margin-bottom: 2.8rem;
            font-size: 1.1rem;
            line-height: 1.85;
            max-width: 420px;
            font-family: 'Slabo 27px', serif;
        }

        .social-links {
            display: flex;
            gap: 1.5rem;
        }

        .social-link {
            width: 48px;
            height: 48px;
            background: var(--card-bg);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            border: 1px solid var(--card-border);
            color: var(--primary-text);
        }

        .social-link:hover {
            background: var(--accent-color);
            color: white !important;
            transform: translateY(-5px);
            border-color: var(--accent-color);
            box-shadow: 0 10px 20px rgba(108, 93, 252, 0.2);
        }

        .footer-nav h4 {
            font-size: 1.4rem;
            margin-bottom: 2.5rem;
            font-family: 'Slabo 27px', serif;
            font-weight: 800;
            color: var(--primary-text);
        }

        .footer-nav ul {
            list-style: none;
        }

        .footer-nav ul li {
            margin-bottom: 1.4rem;
        }

        .footer-nav ul li a {
            text-decoration: none;
            color: var(--secondary-text);
            transition: var(--transition);
            font-weight: 600;
            font-size: 1.05rem;
            font-family: 'Slabo 27px', serif;
        }

        .footer-nav ul li a:hover {
            color: var(--accent-color);
            transform: translateX(8px);
            display: inline-block;
        }

        .contact-item {
            display: flex;
            gap: 1.2rem;
            margin-bottom: 1.8rem;
            align-items: flex-start;
        }

        .contact-icon {
            font-size: 1.3rem;
            background: var(--card-bg);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid var(--card-border);
        }

        .contact-item p {
            color: var(--secondary-text);
            font-size: 1.05rem;
            line-height: 1.6;
            font-family: 'Slabo 27px', serif;
        }

        /* Footer Bottom */
        .footer-bottom {
            border-top: 1px solid var(--card-border);
            padding: 3rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-info {
            display: flex;
            align-items: center;
            gap: 4rem;
        }

        .footer-info p {
            font-size: 1rem;
            color: var(--secondary-text);
            font-family: 'Slabo 27px', serif;
        }

        .bottom-links {
            display: flex;
            gap: 2.5rem;
        }

        .bottom-links a {
            text-decoration: none;
            color: var(--secondary-text);
            font-size: 1rem;
            transition: var(--transition);
            font-weight: 500;
            font-family: 'Slabo 27px', serif;
        }

        .bottom-links a:hover {
            color: var(--accent-color);
        }

        .footer-extra {
            display: flex;
            align-items: center;
            gap: 3rem;
        }

        .payment-methods {
            display: flex;
            gap: 1.5rem;
            font-size: 1.6rem;
            opacity: 0.7;
            filter: grayscale(0.5);
            transition: var(--transition);
        }

        .payment-methods:hover {
            opacity: 1;
            filter: grayscale(0);
        }

        .scroll-to-top {
            width: 55px;
            height: 55px;
            background: var(--accent-color);
            color: white;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 12px 24px rgba(108, 93, 252, 0.3);
        }

        .scroll-to-top:hover {
            transform: translateY(-5px) scale(1.05);
            filter: brightness(1.1);
        }

        /* Responsive Design */
        @media (max-width: 1100px) {
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 4rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1.5rem;
            }
            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 5rem;
            }
            .footer-logo {
                justify-content: center;
            }
            .brand-description {
                margin: 0 auto 3rem;
            }
            .social-links {
                justify-content: center;
            }
            .contact-item {
                flex-direction: column;
                align-items: center;
                gap: 0.8rem;
            }
            .footer-bottom {
                flex-direction: column-reverse;
                gap: 3rem;
                text-align: center;
            }
            .footer-info {
                flex-direction: column;
                gap: 2rem;
            }
            .bottom-links {
                justify-content: center;
            }
            .footer-extra {
                flex-direction: column;
                gap: 2rem;
            }
        }
    </style>
</footer>


