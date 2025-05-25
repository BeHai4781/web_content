<?php
require_once __DIR__ . '/../config/db.php';

$query = "SELECT id, name FROM categories";
$stmt = $pdo->query($query);
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html><html lang="vi">
<head>
    <meta charset="UTF-8"><title>Trang tin tức và blog PNT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../includes/style.css?v=<?= time(); ?>">

</head>
<body>
    <nav class="custom-navbar">
        <div class="nav-logo">Logo</div>
        <div class="nav-item"><a href="/index.php">Trang chủ</a></div>

        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Danh mục
            </a>
            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                <?php foreach ($categories as $category): ?>
                    <li><a class="dropdown-item" href="#"><?= htmlspecialchars($category['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="nav-search">
            <i class="search-icon">&#128269;</i>
            <input type="text" class="search-input" placeholder="Tìm kiếm...">
        </div>
        <div class="nav-item" style="text-align: left;">
            <a href="../auth/login.php">Đăng xuất</a>
        </div>

        <div class="nav-cta">
            <a href="/auth/register.php" class="btn btn-primary">Đăng ký</a>
            <a href="/auth/login.php" class="btn">Đăng nhập</a>
        </div>
    </nav>

    <script>
        function toggleDropdown(event) {
            event.preventDefault();
            document.getElementById("category-menu").classList.toggle("show");
        }
    </script>
    <div class="container">