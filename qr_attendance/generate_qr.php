<?php
session_start();
include 'db_connect.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Generate QR
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])){
    $subject      = $_POST['subject'];
    $session_date = $_POST['session_date'];
    $session_time = $_POST['session_time'];
    $duration     = $_POST['duration']; // minutes
    $lecturer_id  = $_SESSION['user_id'];

    // Generate unique token
    $token      = md5(uniqid(rand(), true));
    $expires_at = date('Y-m-d H:i:s', strtotime("+$duration minutes"));

    $query = "INSERT INTO sessions (subject, lecturer_id, qr_token, session_date, session_time, expires_at)
              VALUES ('$subject', '$lecturer_id', '$token', '$session_date', '$session_time', '$expires_at')";

    if(mysqli_query($conn, $query)){
        $session_id  = mysqli_insert_id($conn);
        $qr_data     = "http://localhost/qr_attendance/scan_qr.php?token=$token";
        $success     = true;
    } else {
        $error = "Error generating QR!";
    }
}

// Get recent sessions
$sessions = mysqli_query($conn, "SELECT s.*, u.name as lecturer_name FROM sessions s JOIN users u ON s.lecturer_id = u.id ORDER BY s.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate QR — QR Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- QR Code library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
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

        .btn-generate {
            background: #1F4E79;
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            margin-top: 8px;
        }
        .btn-generate:hover { background: #185FA5; }

        /* QR Display */
        .qr-display {
            text-align: center;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }
        .qr-display h5 {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 6px;
        }
        .qr-display p {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 20px;
        }
        #qrcode {
            display: inline-block;
            padding: 16px;
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 16px;
        }
        .qr-info {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            color: #334155;
            margin-top: 16px;
        }
        .qr-info span { font-weight: 600; color: #1F4E79; }

        .expires-badge {
            background: #dcfce7;
            color: #16a34a;
            font-size: 11px;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 8px;
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
    <div class="page-title">Generate QR Code</div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger" style="font-size:12px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <!-- Generate Form -->
            <div class="card-box">
                <div class="card-box-title">New Session QR</div>
                <form method="POST">
                   
                <div class="mb-3">
    <label class="form-label">Subject</label>
    <?php
    $curr_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='{$_SESSION['user_id']}'"));
    if($_SESSION['role'] == 'lecturer' && $curr_user['subject']){
        echo "<input type='text' name='subject' class='form-control'
              value='{$curr_user['subject']}' readonly
              style='background:#f8fafc; font-weight:600; color:#1F4E79;'>";
    } else {
        echo "<input type='text' name='subject' class='form-control'
              placeholder='e.g. Web Technology' required>";
    }
    ?>
</div>
                    <div class="mb-3">
                        <label class="form-label">Session Date</label>
                        <input type="date" name="session_date" class="form-control"
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Session Time</label>
                        <input type="time" name="session_time" class="form-control"
                               value="<?php echo date('H:i'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">QR Expires After (minutes)</label>
                        <select name="duration" class="form-control">
                            <option value="5">5 minutes</option>
                            <option value="10" selected>10 minutes</option>
                            <option value="15">15 minutes</option>
                            <option value="30">30 minutes</option>
                        </select>
                    </div>
                    <button type="submit" name="generate" class="btn-generate">
                        <i class="bi bi-qr-code"></i> Generate QR Code
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <?php if(isset($success) && $success): ?>
            <!-- QR Code Display -->
            <div class="qr-display">
                <h5>QR Code Generated!</h5>
                <p>Display this QR code for students to scan</p>
                <div id="qrcode"></div>
                <div class="expires-badge">
                    ⏱ Expires at: <?php echo date('h:i A', strtotime($expires_at)); ?>
                </div>
                <div class="qr-info">
                    <div><span>Subject:</span> <?php echo $subject; ?></div>
                    <div style="margin-top:6px;"><span>Date:</span> <?php echo $session_date; ?></div>
                    <div style="margin-top:6px;"><span>Time:</span> <?php echo $session_time; ?></div>
                    <div style="margin-top:6px;"><span>Duration:</span> <?php echo $duration; ?> minutes</div>
                </div>
            </div>
            <script>
                new QRCode(document.getElementById("qrcode"), {
                    text: "<?php echo $qr_data; ?>",
                    width: 200,
                    height: 200,
                    colorDark: "#1F4E79",
                    colorLight: "#ffffff",
                });
            </script>
            <?php else: ?>
            <!-- Placeholder -->
            <div class="qr-display" style="padding: 60px 30px;">
                <i class="bi bi-qr-code" style="font-size:64px; color:#cbd5e1;"></i>
                <h5 style="color:#94a3b8; margin-top:16px;">No QR Code Generated Yet</h5>
                <p>Fill the form and click Generate QR Code</p>
            </div>
            <?php endif; ?>

            <!-- Recent Sessions -->
            <div class="card-box">
                <div class="card-box-title">Recent Sessions</div>
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Lecturer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if(mysqli_num_rows($sessions) > 0){
                            while($row = mysqli_fetch_assoc($sessions)){
                                $status = (strtotime($row['expires_at']) > time()) ?
                                    '<span class="badge-active">Active</span>' :
                                    '<span class="badge-ended">Ended</span>';
                                echo "<tr>
                                    <td>{$row['subject']}</td>
                                    <td>{$row['session_date']}</td>
                                    <td>{$row['session_time']}</td>
                                    <td>{$row['lecturer_name']}</td>
                                    <td>{$status}</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; color:#94a3b8; padding:20px;'>No sessions yet</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>