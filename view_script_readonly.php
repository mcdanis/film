<?php
require 'db.php';

$script_id = $_GET['script_id'] ?? 0;

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
    SELECT s.*, sc.id AS content_id, sc.content_type, sc.content, sc.is_completed AS content_done
    FROM scenes s
    LEFT JOIN scene_contents sc ON sc.scene_id = s.id
    WHERE s.script_id = ?
    ORDER BY s.scene_number ASC, sc.id ASC
");
$stmt->execute([$script_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grupkan berdasarkan scene
$scenes = [];
foreach ($rows as $row) {
    $scene_id = $row['id'];
    if (!isset($scenes[$scene_id])) {
        $scenes[$scene_id] = [
            'scene_number' => $row['scene_number'],
            'scene_title' => $row['scene_title'],
            'is_completed' => $row['is_completed'],
            'contents' => [],
        ];
    }

    if ($row['content_id']) {
        $scenes[$scene_id]['contents'][] = [
            'id' => $row['content_id'],
            'type' => $row['content_type'],
            'text' => $row['content'],
            'is_completed' => $row['content_done'],
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Script: <?= htmlspecialchars($script['title']) ?></title>
    <style>
        body {
            font-family: Courier, monospace;
            background-color: #f5f5f5;
            padding: 40px;
        }
        .scene {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .scene-heading {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .action {
            text-align: left;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }
        .dialog {
            text-align: center;
            margin: 0 auto 20px;
            max-width: 50%;
            white-space: pre-wrap;
        }
        .checkbox-right {
            float: right;
        }
        .scene-heading.active {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }
        .content-action.active, .dialog.active{
            background:#d4edda
        }
    </style>
</head>
<body>

    <a href="/film">Home</a>
    <a href="/film/view_script.php?script_id=<?= $script_id ?>">Edit</a>
    <h1><?= htmlspecialchars($script['title']) ?></h1>

    <form action="save_status.php" method="post">
        <input type="hidden" name="script_id" value="<?= $script_id ?>">

        <?php foreach ($scenes as $scene_id => $scene): ?>
            <?php
                $total = count($scene['contents']);
                $done = 0;
                foreach ($scene['contents'] as $c) {
                    if ($c['is_completed']) $done++;
                }
                $percentage = $total > 0 ? round(($done / $total) * 100) : 0;
            ?>
            <div class="scene">
                <div style="font-size: 0.9em; color: #666; margin-bottom: 8px;">
                    ✅ <?= $done ?> dari <?= $total ?> selesai (<?= $percentage ?>%)
                </div>

                        <div class="scene-heading <?= $scene['is_completed'] ? 'active' : '' ?>" id="scene-heading-<?= $scene_id ?>">
            <input type="checkbox" 
                name="scene_done[<?= $scene_id ?>]" 
                class="scene-checkbox" 
                <?= $scene['is_completed'] ? 'checked' : '' ?>
                data-scene-id="<?= $scene_id ?>">
            SCENE <?= $scene['scene_number'] ?> : <?= $scene['scene_title'] ?>
        </div>



                <?php foreach ($scene['contents'] as $content): ?>
                    <?php if ($content['type'] === 'paragraph'): ?>
                        <div class="action">
                            <label>
                                <input type="checkbox" name="content_done[<?= $content['id'] ?>]" <?= $content['is_completed'] ? 'checked' : '' ?>>
                                <span class="content-action <?= $content['is_completed'] ? 'active' : '' ?>"><?= htmlspecialchars($content['text']) ?></span>
                            </label>
                        </div>
                    <?php elseif ($content['type'] === 'dialog'): ?>
                        <div class="dialog <?= $content['is_completed'] ? 'active' : '' ?>"><input type="checkbox" name="content_done[<?= $content['id'] ?>]" <?= $content['is_completed'] ? 'checked' : '' ?>> <?= htmlspecialchars($content['text']) ?></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit">Simpan Status</button>
    </form>

    <script>
    document.querySelectorAll('.toggle-complete').forEach(cb => {
        cb.addEventListener('change', function() {
            const id = this.dataset.id;
            const type = this.dataset.type;
            const status = this.checked ? 1 : 0;

            fetch('update_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${id}&type=${type}&status=${status}`
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) alert('Gagal memperbarui status.');
            });
        });
    });
    </script>

</body>
</html>
