<?php
/**
 * API: Delete Project
 * DELETE /api/projects/delete.php
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
        jsonResponse(['success' => false, 'error' => 'Projekt ID erforderlich'], 400);
    }
    
    $projectId = (int)$data['id'];
    
    // Check ownership
    $existing = dbFetchOne(
        "SELECT * FROM projects WHERE id = ? AND user_id = ?",
        [$projectId, $userId]
    );
    
    if (!$existing) {
        jsonResponse(['success' => false, 'error' => 'Projekt nicht gefunden'], 404);
    }
    
    // Remove project references from related entities instead of deleting them
    dbQuery("UPDATE todos SET project_id = NULL WHERE project_id = ?", [$projectId]);
    dbQuery("UPDATE time_entries SET project_id = NULL WHERE project_id = ?", [$projectId]);
    dbQuery("UPDATE notes SET project_id = NULL WHERE project_id = ?", [$projectId]);
    
    // Delete the project
    dbQuery("DELETE FROM projects WHERE id = ?", [$projectId]);
    
    jsonResponse(['success' => true, 'message' => 'Projekt gelöscht']);
    
} catch (Exception $e) {
    error_log("Error in projects/delete.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Löschen'], 500);
}
