<?php
/**
 * API: Get Single Project
 * GET /api/projects/get.php?id=123
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

try {
    $userId = getCurrentUserId();
    
    if (empty($_GET['id'])) {
        jsonResponse(['success' => false, 'error' => 'Projekt ID erforderlich'], 400);
    }
    
    $projectId = (int)$_GET['id'];
    
    // Get project with stats
    $project = dbFetchOne(
        "SELECT 
            p.*,
            (SELECT COUNT(*) FROM todos WHERE project_id = p.id) as todo_count,
            (SELECT COUNT(*) FROM todos WHERE project_id = p.id AND status = 'done') as completed_count,
            (SELECT COALESCE(SUM(duration_seconds), 0) FROM time_entries WHERE project_id = p.id) as time_seconds,
            (SELECT COUNT(*) FROM notes WHERE project_id = p.id) as notes_count
         FROM projects p
         WHERE p.id = ? AND p.user_id = ?",
        [$projectId, $userId]
    );
    
    if (!$project) {
        jsonResponse(['success' => false, 'error' => 'Projekt nicht gefunden'], 404);
    }
    
    jsonResponse(['success' => true, 'data' => $project]);
    
} catch (Exception $e) {
    error_log("Error in projects/get.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Laden des Projekts'], 500);
}
