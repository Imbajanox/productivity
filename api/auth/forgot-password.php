<?php
/**
 * API - Passwort vergessen
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

$email = trim(strtolower(post('email', '')));

if (empty($email)) {
    jsonError('E-Mail-Adresse ist erforderlich');
}

if (!isValidEmail($email)) {
    jsonError('Bitte eine gültige E-Mail-Adresse eingeben');
}

try {
    $token = createPasswordResetToken($email);

    if ($token) {
        // Hier würde normalerweise eine E-Mail versendet werden
        // Für die Demo zeigen wir den Token in der Konsole
        error_log("Password reset token for {$email}: {$token}");

        jsonSuccess([
            'message' => 'Reset-Link wurde gesendet. Prüfe dein E-Mail-Postfach.'
        ]);
    } else {
        // Keine Info preisgeben ob E-Mail existiert (Security)
        jsonSuccess([
            'message' => 'Falls ein Konto mit dieser E-Mail-Adresse existiert, wurde ein Reset-Link gesendet.'
        ]);
    }
} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    jsonError('Interner Serverfehler', 500);
}
?>