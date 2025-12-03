<?php
/**
 * API: Stop Timer
 * POST /api/time/stop.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $data = getJsonInput();
    $userId = getCurrentUserId();
    
    if (empty($data['id'])) {
        jsonResponse(['success' => false, 'error' => 'Timer ID erforderlich'], 400);
    }
    
    $timerId = (int)$data['id'];
    
    // Check if timer belongs to user and is running
    $timer = dbFetchOne(
        "SELECT * FROM time_entries WHERE id = ? AND user_id = ? AND end_time IS NULL",
        [$timerId, $userId]
    );
    
    if (!$timer) {
        jsonResponse(['success' => false, 'error' => 'Timer nicht gefunden oder bereits gestoppt'], 404);
    }
    
    // Calculate duration
    $startTime = strtotime($timer['start_time']);
    $endTime = time();
    $duration = $endTime - $startTime;
    
    // Update timer
    dbQuery(
        "UPDATE time_entries SET end_time = NOW(), duration_seconds = ?, description = ?, updated_at = NOW() WHERE id = ?",
        [
            $duration,
            sanitize($data['description'] ?? $timer['description']),
            $timerId
        ]
    );
    
    $updatedTimer = dbFetchOne(
        "SELECT te.*, p.name as project_name 
         FROM time_entries te
         LEFT JOIN projects p ON te.project_id = p.id
         WHERE te.id = ?",
        [$timerId]
    );
    
    jsonResponse(['success' => true, 'data' => $updatedTimer, 'message' => 'Timer gestoppt']);
    
} catch (Exception $e) {
    error_log("Error in time/stop.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Stoppen des Timers'], 500);
}
