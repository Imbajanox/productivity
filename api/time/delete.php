<?php
/**
 * API: Delete Time Entry
 * DELETE /api/time/delete.php
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
        jsonResponse(['success' => false, 'error' => 'Eintrag ID erforderlich'], 400);
    }
    
    $entryId = (int)$data['id'];
    
    // Check ownership
    $existing = dbFetchOne(
        "SELECT * FROM time_entries WHERE id = ? AND user_id = ?",
        [$entryId, $userId]
    );
    
    if (!$existing) {
        jsonResponse(['success' => false, 'error' => 'Eintrag nicht gefunden'], 404);
    }
    
    dbQuery("DELETE FROM time_entries WHERE id = ?", [$entryId]);
    
    jsonResponse(['success' => true, 'message' => 'Eintrag gelöscht']);
    
} catch (Exception $e) {
    error_log("Error in time/delete.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Löschen'], 500);
}
