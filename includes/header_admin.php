<!DOCTYPE html><html lang="vi">
<head>
    <meta charset="UTF-8"><title>Web Content PHP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../includes/style.css?v=<?= time(); ?>">

</head>
<body>
    <!-- header_admin.php -->
    <div class="custom-navbar">
        <!-- Danh mục quản lý -->
        <div class="nav-item dropdown">
            <a href="#">Danh mục quản lý</a>
            <ul class="dropdown-menu">
                <li><a href="user_manage.php">Quản lý người dùng</a></li>
                <li><a href="category_manage.php">Quản lý chuyên mục</a></li>
                <li><a href="post_stats.php">Thống kê bài viết theo trạng thái</a></li>
            </ul>
        </div>

        <!-- Thông báo -->
        <div class="nav-item" style="position: relative;">
            <a href="notifications.php">
                🔔
                <?php
                // Giả lập số thông báo chưa đọc
                $unread = 3;
                if ($unread > 0) {
                    echo "<span style='position: absolute; top: -5px; right: -10px; background-color: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px;'>$unread</span>";
                }
                ?>
            </a>
        </div>

        <!-- Logo -->
        <div class="nav-item" style="flex-grow: 1; text-align: center;">
            <a href="index.php"><img src="logo.png" alt="Logo" style="height: 40px;"></a>
        </div>

        <!-- Thông tin admin -->
        <div class="nav-cta">
            <a href="admin_profile.php">Thông tin admin</a>
        </div>
    </div>

    <!-- Script dropdown -->
    <script>
        document.querySelectorAll('.nav-item.dropdown').forEach(function(item) {
            item.addEventListener('mouseenter', function () {
                this.querySelector('.dropdown-menu').classList.add('show');
            });
            item.addEventListener('mouseleave', function () {
                this.querySelector('.dropdown-menu').classList.remove('show');
            });
        });
    </script>
