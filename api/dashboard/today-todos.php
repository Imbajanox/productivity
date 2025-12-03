<?php
/**
 * API - Dashboard - Today's Todos
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
    // Todos für heute abrufen
    $today = date('Y-m-d');
    $todos = dbFetchAll(
        "SELECT id, title, description, priority, status, due_date, completed_at
         FROM todos
         WHERE user_id = ? AND due_date = ? AND status != 'cancelled'
         ORDER BY priority DESC, created_at DESC
         LIMIT 10",
        [$userId, $today]
    );

    // Todos formatieren
    $formattedTodos = array_map(function($todo) {
        return [
            'id' => $todo['id'],
            'title' => $todo['title'],
            'description' => $todo['description'],
            'priority' => $todo['priority'],
            'status' => $todo['status'],
            'due_date' => $todo['due_date'],
            'completed' => !empty($todo['completed_at'])
        ];
    }, $todos);

    jsonSuccess($formattedTodos);
} catch (Exception $e) {
    error_log('Dashboard today todos error: ' . $e->getMessage());
    jsonError('Interner Serverfehler', 500);
}
?>