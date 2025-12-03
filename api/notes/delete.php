<?php
/**
 * API: Delete Note
 * DELETE /api/notes/delete.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $data = getJsonInput();
    $userId = getCurrentUserId();
    
    if (empty($data['id'])) {
        jsonResponse(['success' => false, 'error' => 'Notiz ID erforderlich'], 400);
    }
    
    $noteId = (int)$data['id'];
    
    // Check ownership
    $existing = dbFetchOne(
        "SELECT * FROM notes WHERE id = ? AND user_id = ?",
        [$noteId, $userId]
    );
    
    if (!$existing) {
        jsonResponse(['success' => false, 'error' => 'Notiz nicht gefunden'], 404);
    }
    
    dbQuery("DELETE FROM notes WHERE id = ?", [$noteId]);
    
    jsonResponse(['success' => true, 'message' => 'Notiz gelöscht']);
    
} catch (Exception $e) {
    error_log("Error in notes/delete.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Löschen'], 500);
}
