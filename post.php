<?php require 'config/db.php';
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id=? AND status='approved'");
$stmt->execute([$id]);
$post = $stmt->fetch();
include 'includes/header.php';
echo "<h3>{$post['title']}</h3><p>{$post['content']}</p>";
include 'includes/footer.php';