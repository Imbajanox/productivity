<?php
/**
 * API: Create Project
 * POST /api/projects/create.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $data = getJsonInput();
    $userId = getCurrentUserId();
    
    if (empty($data['name'])) {
        jsonResponse(['success' => false, 'error' => 'Projektname erforderlich'], 400);
    }
    
    $projectId = dbInsert(
        "INSERT INTO projects (user_id, name, description, color, status, deadline, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
        [
            $userId,
            sanitize($data['name']),
            sanitize($data['description'] ?? ''),
            $data['color'] ?? '#3498db',
            in_array($data['status'] ?? 'active', ['active', 'paused', 'completed', 'archived']) ? $data['status'] : 'active',
            !empty($data['deadline']) ? $data['deadline'] : null
        ]
    );
    
    $project = dbFetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);
    
    // Log activity
    logActivity($userId, 'project_created', 'project', $projectId, ['name' => $project['name']]);
    
    jsonResponse(['success' => true, 'data' => $project, 'message' => 'Projekt erstellt']);
    
} catch (Exception $e) {
    error_log("Error in projects/create.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Erstellen des Projekts'], 500);
}
