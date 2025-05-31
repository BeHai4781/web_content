<?php
require_once __DIR__ . '/../config/db.php';

$query = "SELECT id, name FROM categories";
$stmt = $pdo->query($query);
$categories = $stmt->fetchAll(); 
?>

<!DOCTYPE html><html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../includes/style.css?v=<?= time(); ?>">
</head>
<body>
    <nav class="custom-navbar">
        <div class="nav-logo"><img src="../uploads/logo 3.png"></div>
        <div class="nav-item"><a href="/user/index.php">Trang chủ</a></div>

        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="dropdownToggle" onclick="toggleDropdown(event)">
                Danh mục
            </a>
            <ul class="dropdown-menu" id="category-menu">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <li><a class="dropdown-item" href="/user/index.php?category_id=<?= $category['id'] ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </a></li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li><em class="dropdown-item text-muted">Không có danh mục</em></li>
                <?php endif; ?>
            </ul>
        </div>

        <form class="nav-search" method="GET" action="/user/index.php">
            <i class="fa-solid fa-magnifying-glass search-toggle" onclick="toggleSearch()"></i>
            <input type="text" id="searchInput" name="search" class="search-input" placeholder="Tìm kiếm...">
        </form>

        <span class="account text me-2">
            <i class="fas fa-user-circle" style="color: #0091ae;"></i> <?= $_SESSION['user']['username'] ?>
        </span>

        <div class="nav-cta">
            <a href="/auth/logout.php" class="btn btn-primary">
                <i class="fas fa-sign-out-alt me-1"></i> Đăng xuất
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
                input.focus();
            }
        }
    </script>
    <div class="container">
</body>
</html>