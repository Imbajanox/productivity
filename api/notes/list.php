<?php
/**
 * API: List Notes
 * GET /api/notes/list.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

try {
    $userId = getCurrentUserId();
    
    $where = ['n.user_id = ?'];
    $params = [$userId];
    
    if (!empty($_GET['project_id'])) {
        $where[] = 'n.project_id = ?';
        $params[] = (int)$_GET['project_id'];
    }
    
    if (!empty($_GET['search'])) {
        $where[] = '(n.title LIKE ? OR n.content LIKE ?)';
        $searchTerm = '%' . $_GET['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $where);
    
    $notes = dbFetchAll(
        "SELECT n.*, p.name as project_name, p.color as project_color
         FROM notes n
         LEFT JOIN projects p ON n.project_id = p.id
         WHERE {$whereClause}
         ORDER BY n.is_pinned DESC, n.updated_at DESC",
        $params
    );
    
    jsonResponse(['success' => true, 'data' => $notes]);
    
} catch (Exception $e) {
    error_log("Error in notes/list.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Laden der Notizen'], 500);
}
