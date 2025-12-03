<?php
/**
 * Produktivitätstool - Authentifizierung
 * 
 * Funktionen für Login, Logout, Registrierung
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Session starten (falls nicht bereits gestartet)
 */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Benutzer registrieren
 */
function registerUser(string $username, string $email, string $password): array {
    $errors = [];
    
    // Validierung
    $username = trim($username);
    $email = trim(strtolower($email));
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = 'Benutzername muss zwischen 3 und 50 Zeichen lang sein.';
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Benutzername darf nur Buchstaben, Zahlen und Unterstriche enthalten.';
    }
    
    if (!isValidEmail($email)) {
        $errors['email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
    }
    
    if (strlen($password) < 8) {
        $errors['password'] = 'Passwort muss mindestens 8 Zeichen lang sein.';
    }
    
    // Prüfen ob Benutzername oder E-Mail bereits existiert
    if (empty($errors)) {
        $existing = dbFetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($existing) {
            $errors['general'] = 'Benutzername oder E-Mail-Adresse bereits vergeben.';
        }
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Benutzer erstellen
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    
    dbQuery(
        "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)",
        [$username, $email, $passwordHash]
    );
    
    $userId = dbLastInsertId();
    
    // Direkt einloggen
    loginUserById((int)$userId);
    
    return ['success' => true, 'user_id' => $userId];
}

/**
 * Benutzer einloggen (mit Benutzername oder E-Mail)
 */
function loginUser(string $identifier, string $password, bool $remember = false): array {
    $identifier = trim($identifier);
    
    // Benutzer suchen
    $user = dbFetchOne(
        "SELECT * FROM users WHERE username = ? OR email = ?",
        [$identifier, strtolower($identifier)]
    );
    
    if (!$user) {
        return ['success' => false, 'error' => 'Ungültige Anmeldedaten.'];
    }
    
    // Passwort prüfen
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Ungültige Anmeldedaten.'];
    }
    
    // Passwort-Hash aktualisieren falls nötig
    if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => HASH_COST])) {
        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        dbQuery("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $user['id']]);
    }
    
    // Login durchführen
    loginUserById((int)$user['id']);
    
    // "Remember Me" Token setzen
    if ($remember) {
        $token = generateToken(32);
        $hashedToken = hash('sha256', $token);
        
        dbQuery("UPDATE users SET remember_token = ? WHERE id = ?", [$hashedToken, $user['id']]);
        
        setcookie('remember_token', $token, [
            'expires' => time() + (30 * 24 * 60 * 60), // 30 Tage
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    // Letzten Login aktualisieren
    dbQuery("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$user['id']]);
    
    return ['success' => true, 'user' => $user];
}

/**
 * Benutzer per ID einloggen (Session setzen)
 */
function loginUserById(int $userId): void {
    startSession();
    
    // Session regenerieren für Sicherheit
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in_at'] = time();
}

/**
 * Benutzer ausloggen
 */
function logoutUser(): void {
    startSession();
    
    // Remember-Token löschen
    if (isset($_COOKIE['remember_token']) && isLoggedIn()) {
        dbQuery("UPDATE users SET remember_token = NULL WHERE id = ?", [currentUserId()]);
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/'
        ]);
    }
    
    // Session löschen
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Prüfen und Login via Remember-Token
 */
function checkRememberToken(): void {
    if (isLoggedIn() || empty($_COOKIE['remember_token'])) {
        return;
    }
    
    $token = $_COOKIE['remember_token'];
    $hashedToken = hash('sha256', $token);
    
    $user = dbFetchOne(
        "SELECT id FROM users WHERE remember_token = ?",
        [$hashedToken]
    );
    
    if ($user) {
        loginUserById((int)$user['id']);
        
        // Neues Token generieren (Token-Rotation)
        $newToken = generateToken(32);
        $newHashedToken = hash('sha256', $newToken);
        
        dbQuery("UPDATE users SET remember_token = ?, last_login_at = NOW() WHERE id = ?", 
            [$newHashedToken, $user['id']]);
        
        setcookie('remember_token', $newToken, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

/**
 * Passwort ändern
 */
function changePassword(int $userId, string $currentPassword, string $newPassword): array {
    $user = dbFetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
    
    if (!$user) {
        return ['success' => false, 'error' => 'Benutzer nicht gefunden.'];
    }
    
    if (!password_verify($currentPassword, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Aktuelles Passwort ist falsch.'];
    }
    
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'error' => 'Neues Passwort muss mindestens 8 Zeichen lang sein.'];
    }
    
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    dbQuery("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $userId]);
    
    return ['success' => true];
}

/**
 * Passwort-Reset-Token erstellen
 */
function createPasswordResetToken(string $email): ?string {
    $email = trim(strtolower($email));
    
    $user = dbFetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    
    if (!$user) {
        return null; // Keine Info preisgeben ob E-Mail existiert
    }
    
    // Alte Tokens löschen
    dbQuery("DELETE FROM password_resets WHERE email = ?", [$email]);
    
    // Neues Token erstellen
    $token = generateToken(32);
    $hashedToken = hash('sha256', $token);
    
    dbQuery(
        "INSERT INTO password_resets (email, token) VALUES (?, ?)",
        [$email, $hashedToken]
    );
    
    return $token;
}

/**
 * Passwort mit Reset-Token zurücksetzen
 */
function resetPasswordWithToken(string $token, string $newPassword): array {
    $hashedToken = hash('sha256', $token);
    
    $reset = dbFetchOne(
        "SELECT * FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
        [$hashedToken]
    );
    
    if (!$reset) {
        return ['success' => false, 'error' => 'Ungültiger oder abgelaufener Token.'];
    }
    
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'error' => 'Passwort muss mindestens 8 Zeichen lang sein.'];
    }
    
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    
    dbQuery("UPDATE users SET password_hash = ? WHERE email = ?", [$newHash, $reset['email']]);
    dbQuery("DELETE FROM password_resets WHERE email = ?", [$reset['email']]);
    
    return ['success' => true];
}

/**
 * Benutzer-Einstellungen aktualisieren
 */
function updateUserSettings(int $userId, array $settings): bool {
    $allowedFields = ['first_name', 'last_name', 'theme', 'language'];
    $updates = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($settings[$field])) {
            $updates[] = "$field = ?";
            $params[] = $settings[$field];
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    
    dbQuery($sql, $params);
    
    return true;
}
