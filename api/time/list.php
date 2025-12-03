<?php
/**
 * API: List Time Entries
 * GET /api/time/list.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

try {
    $userId = getCurrentUserId();
    
    // Build date range based on period
    $period = $_GET['period'] ?? 'today';
    $today = date('Y-m-d');
    
    switch ($period) {
        case 'yesterday':
            $dateFrom = date('Y-m-d', strtotime('-1 day'));
            $dateTo = $dateFrom;
            break;
        case 'week':
            $dateFrom = date('Y-m-d', strtotime('monday this week'));
            $dateTo = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'month':
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-t');
            break;
        case 'custom':
            $dateFrom = $_GET['date_from'] ?? $today;
            $dateTo = $_GET['date_to'] ?? $today;
            break;
        default: // today
            $dateFrom = $today;
            $dateTo = $today;
    }
    
    $where = ['te.user_id = ?', 'DATE(te.start_time) >= ?', 'DATE(te.start_time) <= ?'];
    $params = [$userId, $dateFrom, $dateTo];
    
    if (!empty($_GET['project_id'])) {
        $where[] = 'te.project_id = ?';
        $params[] = (int)$_GET['project_id'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    $entries = dbFetchAll(
        "SELECT te.*, p.name as project_name, p.color as project_color
         FROM time_entries te
         LEFT JOIN projects p ON te.project_id = p.id
         WHERE {$whereClause}
         ORDER BY te.start_time DESC",
        $params
    );
    
    jsonResponse(['success' => true, 'data' => $entries]);
    
} catch (Exception $e) {
    error_log("Error in time/list.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Laden der Einträge'], 500);
}
