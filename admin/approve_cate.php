<?php
require '../config/db.php';
require '../config/mailer.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../includes/header_admin.php';
// Nếu chưa đăng nhập thì chuyển hướng
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Bạn chưa đăng nhập!'); window.location.href = '/auth/login.php';</script>";
    exit;
}
// Xoá bài viết
if (isset($_GET['delete']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
    $postId = intval($_GET['delete']);

    $stmt = $pdo->prepare("SELECT posts.title, users.email, users.username 
                           FROM posts 
                           JOIN users ON posts.user_id = users.id 
                           WHERE posts.id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if ($post) {
        $delStmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $delStmt->execute([$postId]);

        try {
            $mail = getMailer();
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->addAddress($post['email'], $post['username']);
            $mail->Subject = "Bài viết của bạn đã bị xoá";
            $mail->Body = "Xin chào {$post['username']},<br><br>Bài viết '<strong>{$post['title']}</strong>' đã bị quản trị viên xoá vì lý do không phù hợp.<br><br>Trân trọng.";
            $mail->send();
        } catch (Exception $e) {
            error_log("Không thể gửi email: " . $mail->ErrorInfo);
        }

        header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
}

// Lấy danh sách danh mục
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

$selectedCat = $_GET['category'] ?? null;
$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();

// Tìm kiếm
$where = [];
$params = [];

if (!empty($_GET['keyword'])) {
    $where[] = "(posts.title LIKE ? OR posts.content LIKE ?)";
    $params[] = '%' . $_GET['keyword'] . '%';
    $params[] = '%' . $_GET['keyword'] . '%';
}
if (!empty($_GET['author'])) {
    $where[] = "(users.username LIKE ? OR users.fullname LIKE ?)";
    $params[] = '%' . $_GET['author'] . '%';
    $params[] = '%' . $_GET['author'] . '%';
}
if (!empty($_GET['date'])) {
    $where[] = "DATE(posts.created_at) = ?";
    $params[] = $_GET['date'];
}
if ($selectedCat) {
    $where[] = "posts.category_id = ?";
    $params[] = $selectedCat;
}

$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT posts.*, users.username 
                       FROM posts 
                       JOIN users ON posts.user_id = users.id 
                       $whereSQL 
                       ORDER BY posts.created_at DESC 
                       LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$posts = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts JOIN users ON posts.user_id = users.id $whereSQL");
$countStmt->execute($params);
$totalInCategory = $countStmt->fetchColumn();
$totalPages = ceil($totalInCategory / $limit);
?>

<div class="container mt-4">
    <h1 class="text-center">Danh sách thống kê các chuyên mục</h1>
    <a href="../admin/create_cate.php" class="btn btn-secondary">Chỉnh sửa danh mục</a>

    <!-- Form tìm kiếm -->
    <form method="GET" class="my-3">
        <div class="row">
            <div class="col-md-3">
                <input type="text" name="keyword" class="form-control" placeholder="Từ khóa" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="author" class="form-control" placeholder="Người đăng" value="<?= htmlspecialchars($_GET['author'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <button class="btn btn-secondary w-100">Tìm kiếm</button>
            </div>
        </div>
    </form>

    <div class="row my-4">
        <?php foreach ($categories as $cat): 
            $count = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ?");
            $count->execute([$cat['id']]);
            $catCount = $count->fetchColumn();
        ?>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <a href="?category=<?= $cat['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($cat['name']) ?></a><br>
                    <small><?= $catCount ?> bài viết</small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <strong>Thông tin tag:</strong>
            <?= $selectedCat ? "Danh mục hiện tại có $totalInCategory bài viết" : "Tổng số bài viết: $totalPosts" ?>
        </div>
    </div>

    <?php foreach ($posts as $post): ?>
    <div class="card mb-2">
        <div class="card-body">
            <h5>
                <a href="/post.php?slug=<?= htmlspecialchars($post['slug']) ?>" target="_blank">
                    <?= htmlspecialchars($post['title']) ?>
                </a>
            </h5>
            <p>
                Người đăng: <?= htmlspecialchars($post['username']) ?> |
                Ngày: <?= $post['created_at'] ?> |
                Lượt xem: <?= $post['views'] ?>
            </p>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <a href="?delete=<?= $post['id'] ?>" onclick="return confirm('Bạn có chắc muốn xoá bài viết này không?')" class="btn btn-danger btn-sm">Xoá</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Phân trang -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<?php include '../includes/footer.php'; ?>
