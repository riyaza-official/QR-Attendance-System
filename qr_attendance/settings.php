<?php
session_start();
include 'db_connect.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Get current user
$user_id = $_SESSION['user_id'];
$user    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'"));

// Update profile
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])){
    $name  = $_POST['name'];
    $email = $_POST['email'];

    mysqli_query($conn, "UPDATE users SET name='$name', email='$email' WHERE id='$user_id'");
    $_SESSION['name'] = $name;
    $success = "Profile updated successfully!";
    $user    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'"));
}

// Change password
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])){
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    if(password_verify($current, $user['password'])){
        if($new == $confirm){
            if(strlen($new) >= 6){
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id='$user_id'");
                $pwd_success = "Password changed successfully!";
            } else {
                $pwd_error = "Password must be at least 6 characters!";
            }
        } else {
            $pwd_error = "New passwords do not match!";
        }
    } else {
        $pwd_error = "Current password is incorrect!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings — QR Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; }

        .topbar {
            background: #1F4E79;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            height: 50px;
        }
        .topbar-title { font-size: 14px; font-weight: 600; }

        .sidebar {
            position: fixed;
            top: 50px; left: 0;
            width: 200px;
            height: calc(100vh - 50px);
            background: #1a2533;
            padding: 16px 0;
            z-index: 99;
        }
        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: #94a3b8;
            font-size: 13px;
            text-decoration: none;
        }
        .sidebar-item:hover { background: #1F4E79; color: #fff; }
        .sidebar-item.active { background: #1F4E79; color: #fff; }
        .sidebar-item i { font-size: 15px; }

        .main {
            margin-left: 200px;
            margin-top: 50px;
            padding: 24px;
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 20px;
        }

        .card-box {
            background: #fff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .card-box-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-box-title i { color: #1F4E79; }

        .form-label { font-size: 12px; color: #64748b; margin-bottom: 4px; }
        .form-control { font-size: 12px; }

        .btn-save {
            background: #1F4E79;
            color: #fff;
            border: none;
            padding: 9px 24px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 8px;
        }
        .btn-save:hover { background: #185FA5; }

        /* Profile avatar */
        .profile-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #1F4E79;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #fff;
            margin-bottom: 16px;
        }

        .role-badge {
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 16px;
            text-transform: capitalize;
        }

        /* System info */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 0.5px solid #f1f5f9;
            font-size: 12px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; }
        .info-value { color: #1a1a2e; font-weight: 500; }
        .info-value.green { color: #16a34a; }

        .logout-btn {
            background: none;
            border: 1px solid #475569;
            color: #cbd5e1;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .logout-btn:hover { background: #dc2626; border-color: #dc2626; color:#fff; }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-title">QR Attendance System — ATI Kandy</div>
    <div class="d-flex align-items-center gap-3">
        <span style="font-size:12px; color:#cbd5e1;">👤 <?php echo $_SESSION['name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
        <a href="logout.php"><button class="logout-btn">Logout</button></a>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <a href="dashboard.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='dashboard.php' ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <?php if($_SESSION['role'] == 'admin'): ?>
        <a href="students.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='students.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Students
        </a>
        <a href="generate_qr.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='generate_qr.php' ? 'active' : ''; ?>">
            <i class="bi bi-qr-code"></i> Generate QR
        </a>
        <a href="attendance.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='attendance.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-check"></i> Attendance
        </a>
        <a href="reports.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='reports.php' ? 'active' : ''; ?>">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
        <a href="lecturer_qr.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='lecturer_qr.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-badge"></i> Lecturer QR
        </a>
        <a href="lecturer_attendance.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='lecturer_attendance.php' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i> Lecturer Log
        </a>
        <a href="settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='settings.php' ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i> Settings
        </a>

    <?php elseif($_SESSION['role'] == 'lecturer'): ?>
        <a href="generate_qr.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='generate_qr.php' ? 'active' : ''; ?>">
            <i class="bi bi-qr-code"></i> Generate QR
        </a>
        <a href="attendance.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='attendance.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-check"></i> Attendance
        </a>
        <a href="settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF'])=='settings.php' ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
    <?php endif; ?>

    <a href="logout.php" class="sidebar-item"
       style="position:absolute; bottom:20px; width:100%;">
        <i class="bi bi-box-arrow-left"></i> Logout
    </a>
</div>

<!-- Main -->
<div class="main">
    <div class="page-title">Settings</div>

    <div class="row">
        <div class="col-md-4">

            <!-- Profile Card -->
            <div class="card-box" style="text-align:center;">
                <div class="profile-avatar" style="margin: 0 auto 12px;">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div style="font-size:16px; font-weight:600; color:#1a1a2e;">
                    <?php echo $user['name']; ?>
                </div>
                <div style="font-size:12px; color:#64748b; margin:4px 0 8px;">
                    <?php echo $user['email']; ?>
                </div>
                <div class="role-badge"><?php echo $user['role']; ?></div>
            </div>

            <!-- System Info -->
            <div class="card-box">
                <div class="card-box-title">
                    <i class="bi bi-info-circle"></i> System Info
                </div>
                <div class="info-row">
                    <span class="info-label">System</span>
                    <span class="info-value">QR Attendance</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Institution</span>
                    <span class="info-value">ATI Kandy</span>
                </div>
                <div class="info-row">
                    <span class="info-label">PHP Version</span>
                    <span class="info-value"><?php echo phpversion(); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Database</span>
                    <span class="info-value green">Connected ✅</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Server</span>
                    <span class="info-value">XAMPP localhost</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Developer</span>
                    <span class="info-value">Riyaza Mahir</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Student ID</span>
                    <span class="info-value">KAN/IT/202324/F/0204</span>
                </div>
            </div>

        </div>

        <div class="col-md-8">

            <!-- Update Profile -->
            <div class="card-box">
                <div class="card-box-title">
                    <i class="bi bi-person-gear"></i> Update Profile
                </div>

                <?php if(isset($success)): ?>
                    <div class="alert alert-success" style="font-size:12px; margin-bottom:16px;">
                        ✅ <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?php echo $user['name']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?php echo $user['email']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control"
                                   value="<?php echo ucfirst($user['role']); ?>" readonly
                                   style="background:#f8fafc;">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="card-box">
                <div class="card-box-title">
                    <i class="bi bi-shield-lock"></i> Change Password
                </div>

                <?php if(isset($pwd_success)): ?>
                    <div class="alert alert-success" style="font-size:12px; margin-bottom:16px;">
                        ✅ <?php echo $pwd_success; ?>
                    </div>
                <?php endif; ?>
                <?php if(isset($pwd_error)): ?>
                    <div class="alert alert-danger" style="font-size:12px; margin-bottom:16px;">
                        ❌ <?php echo $pwd_error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password"
                                   class="form-control" placeholder="Enter current password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password"
                                   class="form-control" placeholder="Enter new password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password"
                                   class="form-control" placeholder="Confirm new password" required>
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn-save">
                        <i class="bi bi-shield-check"></i> Change Password
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

</body>
</html>