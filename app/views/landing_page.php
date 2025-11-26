<!DOCTYPE html>
<html lang="en">
<head>
    <script 
        id="Cookiebot" 
        src="https://consent.cookiebot.com/uc.js" 
        data-cbid="b14db11a-de3c-480c-a46d-e585a6e349c7" 
        data-blockingmode="auto" 
        type="text/javascript">
    </script>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT JobHub - Home</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }
        /* ======== GLOBAL STYLES ======== */
:root {
    --primary-color: #0052ff; /* Main Blue */
    --dark-color: #0b0c1f;    /* Dark Navy/Black */
    --light-color: #ffffff;   /* White */
    --gray-color: #f4f7fc;    /* Light Gray Background */
    --text-color: #555;       /* Standard text */
    --heading-color: #222;    /* Headings */
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: var(--light-color);
    color: var(--text-color);
    line-height: 1.6;
}

.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 20px;
}

h1, h2, h3 {
    color: var(--heading-color);
    margin-top: 0;
}

h2 {
    font-size: 2.2rem;
    text-align: center;
    margin-bottom: 40px;
}

a {
    text-decoration: none;
    color: var(--primary-color);
}

img {
    max-width: 100%;
    border-radius: 8px;
}

section {
    padding: 60px 0;
}

/* ======== BUTTONS ======== */
.btn {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    text-align: center;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--light-color);
    border: 2px solid var(--primary-color);
}
.btn-primary:hover {
    background-color: #0045d1;
}

.btn-secondary {
    background-color: var(--light-color);
    color: var(--heading-color);
    border: 2px solid #ddd;
}
.btn-secondary:hover {
    background-color: var(--gray-color);
}

.btn-light {
    background-color: var(--light-color);
    color: var(--primary-color);
    border: 2px solid var(--light-color);
}

.btn-secondary-outline {
    background-color: transparent;
    color: var(--light-color);
    border: 2px solid var(--light-color);
}

/* ======== HEADER ======== */
.header {
    background-color: var(--light-color);
    border-bottom: 1px solid #eee;
    padding: 15px 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
}
.logo i {
    margin-right: 8px;
}

.main-nav a {
    margin-left: 20px;
    color: var(--text-color);
    font-weight: 500;
}
.main-nav a.btn {
    margin-left: 20px;
    padding: 10px 20px;
    color: var(--light-color);
}
.main-nav a:hover {
    color: var(--primary-color);
}

/* ======== SECTION 1: HERO ======== */
.hero-section {
    background-color: #f9faff; /* Light blue tint */
    padding: 80px 0;
}
.hero-content {
    display: flex;
    align-items: center;
    gap: 40px;
}
.hero-text {
    flex: 1;
}
.hero-text h1 {
    font-size: 3rem;
    margin-bottom: 20px;
}
.hero-text p {
    font-size: 1.1rem;
    margin-bottom: 30px;
}
.hero-buttons {
    margin-bottom: 40px;
}
.hero-buttons .btn {
    margin-right: 15px;
}
.hero-stats {
    display: flex;
    gap: 30px;
}
.hero-stats div {
    display: flex;
    flex-direction: column;
}
.hero-stats strong {
    font-size: 1.5rem;
    color: var(--heading-color);
}
.hero-stats span {
    color: var(--text-color);
}
.hero-image {
    flex: 1;
    text-align: center;
}
.hero-image img {
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

/* ======== SECTION 2: CATEGORIES ======== */
.categories-section {
    background-color: var(--light-color);
}
.categories-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
}
.category-card {
    background-color: #fdfdff;
    border: 1px solid #eef;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
}
.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0, 82, 255, 0.08);
}
.category-card i {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 15px;
}
.category-card h3 {
    margin-bottom: 5px;
}
.category-card span {
    color: var(--text-color);
}

/* ======== SECTION 3: HOW IT WORKS ======== */
.how-it-works-section {
    background-color: var(--gray-color);
}
.how-it-works-steps {
    display: flex;
    justify-content: space-around;
    gap: 30px;
    text-align: center;
}
.step {
    flex: 1;
    max-width: 300px;
}
.step-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #e6efff;
    color: var(--primary-color);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 20px;
}
.step h3 {
    margin-bottom: 10px;
}

/* ======== SECTION 4: EMPLOYERS ======== */
.employer-content {
    display: flex;
    align-items: center;
    gap: 60px;
}
.employer-image {
    flex: 1;
}
.employer-text {
    flex: 1;
}
.employer-text h2 {
    text-align: left;
    margin-bottom: 20px;
}
.employer-features {
    list-style: none;
    padding: 0;
    margin: 30px 0;
}
.employer-features li {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}
.employer-features i {
    font-size: 1.5rem;
    color: var(--primary-color);
    width: 30px;
}
.employer-features strong {
    display: block;
    color: var(--heading-color);
    font-size: 1.1rem;
}

/* ======== SECTION 5: FINAL CTA ======== */
.cta-section {
    background-color: var(--primary-color);
    color: var(--light-color);
    text-align: center;
}
.cta-section h2 {
    color: var(--light-color);
    font-size: 2rem;
}
.cta-section p {
    font-size: 1.1rem;
    margin-bottom: 30px;
}
.cta-buttons .btn {
    margin: 0 10px;
}

/* ======== SECTION 6: FOOTER ======== */
.footer {
    background-color: var(--dark-color);
    color: #aaa;
    padding-top: 60px;
}
.footer-content {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr;
    gap: 40px;
    padding-bottom: 40px;
}
.footer .logo {
    color: var(--light-color);
    font-size: 1.5rem;
    margin-bottom: 15px;
    display: inline-block;
}
.footer-col h3 {
    color: var(--light-color);
    margin-bottom: 20px;
    font-size: 1.1rem;
}
.footer-col ul {
    list-style: none;
    padding: 0;
}
.footer-col li {
    margin-bottom: 10px;
}
.footer-col li i {
    margin-right: 10px;
    width: 15px;
}
.footer-col a {
    color: #aaa;
    transition: color 0.3s ease;
}
.footer-col a:hover {
    color: var(--light-color);
}
.footer-bottom {
    border-top: 1px solid #333;
    padding: 20px 0;
    text-align: center;
    font-size: 0.9rem;
}

/* ======== RESPONSIVE (for mobile) ======== */
@media (max-width: 768px) {
    .hero-content, .employer-content {
        flex-direction: column;
    }
    .hero-text {
        text-align: center;
    }
    .hero-stats {
        justify-content: center;
    }
    .categories-grid {
        grid-template-columns: 1fr 1fr;
    }
    .how-it-works-steps {
        flex-direction: column;
        align-items: center;
    }
    .footer-content {
        grid-template-columns: 1fr 1fr;
    }
    .footer-col.about {
        grid-column: 1 / -1; /* Span full width */
    }
}

@media (max-width: 480px) {
    .categories-grid {
        grid-template-columns: 1fr;
    }
    .footer-content {
        grid-template-columns: 1fr;
    }
}

    </style>


</head>
<body>

    <header class="header">
        <div class="container">
            <a href="/" class="logo">
                <i class="fa-solid fa-briefcase"></i> HireTech
            </a>
            <nav class="main-nav">
                <a href="/">Home</a>
                <a href="/login">Browse Jobs</a>
                <a href="/login">Login</a>
                <a href="/register" class="btn btn-primary">Sign Up</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="container hero-content">
                <div class="hero-text">
                    <h1>Find Your Dream IT Career</h1>
                    <p>Connect with top tech companies and discover opportunities that match your skills. Whether you're a developer, designer, or IT professional, we have the perfect job for you.</p>
                    <div class="hero-buttons">
                        <a href="/login" class="btn btn-primary">Browse Jobs</a>
                        <a href="/register" class="btn btn-secondary">Sign Up Free</a>
                    </div>
                    <div class="hero-stats">
                        <div>
                            <strong>10,000+</strong>
                            <span>Active Jobs</span>
                        </div>
                        <div>
                            <strong>5,000+</strong>
                            <span>Companies</span>
                        </div>
                        <div>
                            <strong>50,000+</strong>
                            <span>Job Seekers</span>
                        </div>
                    </div>
                </div>
                <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1762341114881-669da93fef88?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwcm9mZXNzaW9uYWwlMjBvZmZpY2UlMjB0ZWNobm9sb2d5fGVufDF8fHx8MTc2MjM1ODUxOHww&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral" alt="IT Professional">
                </div>
            </div>
        </section>

        <section class="categories-section">
            <div class="container">
                <h2>Popular IT Categories</h2>
                <div class="categories-grid">
                    <div class="category-card">
                        <i class="fa-solid fa-code"></i>
                        <h3>Software Development</h3>
                        <span>2,500+ jobs</span>
                    </div>
                    <div class="category-card">
                        <i class="fa-solid fa-database"></i>
                        <h3>Data Science</h3>
                        <span>1,800+ jobs</span>
                    </div>
                    <div class="category-card">
                        <i class="fa-solid fa-shield-halved"></i>
                        <h3>Cybersecurity</h3>
                        <span>1,200+ jobs</span>
                    </div>
                    <div class="category-card">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                        <h3>Mobile Development</h3>
                        <span>900+ jobs</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="how-it-works-section">
            <div class="container">
                <h2>How It Works</h2>
                <div class="how-it-works-steps">
                    <div class="step">
                        <div class="step-icon">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <h3>1. Search Jobs</h3>
                        <p>Browse thousands of IT job listings from top companies worldwide.</p>
                    </div>
                    <div class="step">
                        <div class="step-icon">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <h3>2. Apply Easily</h3>
                        <p>Submit your application with one click using your profile and resume.</p>
                    </div>
                    <div class="step">
                        <div class="step-icon">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <h3>3. Get Hired</h3>
                        <p>Connect with employers and land your dream IT job.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="employer-section">
            <div class="container employer-content">
                <div class="employer-image">
                <img src="https://images.unsplash.com/photo-1748256622734-92241ae7b43f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx0ZWFtJTIwY29sbGFib3JhdGlvbiUyMHdvcmtzcGFjZXxlbnwxfHx8fDE3NjIyOTEwOTl8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral" alt="Office Environment">
                </div>
                <div class="employer-text">
                    <h2>For Employers</h2>
                    <p>Find the best IT talent for your company. Post jobs, review applications, and hire top professionals quickly and efficiently.</p>
                    <ul class="employer-features">
                        <li>
                            <i class="fa-solid fa-users"></i>
                            <div>
                                <strong>Access to Top Talent</strong>
                                <span>Connect with 50,000+ qualified IT professionals</span>
                            </div>
                        </li>
                        <li>
                            <i class="fa-solid fa-file-alt"></i>
                            <div>
                                <strong>Easy Job Posting</strong>
                                <span>Post jobs in minutes with our simple interface</span>
                            </div>
                        </li>
                        <li>
                            <i class="fa-solid fa-brain"></i>
                            <div>
                                <strong>Smart Matching</strong>
                                <span>Get matched with candidates that fit your requirements</span>
                            </div>
                        </li>
                    </ul>
                    <a href="#" class="btn btn-primary">Start Hiring</a>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="container">
                <h2>Join thousands of IT professionals and companies</h2>
                <p>who are already using IT JobHub to find their perfect match.</p>
                <div class="cta-buttons">
                    <a href="/register" class="btn btn-light">Create Free Account</a>
                    <a href="/login" class="btn btn-secondary-outline">Browse Jobs</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container footer-content">
            <div class="footer-col about">
                <a href="#" class="logo">
                    <i class="fa-solid fa-briefcase"></i> IT JobHub
                </a>
                <p>Connecting IT professionals with their dream careers.</p>
            </div>
            <div class="footer-col">
                <h3>For Job Seekers</h3>
                <ul>
                    <li><a href="#">Browse Jobs</a></li>
                    <li><a href="#">Career Resources</a></li>
                    <li><a href="#">Resume Tips</a></li>
                    <li><a href="#">Interview Prep</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>For Employers</h3>
                <ul>
                    <li><a href="#">Post a Job</a></li>
                    <li><a href="#">Pricing</a></li>
                    <li><a href="#">Recruiting Solutions</a></li>
                    <li><a href="#">Talent Search</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Contact Us</h3>
                <ul>
                    <li><i class="fa-solid fa-envelope"></i> contact@itjobhub.com</li>
                    <li><i class="fa-solid fa-phone"></i> +1 (555) 123-4567</li>
                    <li><i class="fa-solid fa-map-marker-alt"></i> San Francisco, CA</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 IT JobHub. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>