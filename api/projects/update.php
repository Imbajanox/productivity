<?php
/**
 * API: Update Project
 * PUT /api/projects/update.php
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
        jsonResponse(['success' => false, 'error' => 'Projekt ID erforderlich'], 400);
    }
    
    $projectId = (int)$data['id'];
    
    // Check ownership
    $existing = dbFetchOne(
        "SELECT * FROM projects WHERE id = ? AND user_id = ?",
        [$projectId, $userId]
    );
    
    if (!$existing) {
        jsonResponse(['success' => false, 'error' => 'Projekt nicht gefunden'], 404);
    }
    
    $updates = ['updated_at = NOW()'];
    $params = [];
    
    if (isset($data['name'])) {
        $updates[] = 'name = ?';
        $params[] = sanitize($data['name']);
    }
    
    if (isset($data['description'])) {
        $updates[] = 'description = ?';
        $params[] = sanitize($data['description']);
    }
    
    if (isset($data['color'])) {
        $updates[] = 'color = ?';
        $params[] = $data['color'];
    }
    
    if (isset($data['status']) && in_array($data['status'], ['active', 'paused', 'completed', 'archived'])) {
        $updates[] = 'status = ?';
        $params[] = $data['status'];
    }
    
    if (array_key_exists('deadline', $data)) {
        $updates[] = 'deadline = ?';
        $params[] = !empty($data['deadline']) ? $data['deadline'] : null;
    }
    
    $params[] = $projectId;
    
    dbQuery("UPDATE projects SET " . implode(', ', $updates) . " WHERE id = ?", $params);
    
    $project = dbFetchOne("SELECT * FROM projects WHERE id = ?", [$projectId]);
    
    jsonResponse(['success' => true, 'data' => $project, 'message' => 'Projekt aktualisiert']);
    
} catch (Exception $e) {
    error_log("Error in projects/update.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Aktualisieren'], 500);
}
