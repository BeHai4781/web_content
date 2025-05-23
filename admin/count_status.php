<?php
require '../config/db.php';

$latestPosts = $pdo->query("SELECT * FROM posts WHERE status='approved' ORDER BY created_at DESC LIMIT 5")->fetchAll();
$popularPosts = $pdo->query("SELECT * FROM posts WHERE status='approved' ORDER BY views DESC LIMIT 5")->fetchAll();

include '../includes/header_admin.php';
?>

<div class="container mt-4">
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Quản trị hệ thống</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="#">Danh mục</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Tác giả</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Tin tức</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Hỗ trợ</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <h3>Bài viết mới nhất</h3>
    <?php foreach ($latestPosts as $post): ?>
        <div class="card mb-2">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                <a href="post.php?id=<?= $post['id'] ?>" class="btn btn-primary btn-sm">Xem chi tiết</a>
            </div>
        </div>
    <?php endforeach; ?>

    <h3 class="mt-4">Bài viết được quan tâm nhiều nhất</h3>
    <?php foreach ($popularPosts as $post): ?>
        <div class="card mb-2">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                <a href="post.php?id=<?= $post['id'] ?>" class="btn btn-secondary btn-sm">Xem chi tiết</a>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="mt-5">
        <h4>Đánh giá trang</h4>
        <form method="post" action="submit_rating.php">
            <div class="mb-3">
                <label for="rating" class="form-label">Chọn mức đánh giá:</label>
                <select class="form-select" id="rating" name="rating">
                    <option value="5">Rất hài lòng (5 sao)</option>
                    <option value="4">Hài lòng (4 sao)</option>
                    <option value="3">Bình thường (3 sao)</option>
                    <option value="2">Chưa tốt (2 sao)</option>
                    <option value="1">Tệ (1 sao)</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="comment" class="form-label">Ý kiến của bạn:</label>
                <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-success">Gửi đánh giá</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
