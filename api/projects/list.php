<?php
/**
 * API: List Projects
 * GET /api/projects/list.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

try {
    $userId = getCurrentUserId();
    
    // Get projects with todo counts and time tracking stats
    $projects = dbFetchAll(
        "SELECT 
            p.id, p.name, p.color, p.description, p.status, p.deadline,
            p.created_at, p.updated_at,
            (SELECT COUNT(*) FROM todos WHERE project_id = p.id) as todo_count,
            (SELECT COUNT(*) FROM todos WHERE project_id = p.id AND status = 'done') as completed_count,
            (SELECT COALESCE(SUM(duration_seconds), 0) FROM time_entries WHERE project_id = p.id) as time_seconds
         FROM projects p
         WHERE p.user_id = ? 
         ORDER BY 
            CASE p.status 
                WHEN 'active' THEN 1 
                WHEN 'paused' THEN 2 
                WHEN 'completed' THEN 3 
                WHEN 'archived' THEN 4 
            END,
            p.name ASC",
        [$userId]
    );
    
    jsonResponse(['success' => true, 'data' => $projects]);
    
} catch (Exception $e) {
    error_log("Error in projects/list.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Laden der Projekte'], 500);
}
