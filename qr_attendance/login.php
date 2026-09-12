<?php
session_start();
include 'db_connect.php';

$error = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role     = $_POST['role'];

    $query  = "SELECT * FROM users WHERE name='$username'";
    $result = mysqli_query($conn, $query);
    $user   = mysqli_fetch_assoc($result);

 
    if($user && password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Attendance System — ATI Kandy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f1f5f9; }

        .topbar {
            background: #1F4E79;
            padding: 10px 16px;
            display: flex;
            align-items: center;
        }
        .topbar-title {
            color: #fff;
            font-size: 13px;
            font-weight: 500;
        }

        .login-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 42px);
            background: #f1f5f9;
        }

        .login-card {
            background: #fff;
            border: 0.5px solid #dde1e7;
            border-radius: 12px;
            padding: 28px 32px;
            width: 300px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-circle {
            width: 48px;
            height: 48px;
            background: #1F4E79;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }
        .logo-circle svg { width: 24px; height: 24px; fill: #fff; }

        .login-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a2e;
            text-align: center;
            margin-bottom: 4px;
        }
        .login-sub {
            font-size: 12px;
            color: #6b7280;
            text-align: center;
            margin-bottom: 20px;
        }

        .radio-group {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        .radio-opt {
            flex: 1;
            border: 0.5px solid #dde1e7;
            border-radius: 6px;
            padding: 7px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            cursor: pointer;
        }
        .radio-opt.active {
            border-color: #185FA5;
            background: #E6F1FB;
            color: #185FA5;
            font-weight: 500;
        }

        .form-label {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 4px;
            display: block;
        }
        .form-input {
            width: 100%;
            padding: 7px 10px;
            font-size: 12px;
            border: 0.5px solid #dde1e7;
            border-radius: 6px;
            background: #f8fafc;
            color: #1a1a2e;
            margin-bottom: 12px;
            outline: none;
        }
        .form-input:focus { border-color: #185FA5; }

        .btn-login {
            width: 100%;
            padding: 8px;
            background: #1F4E79;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 4px;
        }
        .btn-login:hover { background: #185FA5; }

        .error-msg {
            background: #FCEBEB;
            color: #A32D2D;
            font-size: 12px;
            padding: 8px 10px;
            border-radius: 6px;
            margin-bottom: 12px;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
    <span class="topbar-title">QR Attendance System — ATI Kandy</span>
</div>

<!-- Login -->
<div class="login-wrap">
    <div class="login-card">

        <!-- Logo -->
        <div class="login-logo">
            <div class="logo-circle">
                <!-- QR icon -->
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 3h7v7H3V3zm1 1v5h5V4H4zm1 1h3v3H5V5zM14 3h7v7h-7V3zm1 1v5h5V4h-5zm1 1h3v3h-3V5zM3 14h7v7H3v-7zm1 1v5h5v-5H4zm1 1h3v3H5v-3zm9-1h2v2h-2v-2zm2 2h2v2h-2v-2zm2 2h2v2h-2v-2zm-4 0h2v2h-2v-2zm2 2h2v2h-2v-2zm2-6h2v2h-2v-2zm2 2h-2v2h2v-2z"/>
                </svg>
            </div>
            <div class="login-title">QR Attendance System</div>
            <div class="login-sub">Advanced Technological Institute</div>
        </div>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">

            <!-- Admin / Lecturer Toggle -->
            <div class="radio-group">
                <div class="radio-opt active" onclick="selectRole('admin', this)">Admin</div>
                <div class="radio-opt" onclick="selectRole('lecturer', this)">Lecturer</div>
            </div>
            <input type="hidden" name="role" id="roleInput" value="admin">

            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-input"
                   placeholder="Enter username" required>

            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-input"
                   placeholder="••••••••" required>

            <button type="submit" class="btn-login">Login</button>
        </form>

    </div>
</div>

<script>
function selectRole(role, el) {
    document.querySelectorAll('.radio-opt')
            .forEach(r => r.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('roleInput').value = role;
}
</script>

</body>
</html>