<?php
require 'db.php';

$script_id = $_POST['script_id'] ?? 0;
$scene_done = $_POST['scene_done'] ?? [];
$content_done = $_POST['content_done'] ?? [];

// Reset semua ke 0 dulu (agar uncheck juga ikut tersimpan)
$pdo->prepare("UPDATE scenes SET is_completed = 0 WHERE script_id = ?")->execute([$script_id]);

$scene_ids = array_keys($scene_done);
if ($scene_ids) {
    $in = implode(',', array_fill(0, count($scene_ids), '?'));
    $stmt = $pdo->prepare("UPDATE scenes SET is_completed = 1 WHERE id IN ($in)");
    $stmt->execute($scene_ids);
}

$pdo->prepare("
    UPDATE scene_contents 
    SET is_completed = 0 
    WHERE scene_id IN (SELECT id FROM scenes WHERE script_id = ?)
")->execute([$script_id]);

$content_ids = array_keys($content_done);
if ($content_ids) {
    $in = implode(',', array_fill(0, count($content_ids), '?'));
    $stmt = $pdo->prepare("UPDATE scene_contents SET is_completed = 1 WHERE id IN ($in)");
    $stmt->execute($content_ids);
}

header("Location: view_script_readonly.php?script_id=" . $script_id);
exit;
