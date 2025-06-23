<?php
require 'db.php';
$script_id = $_GET['script_id'];

// Ambil data script
$stmt = $pdo->prepare("SELECT * FROM scripts WHERE id = ?");
$stmt->execute([$script_id]);
$script = $stmt->fetch();

if (!$script) {
    echo "Script tidak ditemukan.";
    exit;
}

// Ambil scenes dan kontennya
$stmt = $pdo->prepare("
    SELECT s.id AS scene_id, s.scene_number, s.scene_title, s.is_completed AS scene_completed,
           sc.id AS content_id, sc.content_type, sc.content, sc.is_completed AS content_completed
    FROM scenes s
    LEFT JOIN scene_contents sc ON sc.scene_id = s.id
    WHERE s.script_id = ?
    ORDER BY s.scene_number ASC, sc.id ASC
");
$stmt->execute([$script_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grupkan berdasarkan scene
$scenes_data = [];
foreach ($rows as $row) {
    $scene_id = $row['scene_id'];
    if (!isset($scenes_data[$scene_id])) {
        $scenes_data[$scene_id] = [
            'id' => $row['scene_id'],
            'scene_number' => $row['scene_number'],
            'scene_title' => $row['scene_title'],
            'is_completed' => $row['scene_completed'],
            'contents' => [],
        ];
    }

    if ($row['content_id']) {
        $scenes_data[$scene_id]['contents'][] = [
            'id' => $row['content_id'],
            'type' => $row['content_type'],
            'text' => $row['content'],
            'is_completed' => $row['content_completed'],
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Script: <?= htmlspecialchars($script['title']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            padding: 30px;
        }
        .scene-block {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            background: #fff;
            box-shadow: 0 0 8px rgba(0,0,0,0.05);
        }
        .scene-block label {
            font-weight: bold;
            margin-right: 10px;
        }
        .scene-block input[type="text"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
        }
        .scene-block .contents {
            margin-top: 15px;
            padding-left: 20px;
            border-left: 2px solid #eee;
        }
        .scene-block .contents div {
            margin-bottom: 10px;
        }
        .scene-block textarea {
            width: 95%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
        button {
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .delete-btn {
            background-color: #dc3545;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

<h2>Edit Script: <?= htmlspecialchars($script['title']) ?></h2>
<form id="sceneForm" action="save_script.php" method="post">
    <input type="hidden" name="script_id" value="<?= $script_id ?>">
    <div id="scenes">
        <?php foreach ($scenes_data as $scene): ?>
            <div class="scene-block" data-scene-id="<?= $scene['id'] ?>">
                <label>Scene #<input type="text" name="scene_title[]" value="<?= htmlspecialchars($scene['scene_title']) ?>" required></label>
                <input type="checkbox" name="scene_completed[]" value="<?= $scene['id'] ?>" <?= $scene['is_completed'] ? 'checked' : '' ?>> Selesai Scene
                <button type="button" class="delete-btn" onclick="deleteScene(this, <?= $scene['id'] ?>)">Hapus Scene</button>
                <div class="contents">
                    <?php foreach ($scene['contents'] as $content): ?>
                        <div data-content-id="<?= $content['id'] ?>">
                            <textarea name="content[<?= array_search($scene, array_values($scenes_data)) ?>][]" rows="2" cols="50" required><?= htmlspecialchars($content['text']) ?></textarea>
                            <input type="hidden" name="content_type[<?= array_search($scene, array_values($scenes_data)) ?>][]" value="<?= $content['type'] ?>">
                            <label><input type="checkbox" name="content_completed[<?= array_search($scene, array_values($scenes_data)) ?>][]" value="<?= $content['id'] ?>" <?= $content['is_completed'] ? 'checked' : '' ?>> Selesai</label>
                            <button type="button" class="delete-btn" onclick="deleteContent(this, <?= $content['id'] ?>)">Hapus</button>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" onclick="addContent(this, 'paragraph')">+ Paragraf</button>
                    <button type="button" onclick="addContent(this, 'dialog')">+ Dialog</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" onclick="addScene()">+ Scene Baru</button>
    <button type="submit">Simpan Perubahan</button>
</form>

<script>
let sceneCounter = <?= count($scenes_data) ?>; // Mulai counter dari jumlah scene yang sudah ada

function addScene() {
    let sceneId = `scene_${sceneCounter++}`;
    let sceneHTML = `
    <div class="scene-block" data-scene-id="new">
        <label>Scene #<input type="text" name="scene_title[]" required></label>
        <input type="checkbox" name="scene_completed[]" value="new"> Selesai Scene
        <button type="button" class="delete-btn" onclick="deleteScene(this, 'new')">Hapus Scene</button>
        <div class="contents">
            <button type="button" onclick="addContent(this, 'paragraph')">+ Paragraf</button>
            <button type="button" onclick="addContent(this, 'dialog')">+ Dialog</button>
        </div>
    </div>`;
    document.getElementById("scenes").insertAdjacentHTML('beforeend', sceneHTML);
    updateFormIndexes(); // Perbarui indeks setelah menambah scene baru
}

function addContent(button, type) {
    const wrapper = button.closest(".scene-block").querySelector(".contents");
    // Dapatkan indeks scene berdasarkan posisinya di DOM
    const sceneBlocks = Array.from(document.querySelectorAll('.scene-block'));
    const sceneIndex = sceneBlocks.indexOf(button.closest('.scene-block'));

    let html = `
        <div data-content-id="new">
            <textarea name="content[${sceneIndex}][]" rows="2" cols="50" required></textarea>
            <input type="hidden" name="content_type[${sceneIndex}][]" value="${type}">
            <label><input type="checkbox" name="content_completed[${sceneIndex}][]" value="new"> Selesai</label>
            <button type="button" class="delete-btn" onclick="deleteContent(this, 'new')">Hapus</button>
        </div>
    `;
    // Sisipkan sebelum tombol "Add Paragraph" dan "Add Dialog"
    button.closest(".contents").querySelector('button[onclick*="addContent"]').insertAdjacentHTML('beforebegin', html);
    updateFormIndexes(); // Perbarui indeks setelah menambah konten baru
}

function deleteScene(button, sceneId) {
    if (confirm('Apakah Anda yakin ingin menghapus scene ini?')) {
        const sceneBlock = button.closest('.scene-block');
        if (sceneId !== 'new') { // Jika scene sudah ada di database
            // Kirim permintaan AJAX untuk menghapus dari database
            fetch('delete_data.php', { // Kita akan membuat file delete_data.php
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `type=scene&id=${sceneId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    sceneBlock.remove();
                    updateFormIndexes();
                } else {
                    alert('Gagal menghapus scene: ' + data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        } else { // Jika scene baru ditambahkan dan belum disimpan
            sceneBlock.remove();
            updateFormIndexes();
        }
    }
}

function deleteContent(button, contentId) {
    if (confirm('Apakah Anda yakin ingin menghapus konten ini?')) {
        const contentDiv = button.closest('div[data-content-id]');
        if (contentId !== 'new') { // Jika konten sudah ada di database
            // Kirim permintaan AJAX untuk menghapus dari database
            fetch('delete_data.php', { // Kita akan membuat file delete_data.php
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `type=content&id=${contentId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contentDiv.remove();
                    updateFormIndexes();
                } else {
                    alert('Gagal menghapus konten: ' + data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        } else { // Jika konten baru ditambahkan dan belum disimpan
            contentDiv.remove();
            updateFormIndexes();
        }
    }
}

// Fungsi untuk memperbarui indeks array pada nama input setelah penambahan/penghapusan
function updateFormIndexes() {
    const sceneBlocks = document.querySelectorAll('.scene-block');
    sceneBlocks.forEach((sceneBlock, sceneIndex) => {
        // Perbarui nama input untuk scene_title dan scene_completed
        const sceneTitleInput = sceneBlock.querySelector('input[name^="scene_title"]');
        if (sceneTitleInput) {
            sceneTitleInput.name = `scene_title[${sceneIndex}]`;
        }
        const sceneCompletedInput = sceneBlock.querySelector('input[name^="scene_completed"]');
        if (sceneCompletedInput) {
            sceneCompletedInput.name = `scene_completed[${sceneIndex}]`;
        }

        // Perbarui nama input untuk content dan content_type di dalam scene ini
        const contentTextareas = sceneBlock.querySelectorAll('textarea[name^="content"]');
        contentTextareas.forEach(textarea => {
            textarea.name = `content[${sceneIndex}][]`;
        });
        const contentTypeInputs = sceneBlock.querySelectorAll('input[name^="content_type"]');
        contentTypeInputs.forEach(input => {
            input.name = `content_type[${sceneIndex}][]`;
        });
        const contentCompletedInputs = sceneBlock.querySelectorAll('input[name^="content_completed"]');
        contentCompletedInputs.forEach(input => {
            input.name = `content_completed[${sceneIndex}][]`;
        });
    });
}

// Panggil saat halaman dimuat untuk memastikan indeks benar
document.addEventListener('DOMContentLoaded', updateFormIndexes);

</script>

</body>
</html>
