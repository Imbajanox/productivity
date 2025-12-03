<?php
/**
 * API: List Note Folders
 * GET /api/notes/folders/list.php
 */

require_once __DIR__ . '/../../../includes/init.php';

requireAuth();

try {
    $userId = getCurrentUserId();
    
    $folders = dbFetchAll(
        "SELECT nf.*, 
                (SELECT COUNT(*) FROM notes n WHERE n.folder_id = nf.id AND n.user_id = ?) as note_count
         FROM note_folders nf
         WHERE nf.user_id = ?
         ORDER BY nf.position ASC, nf.name ASC",
        [$userId, $userId]
    );
    
    jsonResponse(['success' => true, 'data' => $folders]);
    
} catch (Exception $e) {
    error_log("Error in notes/folders/list.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Laden der Ordner'], 500);
}
