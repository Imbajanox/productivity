<?php
/**
 * API - Dashboard - Recent Activity
 */

require_once __DIR__ . '/../../includes/init.php';

// Authentifizierung erforderlich
requireAuth();

// Nur GET-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$userId = currentUserId();

try {
    // Letzte Aktivitäten aus activity_log abrufen
    $activities = dbFetchAll(
        "SELECT action_type, entity_type, entity_id, metadata, created_at
         FROM activity_log
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 10",
        [$userId]
    );

    // Aktivitäten formatieren
    $formattedActivities = array_map(function($activity) {
        $metadata = $activity['metadata'] ? json_decode($activity['metadata'], true) : [];
        
        return [
            'description' => formatActivityDescription($activity['action_type'], $metadata),
            'time' => timeAgo($activity['created_at']),
            'type' => $activity['action_type']
        ];
    }, $activities);

    jsonSuccess($formattedActivities);
} catch (Exception $e) {
    error_log('Dashboard recent activity error: ' . $e->getMessage());
    jsonError('Interner Serverfehler', 500);
}
?>