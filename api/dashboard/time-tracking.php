<?php
/**
 * API - Dashboard - Time Tracking
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
    // Zeiterfassung für diese Woche berechnen
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));

    $timeEntries = dbFetchAll(
        "SELECT SUM(duration_seconds) as total_seconds
         FROM time_entries
         WHERE user_id = ? AND start_time >= ? AND start_time <= ?
         AND is_break = FALSE",
        [$userId, $weekStart . ' 00:00:00', $weekEnd . ' 23:59:59']
    );

    $totalSeconds = $timeEntries[0]['total_seconds'] ?? 0;

    jsonSuccess([
        'totalTime' => (int)$totalSeconds,
        'formattedTime' => formatDuration($totalSeconds),
        'weekStart' => $weekStart,
        'weekEnd' => $weekEnd
    ]);
} catch (Exception $e) {
    error_log('Dashboard time tracking error: ' . $e->getMessage());
    jsonError('Interner Serverfehler', 500);
}
?>