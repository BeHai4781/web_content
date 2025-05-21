<?php
require '../config/db.php';
require '../config/mailer.php'; // nạp mailer

function sendEmail($to, $fullname, $type = 'approved') {
    $mail = getMailer(); // Gọi mailer từ file cấu hình

    // Thiết lập UTF-8
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true); 

    try {
        $mail->addAddress($to, $fullname);

        if ($type === 'approved') {
            $mail->Subject = 'Tài khoản đã được duyệt';
            $mail->Body = "<p>Chào <strong>$fullname</strong>,</p><p>Tài khoản của bạn đã được duyệt. Bây giờ bạn có thể đăng nhập và viết bài.</p>";
        } else {
            $mail->Subject = 'Tài khoản bị từ chối';
            $mail->Body = "<p>Chào <strong>$fullname</strong>,</p><p>Tài khoản của bạn đã bị từ chối.</p>";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
$message = "";

// Xử lý POST duyệt hoặc từ chối
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'], $_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$user_id]);
            $mailResult = sendEmail($user['email'], $user['fullname'], 'approved');
            $message = $mailResult ? "✅ Tài khoản đã được duyệt và email đã gửi." : "⚠️ Duyệt thành công nhưng không gửi được email.";
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?")->execute([$user_id]);
            $mailResult = sendEmail($user['email'], $user['fullname'], 'rejected');
            $message = $mailResult ? "❌ Đã từ chối và gửi email." : "⚠️ Đã từ chối nhưng không gửi được email.";
        }
    } else {
        $message = "❌ Không tìm thấy tài khoản đang chờ duyệt.";
    }
}

// Lấy danh sách người dùng đang chờ duyệt
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Duyệt tài khoản</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background-color: #f5f5f5;
        }
        h2 { color: #333; }
        table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        th, td {
            border: 1px solid #aaa; padding: 10px; text-align: left;
        }
        th { background-color: #ddd; }
        .btn {
            padding: 6px 12px; border-radius: 4px; color: white; border: none; cursor: pointer;
        }
        .approve { background-color: #4CAF50; }
        .reject { background-color: #f44336; }
        .btn:hover { opacity: 0.9; }
        .message {
            padding: 10px; margin-top: 10px; background: #e7f3fe; border-left: 5px solid #2196F3;
        }
    </style>
</head>
<body>

<h2>Danh sách tài khoản chờ duyệt</h2>

<?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (count($pending_users) > 0): ?>
    <table>
        <tr>
            <th>Họ tên</th>
            <th>Email</th>
            <th>SĐT</th>
            <th>Tên đăng nhập</th>
            <th>Thao tác</th>
        </tr>
        <?php foreach ($pending_users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['fullname']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn approve">Duyệt</button>
                    </form>
                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Bạn chắc chắn muốn từ chối?');">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn reject">Từ chối</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Không có tài khoản nào đang chờ duyệt.</p>
<?php endif; ?>

</body>
</html>
