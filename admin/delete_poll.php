<?php
require '../db.php';

$id = $_GET['id'] ?? null;
if (!$id) die('Invalid poll ID.');

$pollStmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
$pollStmt->execute([$id]);
$poll = $pollStmt->fetch();

if (!$poll) die('Poll not found.');

$pdo->prepare("DELETE FROM polls WHERE id = ?")->execute([$id]);

header('Location: dashboard.php');
exit;
?>
