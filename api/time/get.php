<?php
/**
 * API: Get Time Entry
 * GET /api/time/get.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

try {
    $userId = getCurrentUserId();
    
    if (empty($_GET['id'])) {
        jsonResponse(['success' => false, 'error' => 'Eintrag ID erforderlich'], 400);
    }
    
    $entryId = (int)$_GET['id'];
    
    $entry = dbFetchOne(
        "SELECT te.*, p.name as project_name, p.color as project_color
         FROM time_entries te
         LEFT JOIN projects p ON te.project_id = p.id
         WHERE te.id = ? AND te.user_id = ?",
        [$entryId, $userId]
    );
    
    if (!$entry) {
        jsonResponse(['success' => false, 'error' => 'Eintrag nicht gefunden'], 404);
    }
    
    jsonResponse(['success' => true, 'data' => $entry]);
    
} catch (Exception $e) {
    error_log("Error in time/get.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Laden'], 500);
}
