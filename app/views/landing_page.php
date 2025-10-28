<?php
// Preserve session alerts before clearing
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="HireTech is a modern job portal connecting top talent with employers across industries. Start your career or hire exceptional professionals today.">
  <meta name="keywords" content="HireTech, Job Portal, Employment, Careers, Recruitment, Hiring, Job Seekers, Employers">
  <meta name="author" content="HireTech Team">
  <title>HireTech | Job Portal</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  
  <style>
/* ---------- SCROLLBAR HIDE ---------- */
html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

/* ---------- CSS VARIABLES ---------- */
:root {
  --primary-blue: #1e40af;
  --primary-dark: #1e3a8a;
  --primary-light: #1d4ed8;
  --text-dark: #1e293b;
  --text-light: #475569;
  --text-white: #ffffff;
  --bg-light: #f9fafb;
  --bg-white: #ffffff;
  --bg-gray: #f1f5f9;
  --bg-feature: #f8fafc;
  --shadow-light: 0 2px 10px rgba(0,0,0,0.05);
  --shadow-medium: 0 4px 10px rgba(0,0,0,0.05);
  --shadow-heavy: 0 2px 6px rgba(0,0,0,0.2);
  --border-radius: 30px;
  --transition: all 0.3s ease;
}

/* ---------- GLOBAL RESET & BASE STYLES ---------- */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
  overflow-x: hidden;
}

body {
  font-family: 'Poppins', sans-serif;
  background: var(--bg-light);
  color: var(--text-dark);
  line-height: 1.6;
  overflow-x: hidden;
}

/* ---------- ACCESSIBILITY & FOCUS MANAGEMENT ---------- */
a {
  text-decoration: none;
  color: inherit;
  transition: var(--transition);
}

a:focus-visible,
button:focus-visible {
  outline: 2px solid var(--primary-blue);
  outline-offset: 2px;
}

img {
  max-width: 100%;
  display: block;
  height: auto;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* ---------- NAVBAR STYLES ---------- */
nav {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  padding: 0.75rem 5%;
  background: var(--bg-white);
  box-shadow: var(--shadow-light);
  position: sticky;
  top: 0;
  z-index: 1000;
  backdrop-filter: blur(10px);
  gap: 2rem;
}

nav h1 {
  color: var(--primary-blue);
  font-size: clamp(1.2rem, 2vw, 1.8rem);
  font-weight: 700;
  letter-spacing: -0.5px;
  justify-self: start;
}

/* Center the navigation menu */
nav ul {
  list-style: none;
  display: flex;
  gap: clamp(1.5rem, 3vw, 2.5rem);
  margin: 0;
  padding: 0;
  justify-self: center;
}

nav ul li a {
  color: var(--text-dark);
  font-weight: 500;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  position: relative;
  white-space: nowrap;
}

nav ul li a:hover {
  color: var(--primary-blue);
}

nav ul li a::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 50%;
  width: 0;
  height: 2px;
  background: var(--primary-blue);
  transition: var(--transition);
  transform: translateX(-50%);
}

nav ul li a:hover::after {
  width: 80%;
}

/* ---------- AUTH BUTTONS ---------- */
.auth-buttons {
  display: flex;
  gap: 0.75rem;
  justify-self: end;
}

.auth-buttons button {
  padding: 0.5rem 1.25rem;
  border-radius: var(--border-radius);
  border: none;
  cursor: pointer;
  font-weight: 600;
  transition: var(--transition);
  font-size: 0.95rem;
  font-family: inherit;
  white-space: nowrap;
}

.btn-signin {
  background: var(--bg-white);
  border: 2px solid var(--primary-blue);
  color: var(--primary-blue);
}

.btn-signin:hover {
  background: var(--primary-blue);
  color: var(--text-white);
  transform: translateY(-1px);
}

.btn-signup {
  background: var(--primary-blue);
  color: var(--text-white);
}

.btn-signup:hover {
  background: var(--primary-light);
  transform: translateY(-1px);
}

/* ---------- HAMBURGER MENU ---------- */
.hamburger {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;
  justify-self: end;
}

.hamburger span {
  width: 25px;
  height: 3px;
  background: var(--text-dark);
  border-radius: 3px;
  transition: var(--transition);
  transform-origin: center;
}

.hamburger.active span:nth-child(1) {
  transform: rotate(45deg) translate(6px, 6px);
}

.hamburger.active span:nth-child(2) {
  opacity: 0;
}

.hamburger.active span:nth-child(3) {
  transform: rotate(-45deg) translate(6px, -6px);
}

/* ---------- HERO SECTION ---------- */
.hero {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 90vh;
  text-align: center;
  padding: 2rem 1.25rem;
  background: 
    linear-gradient(135deg, rgba(131, 152, 216, 0.8), rgba(20, 36, 82, 0.9)),
    url('https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1950&q=80') no-repeat center center;
  background-size: cover;
  background-attachment: fixed;
  color: var(--text-white);
  position: relative;
}

.hero h2 {
  font-size: clamp(1.8rem, 5vw, 3rem);
  margin-bottom: 1rem;
  line-height: 1.2;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.hero p {
  max-width: 650px;
  margin-bottom: 2rem;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.9);
  font-size: clamp(0.9rem, 2vw, 1.1rem);
  text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.hero-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
}

.hero button {
  background: var(--primary-blue);
  color: var(--text-white);
  border: none;
  border-radius: var(--border-radius);
  padding: 0.75rem 2rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  font-family: inherit;
  min-width: 140px;
}

.hero button:hover {
  background: var(--primary-light);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(30, 64, 175, 0.3);
}

.hero button#postJobBtn {
  background: var(--bg-white);
  color: var(--primary-blue);
}

.hero button#postJobBtn:hover {
  background: #e2e8f0;
  color: var(--primary-dark);
  box-shadow: 0 8px 20px rgba(255, 255, 255, 0.2);
}

/* ---------- FEATURES SECTION ---------- */
.features {
  padding: clamp(3rem, 8vw, 5rem) 5%;
  background: var(--bg-white);
  text-align: center;
}

.features h2 {
  font-size: clamp(1.5rem, 4vw, 2.5rem);
  color: var(--primary-dark);
  margin-bottom: clamp(2rem, 6vw, 3rem);
  font-weight: 700;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr));
  gap: clamp(1.5rem, 4vw, 2.5rem);
  max-width: 1200px;
  margin: 0 auto;
}

.feature {
  background: var(--bg-feature);
  border-radius: 20px;
  padding: clamp(1.5rem, 4vw, 2.5rem);
  box-shadow: var(--shadow-medium);
  transition: var(--transition);
  border: 1px solid transparent;
  text-align: left;
}

.feature:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 30px rgba(0,0,0,0.1);
  border-color: var(--primary-blue);
}

.feature h3 {
  color: var(--primary-blue);
  margin-bottom: 1rem;
  font-size: 1.25rem;
  font-weight: 600;
}

.feature p {
  color: var(--text-light);
  font-size: 0.95rem;
  line-height: 1.6;
}

/* ---------- CONTACT SECTION ---------- */
.contact {
  padding: clamp(3rem, 8vw, 5rem) 5%;
  background: var(--bg-gray);
  text-align: center;
}

.contact h2 {
  color: var(--primary-dark);
  margin-bottom: 1rem;
  font-size: clamp(1.5rem, 4vw, 2.5rem);
  font-weight: 700;
}

.contact p {
  color: var(--text-light);
  max-width: 500px;
  margin: 0 auto;
  line-height: 1.7;
  font-size: 1.05rem;
}

/* ---------- FOOTER ---------- */
footer {
  background: var(--primary-dark);
  color: var(--text-white);
  text-align: center;
  padding: 2rem 1rem;
  font-size: 0.9rem;
}

footer p {
  margin: 0;
  line-height: 1.6;
  opacity: 0.9;
}

/* ---------- ALERT STYLES ---------- */
.alert {
  position: fixed;
  top: 1.5rem;
  right: 1.5rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  color: var(--text-white);
  font-weight: 600;
  display: none;
  z-index: 9999;
  box-shadow: var(--shadow-heavy);
  max-width: min(400px, 90vw);
  backdrop-filter: blur(10px);
  animation: slideInRight 0.3s ease-out;
}

.alert-success {
  background: linear-gradient(135deg, #4caf50, #45a049);
  border-left: 4px solid #2e7d32;
}

.alert-error {
  background: linear-gradient(135deg, #f44336, #e53935);
  border-left: 4px solid #c62828;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* ---------- RESPONSIVE DESIGN ---------- */
@media (max-width: 1024px) {
  nav {
    grid-template-columns: auto 1fr auto;
    gap: 1rem;
  }
  
  nav ul {
    gap: clamp(1rem, 2vw, 2rem);
  }
}

@media (max-width: 768px) {
  /* Navigation */
  nav {
    grid-template-columns: 1fr auto auto;
    padding: 0.75rem 4%;
    gap: 1rem;
  }

  nav ul {
    display: none;
    flex-direction: column;
    width: 100%;
    text-align: center;
    background: var(--bg-white);
    position: absolute;
    top: 100%;
    left: 0;
    box-shadow: var(--shadow-heavy);
    padding: 1rem 0;
    gap: 0;
    justify-self: stretch;
  }

  nav ul.active {
    display: flex;
  }

  nav ul li {
    width: 100%;
  }

  nav ul li a {
    display: block;
    padding: 1rem;
    width: 100%;
    border-radius: 0;
  }

  nav ul li a::after {
    display: none;
  }

  .hamburger {
    display: flex;
    order: 2;
  }

  .auth-buttons {
    display: none;
  }

  nav h1 {
    justify-self: start;
    order: 1;
  }

  /* Hero Section */
  .hero {
    min-height: 80vh;
    padding: 1.5rem 1rem;
    background-attachment: scroll;
  }

  .hero-buttons {
    flex-direction: column;
    width: 100%;
    max-width: 300px;
  }

  .hero button {
    width: 100%;
  }

  /* Features */
  .features {
    padding: 3rem 4%;
  }

  .feature-grid {
    gap: 1.5rem;
  }

  .feature {
    text-align: center;
    padding: 1.5rem;
  }

  /* Alert */
  .alert {
    left: 1rem;
    right: 1rem;
    max-width: none;
  }
}

@media (max-width: 480px) {
  nav {
    padding: 0.5rem 3%;
    grid-template-columns: 1fr auto;
  }

  nav h1 {
    font-size: 1.1rem;
  }

  .hero h2 {
    font-size: 1.6rem;
  }

  .hero p {
    font-size: 0.9rem;
  }

  .features,
  .contact {
    padding: 2rem 3%;
  }

  .feature {
    padding: 1.25rem;
  }
}

/* Alternative flexbox approach for older browsers */
@media (max-width: 1024px) and (min-width: 769px) {
  nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  nav ul {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
  }
}
  </style>
</head>

<body>

<!-- ---------- NAVBAR ---------- -->
<nav>
  <h1>HireTech</h1>
  <ul>
    <li><a href="#">Home</a></li>
    <li><a href="#features">Features</a></li>
    <li><a href="#contact">Contact</a></li>
  </ul>
  <div class="auth-buttons">
    <!-- In your landing_page.html -->
    <button class="btn-signin" id="openSignin" onclick="window.location.href='/login'">Sign In</button>
    <button class="btn-signup" id="openSignup" onclick="window.location.href='/register'">Sign Up</button>
  </div>
  <div class="hamburger">
    <span></span><span></span><span></span>
  </div>
</nav>

<!-- ---------- HERO SECTION ---------- -->
<section class="hero">
  <h2>Connecting Talent with Opportunity</h2>
  <p>Find your dream job or hire the best professionals — all in one place. Join HireTech and experience the future of recruitment and career growth.</p>
  <div>
    <button id="findJobBtn">Find a Job</button>
    <button id="postJobBtn" style="margin-left: 15px;">Post a Job</button>
  </div>
</section>

<!-- ---------- FEATURES ---------- -->
<section class="features" id="features">
  <h2>Why Choose HireTech?</h2>
  <div class="feature-grid">
    <div class="feature">
      <h3>Smart Job Matching</h3>
      <p>Our AI-powered system intelligently connects employers with top candidates based on skills, experience, and preferences.</p>
    </div>
    <div class="feature">
      <h3>Secure Profiles</h3>
      <p>All users’ information is safeguarded through advanced encryption technology, ensuring your data remains private and protected.</p>
    </div>
    <div class="feature">
      <h3>Easy Application</h3>
      <p>Post jobs, manage applications, and track interviews seamlessly with our intuitive dashboard designed for both employers and job seekers.</p>
    </div>
    <div class="feature">
      <h3>24/7 Support</h3>
      <p>Our dedicated support team is always ready to assist you through live chat or email to ensure a smooth experience.</p>
    </div>
  </div>
</section>

<!-- ---------- CONTACT ---------- -->
<section class="contact" id="contact">
  <h2>Get in Touch</h2>
  <p>Need help or have questions? Contact us anytime at <strong>support@hiretech.com</strong> or reach us on our social media platforms.</p>
</section>

<!-- ---------- FOOTER ---------- -->
<footer>
  <p>&copy; <?= date('Y'); ?> HireTech. All rights reserved. | Empowering Careers, Connecting Opportunities.</p>
</footer>

<!-- ---------- ALERT ---------- -->
<?php if($success): ?>
  <div class="alert alert-success" id="alertBox"><?= $success; ?></div>
<?php elseif($error): ?>
  <div class="alert alert-error" id="alertBox"><?= $error; ?></div>
<?php endif; ?>

<!-- ---------- JS ---------- -->
<script>
$(document).ready(function() {
  // Alert animation
  const $alertBox = $('#alertBox');
  if ($alertBox.length) {
    $alertBox.fadeIn();
    setTimeout(() => $alertBox.fadeOut(), 4000);
  }

  // Redirect buttons
  $('#openSignin, #findJobBtn, #postJobBtn').click(function() {
    window.location.href = '/login';
  });

  $('#openSignup').click(function() {
    window.location.href = '/register';
  });

  // Hamburger menu toggle
  $('.hamburger').click(function() {
    $('nav ul').toggleClass('active');
  });
});
</script>

</body>
</html>
