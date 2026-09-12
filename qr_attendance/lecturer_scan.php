<?php
include 'db_connect.php';

$message      = "";
$message_type = "";

if(isset($_GET['token'])){
    $token = $_GET['token'];

    // Find lecturer by token
    $query    = "SELECT * FROM users WHERE qr_token='$token' AND role='lecturer'";
    $result   = mysqli_query($conn, $query);
    $lecturer = mysqli_fetch_assoc($result);

    if($lecturer){
        $valid    = true;
        $today    = date('Y-m-d');

        // Check if entry already marked today
        $check = "SELECT * FROM lecturer_attendance WHERE lecturer_id='{$lecturer['id']}' AND date='$today'";
        $check_result = mysqli_query($conn, $check);
        $record       = mysqli_fetch_assoc($check_result);

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $action = $_POST['action'];

            if($action == 'entry'){
                if($record){
                    $message      = "Entry already marked today!";
                    $message_type = "warning";
                } else {
                    $ins = "INSERT INTO lecturer_attendance (lecturer_id, entry_time, date)
                            VALUES ('{$lecturer['id']}', NOW(), '$today')";
                    mysqli_query($conn, $ins);
                    $message      = "Entry marked successfully!";
                    $message_type = "success";
                    $action_done  = "entry";
                }
            } elseif($action == 'exit'){
                if(!$record){
                    $message      = "Please mark entry first!";
                    $message_type = "error";
                } elseif($record['exit_time']){
                    $message      = "Exit already marked today!";
                    $message_type = "warning";
                } else {
                    $upd = "UPDATE lecturer_attendance SET exit_time=NOW()
                            WHERE lecturer_id='{$lecturer['id']}' AND date='$today'";
                    mysqli_query($conn, $upd);
                    $message      = "Exit marked successfully!";
                    $message_type = "success";
                    $action_done  = "exit";
                }
            }

            // Refresh record
            $check_result = mysqli_query($conn, $check);
            $record       = mysqli_fetch_assoc($check_result);
        }
    } else {
        $message      = "Invalid QR Code!";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Lecturer Scan — QR Attendance</title>
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

        .lecturer-info {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            color: #334155;
            margin-bottom: 20px;
        }
        .lecturer-info div { margin-bottom: 4px; }
        .lecturer-info span { font-weight: 600; color: #1F4E79; }

        .btn-entry {
            width: 100%;
            padding: 12px;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .btn-entry:hover { background: #15803d; }

        .btn-exit {
            width: 100%;
            padding: 12px;
            background: #dc2626;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-exit:hover { background: #b91c1c; }

        .time-badge {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 10px;
            font-size: 12px;
            color: #334155;
            margin-bottom: 16px;
            text-align: left;
        }
        .time-badge span { font-weight: 600; color: #1F4E79; }

        .success-box {
            background: #dcfce7;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .success-box p { color: #166534; font-size: 12px; }

        .warning-box {
            background: #fef9c3;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .warning-box p { color: #92400e; font-size: 12px; }

        .error-box {
            background: #fee2e2;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .error-box p { color: #991b1b; font-size: 12px; }
    </style>
</head>
<body>

<div class="topbar">QR Attendance System — ATI Kandy</div>

<div class="scan-card">

    <?php if(isset($valid) && $valid): ?>

        <div class="scan-icon blue">👨‍🏫</div>
        <div class="scan-title">Welcome, <?php echo $lecturer['name']; ?>!</div>
        <div class="scan-sub">Mark your entry or exit for today</div>

        <!-- Today's record -->
        <?php if($record): ?>
        <div class="time-badge">
            <div><span>Entry:</span> <?php echo $record['entry_time'] ? date('h:i A', strtotime($record['entry_time'])) : 'Not marked'; ?></div>
            <div style="margin-top:4px;"><span>Exit:</span> <?php echo $record['exit_time'] ? date('h:i A', strtotime($record['exit_time'])) : 'Not marked'; ?></div>
        </div>
        <?php endif; ?>

        <!-- Message -->
        <?php if($message_type == 'success'): ?>
            <div class="success-box"><p>✅ <?php echo $message; ?></p></div>
        <?php elseif($message_type == 'warning'): ?>
            <div class="warning-box"><p>⚠️ <?php echo $message; ?></p></div>
        <?php elseif($message_type == 'error'): ?>
            <div class="error-box"><p>❌ <?php echo $message; ?></p></div>
        <?php endif; ?>

        <!-- Buttons -->
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <?php if(!$record || !$record['entry_time']): ?>
                <button type="submit" name="action" value="entry" class="btn-entry">
                    🟢 Mark Entry
                </button>
            <?php endif; ?>
            <?php if($record && $record['entry_time'] && !$record['exit_time']): ?>
                <button type="submit" name="action" value="exit" class="btn-exit">
                    🔴 Mark Exit
                </button>
            <?php endif; ?>
            <?php if($record && $record['entry_time'] && $record['exit_time']): ?>
                <div class="success-box">
                    <p>✅ Both entry and exit marked for today!</p>
                </div>
            <?php endif; ?>
        </form>

    <?php else: ?>
        <div class="scan-icon red">❌</div>
        <div class="scan-title">Invalid QR Code!</div>
        <div class="error-box">
            <p>Please scan a valid lecturer QR code.</p>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
