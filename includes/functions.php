<?php
/**
 * Produktivitätstool - Hilfsfunktionen
 * 
 * Allgemeine PHP-Hilfsfunktionen
 */

/**
 * Sicheres Escapen von HTML-Ausgaben
 */
function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * String bereinigen (für Datenbank-Eingaben)
 */
function sanitize(?string $string): string {
    if ($string === null) {
        return '';
    }
    return trim(htmlspecialchars($string, ENT_QUOTES, 'UTF-8'));
}

/**
 * JSON-Input aus Request-Body lesen
 */
function getJsonInput(): array {
    $json = file_get_contents('php://input');
    if (empty($json)) {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * JSON-Response senden
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Erfolgs-Response
 */
function jsonSuccess(mixed $data = null, string $message = 'Erfolgreich'): void {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Fehler-Response
 */
function jsonError(string $message, int $statusCode = 400, array $errors = []): void {
    jsonResponse([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $statusCode);
}

/**
 * CSRF-Token generieren
 */
function generateCsrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * CSRF-Token validieren
 */
function validateCsrfToken(?string $token): bool {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * CSRF-Token-Feld für Formulare
 */
function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCsrfToken() . '">';
}

/**
 * Prüfen ob Benutzer eingeloggt ist
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Aktuellen Benutzer abrufen
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    static $user = null;
    
    if ($user === null) {
        $user = dbFetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    return $user;
}

/**
 * Aktuelle Benutzer-ID
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Alias für currentUser()
 */
function getCurrentUser(): ?array {
    return currentUser();
}

/**
 * Alias für currentUserId()
 */
function getCurrentUserId(): ?int {
    return currentUserId();
}

/**
 * Benutzer muss eingeloggt sein
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        if (isAjaxRequest()) {
            jsonError('Nicht autorisiert', 401);
        }
        redirect('/login.php');
    }
}

/**
 * Prüfen ob AJAX-Request
 */
function isAjaxRequest(): bool {
    // Check X-Requested-With header
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    
    // Check if Accept header contains application/json
    if (!empty($_SERVER['HTTP_ACCEPT']) 
        && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        return true;
    }
    
    // Check if Content-Type is application/json (for POST/PUT requests)
    if (!empty($_SERVER['CONTENT_TYPE']) 
        && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        return true;
    }
    
    // Check if request is to /api/ path
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, '/api/') !== false) {
        return true;
    }
    
    return false;
}

/**
 * Weiterleitung
 */
function redirect(string $url): void {
    // Wenn URL mit / beginnt, BASE_PATH voranstellen
    if (strpos($url, '/') === 0) {
        $url = BASE_PATH . $url;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * URL mit Base Path generieren
 */
function url(string $path = ''): string {
    return BASE_PATH . '/' . ltrim($path, '/');
}

/**
 * Asset-URL generieren
 */
function asset(string $path): string {
    return BASE_PATH . '/assets/' . ltrim($path, '/');
}

/**
 * Flash-Nachricht setzen
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Flash-Nachricht abrufen und löschen
 */
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Datum formatieren
 */
function formatDate(?string $date, string $format = 'd.m.Y'): string {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Datum und Zeit formatieren
 */
function formatDateTime(?string $datetime, string $format = 'd.m.Y H:i'): string {
    if (empty($datetime)) {
        return '';
    }
    return date($format, strtotime($datetime));
}

/**
 * Relative Zeit (z.B. "vor 5 Minuten")
 */
function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'gerade eben';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "vor $mins Minute" . ($mins > 1 ? 'n' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "vor $hours Stunde" . ($hours > 1 ? 'n' : '');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "vor $days Tag" . ($days > 1 ? 'en' : '');
    } else {
        return formatDate($datetime);
    }
}

/**
 * Alias für timeAgo()
 */
function formatRelativeTime(string $datetime): string {
    return timeAgo($datetime);
}

/**
 * Sekunden in lesbares Format (z.B. "2h 30m")
 */
function formatDuration(int $seconds): string {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    $parts = [];
    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }
    if ($minutes > 0 || empty($parts)) {
        $parts[] = $minutes . 'm';
    }
    
    return implode(' ', $parts);
}

/**
 * String kürzen
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Slug aus String generieren
 */
function slugify(string $text): string {
    $text = preg_replace('/[^a-zA-Z0-9\s-]/', '', $text);
    $text = strtolower(trim($text));
    $text = preg_replace('/[\s-]+/', '-', $text);
    return $text;
}

/**
 * Zufälliges Token generieren
 */
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Input-Wert abrufen (POST oder GET)
 */
function input(string $key, mixed $default = null): mixed {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/**
 * Nur POST-Wert abrufen
 */
function post(string $key, mixed $default = null): mixed {
    return $_POST[$key] ?? $default;
}

/**
 * Nur GET-Wert abrufen
 */
function get(string $key, mixed $default = null): mixed {
    return $_GET[$key] ?? $default;
}

/**
 * Validierung: E-Mail
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Priorität als Farbe
 */
function priorityColor(string $priority): string {
    return match($priority) {
        'urgent' => '#e74c3c',
        'high' => '#e67e22',
        'medium' => '#f1c40f',
        'low' => '#27ae60',
        default => '#95a5a6'
    };
}

/**
 * Status als Farbe
 */
function statusColor(string $status): string {
    return match($status) {
        'todo' => '#3498db',
        'in_progress' => '#f39c12',
        'done' => '#27ae60',
        'cancelled' => '#95a5a6',
        default => '#95a5a6'
    };
}

/**
 * Priorität als Text
 */
function priorityLabel(string $priority): string {
    return match($priority) {
        'urgent' => 'Dringend',
        'high' => 'Hoch',
        'medium' => 'Mittel',
        'low' => 'Niedrig',
        default => 'Unbekannt'
    };
}

/**
 * Status als Text
 */
function statusLabel(string $status): string {
    return match($status) {
        'todo' => 'Offen',
        'in_progress' => 'In Bearbeitung',
        'done' => 'Erledigt',
        'cancelled' => 'Abgebrochen',
        default => 'Unbekannt'
    };
}

/**
 * Aktivität protokollieren
 * 
 * @param int $userId Benutzer-ID
 * @param string $actionType Typ der Aktion (z.B. 'todo_created', 'todo_completed')
 * @param string|null $entityType Entitätstyp (z.B. 'todo', 'project')
 * @param int|null $entityId ID der Entität
 * @param array|null $metadata Zusätzliche Metadaten als Array
 */
function logActivity(int $userId, string $actionType, ?string $entityType = null, ?int $entityId = null, ?array $metadata = null): void {
    try {
        $metadataJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
        
        dbInsert(
            "INSERT INTO activity_log (user_id, action_type, entity_type, entity_id, metadata, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            [$userId, $actionType, $entityType, $entityId, $metadataJson]
        );
    } catch (Exception $e) {
        // Fehler nur loggen, nicht die ursprüngliche Aktion blockieren
        error_log("Fehler beim Aktivitäts-Logging: " . $e->getMessage());
    }
}
