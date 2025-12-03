<?php
/**
 * API: Update Note
 * PUT /api/notes/update.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
    
    // Build update
    $updates = ['updated_at = NOW()'];
    $params = [];
    
    if (isset($data['title'])) {
        $updates[] = 'title = ?';
        $params[] = sanitize($data['title']);
    }
    
    if (isset($data['content'])) {
        $updates[] = 'content = ?';
        $params[] = $data['content'];
    }
    
    if (array_key_exists('project_id', $data)) {
        $updates[] = 'project_id = ?';
        $params[] = !empty($data['project_id']) ? (int)$data['project_id'] : null;
    }
    
    if (array_key_exists('folder_id', $data)) {
        $updates[] = 'folder_id = ?';
        $params[] = !empty($data['folder_id']) ? (int)$data['folder_id'] : null;
    }
    
    if (isset($data['content_type'])) {
        $updates[] = 'content_type = ?';
        $params[] = in_array($data['content_type'], ['html', 'markdown', 'plain']) 
            ? $data['content_type'] 
            : 'html';
    }
    
    if (isset($data['is_pinned'])) {
        $updates[] = 'is_pinned = ?';
        $params[] = $data['is_pinned'] ? 1 : 0;
    }
    
    $params[] = $noteId;
    
    dbQuery("UPDATE notes SET " . implode(', ', $updates) . " WHERE id = ?", $params);
    
    $note = dbFetchOne(
        "SELECT n.*, p.name as project_name, p.color as project_color
         FROM notes n
         LEFT JOIN projects p ON n.project_id = p.id
         WHERE n.id = ?",
        [$noteId]
    );
    
    jsonResponse(['success' => true, 'data' => $note, 'message' => 'Notiz aktualisiert']);
    
} catch (Exception $e) {
    error_log("Error in notes/update.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Aktualisieren'], 500);
}
