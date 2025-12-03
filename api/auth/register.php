<?php
/**
 * API - Registrierung
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

$username = trim(post('username', ''));
$email = trim(strtolower(post('email', '')));
$password = post('password', '');
$passwordConfirm = post('password_confirm', '');
$firstName = trim(post('first_name', ''));
$lastName = trim(post('last_name', ''));
$acceptTerms = post('accept_terms') === 'on';

if (empty($username) || empty($email) || empty($password)) {
    jsonError('Alle erforderlichen Felder müssen ausgefüllt werden');
}

if ($password !== $passwordConfirm) {
    jsonError('Passwörter stimmen nicht überein');
}

if (!$acceptTerms) {
    jsonError('Bitte akzeptiere die Nutzungsbedingungen');
}

try {
    $result = registerUser($username, $email, $password);

    if ($result['success']) {
        // Zusätzliche Benutzerdaten aktualisieren
        if (!empty($firstName) || !empty($lastName)) {
            updateUserSettings($result['user_id'], [
                'first_name' => $firstName,
                'last_name' => $lastName
            ]);
        }

        jsonSuccess([
            'message' => 'Konto erfolgreich erstellt',
            'redirect' => '/index.php'
        ]);
    } else {
        jsonError('Registrierung fehlgeschlagen: ' . implode(', ', $result['errors']));
    }
} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    jsonError('Interner Serverfehler', 500);
}
?>