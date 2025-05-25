<?php
$stmt = $pdo->prepare("SELECT n.*, u.fullname, u.username FROM notifications n 
    JOIN users u ON n.user_id = u.id 
    WHERE n.is_read = 0 
    ORDER BY n.created_at DESC");
$stmt->execute();
$notifications = $stmt->fetchAll();

$unread = count($notifications);
?>
<!DOCTYPE html><html lang="vi">
<head>
    <meta charset="UTF-8"><title>Quản trị hệ thống PNT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../includes/style.css?v=<?= time(); ?>">

</head>
<body>
    <!-- header_admin.php -->
    <div class="custom-navbar">
        <!-- Danh mục quản lý -->
        <div class="dropdown">
            <a class="btn dropdown-toggle" href="#" role="button" id="dropdownMenuLink" 
                data-bs-toggle="dropdown" aria-expanded="false">
                Danh mục quản lý
            </a>

            <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                <li><a class="dropdown-item" href="../admin/approve_user.php">Quản lý người dùng</a></li>
                <li><a class="dropdown-item" href="../admin/approve_cate.php">Quản lý chuyên mục</a></li>
                <li><a class="dropdown-item" href="../admin/post_stats.php">Thống kê bài viết theo trạng thái</a></li>
            </ul>
        </div>
        <!-- Thông báo -->
        <div class="nav-item dropdown" style="position: relative;">
            <a class="nav-link" href="#" style="cursor: pointer;" id="notificationToggle">
                🔔
                <?php if ($unread > 0): ?>
                    <span class="badge bg-danger" style="position: absolute; top: -5px; right: -10px;"><?= $unread ?></span>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu" id="notificationDropdown">
                <?php foreach ($notifications as $n): ?>
                <li class="dropdown-item-text">
                    <?php if ($n['type'] === 'approval_request'): ?>
                        🟢 <?= htmlspecialchars($n['fullname']) ?> đã gửi một yêu cầu. 
                        <a href="approve_user.php">Xét duyệt</a>
                    <?php elseif ($n['type'] === 'new_post'): ?>
                        📝 <?= htmlspecialchars($n['username']) ?> đã đăng bài viết mới. 
                        <a href="approve_post.php">Xem bài</a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
                <li class="dropdown-item-text text-center">
                    <a href="../notifications/all_notices.php">Xem tất cả thông báo</a>
                </li>
            </ul>
        </div>

        <!-- Logo -->
        <div class="nav-item" style="flex-grow: 1; text-align: center;">
            <a href="index.php"><img src="logo.png" alt="Logo" style="height: 40px;"></a>
        </div>

        <div class="nav-item">
            <a href="../auth/login.php">Đăng xuất</a>
        </div>
        <!-- Thông tin admin -->
        <div class="nav-cta">
            <a href="../admin/admin_profile.php">
                👤 <?php echo htmlspecialchars($_SESSION['user']['username'] ?? ''); ?>
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