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
        $description = '';
        $metadata = $activity['metadata'] ? json_decode($activity['metadata'], true) : [];
        
        switch ($activity['action_type']) {
            case 'todo_completed':
                $title = $metadata['title'] ?? 'Todo';
                $description = "Todo \"$title\" erledigt";
                break;
            case 'todo_created':
                $title = $metadata['title'] ?? 'Todo';
                $description = "Todo \"$title\" erstellt";
                break;
            case 'todo_updated':
                $title = $metadata['title'] ?? 'Todo';
                $description = "Todo \"$title\" aktualisiert";
                break;
            case 'todo_deleted':
                $title = $metadata['title'] ?? 'Todo';
                $description = "Todo \"$title\" gelöscht";
                break;
            case 'time_logged':
                $duration = isset($metadata['duration_seconds']) ? formatDuration($metadata['duration_seconds']) : '?';
                $projectName = $metadata['project_name'] ?? 'Allgemein';
                $description = "$duration an \"$projectName\" gearbeitet";
                break;
            case 'note_created':
                $title = $metadata['title'] ?? 'Notiz';
                $description = "Notiz \"$title\" erstellt";
                break;
            case 'note_updated':
                $title = $metadata['title'] ?? 'Notiz';
                $description = "Notiz \"$title\" aktualisiert";
                break;
            case 'note_deleted':
                $title = $metadata['title'] ?? 'Notiz';
                $description = "Notiz \"$title\" gelöscht";
                break;
            case 'project_created':
                $name = $metadata['name'] ?? 'Projekt';
                $description = "Projekt \"$name\" erstellt";
                break;
            case 'project_updated':
                $name = $metadata['name'] ?? 'Projekt';
                $description = "Projekt \"$name\" aktualisiert";
                break;
            case 'project_deleted':
                $name = $metadata['name'] ?? 'Projekt';
                $description = "Projekt \"$name\" gelöscht";
                break;
            default:
                $description = ucfirst(str_replace('_', ' ', $activity['action_type']));
        }

        return [
            'description' => $description,
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