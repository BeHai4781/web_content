<?php session_start(); require '../config/db.php';
if (!isset($_SESSION['user'])) die("Cần đăng nhập");
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $pdo->prepare("INSERT INTO posts(title, content, user_id) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['title'], $_POST['content'], $_SESSION['user']['id']]);
    header("Location: ../index.php");
}
?><form method="post"><input name="title"><textarea name="content"></textarea><button>Đăng bài</button></form>