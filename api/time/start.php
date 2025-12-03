<?php
/**
 * API: Start Timer
 * POST /api/time/start.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $data = getJsonInput();
    $userId = getCurrentUserId();
    
    // Check for existing running timer
    $runningTimer = dbFetchOne(
        "SELECT id FROM time_entries WHERE user_id = ? AND end_time IS NULL",
        [$userId]
    );
    
    if ($runningTimer) {
        jsonResponse(['success' => false, 'error' => 'Es läuft bereits ein Timer'], 400);
    }
    
    // Check if this is a break entry
    $isBreak = isset($data['is_break']) ? (bool)$data['is_break'] : false;
    
    // Start new timer
    $timerId = dbInsert('time_entries', [
        'user_id' => $userId,
        'project_id' => !empty($data['project_id']) ? (int)$data['project_id'] : null,
        'todo_id' => !empty($data['todo_id']) ? (int)$data['todo_id'] : null,
        'description' => sanitize($data['description'] ?? ''),
        'start_time' => date('Y-m-d H:i:s'),
        'is_break' => $isBreak ? 1 : 0,
        'is_running' => 1
    ]);
    
    $timer = dbFetchOne(
        "SELECT te.*, p.name as project_name 
         FROM time_entries te
         LEFT JOIN projects p ON te.project_id = p.id
         WHERE te.id = ?",
        [$timerId]
    );
    
    jsonResponse(['success' => true, 'data' => $timer, 'message' => $isBreak ? 'Pause gestartet' : 'Timer gestartet']);
    
} catch (Exception $e) {
    error_log("Error in time/start.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Starten des Timers'], 500);
}
