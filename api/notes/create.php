<?php
/**
 * API: Create Note
 * POST /api/notes/create.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $data = getJsonInput();
    $userId = getCurrentUserId();
    
    $title = sanitize($data['title'] ?? 'Neue Notiz');
    $content = $data['content'] ?? '';
    $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
    
    $noteId = dbInsert(
        "INSERT INTO notes (user_id, project_id, title, content, created_at, updated_at)
         VALUES (?, ?, ?, ?, NOW(), NOW())",
        [$userId, $projectId, $title, $content]
    );
    
    $note = dbFetchOne(
        "SELECT n.*, p.name as project_name, p.color as project_color
         FROM notes n
         LEFT JOIN projects p ON n.project_id = p.id
         WHERE n.id = ?",
        [$noteId]
    );
    
    jsonResponse(['success' => true, 'data' => $note, 'message' => 'Notiz erstellt']);
    
} catch (Exception $e) {
    error_log("Error in notes/create.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Erstellen der Notiz'], 500);
}
