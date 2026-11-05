<?php
include 'db.php';
$message = "";

if (isset($_POST['signup'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // 1. Check if username already exists in the system
    $checkUser = "SELECT status FROM users WHERE username = '$user'";
    $result = $conn->query($checkUser);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['status'] == 'deactivated') {
            $message = "<div class='alert alert-warning shadow-sm py-2 small'>
                            <strong>Account already exists.</strong><br>
                            It is currently pending approval from the admin.
                        </div>";
        } else {
            $message = "<div class='alert alert-success shadow-sm py-2 small'>
                            <strong>Account is Active!</strong><br>
                            You already have access. <a href='login.php' class='alert-link'>Click here to Login</a>.
                        </div>";
        }
    } else {
        // 2. New Registration (Set to deactivated by default)
        $sql = "INSERT INTO users (username, password, attempts, status) 
                VALUES ('$user', '$pass', 0, 'deactivated')";
        
        if ($conn->query($sql)) {
            $message = "<div class='alert alert-info shadow-sm py-2 small'>
                            <strong>Request Sent!</strong><br>
                            Your details are submitted. Please wait for the admin to activate your account.
                        </div>";
        } else {
            $message = "<div class='alert alert-danger shadow-sm py-2 small'>System Error: " . $conn->error . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe-Vault | Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --vault-blue: #3498db; }
        body { 
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://wallpaperaccess.com/full/3771886.jpg');
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed;
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0; 
            font-family: 'Segoe UI', sans-serif;
        }
        .signup-card {
            background: rgba(255, 255, 255, 0.1); 
            backdrop-filter: blur(15px); 
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2); 
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4); 
            width: 100%; 
            max-width: 400px; 
            padding: 40px; 
            color: white; 
        }
        .brand-logo { font-weight: 800; letter-spacing: 1px; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
        .form-control { 
            background: rgba(255, 255, 255, 0.15); 
            border: 1px solid rgba(255, 255, 255, 0.3); 
            border-radius: 10px; 
            padding: 12px; 
            color: white; 
        }
        .form-control:focus { 
            background: rgba(255, 255, 255, 0.25); 
            border-color: var(--vault-blue); 
            color: white; 
            box-shadow: none; 
        }
        .btn-vault { 
            background-color: #27ae60; 
            color: white; 
            border: none; 
            border-radius: 10px; 
            padding: 12px; 
            font-weight: bold; 
            transition: 0.3s; 
        }
        .btn-vault:hover { 
            background-color: #219150; 
            transform: translateY(-2px); 
        }
        .btn-admin-link { 
            background: rgba(52, 152, 219, 0.2); 
            border: 1px solid var(--vault-blue); 
            color: white; 
            border-radius: 10px; 
            padding: 10px; 
            text-decoration: none; 
            display: block; 
            text-align: center; 
            font-weight: 600; 
            font-size: 0.85rem;
            transition: 0.3s; 
        }
        .btn-admin-link:hover { background: var(--vault-blue); color: white; }
        a { color: #81d4fa; text-decoration: none; }
    </style>
</head>
<body>

<div class="signup-card shadow-lg">
    <div class="text-center mb-4">
        <h2 class="brand-logo mb-0">Join Vault</h2>
        <p class="text-white-50 small">New user registration</p>
    </div>

    <?php if(!empty($message)) echo $message; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold text-white-50">Username</label>
            <input type="text" name="username" class="form-control shadow-sm" placeholder="Choose a username" required>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold text-white-50">Password</label>
            <input type="password" name="password" class="form-control shadow-sm" placeholder="••••••••" required>
        </div>
        
        <button name="signup" class="btn btn-vault w-100 mb-3 shadow">Submit Request</button>
        
        <div class="text-center mb-3">
            <a href="login.php" class="small text-white-50">Existing User? <strong class="text-white">Login</strong></a>
        </div>

        <div class="pt-3 border-top border-white border-opacity-10">
            <a href="index.php" class="btn-admin-link">Go to Admin Dashboard</a>
        </div>
    </form>
</div>

</body>
</html>