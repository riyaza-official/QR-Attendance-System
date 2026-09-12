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
    $title = "Daily Attendance Report — " . date('d M Y', strtotime($filter_date));

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
    $title = "Weekly Attendance Report — " . date('d M', strtotime($week_start)) . " to " . date('d M Y', strtotime($week_end));

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
    $title = "Monthly Attendance Report — " . date('F Y', strtotime($month_start));
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

// Force download
$filename = 'ATI_Attendance_Report_' . date('Y-m-d') . '.html';
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #333;
        padding: 30px;
        background: #fff;
    }

    /* Header */
    .header {
        text-align: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 3px solid #1F4E79;
    }
    .header h1 {
        color: #1F4E79;
        font-size: 20px;
        margin-bottom: 4px;
    }
    .header h2 {
        color: #333;
        font-size: 14px;
        font-weight: normal;
        margin-bottom: 4px;
    }
    .header p {
        color: #666;
        font-size: 11px;
    }

    /* Stats */
    .stats {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
    }
    .stat-box {
        flex: 1;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
    }
    .stat-box .val {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 4px;
    }
    .stat-box .lbl {
        font-size: 10px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-box.blue   { border-color: #1d4ed8; }
    .stat-box.blue   .val { color: #1d4ed8; }
    .stat-box.green  { border-color: #16a34a; }
    .stat-box.green  .val { color: #16a34a; }
    .stat-box.red    { border-color: #dc2626; }
    .stat-box.red    .val { color: #dc2626; }
    .stat-box.orange { border-color: #ea580c; }
    .stat-box.orange .val { color: #ea580c; }

    /* Progress */
    .progress-section {
        margin-bottom: 20px;
        padding: 12px 16px;
        background: #f8fafc;
        border-radius: 8px;
    }
    .progress-title {
        font-size: 12px;
        font-weight: 600;
        color: #1a1a2e;
        margin-bottom: 8px;
    }
    .progress-bg {
        background: #e2e8f0;
        border-radius: 10px;
        height: 14px;
        overflow: hidden;
        margin-bottom: 6px;
    }
    .progress-fill {
        background: #16a34a;
        height: 100%;
        border-radius: 10px;
    }
    .progress-label {
        font-size: 11px;
        color: #64748b;
    }

    /* Table */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }
    thead tr {
        background: #1F4E79;
        color: #fff;
    }
    th {
        padding: 10px 12px;
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    td {
        padding: 9px 12px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 11px;
    }
    tr:nth-child(even) td { background: #f8fafc; }
    tr:hover td { background: #f1f5f9; }

    .present {
        background: #dcfce7;
        color: #16a34a;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
    }
    .absent {
        background: #fee2e2;
        color: #dc2626;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
    }

    /* Footer */
    .footer {
        margin-top: 24px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
        text-align: center;
        font-size: 10px;
        color: #94a3b8;
        line-height: 1.8;
    }

    .report-info {
        margin-bottom: 16px;
        padding: 10px 14px;
        background: #dbeafe;
        border-radius: 6px;
        font-size: 11px;
        color: #1d4ed8;
    }
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <h1>QR Code Attendance Management System</h1>
    <h2>Advanced Technological Institute, Kandy (Dambawela)</h2>
    <p><?php echo $title; ?></p>
    <p>Generated on: <?php echo date('d M Y — h:i A'); ?></p>
</div>

<!-- Report Info -->
<div class="report-info">
    📊 Report Type: <strong><?php echo ucfirst($report_type); ?></strong>
    &nbsp;|&nbsp;
    Generated by: <strong><?php echo $_SESSION['name']; ?></strong>
    &nbsp;|&nbsp;
    Role: <strong><?php echo ucfirst($_SESSION['role']); ?></strong>
</div>

<!-- Stats -->
<div class="stats">
    <div class="stat-box blue">
        <div class="val"><?php echo $total; ?></div>
        <div class="lbl">Total Records</div>
    </div>
    <div class="stat-box green">
        <div class="val"><?php echo $present; ?></div>
        <div class="lbl">Present</div>
    </div>
    <div class="stat-box red">
        <div class="val"><?php echo $absent; ?></div>
        <div class="lbl">Absent</div>
    </div>
    <div class="stat-box orange">
        <div class="val"><?php echo $percentage; ?>%</div>
        <div class="lbl">Attendance Rate</div>
    </div>
</div>

<!-- Progress -->
<div class="progress-section">
    <div class="progress-title">Attendance Rate — <?php echo $percentage; ?>%</div>
    <div class="progress-bg">
        <div class="progress-fill" style="width:<?php echo $percentage; ?>%"></div>
    </div>
    <div class="progress-label">
        <?php echo $present; ?> present out of <?php echo $total; ?> total records
    </div>
</div>

<!-- Table -->
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
                $status = $row['status'] == 'present' ?
                    '<span class="present">Present</span>' :
                    '<span class="absent">Absent</span>';
                echo "<tr>
                    <td>{$i}</td>
                    <td>{$row['student_code']}</td>
                    <td>{$row['student_name']}</td>
                    <td>{$row['subject']}</td>
                    <td>{$row['session_date']}</td>
                    <td>{$row['session_time']}</td>
                    <td>{$row['marked_at']}</td>
                    <td>{$status}</td>
                </tr>";
                $i++;
            }
        } else {
            echo "<tr><td colspan='8' style='text-align:center; padding:20px; color:#999;'>
                  No attendance records found for this period
                  </td></tr>";
        }
        ?>
    </tbody>
</table>

<!-- Footer -->
<div class="footer">
    <strong>QR Code Based Student Attendance Management System</strong><br>
    Advanced Technological Institute, Kandy (Dambawela)<br>
    Developer: Riyaza Mahir — KAN/IT/202324/F/0204<br>
    Generated: <?php echo date('d M Y h:i A'); ?>
</div>

</body>
</html>