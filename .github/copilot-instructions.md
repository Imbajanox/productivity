# Copilot Instructions - Produktivitätstool

## Project Overview

German-language productivity web app (WAMP/XAMPP) built with vanilla PHP 8+, MySQL 8+, and ES6+ JavaScript. No PHP framework - uses custom patterns. Multi-module system: Todos, Time Tracking, Notes, Projects, Calendar.

## Architecture

### Request Flow
1. **Page requests** → `*.php` in root → `includes/init.php` → `includes/layout.php` (template wrapper)
2. **API requests** → `api/{module}/{action}.php` → JSON responses via `jsonResponse()`/`jsonSuccess()`/`jsonError()`

### Key Files
- `includes/init.php` - Bootstrap: loads config, db, functions, auth; starts session
- `includes/db.php` - Singleton PDO with helpers: `db()`, `dbQuery()`, `dbFetchOne()`, `dbFetchAll()`, `dbInsert()`
- `includes/functions.php` - Global helpers: `e()`, `sanitize()`, `getJsonInput()`, `requireAuth()`, `url()`, `asset()`
- `includes/layout.php` - HTML wrapper; expects `$pageTitle`, `$pageId`, `$extraCss[]`, `$extraJs[]`, `$content`
- `assets/js/utils.js` - JS utilities: `ApiClient`, `ThemeManager`, `NotificationManager`, `DomUtils`, `FormUtils`

### Database Pattern
```php
// Always use prepared statements via helper functions
$user = dbFetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
$todos = dbFetchAll("SELECT * FROM todos WHERE user_id = ? AND status = ?", [$userId, 'todo']);
$newId = dbInsert("INSERT INTO todos (user_id, title) VALUES (?, ?)", [$userId, $title]);
// Or ORM-style insert:
$newId = dbInsert('todos', ['user_id' => $userId, 'title' => $title]);
```

## Conventions

### PHP API Endpoints
```php
<?php
require_once __DIR__ . '/../../includes/init.php';
requireAuth();  // Always first for protected endpoints

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

try {
    $data = getJsonInput();  // Parse JSON body
    $userId = getCurrentUserId();
    // ... business logic with dbQuery/dbFetchOne/dbFetchAll ...
    jsonSuccess($result, 'Erfolgreich');  // German messages
} catch (Exception $e) {
    error_log("Error in {endpoint}: " . $e->getMessage());
    jsonError('Fehler beim ...', 500);
}
```

### PHP Page Structure
```php
<?php
require_once __DIR__ . '/includes/init.php';
requireAuth();

$pageTitle = 'Page Name';
$pageId = 'page-id';  // For nav highlighting
$extraCss = ['css/page.css'];
$extraJs = ['js/page.js'];

ob_start();
?>
<!-- Page HTML content -->
<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
?>
```

### JavaScript API Calls
```javascript
// Global `api` instance from utils.js
const response = await api.get('/api/todos/list.php', { status: 'todo' });
const result = await api.post('/api/todos/create.php', { title: 'New todo' });
// Error handling via ApiError class
// Notifications via global `notifications.success()`, `notifications.error()`
```

### Escaping
- HTML output: `<?php echo e($variable); ?>` - never raw output
- SQL: Always use prepared statements with `?` placeholders
- JSON input: `$data = getJsonInput()` then validate/sanitize

## File Locations

| Type | Location | Notes |
|------|----------|-------|
| CSS | `assets/css/` | Served to browser |
| JS | `assets/js/` | Served to browser |
| API endpoints | `api/{module}/{action}.php` | Return JSON only |
| DB migrations | `database/migrations/` | Numbered SQL files |

## Database Schema

Main tables: `users`, `projects`, `todos`, `subtasks`, `tags`, `todo_tags`, `time_entries`, `notes`, `calendar_events`, `snippets`, `bookmarks`, `habits`, `habit_logs`

Foreign keys use `ON DELETE CASCADE` or `ON DELETE SET NULL`. All tables use `INT UNSIGNED AUTO_INCREMENT` for IDs and `TIMESTAMP` for created_at/updated_at.

## Development Notes

- **Language**: All UI text and comments are in German
- **Theme**: CSS variables in `framework.css`, toggle via `ThemeManager`
- **npm packages**: Quill.js, SortableJS, Chart.js, FullCalendar.js, Prism.js already installed
- **Auth**: Session-based with optional remember-me token; `requireAuth()` for protected routes
- **Paths**: Use `url('path')` for URLs, `asset('css/file.css')` for assets, `BASE_PATH` constant available
