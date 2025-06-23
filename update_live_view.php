<?php
require 'db.php';

$id = $_POST['id'] ?? null;
$type = $_POST['type'] ?? null;
$status = $_POST['status'] ?? null;

if (!in_array($type, ['scene', 'content']) || !in_array($status, [0, 1, '0', '1']) || !is_numeric($id)) {
    echo json_encode(['success' => false, 'error' => 'Data tidak valid.']);
    exit;
}

$table = $type === 'scene' ? 'scenes' : 'scene_contents';

try {
    $stmt = $pdo->prepare("UPDATE $table SET is_completed = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
