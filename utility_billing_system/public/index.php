<!-- File: C:\Users\angelo\CascadeProjects\utility_billing_system\public\index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ILWWD - Iligan City Water Works System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0056b3;
            --secondary-blue: #003d82;
            --accent-blue: #1a73e8;
            --light-blue: #e8f0fe;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        /* Header */
        .top-bar {
            background-color: var(--primary-blue);
            color: white;
            padding: 8px 0;
            font-size: 0.9rem;
        }
        
        .main-header {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            height: 70px;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0, 86, 179, 0.85), rgba(0, 61, 130, 0.9)), 
                        url('../assets/images/iligan-water.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 120px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 3.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero p {
            font-size: 1.3rem;
            max-width: 800px;
            margin: 0 auto 30px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        
        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 30px;
            color: var(--secondary-blue);
            font-weight: 700;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            width: 60%;
            height: 4px;
            background: var(--accent-blue);
            bottom: -10px;
            left: 20%;
            border-radius: 2px;
        }
        
        .feature-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 4px solid var(--accent-blue);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 2.8rem;
            color: var(--accent-blue);
            margin-bottom: 20px;
            background: rgba(26, 115, 232, 0.1);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
        }
        
        /* About Section */
        .about-section {
            padding: 80px 0;
            background: white;
        }
        
        .about-img {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .about-img img {
            width: 100%;
            height: auto;
            transition: transform 0.5s ease;
        }
        
        .about-img:hover img {
            transform: scale(1.05);
        }
        
        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        
        .stat-item {
            padding: 20px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: white;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* How It Works */
        .how-it-works {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        
        .step {
            text-align: center;
            padding: 0 20px;
            position: relative;
            margin-bottom: 30px;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            background-color: var(--accent-blue);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 20px;
            position: relative;
            z-index: 1;
        }
        
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 25px;
            left: 50%;
            width: 80%;
            height: 2px;
            background: #dee2e6;
            transform: translateX(-50%);
            z-index: 0;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(rgba(0, 86, 179, 0.9), rgba(0, 61, 130, 0.9)), 
                        url('../assets/images/water-drops-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 100px 0;
            text-align: center;
            position: relative;
        }
        
        .cta-section h2 {
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .cta-section p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background-color: #1a237e;
            color: white;
            padding: 60px 0 20px;
        }
        
        .footer-logo {
            height: 60px;
            margin-bottom: 20px;
        }
        
        .footer-about {
            margin-bottom: 20px;
            opacity: 0.9;
            line-height: 1.7;
        }
        
        .footer-links h5 {
            color: white;
            margin-bottom: 20px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-links h5:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 2px;
            background: var(--accent-blue);
        }
        
        .footer-links ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: #d1d5db;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .footer-links a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        .contact-info {
            list-style: none;
            padding: 0;
        }
        
        .contact-info li {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
        }
        
        .contact-info i {
            position: absolute;
            left: 0;
            top: 5px;
            color: var(--accent-blue);
            font-size: 1.1rem;
        }
        
        .social-icons {
            margin-top: 20px;
        }
        
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            color: white;
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .social-icons a:hover {
            background: var(--accent-blue);
            transform: translateY(-3px);
        }
        
        .copyright {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            margin-top: 40px;
            text-align: center;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.6);
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--accent-blue);
            border-color: var(--accent-blue);
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        
        .btn-outline-light {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            border-width: 2px;
        }
        
        .btn-primary:hover {
            background-color: #0d47a1;
            border-color: #0d47a1;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-outline-light:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                padding: 80px 0;
            }
            
            .hero h1 {
                font-size: 2.2rem;
            }
            
            .hero p {
                font-size: 1.1rem;
                padding: 0 15px;
            }
            
            .btn {
                display: block;
                width: 80%;
                margin: 10px auto;
                max-width: 250px;
            }
            
            .btn-outline-light {
                margin-left: auto;
                margin-right: auto;
                margin-top: 15px;
            }
            
            .step:not(:last-child):after {
                display: none;
            }
        }
        
        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .delay-1 {
            animation-delay: 0.2s;
        }
        
        .delay-2 {
            animation-delay: 0.4s;
        }
        
        .delay-3 {
            animation-delay: 0.6s;
        }
    </style>
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light py-3">
                <div class="container-fluid">
                    <a class="navbar-brand" href="index.php">
                        <img src="../assets/images/ilwd-logo.jpg" alt="Iligan City Water District" class="logo">
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item">
                                <a class="nav-link active" href="#">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#services">Services</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#about">About Us</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#contact">Contact</a>
                            </li>
                            <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                                <a href="register.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i> Customer Portal
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="fade-in-up">Welcome to Iligan City Water District</h1>
                    <p class="fade-in-up delay-1">Providing safe, clean, and reliable water supply to the people of Iligan City since 1973. Your trusted partner in water utility services.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="services">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Our Services</h2>
                <p class="lead text-muted">Comprehensive water and wastewater services for Iligan City</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card fade-in-up">
                        <div class="feature-icon">
                            <i class="fas fa-faucet"></i>
                        </div>
                        <h4>Water Supply</h4>
                        <p>24/7 reliable water supply with consistent pressure and quality that meets national standards for safety and cleanliness.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card fade-in-up delay-1">
                        <div class="feature-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h4>Billing & Payment</h4>
                        <p>Convenient billing options including online payment, over-the-counter, and authorized payment centers throughout Iligan City.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card fade-in-up delay-2">
                        <div class="feature-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h4>Maintenance</h4>
                        <p>Regular maintenance and immediate response to water line repairs and other service concerns to ensure uninterrupted service.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="about-img">
                        <img src="../assets/images/iligan-water-plant.jpg" alt="Iligan Water Treatment Plant" class="img-fluid">
                    </div>
                </div>
                <div class="col-lg-6 ps-lg-5">
                    <h2 class="section-title text-start mb-4">About Iligan City Water District</h2>
                    <p class="lead">Serving Iligan City with pride since 1973</p>
                    <p>The Iligan City Water District (ILWWD) is a government-owned and controlled corporation providing safe, potable water to the residents of Iligan City. We are committed to delivering excellent water service through efficient management and sustainable practices.</p>
                    <p>Our mission is to provide adequate, safe, potable and affordable water supply to all consumers in the most efficient and effective manner, while promoting environmental protection and conservation of water resources.</p>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <i class="fas fa-check-circle text-primary me-2 mt-1"></i>
                                <div>
                                    <h5 class="mb-1">Certified Water Quality</h5>
                                    <p class="text-muted small">Regularly tested and monitored</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <i class="fas fa-check-circle text-primary me-2 mt-1"></i>
                                <div>
                                    <h5 class="mb-1">24/7 Support</h5>
                                    <p class="text-muted small">Always here to help you</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-4 mb-md-0">
                    <div class="stat-item">
                        <div class="stat-number" data-count="250000">0</div>
                        <div class="stat-label">Consumers Served</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4 mb-md-0">
                    <div class="stat-item">
                        <div class="stat-number" data-count="45">0</div>
                        <div class="stat-label">Years in Service</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number" data-count="98">0</div>
                        <div class="stat-label">% Service Coverage</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number" data-count="24">0</div>
                        <div class="stat-label">/7 Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">How to Get Connected</h2>
                <p class="lead text-muted">Simple steps to avail of our water services</p>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h4>Application</h4>
                        <p>Submit the required documents and application form at our main office or through our online portal.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="step">
                        <div class="step-number">2</div>
                        <h4>Inspection</h4>
                        <p>Our team will conduct an inspection of your premises to determine the feasibility of connection.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="step">
                        <div class="step-number">3</div>
                        <h4>Installation</h4>
                        <p>Upon approval, our team will install the water meter and connect your property to our main line.</p>
                    </div>
                </div>
            </div>
            
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-5 mb-lg-0">
                    <img src="../assets/images/ilwd-logo.jpg" alt="Iligan City Water District" class="footer-logo">
                    <p class="footer-about">
                        The Iligan City Water District is committed to providing safe, clean, and reliable water supply to all residents of Iligan City through efficient and sustainable water resource management.
                    </p>
                    <div class="social-icons">
                        <a href="https://www.facebook.com/iliganwater" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/iliganwater" target="_blank" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instagram.com/iliganwater" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.youtube.com/iliganwater" target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="#">Home</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                        <li><a href="login.php">Customer Portal</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5>Our Services</h5>
                    <ul class="footer-links">
                        <li><a href="#">Bill Payment</a></li>
                        <li><a href="#">Water Quality</a></li>
                        <li><a href="#">Leak Reporting</a></li>
                        <li><a href="#">Conservation Tips</a></li>
                        <li><a href="#">Service Interruptions</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5>Contact Us</h5>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <div>Roxas Avenue, Pala-o, Iligan City, 9200 Lanao del Norte, Philippines</div>
                        </li>
                        <li>
                            <i class="fas fa-phone-alt"></i>
                            <div>
                                <a href="tel:+63632213499">(063) 221-3499</a><br>
                                <a href="tel:+639123456789">0912-345-6789</a> (Globe)<br>
                                <a href="tel:+639876543210">0999-999-9999</a> (Smart)
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <div>
                                <a href="mailto:info@iliganwater.gov.ph">info@iliganwater.gov.ph</a><br>
                                <a href="mailto:customercare@iliganwater.gov.ph">customercare@iliganwater.gov.ph</a>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <div>
                                Monday to Friday: 8:00 AM - 5:00 PM<br>
                                Saturday: 8:00 AM - 12:00 NN<br>
                                Sunday: Closed
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="copyright">
                        <p class="mb-0">&copy; 2025 Iligan City Water District. All Rights Reserved. | <a href="#" class="text-white">Privacy Policy</a> | <a href="#" class="text-white">Terms of Service</a></p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Apply Modal -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                if (this.getAttribute('href') !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        window.scrollTo({
                            top: target.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
        
        // Add shadow to navbar on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.main-header');
            if (window.scrollY > 50) {
                header.style.boxShadow = '0 2px 15px rgba(0,0,0,0.1)';
            } else {
                header.style.boxShadow = 'none';
            }
        });
        
        // Animate numbers in stats section
        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start) + (obj.getAttribute('data-count') > 1000 ? '+' : '');
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }
        
        // Intersection Observer for fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                    
                    // Animate stats if in view
                    if (entry.target.classList.contains('stats-section')) {
                        const counters = document.querySelectorAll('.stat-number');
                        counters.forEach(counter => {
                            const target = +counter.getAttribute('data-count');
                            animateValue(counter, 0, target, 2000);
                        });
                    }
                    
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe elements
        document.querySelectorAll('.fade-in-up, .stats-section').forEach(el => {
            observer.observe(el);
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>