<?php
/**
 * API: Create Todo
 * POST /api/todos/create.php
 */

require_once __DIR__ . '/../../includes/init.php';

// Require authentication
requireAuth();

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $data = getJsonInput();
    $userId = getCurrentUserId();
    
    // Validate required fields
    if (empty($data['title'])) {
        jsonResponse(['success' => false, 'error' => 'Titel ist erforderlich'], 400);
    }
    
    // Get next position
    $maxPosition = dbFetchOne(
        "SELECT MAX(position) as max_pos FROM todos WHERE user_id = ?",
        [$userId]
    );
    $position = ($maxPosition['max_pos'] ?? 0) + 1;
    
    // Insert todo
    $sql = "
        INSERT INTO todos (user_id, project_id, title, description, priority, status, due_date, position, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ";
    
    $params = [
        $userId,
        !empty($data['project_id']) ? (int)$data['project_id'] : null,
        sanitize($data['title']),
        sanitize($data['description'] ?? ''),
        in_array($data['priority'] ?? 'medium', ['urgent', 'high', 'medium', 'low']) ? $data['priority'] : 'medium',
        in_array($data['status'] ?? 'todo', ['todo', 'in_progress', 'done']) ? $data['status'] : 'todo',
        !empty($data['due_date']) ? $data['due_date'] : null,
        $position
    ];
    
    $todoId = dbInsert($sql, $params);
    
    // Add subtasks if provided
    if (!empty($data['subtasks']) && is_array($data['subtasks'])) {
        $subtaskPosition = 0;
        foreach ($data['subtasks'] as $subtask) {
            if (!empty($subtask['title'])) {
                dbInsert(
                    "INSERT INTO subtasks (todo_id, title, is_completed, position, created_at) VALUES (?, ?, ?, ?, NOW())",
                    [
                        $todoId,
                        sanitize($subtask['title']),
                        !empty($subtask['is_completed']) ? 1 : 0,
                        $subtaskPosition++
                    ]
                );
            }
        }
    }
    
    // Add tags if provided
    if (!empty($data['tags']) && is_array($data['tags'])) {
        foreach ($data['tags'] as $tagId) {
            dbInsert(
                "INSERT INTO todo_tags (todo_id, tag_id) VALUES (?, ?)",
                [$todoId, (int)$tagId]
            );
        }
    }
    
    // Get created todo with all data
    $todo = dbFetchOne(
        "SELECT t.*, p.name as project_name 
         FROM todos t 
         LEFT JOIN projects p ON t.project_id = p.id 
         WHERE t.id = ?",
        [$todoId]
    );
    
    // Get subtasks
    $todo['subtasks'] = dbFetchAll(
        "SELECT * FROM subtasks WHERE todo_id = ? ORDER BY position",
        [$todoId]
    );
    
    // Get tags
    $todo['tags'] = dbFetchAll(
        "SELECT tg.id, tg.name, tg.color FROM tags tg JOIN todo_tags tt ON tg.id = tt.tag_id WHERE tt.todo_id = ?",
        [$todoId]
    );
    
    jsonResponse(['success' => true, 'data' => $todo, 'message' => 'Todo erstellt']);
    
} catch (Exception $e) {
    error_log("Error in todos/create.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Erstellen des Todos'], 500);
}
