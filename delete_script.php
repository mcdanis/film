<?php
require 'db.php'; // Include your database connection

// Get the script_id from the request
$script_id = $_GET['script_id'] ?? null;

// Validate the script_id
if (!is_numeric($script_id)) {
    die("Invalid script ID");
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Delete all scene contents associated with the scenes of the script
    $stmt = $pdo->prepare("DELETE FROM scene_contents WHERE scene_id IN (SELECT id FROM scenes WHERE script_id = ?)");
    $stmt->execute([$script_id]);

    // Delete all scenes associated with the script
    $stmt = $pdo->prepare("DELETE FROM scenes WHERE script_id = ?");
    $stmt->execute([$script_id]);

    // Finally, delete the script itself
    $stmt = $pdo->prepare("DELETE FROM scripts WHERE id = ?");
    $stmt->execute([$script_id]);

    // Commit the transaction
    $pdo->commit();

    // Redirect or output success message
    header("Location: index.php"); // Redirect to the index page or wherever appropriate
    exit();
    
} catch (PDOException $e) {
    // Rollback if any error occurred
    $pdo->rollBack();
    die("Error deleting script: " . $e->getMessage());
}
?>
