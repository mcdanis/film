<?php
header('Content-Type: application/json');
require 'db.php';

$type = $_POST['type'] ?? null;
$id = $_POST['id'] ?? null;

$response = ['success' => false];

try {
    $pdo->beginTransaction();

    if ($type === 'scene') {
        // Hapus konten scene dulu
        $stmt = $pdo->prepare("DELETE FROM scene_contents WHERE scene_id = ?");
        $stmt->execute([$id]);

        // Lalu hapus scene-nya
        $stmt = $pdo->prepare("DELETE FROM scenes WHERE id = ?");
        $stmt->execute([$id]);

        $response['success'] = true;
    } elseif ($type === 'content') {
        $stmt = $pdo->prepare("DELETE FROM scene_contents WHERE id = ?");
        $stmt->execute([$id]);

        $response['success'] = true;
    } else {
        $response['error'] = 'Invalid type';
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
