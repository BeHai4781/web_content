<?php session_start(); require '../config/db.php';
if ($_SESSION['user']['role'] != 'admin') die("Không có quyền");
if (isset($_GET['approve'])) {
    $stmt = $pdo->prepare("UPDATE posts SET status='approved' WHERE id=?");
    $stmt->execute([$_GET['approve']]);
}
$posts = $pdo->query("SELECT * FROM posts WHERE status='pending'")->fetchAll();
foreach ($posts as $post) {
    echo "<p>{$post['title']} <a href='?approve={$post['id']}'>Duyệt</a></p>";
}