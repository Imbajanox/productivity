<?php
/**
 * API: Update Todo
 * PUT /api/todos/update.php
 */

require_once __DIR__ . '/../../includes/init.php';

// Require authentication
requireAuth();

// Only PUT allowed
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
    
    // Build update query dynamically
    $updates = [];
    $params = [];
    
    if (isset($data['title'])) {
        $updates[] = 'title = ?';
        $params[] = sanitize($data['title']);
    }
    
    if (isset($data['description'])) {
        $updates[] = 'description = ?';
        $params[] = sanitize($data['description']);
    }
    
    if (isset($data['priority']) && in_array($data['priority'], ['urgent', 'high', 'medium', 'low'])) {
        $updates[] = 'priority = ?';
        $params[] = $data['priority'];
    }
    
    if (isset($data['status']) && in_array($data['status'], ['todo', 'in_progress', 'done'])) {
        $updates[] = 'status = ?';
        $params[] = $data['status'];
        
        // Set completed_at if marking as done
        if ($data['status'] === 'done' && $existingTodo['status'] !== 'done') {
            $updates[] = 'completed_at = NOW()';
        } elseif ($data['status'] !== 'done') {
            $updates[] = 'completed_at = NULL';
        }
    }
    
    if (array_key_exists('due_date', $data)) {
        $updates[] = 'due_date = ?';
        $params[] = !empty($data['due_date']) ? $data['due_date'] : null;
    }
    
    if (array_key_exists('project_id', $data)) {
        $updates[] = 'project_id = ?';
        $params[] = !empty($data['project_id']) ? (int)$data['project_id'] : null;
    }
    
    if (isset($data['position'])) {
        $updates[] = 'position = ?';
        $params[] = (int)$data['position'];
    }
    
    if (empty($updates)) {
        jsonResponse(['success' => false, 'error' => 'Keine Änderungen angegeben'], 400);
    }
    
    $updates[] = 'updated_at = NOW()';
    $params[] = $todoId;
    
    $sql = "UPDATE todos SET " . implode(', ', $updates) . " WHERE id = ?";
    dbQuery($sql, $params);
    
    // Update subtasks if provided
    if (isset($data['subtasks']) && is_array($data['subtasks'])) {
        // Get existing subtask IDs
        $existingSubtasks = dbFetchAll("SELECT id FROM subtasks WHERE todo_id = ?", [$todoId]);
        $existingIds = array_column($existingSubtasks, 'id');
        $newIds = [];
        
        $subtaskPosition = 0;
        foreach ($data['subtasks'] as $subtask) {
            if (empty($subtask['title'])) continue;
            
            if (!empty($subtask['id']) && in_array($subtask['id'], $existingIds)) {
                // Update existing
                dbQuery(
                    "UPDATE subtasks SET title = ?, is_completed = ?, position = ? WHERE id = ?",
                    [
                        sanitize($subtask['title']),
                        !empty($subtask['is_completed']) ? 1 : 0,
                        $subtaskPosition++,
                        $subtask['id']
                    ]
                );
                $newIds[] = $subtask['id'];
            } else {
                // Insert new
                $newId = dbInsert(
                    "INSERT INTO subtasks (todo_id, title, is_completed, position, created_at) VALUES (?, ?, ?, ?, NOW())",
                    [
                        $todoId,
                        sanitize($subtask['title']),
                        !empty($subtask['is_completed']) ? 1 : 0,
                        $subtaskPosition++
                    ]
                );
                $newIds[] = $newId;
            }
        }
        
        // Delete removed subtasks
        $toDelete = array_diff($existingIds, $newIds);
        if (!empty($toDelete)) {
            $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
            dbQuery("DELETE FROM subtasks WHERE id IN ($placeholders)", array_values($toDelete));
        }
    }
    
    // Update tags if provided
    if (isset($data['tags']) && is_array($data['tags'])) {
        // Remove existing tags
        dbQuery("DELETE FROM todo_tags WHERE todo_id = ?", [$todoId]);
        
        // Add new tags
        foreach ($data['tags'] as $tagId) {
            dbInsert(
                "INSERT INTO todo_tags (todo_id, tag_id) VALUES (?, ?)",
                [$todoId, (int)$tagId]
            );
        }
    }
    
    // Get updated todo with all data
    $todo = dbFetchOne(
        "SELECT t.*, p.name as project_name 
         FROM todos t 
         LEFT JOIN projects p ON t.project_id = p.id 
         WHERE t.id = ?",
        [$todoId]
    );
    
    $todo['subtasks'] = dbFetchAll(
        "SELECT * FROM subtasks WHERE todo_id = ? ORDER BY position",
        [$todoId]
    );
    
    $todo['tags'] = dbFetchAll(
        "SELECT tg.id, tg.name, tg.color FROM tags tg JOIN todo_tags tt ON tg.id = tt.tag_id WHERE tt.todo_id = ?",
        [$todoId]
    );
    
    // Log activity - especially if todo was completed
    if (isset($data['status'])) {
        if ($data['status'] === 'done' && $existingTodo['status'] !== 'done') {
            logActivity($userId, 'todo_completed', 'todo', $todoId, ['title' => $todo['title']]);
        } elseif ($data['status'] !== $existingTodo['status']) {
            logActivity($userId, 'todo_updated', 'todo', $todoId, [
                'title' => $todo['title'],
                'old_status' => $existingTodo['status'],
                'new_status' => $data['status']
            ]);
        }
    } else {
        logActivity($userId, 'todo_updated', 'todo', $todoId, ['title' => $todo['title']]);
    }
    
    jsonResponse(['success' => true, 'data' => $todo, 'message' => 'Todo aktualisiert']);
    
} catch (Exception $e) {
    error_log("Error in todos/update.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Aktualisieren des Todos'], 500);
}
