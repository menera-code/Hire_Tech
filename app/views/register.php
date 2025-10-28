<?php

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - HireTech</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        :root {
            --primary-blue: #1e40af;
            --primary-dark: #1e3a8a;
            --primary-light: #1d4ed8;
            --text-dark: #1e293b;
            --text-light: #475569;
            --text-white: #ffffff;
            --bg-light: #f9fafb;
            --bg-white: #ffffff;
            --border-radius: 8px;
            --transition: all 0.3s ease;
            --success-green: #059669;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: var(--bg-white);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h1 {
            color: var(--primary-blue);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .auth-header p {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .back-home:hover {
            color: var(--primary-blue);
        }

        /* Google Sign-In Section */
        .google-signin-section {
            margin-bottom: 25px;
        }

        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            background: white;
            color: var(--text-dark);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            font-family: inherit;
            font-size: 1rem;
            cursor: pointer;
        }

        .btn-google:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        .divider {
            position: relative;
            text-align: center;
            margin: 20px 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
        }

        .divider span {
            background: var(--bg-white);
            padding: 0 15px;
            position: relative;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition);
            background: var(--bg-white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }

        .select-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }

        .btn {
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: var(--text-white);
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .auth-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .auth-footer p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: 500;
            display: none;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: var(--success-green);
            border: 1px solid #bbf7d0;
        }

        .password-toggle {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: var(--transition);
        }

        .toggle-password:hover {
            color: var(--primary-blue);
            background: #f1f5f9;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 0.8rem;
        }

        .strength-weak { color: #dc2626; }
        .strength-medium { color: #d97706; }
        .strength-strong { color: var(--success-green); }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .auth-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <a href="/" class="back-home">
            ← Back to Home
        </a>
        
        <div class="auth-header">
            <h1>Join HireTech</h1>
            <p>Create your account to get started</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error" id="alertBox"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success" id="successAlert"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Google Sign-Up Option -->
        <div class="google-signin-section">
            <a href="/auth/google" class="btn-google">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAxOCAxOCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE2LjUxIDkuMjA0NTVWOS4xMDQ1NUg5LjE4VjEwLjg5NDVIMTMuOTlDMTMuNjcgMTIuNzE0NSAxMi4wOSAxMy45OTQ1IDEwLjE4IDEzLjk5NDVDNy44MyAxMy45OTQ1IDUuOTEgMTIuMDg0NSA1LjkxIDkuNzM0NTVDNS45MSA3LjM4NDU1IDcuODIgNS40NzQ1NSAxMC4xNyA1LjQ3NDU1QzExLjM0IDUuNDc0NTUgMTIuNCA1LjkyNDU1IDEzLjE3IDYuNjg0NTVMMTQuOTIgNC45MzQ1NUMxMy42NCAzLjczNDU1IDExLjkgMy4wMDQ1NSAxMC4xNyAzLjAwNDU1QzYuMzYgMy4wMDQ1NSAzLjI3IDYuMDk0NTUgMy4yNyA5LjkwNDU1QzMuMjcgMTMuNzE0NSA2LjM2IDE2LjgwNDUgMTAuMTcgMTYuODA0NUMxMy42MiAxNi44MDQ1IDE2LjM1IDE0LjI3NDUgMTYuMzUgMTAuMjA0NUMxNi4zNSA5LjU1NDU1IDE2LjI4IDkuMDA0NTUgMTYuNTEgOC40NTQ1NVoiIGZpbGw9IiM0Mjg1RjQiLz4KPC9zdmc+" 
                     alt="Google" width="18" height="18">
                Sign up with Google
            </a>
            <div class="divider">
                <span>or continue with email</span>
            </div>
        </div>

        <!-- Regular Email Registration Form -->
        <form id="registerForm" action="/auth/register" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="role">I am a</label>
                <select id="role" name="role" class="form-control select-control" required>
                    <option value="">Select your role</option>
                    <option value="job_seeker" <?= (($_POST['role'] ?? '') == 'job_seeker') ? 'selected' : '' ?>>Job Seeker</option>
                    <option value="employer" <?= (($_POST['role'] ?? '') == 'employer') ? 'selected' : '' ?>>Employer</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-toggle">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                        👁️
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-toggle">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                        👁️
                    </button>
                </div>
                <div class="password-match" id="passwordMatch"></div>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn">
                Create Account
            </button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="/login">Sign in</a></p>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Alert animation
            const $alertBox = $('#alertBox');
            const $successAlert = $('#successAlert');
            
            if ($alertBox.length) {
                $alertBox.show();
                setTimeout(() => $alertBox.fadeOut(), 5000);
            }
            
            if ($successAlert.length) {
                $successAlert.show();
                // Redirect to login page after 2 seconds if success message is shown
                setTimeout(() => {
                    window.location.href = '/login';
                }, 2000);
            }

            // Password visibility toggle
            $('.toggle-password').click(function() {
                const $password = $(this).siblings('input');
                const type = $password.attr('type') === 'password' ? 'text' : 'password';
                $password.attr('type', type);
                $(this).text(type === 'password' ? '👁️' : '🔒');
            });

            // Password strength indicator
            $('#password').on('input', function() {
                const password = $(this).val();
                const strength = checkPasswordStrength(password);
                $('#passwordStrength').html(strength.text).attr('class', `password-strength ${strength.class}`);
            });

            // Password match indicator
            $('#confirm_password').on('input', function() {
                const password = $('#password').val();
                const confirm = $(this).val();
                
                if (confirm === '') {
                    $('#passwordMatch').html('').removeClass('strength-weak strength-strong');
                    return;
                }
                
                if (password === confirm) {
                    $('#passwordMatch').html('✓ Passwords match').addClass('strength-strong').removeClass('strength-weak');
                } else {
                    $('#passwordMatch').html('✗ Passwords do not match').addClass('strength-weak').removeClass('strength-strong');
                }
            });

            // Form submission
            $('#registerForm').submit(function(e) {
                const $submitBtn = $('#submitBtn');
                const password = $('#password').val();
                const confirm = $('#confirm_password').val();
                
                if (password !== confirm) {
                    e.preventDefault();
                    $('#passwordMatch').html('✗ Passwords must match').addClass('strength-weak');
                    $('#confirm_password').focus();
                    return false;
                }
                
                $submitBtn.addClass('loading').text('Creating Account...');
                return true;
            });

            // Auto-focus name field
            $('#name').focus();

            function checkPasswordStrength(password) {
                if (password.length === 0) {
                    return { text: '', class: '' };
                }
                
                let strength = 0;
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
                if (password.match(/\d/)) strength++;
                if (password.match(/[^a-zA-Z\d]/)) strength++;
                
                if (strength < 2) {
                    return { text: 'Weak password', class: 'strength-weak' };
                } else if (strength < 4) {
                    return { text: 'Medium strength', class: 'strength-medium' };
                } else {
                    return { text: 'Strong password', class: 'strength-strong' };
                }
            }
        });
    </script>
</body>
</html>