# Produktivitätstool

Ein umfassendes Produktivitätstool für Webentwickler, entwickelt mit HTML, CSS, JavaScript und PHP mit MySQL-Datenbank.

## 🚀 Installation & Setup

### Voraussetzungen

- **WAMP/XAMPP** oder ähnlicher lokaler Webserver
- **PHP 8.0+** mit folgenden Extensions:
  - PDO
  - PDO MySQL
  - mbstring
  - session
- **MySQL 8.0+**
- **Node.js** (für npm-Pakete)

### 1. Repository klonen/herunterladen

```bash
# In deinen Webserver-Ordner wechseln (z.B. wamp64/www)
cd /path/to/webserver/www
git clone <repository-url> productivity
# oder entpacke die ZIP-Datei
```

### 2. Datenbank einrichten

1. **MySQL-Verbindung herstellen** (via phpMyAdmin, MySQL Workbench oder Kommandozeile)

2. **Datenbank erstellen:**
   ```sql
   CREATE DATABASE productivity CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Schema importieren:**
   - Öffne phpMyAdmin
   - Wähle die Datenbank `productivity` aus
   - Gehe zu "Importieren"
   - Wähle die Datei `database/schema.sql` aus
   - Klicke auf "OK"

   **Oder über Kommandozeile:**
   ```bash
   mysql -u root -p productivity < database/schema.sql
   ```

### 3. Konfiguration anpassen

Bearbeite `includes/config.php` und passe die Datenbank-Verbindung an:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'productivity');
define('DB_USER', 'root');  // Dein MySQL-Benutzername
define('DB_PASS', '');      // Dein MySQL-Passwort
```

### 4. Abhängigkeiten installieren

```bash
# Im Projektverzeichnis
npm install
```

### 5. Berechtigungen setzen

Stelle sicher, dass der Webserver Schreibrechte für diese Ordner hat:
- `assets/`
- `src/`

### 6. Anwendung starten

1. **WAMP/XAMPP starten**
2. **Browser öffnen** und zu `http://localhost/productivity` navigieren
3. **Registrieren** oder mit dem Demo-Konto anmelden:
   - Benutzername: `demo`
   - Passwort: `password123`

## 📁 Projektstruktur

```
productivity/
├── api/                    # API-Endpunkte
│   ├── auth/              # Authentifizierung
│   └── dashboard/         # Dashboard-Daten
├── assets/                # Kompilierte Assets
│   ├── css/
│   ├── js/
│   └── images/
├── database/              # Datenbank-Dateien
│   └── schema.sql         # Datenbankschema
├── includes/              # PHP-Includes
│   ├── config.php         # Konfiguration
│   ├── db.php            # Datenbankverbindung
│   ├── auth.php          # Authentifizierungsfunktionen
│   ├── functions.php     # Hilfsfunktionen
│   └── init.php          # Initialisierung
├── src/                   # Quell-Dateien
│   ├── css/              # CSS-Quellen
│   └── js/               # JavaScript-Quellen
├── index.php             # Dashboard
├── login.php             # Login-Seite
├── register.php          # Registrierung
├── forgot-password.php   # Passwort vergessen
├── roadmap.md            # Projekt-Roadmap
├── package.json          # Node.js-Abhängigkeiten
└── README.md             # Diese Datei
```

## 🎨 Technologie-Stack

| Bereich | Technologie |
|---------|-------------|
| Frontend | HTML5, CSS3 (Custom Framework), Vanilla JavaScript (ES6+) |
| Backend | PHP 8.x |
| Datenbank | MySQL 8.x |
| Icons | Font Awesome (CDN) |
| Editor | Quill.js |
| Syntax-Highlighting | Prism.js |
| Kalender | FullCalendar.js |
| Charts | Chart.js |
| Drag & Drop | SortableJS |

## 🔧 Entwicklung

### CSS-Framework

Das CSS-Framework befindet sich in `src/css/framework.css` und bietet:

- CSS-Variablen für Dark/Light Mode
- Responsive Grid-System
- Utility-Klassen
- Komponenten (Buttons, Cards, Forms, etc.)
- Mobile-first Design

### JavaScript-Utilities

Die JavaScript-Utilities in `src/js/utils.js` bieten:

- Theme-Management (Dark/Light Mode)
- API-Client für AJAX-Requests
- DOM-Manipulation-Helper
- Form-Validierung
- Notification-System
- Date/Time-Utilities

### API-Struktur

Alle API-Endpunkte befinden sich im `api/`-Ordner und geben JSON zurück:

- `POST /api/auth/login.php` - Anmeldung
- `POST /api/auth/register.php` - Registrierung
- `POST /api/auth/logout.php` - Abmeldung
- `GET /api/dashboard/*` - Dashboard-Daten

## 🚦 Nächste Schritte

Phase 1 (Grundlagen) ist abgeschlossen! Als nächstes:

1. **Phase 2 starten:** Todo-Management implementieren
2. **Datenbank testen:** Stelle sicher, dass alle Tabellen korrekt erstellt wurden
3. **Authentifizierung testen:** Registrierung und Login funktionieren
4. **Dashboard testen:** Alle Widgets laden korrekt

## 📝 Hinweise

- **Sicherheit:** In Produktion sollten CSRF-Tokens, Input-Validierung und SQL-Injection-Schutz überprüft werden
- **Performance:** Für größere Datenmengen sollten Indizes und Caching implementiert werden
- **Backup:** Regelmäßige Datenbank-Backups sind empfehlenswert
- **Updates:** Bei Schema-Änderungen müssen Migrations-Scripts erstellt werden

## 🐛 Fehlerbehebung

### Datenbank-Verbindung fehlgeschlagen
- Überprüfe die Konfiguration in `includes/config.php`
- Stelle sicher, dass MySQL läuft
- Überprüfe Benutzername und Passwort

### Seiten laden nicht
- Überprüfe PHP-Fehler logs
- Stelle sicher, dass `mod_rewrite` aktiviert ist (falls verwendet)
- Überprüfe Dateiberechtigungen

### JavaScript-Fehler
- Überprüfe Browser-Konsole auf Fehler
- Stelle sicher, dass alle Abhängigkeiten geladen sind
- Überprüfe Pfade zu Assets

## 📄 Lizenz

Dieses Projekt ist für Bildungszwecke gedacht. Bei kommerzieller Nutzung bitte entsprechende Lizenzen beachten.

---

**Entwickelt mit ❤️ für Webentwickler**