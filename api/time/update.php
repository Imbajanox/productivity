<?php
/**
 * API: Update Time Entry
 * PUT /api/time/update.php
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
        jsonResponse(['success' => false, 'error' => 'Eintrag ID erforderlich'], 400);
    }
    
    $entryId = (int)$data['id'];
    
    // Check ownership
    $existing = dbFetchOne(
        "SELECT * FROM time_entries WHERE id = ? AND user_id = ?",
        [$entryId, $userId]
    );
    
    if (!$existing) {
        jsonResponse(['success' => false, 'error' => 'Eintrag nicht gefunden'], 404);
    }
    
    // Build update
    $updates = ['updated_at = NOW()'];
    $params = [];
    
    if (isset($data['project_id'])) {
        $updates[] = 'project_id = ?';
        $params[] = !empty($data['project_id']) ? (int)$data['project_id'] : null;
    }
    
    if (isset($data['description'])) {
        $updates[] = 'description = ?';
        $params[] = sanitize($data['description']);
    }
    
    if (isset($data['start_time'])) {
        $updates[] = 'start_time = ?';
        $params[] = $data['start_time'];
    }
    
    if (isset($data['end_time'])) {
        $updates[] = 'end_time = ?';
        $params[] = $data['end_time'];
        
        // Recalculate duration
        $startTime = isset($data['start_time']) ? strtotime($data['start_time']) : strtotime($existing['start_time']);
        $endTime = strtotime($data['end_time']);
        $duration = $endTime - $startTime;
        
        if ($duration > 0) {
            $updates[] = 'duration_seconds = ?';
            $params[] = $duration;
        }
    }
    
    $params[] = $entryId;
    
    dbQuery("UPDATE time_entries SET " . implode(', ', $updates) . " WHERE id = ?", $params);
    
    $entry = dbFetchOne(
        "SELECT te.*, p.name as project_name 
         FROM time_entries te
         LEFT JOIN projects p ON te.project_id = p.id
         WHERE te.id = ?",
        [$entryId]
    );
    
    jsonResponse(['success' => true, 'data' => $entry, 'message' => 'Eintrag aktualisiert']);
    
} catch (Exception $e) {
    error_log("Error in time/update.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Aktualisieren'], 500);
}
