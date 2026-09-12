<?php
session_start();
include 'db_connect.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$records = mysqli_query($conn, "SELECT la.*, u.name as lecturer_name
           FROM lecturer_attendance la
           JOIN users u ON la.lecturer_id = u.id
           WHERE la.date='$filter_date'
           ORDER BY la.entry_time ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Lecturer Log — QR Attendance</title>
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
        .badge-present { background: #dcfce7; color: #16a34a; font-size: 10px; padding: 2px 8px; border-radius: 20px; }
        .badge-pending { background: #fef9c3; color: #ca8a04; font-size: 10px; padding: 2px 8px; border-radius: 20px; }
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

<div class="topbar">
    <div class="topbar-title">QR Attendance System — ATI Kandy</div>
    <div class="d-flex align-items-center gap-3">
        <span style="font-size:12px; color:#cbd5e1;">👤 <?php echo $_SESSION['name']; ?></span>
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

<div class="main">
    <div class="page-title">Lecturer Entry/Exit Log</div>

    <!-- Filter -->
    <div class="card-box">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $filter_date; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filter">Filter</button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card-box">
        <div class="card-box-title">
            Lecturer Log for <?php echo date('d M Y', strtotime($filter_date)); ?>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Lecturer</th>
                    <th>Date</th>
                    <th>Entry Time</th>
                    <th>Exit Time</th>
                    <th>Duration</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                if(mysqli_num_rows($records) > 0){
                    while($row = mysqli_fetch_assoc($records)){
                        $duration = '';
                        if($row['entry_time'] && $row['exit_time']){
                            $diff     = strtotime($row['exit_time']) - strtotime($row['entry_time']);
                            $hours    = floor($diff / 3600);
                            $mins     = floor(($diff % 3600) / 60);
                            $duration = "{$hours}h {$mins}m";
                            $status   = '<span class="badge-present">Completed</span>';
                        } else {
                            $duration = '-';
                            $status   = '<span class="badge-pending">In Class</span>';
                        }
                        $entry = $row['entry_time'] ? date('h:i A', strtotime($row['entry_time'])) : '-';
                        $exit  = $row['exit_time']  ? date('h:i A', strtotime($row['exit_time']))  : '-';
                        echo "<tr>
                            <td>{$i}</td>
                            <td>{$row['lecturer_name']}</td>
                            <td>{$row['date']}</td>
                            <td>{$entry}</td>
                            <td>{$exit}</td>
                            <td>{$duration}</td>
                            <td>{$status}</td>
                        </tr>";
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align:center; color:#94a3b8; padding:20px;'>No records for this date</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
