<?php
// login.php

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO('mysql:host=localhost;dbname=npa_training', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize error variable
$error = null;

// Rate limiting to prevent brute force attacks
$max_attempts = 5; // Maximum allowed login attempts
$lockout_time = 300; // Lockout time in seconds (5 minutes)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Trim and sanitize user input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if the user is locked out
    if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_attempts) {
        $error = "Too many failed login attempts. Please try again later.";
    } else {
        // Validate non-empty input
        if (empty($username) || empty($password)) {
            $error = "Both username and password are required!";
        } else {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = "Invalid username or password! (User not found)";
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            } else {
                if (password_verify($password, $user['password'])) {
                    // Reset login attempts on successful login
                    unset($_SESSION['login_attempts']);

                    // Check if 2FA is enabled for the user
                    if ($user['2fa_enabled']) {
                        // Redirect to 2FA verification page
                        $_SESSION['2fa_user_id'] = $user['id'];
                        header('Location: verify_2fa.php');
                        exit;
                    } else {
                        // Log the user in
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        header('Location: admin_dashboard.php');
                        exit;
                    }
                } else {
                    $error = "Invalid username or password! (Password mismatch)";
                    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NPA Training Portal</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Custom Styles -->
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('npa.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 40px 50px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container h2 {
            font-size: 32px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 25px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
            text-align: left;
        }

        .form-group label {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            font-weight: 600;
            color: #ccc;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 50px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            font-size: 16px;
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.5);
        }

        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label {
            top: 0;
            left: 10px;
            font-size: 12px;
            color: #3498db;
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 5px;
            border-radius: 5px;
        }

        .error-message {
            background: #e74c3c;
            color: #fff;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            50% { transform: translateX(10px); }
            75% { transform: translateX(-10px); }
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            background: linear-gradient(135deg, #3498db, #6dd5ed);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #2980b9, #5cb8d6);
            transform: translateY(-2px);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 25px;
            font-size: 14px;
            color: #ccc;
        }

        .login-footer a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px;
            }

            .login-container h2 {
                font-size: 28px;
            }

            .form-group input {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Admin Login</h2>
    <?php if (isset($error)): ?>
        <div class="error-message"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <input type="text" id="username" name="username" placeholder=" " required>
            <label for="username">Username</label>
        </div>
        <div class="form-group">
            <input type="password" id="password" name="password" placeholder=" " required>
            <label for="password">Password</label>
        </div>
        <button type="submit" class="login-btn">Login</button>
    </form>
    <div class="login-footer">
        <p>Forgot password? <a href="reset_password.php">Click here</a></p>
    </div>
</div>

</body>
</html>