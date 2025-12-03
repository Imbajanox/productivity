<?php
/**
 * API - Logout
 */

require_once __DIR__ . '/../../includes/init.php';

// Nur POST-Requests erlauben (für Sicherheit)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isAjaxRequest()) {
    jsonError('Method not allowed', 405);
}

try {
    logoutUser();

    if (isAjaxRequest()) {
        jsonSuccess([
            'message' => 'Erfolgreich abgemeldet',
            'redirect' => '/login.php'
        ]);
    } else {
        redirect('/login.php');
    }
} catch (Exception $e) {
    error_log('Logout error: ' . $e->getMessage());

    if (isAjaxRequest()) {
        jsonError('Interner Serverfehler', 500);
    } else {
        // Bei Fehler trotzdem zur Login-Seite weiterleiten
        redirect('/login.php');
    }
}
?>