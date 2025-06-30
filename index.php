<?php
require 'db.php';

$stmt = $pdo->query("SELECT id, title FROM scripts ORDER BY id DESC");
$scripts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daftar Skrip Film</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            padding: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 12px 20px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f7f7f7;
            text-align: left;
        }
        tr:hover {
            background-color: #f0f0f0;
        }
        a {
            color: #007BFF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        h1 {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<?php
    include('header.php');
?>
<h2>Tambah Script Baru</h2>
<form action="add_script.php" method="post">
    Judul Script: <input type="text" name="title" required>
    <button type="submit">Mulai</button>
</form>
<h3>Daftar Skrip</h3>
<table>
    <thead>
        <tr>
            <th>Judul</th>
            <th>Lihat</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($scripts) > 0): ?>
            <?php foreach ($scripts as $script): ?>
                <tr>
                    <td><?= htmlspecialchars($script['title']) ?></td>
                    <td>
                        <a href="view_script_readonly.php?script_id=<?= $script['id'] ?>">Lihat</a> |
                        <a href="delete_script.php?script_id=<?= $script['id'] ?>" onclick="return confirm('Yakin ingin menghapus skrip ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3">Belum ada skrip.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
 
</body>
</html>
