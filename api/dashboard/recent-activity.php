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

    // Wenn keine Aktivitäten vorhanden sind, Demo-Daten erstellen
    if (empty($activities)) {
        $demoActivities = [
            ['action_type' => 'todo_completed', 'description' => 'Todo "Projekt planen" erledigt', 'time' => 'vor 2h'],
            ['action_type' => 'time_logged', 'description' => '2h an "Webentwicklung" gearbeitet', 'time' => 'vor 3h'],
            ['action_type' => 'note_created', 'description' => 'Notiz "Meeting-Notizen" erstellt', 'time' => 'vor 5h'],
            ['action_type' => 'project_created', 'description' => 'Projekt "Produktivitätstool" erstellt', 'time' => 'vor 1d'],
        ];

        $formattedActivities = array_map(function($activity) {
            return [
                'description' => $activity['description'],
                'time' => $activity['time'],
                'type' => $activity['action_type']
            ];
        }, $demoActivities);
    } else {
        // Aktivitäten formatieren
        $formattedActivities = array_map(function($activity) {
            $description = '';

            switch ($activity['action_type']) {
                case 'todo_completed':
                    $description = 'Todo erledigt';
                    break;
                case 'todo_created':
                    $description = 'Todo erstellt';
                    break;
                case 'time_logged':
                    $description = 'Zeit erfasst';
                    break;
                case 'note_created':
                    $description = 'Notiz erstellt';
                    break;
                case 'project_created':
                    $description = 'Projekt erstellt';
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
    }

    jsonSuccess($formattedActivities);
} catch (Exception $e) {
    error_log('Dashboard recent activity error: ' . $e->getMessage());
    jsonError('Interner Serverfehler', 500);
}
?>