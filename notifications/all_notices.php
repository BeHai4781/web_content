<?php
require '../config/db.php';
require '../includes/header_admin.php'; 

// Thiết lập phân trang
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số thông báo
$total_stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
$total_notifications = $total_stmt->fetchColumn();
$total_pages = ceil($total_notifications / $limit);

// Truy vấn thông báo kèm thông tin user
$stmt = $pdo->prepare("SELECT n.*, u.fullname, u.username 
                       FROM notifications n 
                       JOIN users u ON n.user_id = u.id 
                       ORDER BY n.created_at DESC
                       LIMIT ?, ?");
$stmt->bindValue(1, $offset, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h3 class="mb-4">📬 Danh sách thông báo</h3>
    <ul class="list-group">
        <?php if (count($notifications) === 0): ?>
            <li class="list-group-item text-muted">Không có thông báo nào.</li>
        <?php endif; ?>

        <?php foreach ($notifications as $notice): 
            $isRead = $notice['is_read'];
            $fullname = htmlspecialchars($notice['fullname']);
            $user_id = $notice['user_id'];
            $created_at = $notice['created_at'];
        ?>
            <div class="notice">
                <p>
                    Yêu cầu duyệt tài khoản: <strong><?= $fullname ?></strong>
                    <?php if (!$isRead): ?>
                        <span class="badge bg-danger rounded-pill">Mới</span>
                    <?php endif; ?>
                </p>

                <?php if ($isRead): ?>
                    <button class="btn btn-secondary" disabled>Đã duyệt</button>
                <?php else: ?>
                    <a href="../admin/approve_user.php?user_id=<?= $user_id ?>" class="btn btn-success">Duyệt</a>
                <?php endif; ?>
                <p><?= $created_at ?></p>
            </div>
        <hr>
    <?php endforeach; ?>
    </ul>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>
