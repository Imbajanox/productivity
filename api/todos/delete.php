<?php
/**
 * API: Delete Todo
 * DELETE /api/todos/delete.php
 */

require_once __DIR__ . '/../../includes/init.php';

// Require authentication
requireAuth();

// Only DELETE allowed
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $data = getJsonInput();
    $userId = getCurrentUserId();
    
    // Validate required fields
    if (empty($data['id'])) {
        jsonResponse(['success' => false, 'error' => 'Todo ID ist erforderlich'], 400);
    }
    
    $todoId = (int)$data['id'];
    
    // Check if todo belongs to user
    $existingTodo = dbFetchOne(
        "SELECT * FROM todos WHERE id = ? AND user_id = ?",
        [$todoId, $userId]
    );
    
    if (!$existingTodo) {
        jsonResponse(['success' => false, 'error' => 'Todo nicht gefunden'], 404);
    }
    
    // Delete related data first (cascades should handle this, but being explicit)
    dbQuery("DELETE FROM todo_tags WHERE todo_id = ?", [$todoId]);
    dbQuery("DELETE FROM subtasks WHERE todo_id = ?", [$todoId]);
    
    // Delete the todo
    dbQuery("DELETE FROM todos WHERE id = ?", [$todoId]);
    
    // Log activity
    logActivity($userId, 'todo_deleted', 'todo', $todoId, ['title' => $existingTodo['title']]);
    
    jsonResponse(['success' => true, 'message' => 'Todo gelöscht']);
    
} catch (Exception $e) {
    error_log("Error in todos/delete.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Löschen des Todos'], 500);
}
