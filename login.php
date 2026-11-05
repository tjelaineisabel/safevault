<?php
session_status() === PHP_SESSION_NONE && session_start();
include 'db.php';
$message = "";

if (isset($_POST['login'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password'];
    $mode = $_POST['login_mode'] ?? 'user';

    if ($mode === 'admin') {
        if ($user === 'jelaine123' && $pass === 'isabel123') {
            $_SESSION['user'] = 'jelaine123'; $_SESSION['role'] = 'admin';
            header("Location: index.php"); exit();
        }
        $message = "Access Denied: Invalid Admin Credentials.";
    } else {
        if ($user === 'jelaine123') {
            $message = "Admin must use the Admin Dashboard portal below.";
        } else {
            $res = $conn->query("SELECT * FROM users WHERE username = '$user'");
            if ($res && $u = $res->fetch_assoc()) {
                if ($u['status'] == 'deactivated') {
                    $message = "Account pending approval or deactivated.";
                } elseif (password_verify($pass, $u['password'])) {
                    $_SESSION['user'] = $user; $_SESSION['role'] = 'user';
                    header("Location: user_dashboard.php"); exit();
                } else { $message = "Invalid password."; }
            } else { $message = "User not found."; }
        }
    }
    $message = "<div class='alert alert-danger shadow-sm py-2 small'>$message</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe-Vault | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --vault-blue: #3498db; }
        body { 
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://wallpaperaccess.com/full/3771886.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4); width: 100%; max-width: 400px; padding: 40px; color: white;
        }
        .form-control { background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 10px; color: white; padding: 12px; }
        .form-control:focus { background: rgba(255, 255, 255, 0.25); color: white; border-color: var(--vault-blue); box-shadow: none; }
        
        /* Idinagdag para sa positioning ng eye icon */
        .password-container { position: relative; }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255,255,255,0.6);
            z-index: 10;
        }

        .btn-vault { background: var(--vault-blue); color: white; border: none; border-radius: 10px; padding: 12px; font-weight: bold; transition: 0.3s; }
        .btn-vault:hover { background: #2980b9; transform: translateY(-2px); }
        .btn-admin-link { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); color: white; border-radius: 10px; padding: 10px; text-align: center; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: 0.3s; }
        .btn-admin-link:hover { background: rgba(255, 255, 255, 0.2); }
        a { color: #81d4fa; text-decoration: none; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <h2 class="fw-bold mb-0">Safe-Vault</h2>
        <p id="sub-header" class="text-white-50 small">Secure Document Organizer</p>
    </div>

    <?=$message?>

    <form method="POST">
        <input type="hidden" name="login_mode" id="login_mode" value="user">
        <div class="mb-3">
            <label id="l-user" class="form-label small fw-bold text-white-50">Username</label>
            <input type="text" name="username" class="form-control shadow-sm" placeholder="Enter username" required>
        </div>
        <div class="mb-4">
            <label id="l-pass" class="form-label small fw-bold text-white-50">Password</label>
            <div class="password-container">
                <input type="password" name="password" id="password" class="form-control shadow-sm" placeholder="••••••••" required>
                <i class="bi bi-eye-slash toggle-password" id="eyeIcon" onclick="togglePassword()"></i>
            </div>
        </div>
        <button id="btn-sub" name="login" class="btn btn-vault w-100 mb-3">Sign In</button>
        <div id="signup-box" class="text-center mb-3">
            <span class="small text-white-50">New user?</span> <a href="signup.php" class="small fw-bold">Request Access</a>
        </div>
        <div class="pt-3 border-top border-white border-opacity-10">
            <div id="toggle-admin" class="btn-admin-link" onclick="toggleMode()">Go to Admin Dashboard</div>
        </div>
    </form>
</div>

<script>
// Logic para sa Show/Hide Password
function togglePassword() {
    const passwordField = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");
    
    if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.classList.replace("bi-eye-slash", "bi-eye");
    } else {
        passwordField.type = "password";
        eyeIcon.classList.replace("bi-eye", "bi-eye-slash");
    }
}

let isAdmin = false;
function toggleMode() {
    isAdmin = !isAdmin;
    document.getElementById('login_mode').value = isAdmin ? 'admin' : 'user';
    document.getElementById('l-user').innerText = isAdmin ? 'Admin Username' : 'Username';
    document.getElementById('l-pass').innerText = isAdmin ? 'Admin Password' : 'Password';
    document.getElementById('btn-sub').innerText = isAdmin ? 'Unlock Admin Vault' : 'Sign In';
    document.getElementById('sub-header').innerText = isAdmin ? 'Restricted Admin Access' : 'Secure Document Organizer';
    document.getElementById('signup-box').style.display = isAdmin ? 'none' : 'block';
    document.getElementById('toggle-admin').innerText = isAdmin ? 'Back to User Login' : 'Go to Admin Dashboard';
}
</script>
</body>
</html>