<?php
require_once __DIR__ . '/../config/db.php';

$query = "SELECT id, name FROM categories";
$stmt = $pdo->query($query);
$categories = $stmt->fetchAll(); 
?>

<!DOCTYPE html><html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang tin tức PNT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../includes/style.css?v=<?= time(); ?>">

</head>
<body>
    <!--Banner--> 
    <div class="header-container">
        <div class="header-banner">
            <img src="/uploads/official banner 1.gif" alt="">
        </div>
    </div>
    <nav class="custom-navbar">
        <div class="nav-logo"><img src="/uploads/logo 3.png"></div>
        <div class="nav-item"><a href="/index.php">Trang chủ</a></div>

        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="dropdownToggle" onclick="toggleDropdown(event)">
                Danh mục
            </a>
            <ul class="dropdown-menu" id="category-menu">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <li><a class="dropdown-item" href="/index.php?category_id=<?= $category['id'] ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li><em class="dropdown-item text-muted">Không có danh mục</em></li>
                <?php endif; ?>
            </ul>
        </div>

        <form class="nav-search" method="GET" action="/index.php">
            <i class="fa-solid fa-magnifying-glass search-toggle" onclick="toggleSearch()"></i>
            <input type="text" id="searchInput" name="search" class="search-input" placeholder="Tìm kiếm...">
        </form>

        <div class="nav-cta">
            <a href="../auth/register.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i> Đăng ký
            </a>
            <a href="../auth/login.php" class="btn btn-light">
                <i class="fas fa-sign-in-alt me-1"></i> Đăng nhập
            </a>
        </div>
    </nav>

    <script>
        function toggleDropdown(event) {
            event.preventDefault();
            const menu = document.getElementById("category-menu");
            menu.classList.toggle("show");
        }
        function toggleSearch() {
            const input = document.getElementById('searchInput');
            input.classList.toggle('show');
            if (input.classList.contains('show')) {
            setTimeout(() => input.focus(), 300);
            }else {
             input.blur();
            }
        }
    </script>
    <div class="container">