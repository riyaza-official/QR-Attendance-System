<?php
session_start();
include 'db_connect.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Add student
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])){
    $student_id = $_POST['student_id'];
    $name       = $_POST['name'];
    $email      = $_POST['email'];
    $batch      = $_POST['batch'];

    $query = "INSERT INTO students (student_id, name, email, batch) VALUES ('$student_id', '$name', '$email', '$batch')";
    if(mysqli_query($conn, $query)){
        $success = "Student added successfully!";
    } else {
        $error = "Error adding student!";
    }
}

// Delete student
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM students WHERE id='$id'");
    header("Location: students.php");
    exit();
}

// Get all students
$students = mysqli_query($conn, "SELECT * FROM students ORDER BY id DESC");
$total    = mysqli_num_rows($students);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Students — QR Attendance</title>
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

        .badge-batch {
            background: #dbeafe;
            color: #1d4ed8;
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

<!-- Main -->
<div class="main">
    <div class="page-title">Students</div>

    <?php if(isset($success)): ?>
        <div class="alert alert-success" style="font-size:12px; margin-bottom:16px;"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="alert alert-danger" style="font-size:12px; margin-bottom:16px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Add Student Form -->
    <div class="card-box">
        <div class="card-box-title">Add New Student</div>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Student ID</label>
                    <input type="text" name="student_id" class="form-control" placeholder="e.g. KAN/IT/2023/001" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter email">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Batch</label>
                    <input type="text" name="batch" class="form-control" placeholder="e.g. 2023">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" name="add_student" class="btn-add w-100">Add</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <div class="card-box">
        <div class="card-box-title">All Students (<?php echo $total; ?>)</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Batch</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                if(mysqli_num_rows($students) > 0){
                    mysqli_data_seek($students, 0);
                    while($row = mysqli_fetch_assoc($students)){
                        echo "<tr>
                            <td>{$i}</td>
                            <td>{$row['student_id']}</td>
                            <td>{$row['name']}</td>
                            <td>{$row['email']}</td>
                            <td><span class='badge-batch'>{$row['batch']}</span></td>
                            <td><a href='students.php?delete={$row['id']}' class='btn-delete' onclick='return confirm(\"Delete this student?\")'>Delete</a></td>
                        </tr>";
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; color:#94a3b8; padding:20px;'>No students yet</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>