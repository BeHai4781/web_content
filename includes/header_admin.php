<?php
// Lấy thông báo từ bảng notifications
$stmt = $pdo->prepare("SELECT n.*, u.fullname, u.username FROM notifications n 
    JOIN users u ON n.user_id = u.id 
    WHERE n.is_read = 0 
    ORDER BY n.created_at DESC");
$stmt->execute();
$system_notifications = $stmt->fetchAll();

// Lấy thông báo từ bảng ratings
$stmt = $pdo->prepare("
    SELECT ratings.*, posts.title AS post_title 
    FROM ratings 
    LEFT JOIN posts ON ratings.post_id = posts.id 
    WHERE ratings.is_read = 0
    ORDER BY submitted_at DESC 
    LIMIT 10
");
$stmt->execute();
$rating_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gộp lại 2 mảng
$notifications = array_merge($system_notifications, $rating_notifications);

// Đếm tổng chưa đọc
$unread = min(99, count($notifications));
// Chỉ lấy 5 thông báo đầu tiên
$notifications = array_slice($notifications, 0, 5);
?>
<!DOCTYPE html><html lang="vi">
<head>
    <meta charset="UTF-8"><title>Quản trị hệ thống PNT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../includes/style.css?v=<?= time(); ?>">

</head>
<body>
    <div class="custom-navbar">
        <div class="nav-item"><a href="../admin/index.php">Trang chủ</a></div>
        <!-- Danh mục quản lý -->
        <div class="dropdown">
            <a class="btn dropdown-toggle" href="#" role="button" id="dropdownMenuLink" 
                data-bs-toggle="dropdown" aria-expanded="false" style="color: white;">
                Danh mục quản lý
            </a>

            <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                <li><a class="dropdown-item" href="../admin/approve_user.php">Quản lý người dùng</a></li>
                <li><a class="dropdown-item" href="../admin/approve_cate.php">Quản lý chuyên mục</a></li>
            </ul>
        </div>
        <!-- Thông báo -->
        <div class="nav-item dropdown" style="position: relative;">
            <a class="nav-link" href="#" style="cursor: pointer;" id="notificationToggle">
                🔔
                <?php if ($unread > 0): ?>
                    <span class="badge bg-danger" style="position: absolute; top: -5px; right: -10px;">
                        <?= $unread > 99 ? '99+' : $unread ?>
                    </span>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu" id="notificationDropdown">
                <?php if (empty($notifications)): ?>
                    <li class="dropdown-item-text text-center text-muted">
                        Không có thông báo nào.
                    </li>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <li class="dropdown-item-text">
                            <?php if (($n['type'] ?? '') === 'approval_request'): ?>
                                🟢 <?= htmlspecialchars($n['fullname']) ?> đã gửi một yêu cầu. 
                                <a href="approve_user.php?duyet=false">Xét duyệt</a>
                            <?php elseif (($n['type'] ?? '') === 'new_post'): ?>
                                📝 <?= htmlspecialchars($n['username']) ?> đã đăng bài viết mới. 
                                <?php 
                                    $post = null;
                                    if (!empty($n['post_id'])) {
                                        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
                                        $stmt->execute([$n['post_id']]);
                                        $post = $stmt->fetch(PDO::FETCH_ASSOC);
                                    }
                                ?>
                                <?php if ($post): ?>
                                    <a href="../post.php?slug=<?= htmlspecialchars($post['slug']) ?>" target="_blank">
                                        <?= htmlspecialchars($post['title']) ?> - Xem bài
                                    </a>
                                <?php else: ?>
                                    <span>(Bài viết không tồn tại)</span>
                                <?php endif; ?>
                            <?php elseif (($n['type'] ?? '') === 'user_contact'): ?>
                                📩 <?= htmlspecialchars($n['email_rate']) ?> vừa gửi liên hệ. 
                                <a href="../notifications/all_notices.php">Xem chi tiết</a>
                            <?php elseif (($n['type'] ?? '') === 'user_rate'): ?>
                                💬 <?= htmlspecialchars($n['email_rate']) ?> vừa bình luận bài viết 
                                <strong><?= htmlspecialchars($n['post_title']) ?></strong>. 
                                <a href="../notifications/all_notices.php">Xem chi tiết</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <li class="dropdown-item-text text-center">
                        <a href="../notifications/all_notices.php">Xem tất cả thông báo</a>
                    </li>
                <?php endif; ?>
            </ul>

        </div>

        <!-- Logo -->
        <div class="nav-logo-ad">
            <a href="index.php"><img src="/uploads/logo%203.png"></a>
        </div>

        <!-- Thông tin admin -->
        <div class="nav-cta">
            <a href="../admin/admin_profile.php">
                <i class="fas fa-user-circle" style="color: #0091ae;"></i> 
                <?php echo htmlspecialchars($_SESSION['user']['username'] ?? ''); ?>
            </a>
        </div>

        <div class="nav-cta">
            <a href="/auth/logout.php" class="btn btn-primary">
                <i class="fas fa-sign-out-alt me-1"></i> Đăng xuất
            </a>
        </div>
    </div>

    <!-- Script dropdown -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const toggle = document.getElementById("notificationToggle");
            const dropdown = document.getElementById("notificationDropdown");

            toggle.addEventListener("click", function (e) {
                e.preventDefault();
                dropdown.classList.toggle("show");
            });

            // Ẩn dropdown khi click ngoài khu vực
            document.addEventListener("click", function (event) {
                if (!toggle.contains(event.target) && !dropdown.contains(event.target)) {
                    dropdown.classList.remove("show");
                }
            });
        });
    </script>