<?php
/**
 * API - Dashboard - Quick Stats
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
    // Verschiedene Statistiken sammeln
    $stats = [];

    // Todos gesamt
    $totalTodos = dbFetchOne(
        "SELECT COUNT(*) as count FROM todos WHERE user_id = ?",
        [$userId]
    );
    $stats['totalTodos'] = (int)($totalTodos['count'] ?? 0);

    // Erledigte Todos
    $completedTodos = dbFetchOne(
        "SELECT COUNT(*) as count FROM todos WHERE user_id = ? AND status = 'done'",
        [$userId]
    );
    $stats['completedTodos'] = (int)($completedTodos['count'] ?? 0);

    // Notizen gesamt
    $totalNotes = dbFetchOne(
        "SELECT COUNT(*) as count FROM notes WHERE user_id = ?",
        [$userId]
    );
    $stats['totalNotes'] = (int)($totalNotes['count'] ?? 0);

    // Projekte gesamt
    $totalProjects = dbFetchOne(
        "SELECT COUNT(*) as count FROM projects WHERE user_id = ?",
        [$userId]
    );
    $stats['totalProjects'] = (int)($totalProjects['count'] ?? 0);

    // Code-Snippets
    $totalSnippets = dbFetchOne(
        "SELECT COUNT(*) as count FROM snippets WHERE user_id = ?",
        [$userId]
    );
    $stats['totalSnippets'] = (int)($totalSnippets['count'] ?? 0);

    // Gewohnheiten
    $totalHabits = dbFetchOne(
        "SELECT COUNT(*) as count FROM habits WHERE user_id = ?",
        [$userId]
    );
    $stats['totalHabits'] = (int)($totalHabits['count'] ?? 0);

    jsonSuccess($stats);
} catch (Exception $e) {
    error_log('Dashboard quick stats error: ' . $e->getMessage());
    jsonError('Interner Serverfehler', 500);
}
?>