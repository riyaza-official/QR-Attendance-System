<?php
session_start();
include 'db_connect.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

// Add Lecturer
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_lecturer'])){
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $subject  = $_POST['subject'];
    $phone    = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = 'lecturer';

    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if(mysqli_num_rows($check) > 0){
        $error = "Email already exists!";
    } else {
        $query = "INSERT INTO users (name, email, password, role, subject, phone)
                  VALUES ('$name', '$email', '$password', '$role', '$subject', '$phone')";
        if(mysqli_query($conn, $query)){
            $success = "Lecturer added successfully!";
        } else {
            $error = "Error adding lecturer!";
        }
    }
}

// Delete Lecturer
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id='$id' AND role='lecturer'");
    header("Location: lecturer_qr.php");
    exit();
}

// Generate QR for lecturer
if(isset($_GET['generate'])){
    $lecturer_id = $_GET['generate'];
    $token       = md5(uniqid($lecturer_id, true));
    mysqli_query($conn, "UPDATE users SET qr_token='$token' WHERE id='$lecturer_id'");
    $gen_result  = mysqli_query($conn, "SELECT * FROM users WHERE id='$lecturer_id'");
    $gen_lect    = mysqli_fetch_assoc($gen_result);
    $qr_data     = "http://172.20.10.3/qr_attendance/lecturer_scan.php?token=$token";
    $show_qr     = true;
}

// Get all lecturers
$lecturers = mysqli_query($conn, "SELECT * FROM users WHERE role='lecturer' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Lecturer Management — QR Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
        .btn-add {
            background: #1F4E79;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-add:hover { background: #185FA5; }
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
        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-delete:hover { background: #dc2626; color: #fff; }
        .btn-genqr {
            background: #dcfce7;
            color: #16a34a;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-genqr:hover { background: #16a34a; color: #fff; }
        .badge-has-qr {
            background: #dcfce7;
            color: #16a34a;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .badge-no-qr {
            background: #fee2e2;
            color: #dc2626;
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

        /* QR Modal */
        .qr-modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-modal-box {
            background: #fff;
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            width: 320px;
        }
        .qr-modal-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 6px;
        }
        .qr-modal-sub {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 20px;
        }
        #qrcode {
            display: inline-block;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .btn-close-modal {
            background: #1F4E79;
            color: #fff;
            border: none;
            padding: 8px 24px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            margin-top: 8px;
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
    <div class="page-title">Lecturer Management</div>

    <?php if(isset($success)): ?>
        <div class="alert alert-success" style="font-size:12px; margin-bottom:16px;"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="alert alert-danger" style="font-size:12px; margin-bottom:16px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Add Lecturer Form -->
    <div class="card-box">
        <div class="card-box-title">Add New Lecturer</div>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control"
                           placeholder="e.g. Mr. Arun" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control"
                           placeholder="e.g. Web Technology">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           placeholder="07X XXXXXXX">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="email@ati.ac.lk" required>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="••••••" required>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" name="add_lecturer" class="btn-add w-100">Add</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Lecturers Table -->
    <div class="card-box">
        <div class="card-box-title">All Lecturers</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>QR Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                if(mysqli_num_rows($lecturers) > 0){
                    while($row = mysqli_fetch_assoc($lecturers)){
                        $qr_status = $row['qr_token'] ?
                            '<span class="badge-has-qr">QR Generated</span>' :
                            '<span class="badge-no-qr">No QR</span>';
                        echo "<tr>
                            <td>{$i}</td>
                            <td>{$row['name']}</td>
                            <td>{$row['subject']}</td>
                            <td>{$row['phone']}</td>
                            <td>{$row['email']}</td>
                            <td>{$qr_status}</td>
                            <td>
                                <a href='lecturer_qr.php?generate={$row['id']}' class='btn-genqr'>Generate QR</a>
                                &nbsp;
                                <a href='lecturer_qr.php?delete={$row['id']}' class='btn-delete' onclick='return confirm(\"Delete this lecturer?\")'>Delete</a>
                            </td>
                        </tr>";
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align:center; color:#94a3b8; padding:20px;'>No lecturers added yet</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- QR Modal -->
<?php if(isset($show_qr) && $show_qr): ?>
<div class="qr-modal" id="qrModal">
    <div class="qr-modal-box">
        <div class="qr-modal-title">QR Code — <?php echo $gen_lect['name']; ?></div>
        <div class="qr-modal-sub">Subject: <?php echo $gen_lect['subject']; ?></div>
        <div id="qrcode"></div>
        <p style="font-size:11px; color:#94a3b8;">
            Give this QR to the lecturer for entry/exit scanning
        </p>
        <button class="btn-close-modal" onclick="document.getElementById('qrModal').style.display='none'">
            Close
        </button>
    </div>
</div>
<script>
    new QRCode(document.getElementById("qrcode"), {
        text: "<?php echo $qr_data; ?>",
        width: 180,
        height: 180,
        colorDark: "#1F4E79",
        colorLight: "#ffffff",
    });
</script>
<?php endif; ?>

</body>
</html>
