<?php
/**
 * API - Login
 */

require_once __DIR__ . '/../../includes/init.php';

// Nur POST-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// CSRF-Token validieren
if (!validateCsrfToken(post('csrf_token'))) {
    jsonError('Invalid CSRF token', 403);
}

$identifier = trim(post('identifier', ''));
$password = post('password', '');
$remember = post('remember') === '1';

if (empty($identifier) || empty($password)) {
    jsonError('Benutzername/E-Mail und Passwort sind erforderlich');
}

try {
    $result = loginUser($identifier, $password, $remember);

    if ($result['success']) {
        jsonSuccess([
            'message' => 'Erfolgreich angemeldet',
            'redirect' => '/index.php'
        ]);
    } else {
        jsonError($result['error']);
    }
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    jsonError('Interner Serverfehler', 500);
}
?>