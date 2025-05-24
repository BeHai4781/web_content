<?php
require '../config/db.php';
require '../config/mailer.php';

function sendEmail($to, $fullname, $type = 'approved') {
    $mail = getMailer(); 
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
$limit = 10;

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
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type = 'approval_request'")->execute([$user_id]);

            $mailResult = sendEmail($user['email'], $user['fullname'], 'approved');
            $message = $mailResult ? "✅ Tài khoản đã được duyệt và email đã gửi." : "⚠️ Duyệt thành công nhưng không gửi được email.";
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?")->execute([$user_id]);
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND type = 'approval_request'")->execute([$user_id]);

            $mailResult = sendEmail($user['email'], $user['fullname'], 'rejected');
            $message = $mailResult ? "❌ Đã từ chối và gửi email." : "⚠️ Đã từ chối nhưng không gửi được email.";
        }
    }
}

$duyet_mode = isset($_GET['duyet']) ? $_GET['duyet'] === 'false' : true;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

if ($duyet_mode == 'false') {
    // Danh sách chờ duyệt
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    $total_users = $total_stmt->fetchColumn();

    $start = 0;
    $limit = 10;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE status = 'pending' ORDER BY id DESC LIMIT ?, ?");
    $stmt->bindValue(1, $start, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $pendingUsers = $stmt->fetchAll();
    $users = $pendingUsers;
} else {
    // Danh sách đã duyệt
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $total_users = $total_stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE status = 'active' ORDER BY id DESC LIMIT ?, ?");
    $stmt->bindValue(1, $offset, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$total_pages = ceil($total_users / $limit);

include '../includes/header_admin.php';
?>

<div class="main-wrapper">
    <h2><?= $duyet_mode ? "Danh sách tài khoản chờ duyệt" : "Danh sách tài khoản hiện có" ?></h2>
    <div style="margin: 10px 0;">
        <a href="?duyet=false" class="btn btn-secondary">📋 Danh sách chờ duyệt</a>
        <a href="?duyet=true" class="btn btn-secondary">👥 Danh sách tài khoản</a>
    </div>
    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (count($users) > 0): ?>
        <table>
            <tr>
                <th>STT</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>SĐT</th>
                <th>Tên đăng nhập</th>
                <th>Vai trò</th>
                <th>Thao tác</th>
            </tr>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['fullname']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td>
                        <?php if ($duyet_mode): ?>
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
                        <?php else: ?>
                            <a href="#" class="btn update">Cập nhật</a>
                            <a href="#" class="btn delete" onclick="return confirm('Xóa tài khoản này?');">Xóa</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= $duyet_mode ? 'duyet=true&' : '' ?>page=<?= $page - 1 ?>">&laquo;</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?= $duyet_mode ? 'duyet=true&' : '' ?>page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?<?= $duyet_mode ? 'duyet=true&' : '' ?>page=<?= $page + 1 ?>">&raquo;</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>Không có tài khoản nào.</p>
    <?php endif; ?>

</div>
<div class="row mb-3">
    <div class="col text-end">
        <a href="#" class="btn btn-secondary">➕ Thêm tài khoản</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>