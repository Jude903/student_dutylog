<?php
session_start();
require_once __DIR__ . '/config/config.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

$error = '';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - Scholar's Duty Log</title>
    <link rel="stylesheet" href="assets/css/login.css" />
</head>
<body class="login-page">
    <div class="split-container">
        <div class="left-side">
            <img src="assets/img/CSDL logo.png" alt="CSDL Logo" class="large-logo" />
            <h2>Center for Student Development and Leadership</h2>
        </div>
        <div class="right-side">
            <img src="assets/img/cocpgp.png" alt="Cagayan de Oro College Logo" class="small-logo" />
            <h3><center>Cagayan de Oro College<br>Puerto Campus</center></h3>
            <h4>Please enter your credentials to access your account</h4>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus />
                </div>
                <div class="form-group password-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required />
                        <button type="button" class="toggle-password" aria-label="Toggle password visibility">&#128065;</button>
                    </div>
                </div>
                <button type="submit" name="login" class="btn login-btn">Login</button>
            </form>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.querySelector('#password');

    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        // Toggle icon (optional)
        this.textContent = type === 'password' ? '\u{1F441}' : '\u{1F576}';
    });
});
</script>
</body>
</html>
