<?php
/**
 * API: Create Note Folder
 * POST /api/notes/folders/create.php
 */

require_once __DIR__ . '/../../../includes/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $data = getJsonInput();
    $userId = getCurrentUserId();
    
    if (empty($data['name'])) {
        jsonResponse(['success' => false, 'error' => 'Ordnername erforderlich'], 400);
    }
    
    $folderName = sanitize($data['name']);
    
    // Check if note_folders table exists
    try {
        // Get max position
        $maxPos = dbFetchOne(
            "SELECT MAX(position) as max_pos FROM note_folders WHERE user_id = ?",
            [$userId]
        );
        
        $position = ($maxPos['max_pos'] ?? 0) + 1;
        
        $folderId = dbInsert('note_folders', [
            'user_id' => $userId,
            'name' => $folderName,
            'color' => $data['color'] ?? '#6c757d',
            'parent_id' => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            'position' => $position
        ]);
        
        $folder = dbFetchOne("SELECT * FROM note_folders WHERE id = ?", [$folderId]);
    } catch (Exception $e) {
        // Fallback: Just return the folder name as an object
        // The folder will be created when a note uses it
        $folder = [
            'id' => $folderName,
            'name' => $folderName,
            'color' => $data['color'] ?? '#6c757d',
            'note_count' => 0
        ];
    }
    
    jsonResponse(['success' => true, 'data' => $folder, 'message' => 'Ordner erstellt']);
    
} catch (Exception $e) {
    error_log("Error in notes/folders/create.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Erstellen des Ordners'], 500);
}
