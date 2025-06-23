<?php
require 'db.php';

$script_id = $_POST['script_id'];
$scene_titles = $_POST['scene_title']; // Mengubah nama variabel untuk kejelasan
$scene_completed_ids = $_POST['scene_completed'] ?? []; // Mengambil ID scene yang dicentang
$contents_data = $_POST['content'];
$content_types = $_POST['content_type'];
$content_completed_ids = $_POST['content_completed'] ?? []; // Mengambil ID konten yang dicentang

// Hapus semua scene dan konten yang terkait dengan script_id ini
// Ini adalah pendekatan sederhana untuk "edit" dengan menghapus dan memasukkan ulang.
// Untuk solusi UPDATE yang lebih canggih, Anda perlu membandingkan data yang ada dengan data yang dikirim.
$pdo->prepare("DELETE FROM scene_contents WHERE scene_id IN (SELECT id FROM scenes WHERE script_id = ?)")->execute([$script_id]);
$pdo->prepare("DELETE FROM scenes WHERE script_id = ?")->execute([$script_id]);


foreach ($scene_titles as $index => $scene_title) {
    // Periksa apakah scene ini adalah scene baru atau yang sudah ada
    // Karena kita menghapus dan memasukkan ulang, semua akan dianggap baru di sini.
    $is_scene_done = in_array($index, array_keys($scene_completed_ids)) ? 1 : 0; // Cek berdasarkan indeks

    $stmt = $pdo->prepare("INSERT INTO scenes (script_id, scene_number, scene_title, is_completed) VALUES (?, ?, ?, ?)");
    $stmt->execute([$script_id, $index + 1, $scene_title, $is_scene_done]);
    $scene_id = $pdo->lastInsertId();

    if (isset($contents_data[$index]) && is_array($contents_data[$index])) {
        foreach ($contents_data[$index] as $j => $text) {
            $type = $content_types[$index][$j];
            $is_done = in_array($j, array_keys($content_completed_ids[$index] ?? [])) ? 1 : 0; // Cek berdasarkan indeks

            $stmt2 = $pdo->prepare("INSERT INTO scene_contents (scene_id, content_type, content, is_completed) VALUES (?, ?, ?, ?)");
            $stmt2->execute([$scene_id, $type, $text, $is_done]);
        }
    }
}

header("Location: view_script.php?script_id=$script_id");
exit;
