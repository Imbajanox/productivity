<?php
/**
 * API: Delete Note Folder
 * DELETE /api/notes/folders/delete.php
 */

require_once __DIR__ . '/../../../includes/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $data = getJsonInput();
    $userId = getCurrentUserId();
    
    if (empty($data['id'])) {
        jsonResponse(['success' => false, 'error' => 'Ordner ID erforderlich'], 400);
    }
    
    $folderId = (int)$data['id'];
    
    // Check ownership
    $folder = dbFetchOne(
        "SELECT * FROM note_folders WHERE id = ? AND user_id = ?",
        [$folderId, $userId]
    );
    
    if (!$folder) {
        jsonResponse(['success' => false, 'error' => 'Ordner nicht gefunden'], 404);
    }
    
    // Move notes to no folder
    dbQuery("UPDATE notes SET folder_id = NULL WHERE folder_id = ?", [$folderId]);
    
    // Delete folder
    dbQuery("DELETE FROM note_folders WHERE id = ?", [$folderId]);
    
    jsonResponse(['success' => true, 'message' => 'Ordner gelöscht']);
    
} catch (Exception $e) {
    error_log("Error in notes/folders/delete.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Löschen des Ordners'], 500);
}
