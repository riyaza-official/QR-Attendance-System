<?php
session_start();
include 'db_connect.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Filter
$filter_date    = isset($_GET['date'])    ? $_GET['date']    : date('Y-m-d');
$filter_subject = isset($_GET['subject']) ? $_GET['subject'] : '';

// Build query
$query = "SELECT a.*, s.name as student_name, s.student_id as student_code,
          se.subject, se.session_date, se.session_time
          FROM attendance a
          JOIN students s ON a.student_id = s.id
          JOIN sessions se ON a.session_id = se.id
          WHERE DATE(se.session_date) = '$filter_date'";

if($filter_subject != ''){
    $query .= " AND se.subject LIKE '%$filter_subject%'";
}
$query .= " ORDER BY a.marked_at DESC";

$records = mysqli_query($conn, $query);
$total   = mysqli_num_rows($records);

// Get all subjects for filter
$subjects = mysqli_query($conn, "SELECT DISTINCT subject FROM sessions ORDER BY subject");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance — QR Attendance</title>
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
        .topbar-user  { font-size: 12px; color: #cbd5e1; }

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
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }
        .card-box-title {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 14px;
        }

        .form-label { font-size: 12px; color: #64748b; }
        .form-control { font-size: 12px; }

        .btn-filter {
            background: #1F4E79;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }
        .btn-filter:hover { background: #185FA5; }

        .btn-reset {
            background: #f1f5f9;
            color: #64748b;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
        }

        /* Stat row */
        .stat-row {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-mini {
            background: #fff;
            border-radius: 8px;
            padding: 14px 18px;
            flex: 1;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .stat-mini-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .stat-mini-icon.green  { background: #dcfce7; color: #16a34a; }
        .stat-mini-icon.red    { background: #fee2e2; color: #dc2626; }
        .stat-mini-icon.blue   { background: #dbeafe; color: #1d4ed8; }
        .stat-mini-value { font-size: 20px; font-weight: 700; color: #1a1a2e; }
        .stat-mini-label { font-size: 11px; color: #64748b; }

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

        .badge-present {
            background: #dcfce7;
            color: #16a34a;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }
        .badge-absent {
            background: #fee2e2;
            color: #dc2626;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
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
        .logout-btn:hover { background: #dc2626; border-color: #dc2626; color:#fff; }
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

<!-- Main -->
<div class="main">
    <div class="page-title">Attendance Records</div>

    <!-- Filter -->
    <div class="card-box">
        <div class="card-box-title">Filter Attendance</div>
        <form method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control"
                           value="<?php echo $filter_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subject</label>
                    <select name="subject" class="form-control">
                        <option value="">All Subjects</option>
                        <?php while($sub = mysqli_fetch_assoc($subjects)): ?>
                            <option value="<?php echo $sub['subject']; ?>"
                                <?php echo ($filter_subject == $sub['subject']) ? 'selected' : ''; ?>>
                                <?php echo $sub['subject']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn-filter">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="attendance.php" class="btn-reset">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Stats -->
    <?php
    $present = mysqli_num_rows(mysqli_query($conn, "SELECT a.id FROM attendance a JOIN sessions se ON a.session_id = se.id WHERE DATE(se.session_date)='$filter_date' AND a.status='present'"));
    $absent  = mysqli_num_rows(mysqli_query($conn, "SELECT a.id FROM attendance a JOIN sessions se ON a.session_id = se.id WHERE DATE(se.session_date)='$filter_date' AND a.status='absent'"));
    ?>
    <div class="stat-row">
        <div class="stat-mini">
            <div class="stat-mini-icon blue"><i class="bi bi-list-check"></i></div>
            <div>
                <div class="stat-mini-value"><?php echo $total; ?></div>
                <div class="stat-mini-label">Total Records</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon green"><i class="bi bi-person-check"></i></div>
            <div>
                <div class="stat-mini-value"><?php echo $present; ?></div>
                <div class="stat-mini-label">Present</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-icon red"><i class="bi bi-person-x"></i></div>
            <div>
                <div class="stat-mini-value"><?php echo $absent; ?></div>
                <div class="stat-mini-label">Absent</div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card-box">
        <div class="card-box-title">
            Attendance for <?php echo date('d M Y', strtotime($filter_date)); ?>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Marked At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                if($total > 0){
                    mysqli_data_seek($records, 0);
                    while($row = mysqli_fetch_assoc($records)){
                        $badge = $row['status'] == 'present' ?
                            '<span class="badge-present">Present</span>' :
                            '<span class="badge-absent">Absent</span>';
                        echo "<tr>
                            <td>{$i}</td>
                            <td>{$row['student_code']}</td>
                            <td>{$row['student_name']}</td>
                            <td>{$row['subject']}</td>
                            <td>{$row['session_date']}</td>
                            <td>{$row['session_time']}</td>
                            <td>{$row['marked_at']}</td>
                            <td>{$badge}</td>
                        </tr>";
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center; color:#94a3b8; padding:20px;'>No attendance records for this date</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>