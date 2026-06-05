<?php
session_start();
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = 'Akses Ditolak: Kredensial Tidak Valid!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WayDash - Login</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            /* Gradasi warna gelap (Midnight Blue & Slate) */
            background: linear-gradient(-45deg, #020617, #0f172a, #172554, #1e293b);
            background-size: 300% 300%;
            /* Animasi 6 detik */
            animation: gradientBG 6s ease infinite;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; padding: 20px;
        }
        @keyframes gradientBG { 
            0% { background-position: 0% 50%; } 
            50% { background-position: 100% 50%; } 
            100% { background-position: 0% 50%; } 
        }
        
        .login-card {
            background: #f8fafc;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); 
            width: 100%; max-width: 420px;
            padding: 3rem 2.5rem;
            text-align: center;
        }
        .logo-container img { 
            width: 100%; max-width: 220px; height: auto; 
            margin-bottom: 1.5rem; 
        }
        
        /* Custom Input Group */
        .custom-input-group {
            display: flex;
            align-items: center;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.2rem 0.5rem;
            transition: 0.3s;
        }
        .custom-input-group:focus-within {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        }
        .custom-input-group i {
            color: #64748b;
            padding: 0 10px;
            font-size: 1.1rem;
        }
        .custom-input-group input {
            border: none;
            background: transparent;
            padding: 0.75rem 0.5rem;
            width: 100%;
            outline: none;
            color: #1e293b;
            font-size: 0.95rem;
        }
        .custom-input-group input::placeholder {
            color: #94a3b8;
        }
        
        .btn-login {
            background: #0ea5e9;
            border: none; border-radius: 8px; padding: 0.85rem; font-weight: 700;
            font-size: 1rem; letter-spacing: 0.5px; transition: all 0.3s; margin-top: 0.5rem;
        }
        .btn-login:hover { 
            background: #0284c7; 
            transform: translateY(-2px); 
            box-shadow: 0 8px 15px rgba(14, 165, 233, 0.3); 
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-container">
        <img src="logo_waydash.png" alt="WayDash Logo">
    </div>
    
    <h4 class="fw-bold text-dark mb-1">Way Reporting</h4>
    <p class="text-muted small mb-4">Way DashBoard Provisioning Southern Area</p>

    <?php if($error): ?>
        <div class="alert alert-danger py-2 border-0 shadow-sm rounded-3 text-center small fw-bold"><i class="bi bi-shield-x me-1"></i> <?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="custom-input-group mb-3">
            <i class="bi bi-person-badge"></i>
            <input type="text" id="username" name="username" placeholder="ID Karyawan" required autocomplete="off">
        </div>
        
        <div class="custom-input-group mb-4">
            <i class="bi bi-key"></i>
            <input type="password" id="password" name="password" placeholder="Passcode" required>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 btn-login text-white">
            <i class="bi bi-box-arrow-in-right me-1"></i> LOGIN
        </button>
    </form>
    
    <div class="mt-4 pt-3 border-top">
        <small class="text-muted" style="font-size: 0.75rem;">&copy; 2026 WayDash.<br>DashBoard Provisioning Southern Area</small>
    </div>
</div>

</body>
</html>