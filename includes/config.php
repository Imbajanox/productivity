<?php
/**
 * Produktivitätstool - Konfiguration
 * 
 * Zentrale Konfigurationsdatei für das gesamte Projekt
 */

// Fehleranzeige (in Produktion ausschalten)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Session-Konfiguration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Auf 1 setzen wenn HTTPS

// Datenbank-Konfiguration
define('DB_HOST', 'localhost');
define('DB_NAME', 'productivity');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Anwendungs-Konfiguration
define('APP_NAME', 'Produktivitätstool');
define('APP_VERSION', '1.0.0');

// Basis-URL automatisch ermitteln
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// Calculate base path from the root directory, not the current script
$rootPath = dirname(__DIR__);  // This is the productivity folder
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$rootPath = str_replace('\\', '/', $rootPath);

// Get the relative path from document root
$basePath = str_replace($docRoot, '', $rootPath);
$basePath = '/' . trim($basePath, '/');
if ($basePath === '/') {
    $basePath = '';
}

define('APP_URL', $protocol . $host . $basePath);
define('BASE_PATH', $basePath);

// Pfade
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('API_PATH', ROOT_PATH . 'api' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);

// Sicherheit
define('HASH_COST', 12); // bcrypt cost factor
define('SESSION_LIFETIME', 86400); // 24 Stunden in Sekunden
define('CSRF_TOKEN_NAME', 'csrf_token');

// Standardwerte
define('DEFAULT_THEME', 'light'); // 'light' oder 'dark'
define('DEFAULT_LANGUAGE', 'de');
define('ITEMS_PER_PAGE', 20);
