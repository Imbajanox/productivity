<?php
/**
 * API: Create Manual Time Entry
 * POST /api/time/create.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $data = getJsonInput();
    $userId = getCurrentUserId();
    
    if (empty($data['start_time']) || empty($data['end_time'])) {
        jsonResponse(['success' => false, 'error' => 'Start- und Endzeit erforderlich'], 400);
    }
    
    $startTime = strtotime($data['start_time']);
    $endTime = strtotime($data['end_time']);
    
    if ($endTime <= $startTime) {
        jsonResponse(['success' => false, 'error' => 'Endzeit muss nach Startzeit liegen'], 400);
    }
    
    $duration = $endTime - $startTime;
    
    $entryId = dbInsert(
        "INSERT INTO time_entries (user_id, project_id, description, start_time, end_time, duration_seconds, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, NOW())",
        [
            $userId,
            !empty($data['project_id']) ? (int)$data['project_id'] : null,
            sanitize($data['description'] ?? ''),
            $data['start_time'],
            $data['end_time'],
            $duration
        ]
    );
    
    $entry = dbFetchOne(
        "SELECT te.*, p.name as project_name 
         FROM time_entries te
         LEFT JOIN projects p ON te.project_id = p.id
         WHERE te.id = ?",
        [$entryId]
    );
    
    jsonResponse(['success' => true, 'data' => $entry, 'message' => 'Eintrag erstellt']);
    
} catch (Exception $e) {
    error_log("Error in time/create.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Erstellen des Eintrags'], 500);
}
