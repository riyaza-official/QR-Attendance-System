<?php
session_start();
include 'db_connect.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Get counts for stat cards
$today = date('Y-m-d');

if($_SESSION['role'] == 'admin'){
    $total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students"))['total'];
    $total_sessions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM sessions"))['total'];
    $today_present  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance a JOIN sessions se ON a.session_id=se.id WHERE DATE(se.session_date)='$today'"))['total'];
    $today_absent   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance a JOIN sessions se ON a.session_id=se.id WHERE DATE(se.session_date)='$today' AND a.status='absent'"))['total'];
} else {
    // Lecturer - show only their subject stats
    $lecturer_id    = $_SESSION['user_id'];
    $lecturer_info  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$lecturer_id'"));
    $total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students"))['total'];
    $total_sessions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM sessions WHERE lecturer_id='$lecturer_id'"))['total'];
    $today_present  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance a JOIN sessions se ON a.session_id=se.id WHERE DATE(se.session_date)='$today' AND se.lecturer_id='$lecturer_id'"))['total'];
    $today_absent   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance a JOIN sessions se ON a.session_id=se.id WHERE DATE(se.session_date)='$today' AND se.lecturer_id='$lecturer_id' AND a.status='absent'"))['total'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard — QR Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; }

        /* Topbar */
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
        .topbar-user  { font-size: 12px; color: #cbd5e1; }

        /* Sidebar */
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
            cursor: pointer;
        }
        .sidebar-item:hover { background: #1F4E79; color: #fff; }
        .sidebar-item.active { background: #1F4E79; color: #fff; }
        .sidebar-item i { font-size: 15px; }

        /* Main content */
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

        /* Stat cards */
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .stat-icon.blue   { background: #dbeafe; color: #1d4ed8; }
        .stat-icon.green  { background: #dcfce7; color: #16a34a; }
        .stat-icon.orange { background: #ffedd5; color: #ea580c; }
        .stat-icon.red    { background: #fee2e2; color: #dc2626; }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1;
        }
        .stat-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Table card */
        .card-box {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            margin-top: 24px;
        }
        .card-box-title {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 14px;
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            font-size: 11px;
            color: #64748b;
            text-align: left;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }
        td {
            font-size: 12px;
            color: #334155;
            padding: 10px;
            border-bottom: 0.5px solid #f1f5f9;
        }
        .badge-active {
            background: #dcfce7;
            color: #16a34a;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }
        .badge-ended {
            background: #f1f5f9;
            color: #64748b;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
        }

        .logout-btn {
            background: none;
            border: 1px solid #475569;
            color: #cbd5e1;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .logout-btn:hover { background: #dc2626; border-color: #dc2626; color: #fff; }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-title">QR Attendance System — ATI Kandy</div>
    <div class="d-flex align-items-center gap-3">
        <span class="topbar-user">👤 <?php echo $_SESSION['name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
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
<!-- Main Content -->
<div class="main">
    <div class="page-title">Dashboard</div>
    
<?php if($_SESSION['role'] == 'lecturer' && isset($lecturer_info)): ?>
<div style="background:#dbeafe; border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:13px; color:#1d4ed8;">
    <i class="bi bi-person-badge"></i>
    <strong>Welcome, <?php echo $lecturer_info['name']; ?>!</strong>
    &nbsp;|&nbsp;
    Your Subject: <strong><?php echo $lecturer_info['subject'] ? $lecturer_info['subject'] : 'Not assigned'; ?></strong>
</div>
<?php endif; ?>

    <!-- Stat Cards -->
    <div class="row g-3">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
                <div>
                    <div class="stat-value"><?php echo $today_present; ?></div>
                    <div class="stat-label">Today Present</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon orange"><i class="bi bi-qr-code-scan"></i></div>
                <div>
                    <div class="stat-value"><?php echo $total_sessions; ?></div>
                    <div class="stat-label">Total Sessions</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="bi bi-person-x-fill"></i></div>
                <div>
                    <div class="stat-value"><?php echo $today_absent; ?></div>
                    <div class="stat-label">Absent Today</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sessions Table -->
    <div class="card-box">
        <div class="card-box-title">Recent Sessions</div>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sessions = mysqli_query($conn, "SELECT * FROM sessions ORDER BY created_at DESC LIMIT 5");
                if(mysqli_num_rows($sessions) > 0){
                    while($row = mysqli_fetch_assoc($sessions)){
                        $status = (strtotime($row['expires_at']) > time()) ?
                            '<span class="badge-active">Active</span>' :
                            '<span class="badge-ended">Ended</span>';
                        echo "<tr>
                            <td>{$row['subject']}</td>
                            <td>{$row['session_date']}</td>
                            <td>{$row['session_time']}</td>
                            <td>{$status}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center; color:#94a3b8; padding:20px;'>No sessions yet</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>