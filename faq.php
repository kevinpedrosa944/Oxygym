<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OxyGym - FAQ</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* FAQ specific overrides */
        .faq-section {
            padding-top: 8rem; /* Pushes content below the fixed header */
            padding-bottom: 3rem;
        }
        .faq-section h2 { /* Targeting the h2 within the section */
            margin-bottom: 3rem; /* Added space below title */
        }
        .faq-grid { max-width: 800px; margin: 0 auto; }
        .faq-item { margin-bottom: 20px; } /* Increased from 15px */
        .faq-toggle {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            text-align: left;
            font-size: 1.1rem;
            padding: 1rem; /* Added padding */
            transition: background 0.3s;
        }
        .faq-toggle i { transition: transform 0.3s; }
        .faq-content {
            display: none;
            padding: 25px; /* Increased from 15px */
            background: #1f1f38; /* Dark card background */
            color: #fff; /* White text */
            border-left: 3px solid #ff7f00;
            font-size: 1rem;
            line-height: 1.7; /* Increased from 1.5 */
        }
        .faq-item.active .faq-toggle i { transform: rotate(180deg); }
        .faq-item.active .faq-content { display: block; }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container nav-container">
            <h1 class="logo">🏋️ OXYGYM</h1>
            <nav>
                <ul>
                    <li><a href="index.html#about-section">About</a></li>
                    <li><a href="index.html#plan-section">Plans</a></li>
                    <li><a href="index.html#contact-section">Contact</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                </ul>
            </nav>
            <div id="authButtons">
                <a href="Login.html" class="register-btn">Sign Up</a>
            </div>
        </div>
    </header>

    <!-- FAQ Section -->
    <section id="faq-section" class="faq-section">
        <div class="container">
            <h2 class="text-center">Frequently Asked Questions</h2>

            <div class="faq-grid">
                <div class="faq-item">
                    <button class="membership-btn faq-toggle">
                        Are there any technical requirements to access OxyGym website?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-content">
                        You need an internet connection and a device such as a computer, tablet, or phone. For best experience, a computer or iPad is recommended.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="membership-btn faq-toggle">
                        How do I register?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-content">
                        To register, purchase a membership plan via our website. You will then receive login access to your account.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="membership-btn faq-toggle">
                        What membership plans are available?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-content">
                        We offer Standard, Prime, and Premium plans. Each plan has different benefits tailored for your fitness journey.
                    </div>
                </div>

                <div class="faq-item">
                    <button class="membership-btn faq-toggle">
                        How do I contact OxyGym for further inquiries?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-content">
                        You can email us at <a href="mailto:info@oxygym.com">info@oxygym.com</a> or call our hotline listed in the Contact section.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <div class="footer-links">
                <div>
                    <h4>GYM</h4>
                    <ul>
                        <li><a href="index.html#about-section">About</a></li>
                        <li><a href="index.html#plan-section">Plans</a></li>
                        <li><a href="index.html#contact-section">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4>MEMBERS</h4>
                    <ul>
                        <li><a href="faq.php">FAQs</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 OxyGym. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- FAQ Script -->
    <script>
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            const toggle = item.querySelector('.faq-toggle');
            toggle.addEventListener('click', () => {
                // Close other items
                faqItems.forEach(i => {
                    if(i !== item) {
                        i.classList.remove('active');
                    }
                });
                // Toggle current
                item.classList.toggle('active');
            });
        });
    </script>
</body>
</html>