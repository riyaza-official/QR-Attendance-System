<?php
include 'db_connect.php';

$message = "";
$message_type = "";

if(isset($_GET['token'])){
    $token = $_GET['token'];

    // Check if session exists and not expired
    $query  = "SELECT * FROM sessions WHERE qr_token='$token'";
    $result = mysqli_query($conn, $query);
    $session = mysqli_fetch_assoc($result);

    if($session){
        if(strtotime($session['expires_at']) > time()){
            // Session is valid - show attendance form
            $valid_session = true;
        } else {
            $message      = "This QR Code has expired!";
            $message_type = "error";
        }
    } else {
        $message      = "Invalid QR Code!";
        $message_type = "error";
    }
}

// Mark attendance
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])){
    $token      = $_POST['token'];
    $student_id = $_POST['student_id'];

    // Get session
    $s_query  = "SELECT * FROM sessions WHERE qr_token='$token'";
    $s_result = mysqli_query($conn, $s_query);
    $session  = mysqli_fetch_assoc($s_result);

    if($session && strtotime($session['expires_at']) > time()){
        // Check if student exists
        $st_query  = "SELECT * FROM students WHERE student_id='$student_id'";
        $st_result = mysqli_query($conn, $st_query);
        $student   = mysqli_fetch_assoc($st_result);

        if($student){
            // Check if already marked
            $check = "SELECT * FROM attendance WHERE session_id='{$session['id']}' AND student_id='{$student['id']}'";
            $check_result = mysqli_query($conn, $check);

            if(mysqli_num_rows($check_result) > 0){
                $message      = "Attendance already marked for this session!";
                $message_type = "warning";
            } else {
                // Mark attendance
                $att_query = "INSERT INTO attendance (session_id, student_id, status, marked_at)
                              VALUES ('{$session['id']}', '{$student['id']}', 'present', NOW())";
                if(mysqli_query($conn, $att_query)){
                    $message      = "Attendance marked successfully!";
                    $message_type = "success";
                    $student_name = $student['name'];
                }
            }
        } else {
            $message      = "Student ID not found! Please contact your lecturer.";
            $message_type = "error";
        }
    } else {
        $message      = "QR Code has expired!";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance — QR Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .topbar {
            background: #1F4E79;
            color: #fff;
            padding: 12px 20px;
            width: 100%;
            position: fixed;
            top: 0; left: 0;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
        }

        .scan-card {
            background: #fff;
            border-radius: 12px;
            padding: 32px 28px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin-top: 60px;
            text-align: center;
        }

        .scan-icon {
            width: 64px; height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px;
        }
        .scan-icon.blue   { background: #dbeafe; color: #1d4ed8; }
        .scan-icon.green  { background: #dcfce7; color: #16a34a; }
        .scan-icon.red    { background: #fee2e2; color: #dc2626; }
        .scan-icon.yellow { background: #fef9c3; color: #ca8a04; }

        .scan-title {
            font-size: 17px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 6px;
        }
        .scan-sub {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 20px;
        }

        .session-info {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            color: #334155;
            margin-bottom: 20px;
        }
        .session-info div { margin-bottom: 4px; }
        .session-info span { font-weight: 600; color: #1F4E79; }

        .form-label { font-size: 12px; color: #64748b; text-align: left; display: block; margin-bottom: 4px; }
        .form-input {
            width: 100%;
            padding: 10px 12px;
            font-size: 13px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 16px;
            outline: none;
            text-align: center;
            letter-spacing: 1px;
        }
        .form-input:focus { border-color: #1F4E79; }

        .btn-mark {
            width: 100%;
            padding: 12px;
            background: #1F4E79;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-mark:hover { background: #185FA5; }

        /* Success */
        .success-box {
            background: #dcfce7;
            border-radius: 10px;
            padding: 24px;
            text-align: center;
        }
        .success-box h4 { color: #16a34a; font-size: 16px; margin-bottom: 6px; }
        .success-box p  { color: #166534; font-size: 12px; }

        /* Error */
        .error-box {
            background: #fee2e2;
            border-radius: 10px;
            padding: 24px;
            text-align: center;
        }
        .error-box h4 { color: #dc2626; font-size: 16px; margin-bottom: 6px; }
        .error-box p  { color: #991b1b; font-size: 12px; }

        /* Warning */
        .warning-box {
            background: #fef9c3;
            border-radius: 10px;
            padding: 24px;
            text-align: center;
        }
        .warning-box h4 { color: #ca8a04; font-size: 16px; margin-bottom: 6px; }
        .warning-box p  { color: #92400e; font-size: 12px; }

        .expires-badge {
            background: #dcfce7;
            color: #16a34a;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>

<div class="topbar">QR Attendance System — ATI Kandy</div>

<div class="scan-card">

    <?php if($message_type == 'success'): ?>
        <!-- Success -->
        <div class="scan-icon green">✅</div>
        <div class="scan-title">Attendance Marked!</div>
        <div class="scan-sub">Your attendance has been recorded</div>
        <div class="success-box">
            <h4>✅ Success!</h4>
            <p>Welcome, <strong><?php echo $student_name; ?></strong>!<br>
            Your attendance for <strong><?php echo $session['subject']; ?></strong> has been marked successfully.</p>
        </div>

    <?php elseif($message_type == 'warning'): ?>
        <!-- Already marked -->
        <div class="scan-icon yellow">⚠️</div>
        <div class="scan-title">Already Marked!</div>
        <div class="scan-sub"><?php echo $message; ?></div>

    <?php elseif($message_type == 'error'): ?>
        <!-- Error -->
        <div class="scan-icon red">❌</div>
        <div class="scan-title">Error!</div>
        <div class="error-box">
            <h4>❌ Failed!</h4>
            <p><?php echo $message; ?></p>
        </div>

    <?php elseif(isset($valid_session) && $valid_session): ?>
        <!-- Show form -->
        <div class="scan-icon blue">📋</div>
        <div class="scan-title">Mark Attendance</div>
        <div class="scan-sub">Enter your Student ID to mark attendance</div>

        <div class="session-info">
            <div><span>Subject:</span> <?php echo $session['subject']; ?></div>
            <div><span>Date:</span> <?php echo $session['session_date']; ?></div>
            <div><span>Time:</span> <?php echo $session['session_time']; ?></div>
        </div>

        <div class="expires-badge">
            ⏱ Expires at: <?php echo date('h:i A', strtotime($session['expires_at'])); ?>
        </div>

        <form method="POST">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <label class="form-label">Your Student ID</label>
            <input type="text" name="student_id" class="form-input"
                   placeholder="e.g. KAN/IT/2023/001" required>
            <button type="submit" name="mark_attendance" class="btn-mark">
                ✅ Mark My Attendance
            </button>
        </form>

    <?php else: ?>
        <!-- No token -->
        <div class="scan-icon red">❌</div>
        <div class="scan-title">Invalid QR Code</div>
        <div class="error-box">
            <h4>❌ Invalid!</h4>
            <p>Please scan a valid QR code from your lecturer.</p>
        </div>
    <?php endif; ?>

</div>

</body>
</html>