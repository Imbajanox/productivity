<?php
/**
 * API: List Todos
 * GET /api/todos/list.php
 */

require_once __DIR__ . '/../../includes/init.php';

// Require authentication
requireAuth();

try {
    $userId = getCurrentUserId();
    
    // Build query with optional filters
    $where = ['t.user_id = ?'];
    $params = [$userId];
    
    // Status filter
    if (!empty($_GET['status'])) {
        $where[] = 't.status = ?';
        $params[] = $_GET['status'];
    }
    
    // Priority filter
    if (!empty($_GET['priority'])) {
        $where[] = 't.priority = ?';
        $params[] = $_GET['priority'];
    }
    
    // Project filter
    if (!empty($_GET['project_id'])) {
        $where[] = 't.project_id = ?';
        $params[] = (int)$_GET['project_id'];
    }
    
    // Search filter
    if (!empty($_GET['search'])) {
        $where[] = '(t.title LIKE ? OR t.description LIKE ?)';
        $searchTerm = '%' . $_GET['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get todos with project info
    $sql = "
        SELECT 
            t.id, t.title, t.description, t.priority, t.status, 
            t.due_date, t.project_id, t.position, t.created_at, t.updated_at,
            p.name as project_name, p.color as project_color,
            (SELECT COUNT(*) FROM subtasks WHERE todo_id = t.id) as subtasks_count,
            (SELECT COUNT(*) FROM subtasks WHERE todo_id = t.id AND is_completed = 1) as subtasks_completed
        FROM todos t
        LEFT JOIN projects p ON t.project_id = p.id
        WHERE {$whereClause}
        ORDER BY 
            CASE t.status 
                WHEN 'in_progress' THEN 1 
                WHEN 'todo' THEN 2 
                WHEN 'done' THEN 3 
            END,
            CASE t.priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END,
            t.position ASC,
            t.created_at DESC
    ";
    
    $todos = dbFetchAll($sql, $params);
    
    // Get tags for each todo
    foreach ($todos as &$todo) {
        $todo['tags'] = dbFetchAll(
            "SELECT tg.id, tg.name, tg.color 
             FROM tags tg
             JOIN todo_tags tt ON tg.id = tt.tag_id
             WHERE tt.todo_id = ?",
            [$todo['id']]
        );
    }
    
    jsonResponse(['success' => true, 'data' => $todos]);
    
} catch (Exception $e) {
    error_log("Error in todos/list.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Laden der Todos'], 500);
}
