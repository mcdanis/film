<?php
require 'db.php';

$title = $_POST['title'];
$stmt = $pdo->prepare("INSERT INTO scripts (title) VALUES (?)");
$stmt->execute([$title]);
$script_id = $pdo->lastInsertId();
header("Location: view_script.php?script_id=$script_id");
exit;