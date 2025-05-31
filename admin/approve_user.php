<?php
require '../config/db.php';
require '../config/mailer.php';
if (session_status() == PHP_SESSION_NONE) session_start();
// Nếu chưa đăng nhập thì chuyển hướng
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Bạn chưa đăng nhập!'); window.location.href = '/auth/login.php';</script>";
    exit;
}
function sendEmail($to, $fullname, $type = 'approved') {
    $mail = getMailer();
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    try {
        $mail->addAddress($to, $fullname);
        $mail->Subject = ($type === 'approved') ? 'Tài khoản đã được duyệt' : 'Tài khoản bị từ chối';
        $mail->Body = ($type === 'approved')
            ? "<p>Chào <strong>$fullname</strong>,</p><p>Tài khoản của bạn đã được duyệt. Bây giờ bạn có thể đăng nhập và viết bài.</p><p>Trân trọng.</p><p>Quản trị hệ thống</p>"
            : "<p>Chào <strong>$fullname</strong>,</p><p>Tài khoản của bạn đã bị từ chối.</p><p>Lí do: không đủ điều kiện tham gia PNT group.</p><p>Trân trọng.</p><p>Quản trị hệ thống</p>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$message = "";
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$search_query = "";
$search_params = [];

if ($search !== '') {
    $search_query = " AND (fullname LIKE ? OR email LIKE ? OR phone LIKE ? OR username LIKE ?)";
    $search_params = array_fill(0, 4, "%$search%");
}

$duyet_mode = isset($_GET['duyet']) ? $_GET['duyet'] === 'true' : false;

// Xử lý POST duyệt / từ chối
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

// Xử lý xoá
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id']);
    $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    header("Location: approve_user.php?duyet=true");
    exit();
}

$status_filter = $duyet_mode ? 'active' : 'pending';
$where_clause = "WHERE status = ? $search_query";
$params = array_merge([$status_filter], $search_params);

// Đếm tổng số user
$count_sql = "SELECT COUNT(*) FROM users $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();

// Lấy danh sách user có phân trang
$sql = "SELECT * FROM users $where_clause ORDER BY id DESC LIMIT :offset, :limit";
$data_stmt = $pdo->prepare($sql);

$where_clause = "WHERE status = :status";
if ($search !== '') {
    $where_clause .= " AND (fullname LIKE :search OR email LIKE :search OR phone LIKE :search OR username LIKE :search)";
}

$params_named = [
    ':status' => $status_filter,
];
if ($search !== '') {
    $params_named[':search'] = "%$search%";
}

$count_sql = "SELECT COUNT(*) FROM users $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params_named);
$total_users = $count_stmt->fetchColumn();

$sql = "SELECT * FROM users $where_clause ORDER BY id DESC LIMIT :offset, :limit";
$data_stmt = $pdo->prepare($sql);

$data_stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$data_stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);

foreach ($params_named as $key => $val) {
    // Nếu là :search thì gán kiểu string, nếu :status cũng string
    $data_stmt->bindValue($key, $val, PDO::PARAM_STR);
}

$data_stmt->execute();
$users = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_pages = ceil($total_users / $limit);

include '../includes/header_admin.php';
?>

<div class="main-wrapper">
    <h2><?= $duyet_mode ? "Danh sách tài khoản đã duyệt" : "Danh sách tài khoản chờ duyệt" ?></h2>

    <form class="mb-3 d-flex justify-content-between" method="get">
        <div class="input-group" style="width: 300px;">
            <input type="hidden" name="duyet" value="<?= $duyet_mode ? 'true' : 'false' ?>">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Tìm theo tên, email, SĐT...">
            <button type="submit" class="btn btn-outline-secondary">🔍</button>
        </div>
        <a href="create_user.php?action=add" class="btn btn-secondary">Thêm tài khoản</a>
    </form>

    <div class="mb-3">
        <a href="../admin/approve_user.php?duyet=false" class="btn btn-primary">📋 Danh sách chờ duyệt</a>
        <a href="../admin/approve_user.php?duyet=true" class="btn btn-primary">👥 Danh sách đã duyệt</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (count($users) > 0): ?>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>STT</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>SĐT</th>
                    <th>Tên đăng nhập</th>
                    <th>Vai trò</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $stt = ($page - 1) * $limit + 1; 
                    foreach ($users as $user): 
                ?>
                    <tr>
                        <td><strong><?= $stt ?></strong></td>
                        <td><?= htmlspecialchars($user['fullname']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['phone']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <?php if (!$duyet_mode): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-secondary btn-sm">Duyệt</button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn từ chối?');">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger btn-sm">Từ chối</button>
                                </form>
                            <?php else: ?>
                                <a href="user_profile.php?action=edit&id=<?= $user['id'] ?>" class="btn btn-sm btn-secondary btn-info">Cập nhật</a>
                                <a href="?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa tài khoản này?');">Xóa</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php 
                    $stt++; 
                    endforeach; 
                ?>
            </tbody>
        </table>

        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?duyet=<?= $duyet_mode ? 'true' : 'false' ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>

    <?php else: ?>
        <p>Không có tài khoản nào.</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
