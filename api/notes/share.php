<?php
/**
 * API: Share Note (Generate Public Link)
 * POST /api/notes/share.php
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
        jsonResponse(['success' => false, 'error' => 'Notiz ID erforderlich'], 400);
    }
    
    $noteId = (int)$data['id'];
    
    // Check ownership
    $note = dbFetchOne(
        "SELECT * FROM notes WHERE id = ? AND user_id = ?",
        [$noteId, $userId]
    );
    
    if (!$note) {
        jsonResponse(['success' => false, 'error' => 'Notiz nicht gefunden'], 404);
    }
    
    $action = $data['action'] ?? 'toggle';
    
    if ($action === 'disable' || ($action === 'toggle' && $note['is_public'])) {
        // Disable sharing
        dbQuery(
            "UPDATE notes SET is_public = 0, public_token = NULL WHERE id = ?",
            [$noteId]
        );
        
        jsonResponse([
            'success' => true,
            'data' => [
                'is_public' => false,
                'public_token' => null,
                'public_url' => null
            ],
            'message' => 'Freigabe deaktiviert'
        ]);
        
    } else {
        // Enable sharing - generate token
        $token = $note['public_token'];
        
        if (!$token) {
            $token = bin2hex(random_bytes(32));
        }
        
        dbQuery(
            "UPDATE notes SET is_public = 1, public_token = ? WHERE id = ?",
            [$token, $noteId]
        );
        
        $publicUrl = url('public/note.php?token=' . $token);
        
        jsonResponse([
            'success' => true,
            'data' => [
                'is_public' => true,
                'public_token' => $token,
                'public_url' => $publicUrl
            ],
            'message' => 'Freigabe aktiviert'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in notes/share.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Ändern der Freigabe'], 500);
}
