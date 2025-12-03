<?php
/**
 * API: Get Note
 * GET /api/notes/get.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

try {
    $userId = getCurrentUserId();
    
    if (empty($_GET['id'])) {
        jsonResponse(['success' => false, 'error' => 'Notiz ID erforderlich'], 400);
    }
    
    $noteId = (int)$_GET['id'];
    
    $note = dbFetchOne(
        "SELECT n.*, p.name as project_name, p.color as project_color
         FROM notes n
         LEFT JOIN projects p ON n.project_id = p.id
         WHERE n.id = ? AND n.user_id = ?",
        [$noteId, $userId]
    );
    
    if (!$note) {
        jsonResponse(['success' => false, 'error' => 'Notiz nicht gefunden'], 404);
    }
    
    jsonResponse(['success' => true, 'data' => $note]);
    
} catch (Exception $e) {
    error_log("Error in notes/get.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Laden der Notiz'], 500);
}
