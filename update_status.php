<?php
require 'db.php';

$type = $_POST['type'];
$id = $_POST['id'];
$status = $_POST['status'];

$table = $type === 'scene' ? 'scenes' : 'scene_contents';
$column = $type === 'scene' ? 'id' : 'id';

$stmt = $pdo->prepare("UPDATE {$table} SET is_completed = ? WHERE {$column} = ?");
$stmt->execute([$status, $id]);

echo json_encode(['success' => true]);