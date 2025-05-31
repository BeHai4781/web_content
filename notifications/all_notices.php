<?php
require '../config/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Nếu có yêu cầu đánh dấu đã đọc theo id và type (đối với new_post, liên hệ, bình luận)
if (isset($_GET['markread'])) {
    $id   = $_GET['markread'];
    $type = $_GET['type'] ?? '';
    if ($type === 'new_post') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($type === 'contact' || $type === 'comment') {
        $stmt = $pdo->prepare("UPDATE ratings SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: all_notices.php");
    exit;
}

// Nút "Đánh dấu tất cả đã đọc" (áp dụng cho các thông báo NEW POST, LIÊN HỆ và BÌNH LUẬN)
if (isset($_GET['markall'])) {
    // Đánh dấu tất cả bài viết mới (notifications)
    $pdo->exec("UPDATE notifications SET is_read = 1 WHERE type = 'new_post' AND is_read = 0");
    // Đánh dấu tất cả liên hệ và bình luận (ratings)
    $pdo->exec("UPDATE ratings SET is_read = 1 WHERE type IN ('user_contact', 'user_rate') AND is_read = 0");
    header("Location: all_notices.php");
    exit;
}

$limit  = 10;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

/* 1. Lấy thông báo duyệt tài khoản (approval_request)
      - Tất cả các thông báo, kể cả đã duyệt */
try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.fullname, u.username, n.created_at 
        FROM notifications n 
        LEFT JOIN users u ON n.user_id = u.id
        WHERE n.type = 'approval_request'
        ORDER BY n.created_at DESC
        LIMIT ?, ?
    ");
    $stmt->bindValue(1, $offset, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $approvalNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn approval_notifications: " . $e->getMessage());
}
// Tổng số yêu cầu duyệt tài khoản
$totalApprovalsStmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE type = 'approval_request'");
$total_approvals    = $totalApprovalsStmt->fetchColumn();
$total_pages_approval = ceil($total_approvals / $limit);

/*2. Lấy thông báo bài viết mới (new_post) từ notifications
      - Chỉ lấy các thông báo chưa đọc (is_read = 0)*/
try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.username, p.title AS post_title, p.slug, n.created_at
        FROM notifications n 
        LEFT JOIN users u ON n.user_id = u.id
        LEFT JOIN posts p ON n.post_id = p.id
        WHERE n.type = 'new_post' AND n.is_read = 0
        ORDER BY n.created_at DESC
        LIMIT ?, ?
    ");
    $stmt->bindValue(1, $offset, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $newPostNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn new_post: " . $e->getMessage());
}
// Tổng số bài viết mới chưa đọc
$totalNewPostsStmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE type = 'new_post' AND is_read = 0");
$total_new_posts    = $totalNewPostsStmt->fetchColumn();
$total_pages_newpost = ceil($total_new_posts / $limit);

/*3. Lấy thông báo liên hệ từ ratings (user_contact)
      - Chỉ lấy các thông báo chưa đọc */
try {
    $stmt = $pdo->prepare("
        SELECT r.*, p.title AS post_title, p.slug, r.submitted_at
        FROM ratings r 
        LEFT JOIN posts p ON r.post_id = p.id
        WHERE r.type = 'user_contact' AND r.is_read = 0
        ORDER BY r.submitted_at DESC
        LIMIT ?, ?
    ");
    $stmt->bindValue(1, $offset, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $contactNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn contact: " . $e->getMessage());
}
// Tổng số liên hệ chưa đọc
$totalContactsStmt = $pdo->query("SELECT COUNT(*) FROM ratings WHERE type = 'user_contact' AND is_read = 0");
$total_contacts    = $totalContactsStmt->fetchColumn();
$total_pages_contact = ceil($total_contacts / $limit);

/*4. Lấy thông báo bình luận từ ratings (user_rate)
      - Chỉ lấy các thông báo chưa đọc*/
try {
    $stmt = $pdo->prepare("
        SELECT r.*, p.title AS post_title, p.slug, r.submitted_at, r.comment
        FROM ratings r 
        LEFT JOIN posts p ON r.post_id = p.id
        WHERE r.type = 'user_rate' AND r.is_read = 0
        ORDER BY r.submitted_at DESC
        LIMIT ?, ?
    ");
    $stmt->bindValue(1, $offset, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $commentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn comment: " . $e->getMessage());
}
// Tổng số bình luận chưa đọc
$totalCommentsStmt = $pdo->query("SELECT COUNT(*) FROM ratings WHERE type = 'user_rate' AND is_read = 0");
$total_comments    = $totalCommentsStmt->fetchColumn();
$total_pages_comment = ceil($total_comments / $limit);

include '../includes/header_admin.php'; 
?>

<div class="container mt-4">
    <h3 class="mb-4">Danh sách thông báo</h3>
    <div class="mb-3">
        <a href="?markall=1" class="btn btn-danger">Đánh dấu tất cả đã đọc</a>
    </div>
    
    <!-- 1. Yêu cầu duyệt tài khoản -->
    <h4 id="approvalSection">🟢 Yêu cầu duyệt tài khoản</h4>
    <ul class="list-group mb-4">
        <?php foreach ($approvalNotifications as $notice): 
            $isRead    = $notice['is_read'];
            $fullname  = htmlspecialchars($notice['fullname'] ?? 'Chưa có tên');
            $user_id   = $notice['user_id'];
            $created_at = $notice['created_at'];
        ?> 
            <li class="list-group-item">
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
                        <a href="../admin/approve_user.php?user_id=<?= $user_id ?>" class="btn btn-secondary">Duyệt</a>
                    <?php endif; ?>
                    <p><?= $created_at ?></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($total_pages_approval > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages_approval; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>#approvalSection"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- 2. Bài viết mới -->
    <h4 id="newPostSection">📝 Bài viết mới</h4>
    <ul class="list-group mb-4">
        <?php foreach ($newPostNotifications as $notice): ?>
            <li class="list-group-item">
                <p>
                    <?= htmlspecialchars($notice['username'] ?? 'Unknown') ?> vừa đăng bài viết:
                    <strong><?= htmlspecialchars($notice['post_title'] ?? 'Chưa có tiêu đề') ?></strong>
                    (<?= $notice['created_at'] ?>)
                </p>
                <a href="../post.php?slug=<?= htmlspecialchars($notice['slug']) ?>" target="_blank" class="btn btn-primary">Xem chi tiết</a>
                <a href="?markread=<?= $notice['id'] ?>&type=new_post" class="btn btn-warning">Đã đọc</a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($total_pages_newpost > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages_newpost; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>#newPostSection"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- 3. Liên hệ -->
    <h4 id="contactSection">📩 Liên hệ</h4>
    <ul class="list-group mb-4">
        <?php foreach ($contactNotifications as $notice): ?>
            <li class="list-group-item">
                <p><?= htmlspecialchars($notice['email_rate'] ?? 'Unknown') ?> đã gửi liên hệ:</p>
                <p><?= htmlspecialchars($notice['comment'] ?? '') ?></p>
                <a href="?markread=<?= $notice['id'] ?>&type=contact" class="btn btn-warning">Đã đọc</a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($total_pages_contact > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages_contact; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>#contactSection"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- 4. Bình luận -->
    <h4 id="commentSection">💬 Bình luận</h4>
    <ul class="list-group mb-4">
        <?php foreach ($commentNotifications as $notice): ?>
            <li class="list-group-item">
                <p>
                    <?= htmlspecialchars($notice['email_rate'] ?? 'Unknown') ?> đã bình luận về bài viết:
                    <strong><?= htmlspecialchars($notice['post_title'] ?? 'Chưa có tiêu đề') ?></strong>
                </p>
                <p><?= htmlspecialchars($notice['comment'] ?? '') ?></p>
                <a href="../post.php?slug=<?= htmlspecialchars($notice['slug']) ?>" target="_blank" class="btn btn-primary">Xem chi tiết</a>
                <a href="?markread=<?= $notice['id'] ?>&type=comment" class="btn btn-warning">Đã đọc</a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($total_pages_comment > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages_comment; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>#commentSection"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>
