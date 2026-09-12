<?php
session_start();
include 'db_connect.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$report_type  = isset($_GET['type'])  ? $_GET['type']  : 'daily';
$filter_date  = isset($_GET['date'])  ? $_GET['date']  : date('Y-m-d');
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

if($report_type == 'daily'){
    $query = "SELECT a.*, s.name as student_name, s.student_id as student_code,
              se.subject, se.session_date, se.session_time
              FROM attendance a
              JOIN students s ON a.student_id = s.id
              JOIN sessions se ON a.session_id = se.id
              WHERE DATE(se.session_date) = '$filter_date'
              ORDER BY a.marked_at DESC";
    $title = "Daily Report — " . date('d M Y', strtotime($filter_date));

} elseif($report_type == 'weekly'){
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end   = date('Y-m-d', strtotime('sunday this week'));
    $query = "SELECT a.*, s.name as student_name, s.student_id as student_code,
              se.subject, se.session_date, se.session_time
              FROM attendance a
              JOIN students s ON a.student_id = s.id
              JOIN sessions se ON a.session_id = se.id
              WHERE DATE(se.session_date) BETWEEN '$week_start' AND '$week_end'
              ORDER BY se.session_date DESC";
    $title = "Weekly Report — " . date('d M', strtotime($week_start)) . " to " . date('d M Y', strtotime($week_end));

} elseif($report_type == 'monthly'){
    $month_start = $filter_month . '-01';
    $month_end   = date('Y-m-t', strtotime($month_start));
    $query = "SELECT a.*, s.name as student_name, s.student_id as student_code,
              se.subject, se.session_date, se.session_time
              FROM attendance a
              JOIN students s ON a.student_id = s.id
              JOIN sessions se ON a.session_id = se.id
              WHERE DATE(se.session_date) BETWEEN '$month_start' AND '$month_end'
              ORDER BY se.session_date DESC";
    $title = "Monthly Report — " . date('F Y', strtotime($month_start));
}

$records = mysqli_query($conn, $query);
$total   = mysqli_num_rows($records);

$present = 0; $absent = 0;
if($total > 0){
    while($r = mysqli_fetch_assoc($records)){
        if($r['status'] == 'present') $present++;
        else $absent++;
    }
    mysqli_data_seek($records, 0);
}
$percentage = $total > 0 ? round(($present / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports — QR Attendance</title>
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
            margin-bottom: 20px;
        }

        /* Type buttons */
        .type-btns {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        .type-btn {
            padding: 8px 24px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            text-decoration: none;
        }
        .type-btn.active {
            background: #1F4E79;
            color: #fff;
            border-color: #1F4E79;
        }
        .type-btn:hover {
            background: #1F4E79;
            color: #fff;
            border-color: #1F4E79;
        }

        .btn-filter {
            background: #1F4E79;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            height: 38px;
        }
        .btn-export {
            background: #16a34a;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            height: 38px;
            display: inline-flex;
            align-items: center;
        }
        .btn-print {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            height: 38px;
        }

        /* Stat cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 16px 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            text-align: center;
        }
        .stat-value { font-size: 28px; font-weight: 700; }
        .stat-label { font-size: 11px; color: #64748b; margin-top: 4px; }
        .stat-card.blue   .stat-value { color: #1d4ed8; }
        .stat-card.green  .stat-value { color: #16a34a; }
        .stat-card.red    .stat-value { color: #dc2626; }
        .stat-card.orange .stat-value { color: #ea580c; }

        /* Progress */
        .progress-bg {
            background: #e2e8f0;
            border-radius: 10px;
            height: 14px;
            overflow: hidden;
            margin: 8px 0;
        }
        .progress-fill {
            background: linear-gradient(90deg, #16a34a, #22c55e);
            height: 100%;
            border-radius: 10px;
        }
        .progress-label { font-size: 12px; color: #64748b; }

        table { width: 100%; border-collapse: collapse; }
        th {
            font-size: 11px;
            color: #64748b;
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            background: #f8fafc;
        }
        td {
            font-size: 12px;
            color: #334155;
            padding: 10px;
            border-bottom: 0.5px solid #f1f5f9;
        }
        tr:hover td { background: #f8fafc; }

        .badge-present {
            background: #dcfce7;
            color: #16a34a;
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .badge-absent {
            background: #fee2e2;
            color: #dc2626;
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .report-title { font-size: 14px; font-weight: 600; color: #1a1a2e; }

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

        .form-label { font-size: 12px; color: #64748b; margin-bottom: 4px; display: block; }
        .form-control { font-size: 12px; }

        @media print {
            .sidebar, .topbar, .no-print { display: none !important; }
            .main { margin-left: 0 !important; margin-top: 0 !important; }

        }
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
    <div class="page-title">Attendance Reports</div>

    <!-- Type Buttons -->
    <div class="type-btns no-print">
        <a href="reports.php?type=daily&date=<?php echo $filter_date; ?>"
           class="type-btn <?php echo $report_type=='daily' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-day"></i> Daily
        </a>
        <a href="reports.php?type=weekly"
           class="type-btn <?php echo $report_type=='weekly' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-week"></i> Weekly
        </a>
        <a href="reports.php?type=monthly&month=<?php echo $filter_month; ?>"
           class="type-btn <?php echo $report_type=='monthly' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-month"></i> Monthly
        </a>
    </div>

    <!-- Filter -->
    <div class="card-box no-print">
        <form method="GET">
            <input type="hidden" name="type" value="<?php echo $report_type; ?>">
            <div class="row g-3 align-items-end">
                <?php if($report_type == 'daily'): ?>
                <div class="col-md-3">
                    <label class="form-label">Select Date</label>
                    <input type="date" name="date" class="form-control"
                           value="<?php echo $filter_date; ?>">
                </div>
                <?php elseif($report_type == 'monthly'): ?>
                <div class="col-md-3">
                    <label class="form-label">Select Month</label>
                    <input type="month" name="month" class="form-control"
                           value="<?php echo $filter_month; ?>">
                </div>
                <?php else: ?>
                <div class="col-md-3">
                    <label class="form-label">Current Week</label>
                    <input type="text" class="form-control"
                           value="<?php echo date('d M', strtotime($week_start)) . ' — ' . date('d M Y', strtotime($week_end)); ?>"
                           readonly>
                </div>
                <?php endif; ?>

                <div class="col-md-auto d-flex gap-2">
                    <button type="submit" class="btn-filter">
                        <i class="bi bi-search"></i> View
                    </button>
                    <button type="button" class="btn-print" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="export_pdf.php?type=<?php echo $report_type; ?>&date=<?php echo $filter_date; ?>&month=<?php echo $filter_month; ?>"
                       class="btn-export">
                        <i class="bi bi-file-earmark-pdf"></i>&nbsp; Export PDF
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Stat Cards -->
    <div class="stat-grid">
        <div class="stat-card blue">
            <div class="stat-value"><?php echo $total; ?></div>
            <div class="stat-label">Total Records</div>
        </div>
        <div class="stat-card green">
            <div class="stat-value"><?php echo $present; ?></div>
            <div class="stat-label">Present</div>
        </div>
        <div class="stat-card red">
            <div class="stat-value"><?php echo $absent; ?></div>
            <div class="stat-label">Absent</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-value"><?php echo $percentage; ?>%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="card-box">
        <div style="font-size:13px; font-weight:600; color:#1a1a2e; margin-bottom:8px;">
            Attendance Rate
        </div>
        <div class="progress-bg">
            <div class="progress-fill" style="width:<?php echo $percentage; ?>%"></div>
        </div>
        <div class="progress-label">
            <?php echo $percentage; ?>% — <?php echo $present; ?> present out of <?php echo $total; ?> total records
        </div>
    </div>

    <!-- Table -->
    <div class="card-box">
        <div class="report-header">
            <div class="report-title"><?php echo $title; ?></div>
            <div style="font-size:11px; color:#94a3b8;"><?php echo $total; ?> records</div>

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
                    echo "<tr><td colspan='8' style='text-align:center; color:#94a3b8; padding:24px;'>No records found for this period</td></tr>";

                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>