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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <style>
        body {
            background: #f2f2f2;
            padding: 30px;
        }
        .scene-block {
            background: #fff;
            box-shadow: 0 0 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
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
<?php
    include('header.php');
?>
<div class="container">
    <h2 class="mb-4">Edit Script: <?= htmlspecialchars($script['title']) ?></h2>
    <form id="sceneForm" action="save_script.php" method="post">
        <input type="hidden" name="script_id" value="<?= $script_id ?>">
        <div id="scenes">
            <?php foreach ($scenes_data as $scene): ?>
                <div class="scene-block border-left border-primary" data-scene-id="<?= $scene['id'] ?>">
                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div class="w-75">
                                <label class="w-100">Scene #
                                    <input type="text" class="form-control form-control-sm w-100" name="scene_title[]" value="<?= htmlspecialchars($scene['scene_title']) ?>" required>
                                </label>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="scene_completed_<?= $scene['id'] ?>" name="scene_completed[]" value="<?= $scene['id'] ?>" <?= $scene['is_completed'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="scene_completed_<?= $scene['id'] ?>">Selesai Scene</label>
                                </div>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-danger delete-btn" onclick="deleteScene(this, <?= $scene['id'] ?>)">Hapus Scene</button>
                            </div>
                        </div>
                    </div>

                    <div class="contents">
                        <?php foreach ($scene['contents'] as $content): ?>
                            <div data-content-id="<?= $content['id'] ?>" class="mb-3">
                                <textarea class="summernote form-control" name="content[<?= array_search($scene, array_values($scenes_data)) ?>][]" rows="2" required><?= htmlspecialchars($content['text']) ?></textarea>
                                <input type="hidden" name="content_type[<?= array_search($scene, array_values($scenes_data)) ?>][]" value="<?= $content['type'] ?>">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="content_completed[<?= array_search($scene, array_values($scenes_data)) ?>][]" value="<?= $content['id'] ?>" <?= $content['is_completed'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Selesai</label>
                                </div>
                                <button type="button" class="btn btn-sm btn btn-outline-danger " onclick="deleteContent(this, <?= $content['id'] ?>)">Hapus</button>
                            </div>
                            <hr>
                        <?php endforeach; ?>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addContent(this, 'paragraph')">+ Paragraf</button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addContent(this, 'dialog')">+ Dialog</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-primary" onclick="addScene()">+ Scene Baru</button>
        <button type="submit" class="btn btn-success">Simpan Perubahan</button>
    </form>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Popper & Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- Summernote -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.js"></script>

<script>
let sceneCounter = <?= count($scenes_data) ?>; // Mulai counter dari jumlah scene yang sudah ada

function addScene() {
    let sceneId = `scene_${sceneCounter++}`;
    let sceneHTML = `
    <div class="scene-block" data-scene-id="new">
        <div class="form-group w-100">
            <label clas="w-100">Scene #<input type="text" class="form-control form-control-sm w-100" name="scene_title[]" required></label>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="scene_completed[]" value="new">
                <label class="form-check-label">Selesai Scene</label>
            </div>
            <button type="button" class="btn btn-danger delete-btn" onclick="deleteScene(this, 'new')">Hapus Scene</button>
        </div>
        
        <div class="contents">
            <button type="button" class="btn btn-secondary btn-sm" onclick="addContent(this, 'paragraph')">+ Paragraf</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addContent(this, 'dialog')">+ Dialog</button>
        </div>
    </div>`;
    document.getElementById("scenes").insertAdjacentHTML('beforeend', sceneHTML);
    updateFormIndexes(); // Perbarui indeks setelah menambah scene baru
}

function addContent(button, type) {
    const wrapper = button.closest(".scene-block").querySelector(".contents");
    const sceneBlocks = Array.from(document.querySelectorAll('.scene-block'));
    const sceneIndex = sceneBlocks.indexOf(button.closest('.scene-block'));

    let html = `
        <div data-content-id="new" class="mb-3">
            <textarea class="summernote form-control" name="content[${sceneIndex}][]" rows="2" required></textarea>
            <input type="hidden" name="content_type[${sceneIndex}][]" value="${type}">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="content_completed[${sceneIndex}][]" value="new">
                <label class="form-check-label">Selesai</label>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteContent(this, 'new')">Hapus</button>
        </div>
    `;
    button.closest(".contents").querySelector('button[onclick*="addContent"]').insertAdjacentHTML('beforebegin', html);
    updateFormIndexes();
    $(wrapper).find('.summernote').last().summernote();
}

function deleteScene(button, sceneId) {
    if (confirm('Apakah Anda yakin ingin menghapus scene ini?')) {
        const sceneBlock = button.closest('.scene-block');
        if (sceneId !== 'new') {
            fetch('delete_data.php', {
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
        } else {
            sceneBlock.remove();
            updateFormIndexes();
        }
    }
}

function deleteContent(button, contentId) {
    if (confirm('Apakah Anda yakin ingin menghapus konten ini?')) {
        const contentDiv = button.closest('div[data-content-id]');
        if (contentId !== 'new') {
            fetch('delete_data.php', {
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
        } else {
            contentDiv.remove();
            updateFormIndexes();
        }
    }
}

function updateFormIndexes() {
    const sceneBlocks = document.querySelectorAll('.scene-block');
    sceneBlocks.forEach((sceneBlock, sceneIndex) => {
        const sceneTitleInput = sceneBlock.querySelector('input[name^="scene_title"]');
        if (sceneTitleInput) {
            sceneTitleInput.name = `scene_title[${sceneIndex}]`;
        }
        const sceneCompletedInput = sceneBlock.querySelector('input[name^="scene_completed"]');
        if (sceneCompletedInput) {
            sceneCompletedInput.name = `scene_completed[${sceneIndex}]`;
        }

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

document.addEventListener('DOMContentLoaded', updateFormIndexes);
$(document).ready(function() {
  $('.summernote').summernote();
});
</script>

</body>
</html>
