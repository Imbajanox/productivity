# Produktivitätstool - Roadmap

## Projektübersicht

Ein umfassendes Produktivitätstool für Webentwickler, entwickelt mit HTML, CSS, JavaScript und PHP mit MySQL-Datenbank.

---
** Quill, Prism.js, Highlight.js, FullCalendar.js, Chart.js, Sortable.js sind bereits über npm installiert **
## Phase 1: Grundlagen & Infrastruktur (Woche 1-2)

### 1.1 Projektsetup
- [x] Ordnerstruktur erstellen (src, assets, includes, api)
- [x] Datenbankschema entwerfen
- [ ] MySQL-Datenbank und Tabellen anlegen
- [x] PHP-Konfiguration (config.php, db.php)
- [x] Grundlegendes CSS-Framework/Reset
- [x] JavaScript-Utilities und Helper-Funktionen

### 1.2 Authentifizierung
- [x] Benutzerregistrierung
- [x] Login/Logout-System
- [x] Passwort-Hashing (bcrypt)
- [x] Session-Management
- [x] "Passwort vergessen"-Funktion

### 1.3 Dashboard-Layout
- [x] Responsive Sidebar-Navigation
- [x] Header mit Benutzermenü
- [x] Hauptbereich mit Widget-Grid
- [x] Dark/Light Mode Toggle

---

## Phase 2: Kernfunktionen (Woche 3-5)

### 2.1 Todo-Management
- [x] Todos erstellen, bearbeiten, löschen
- [x] Prioritäten (Hoch, Mittel, Niedrig)
- [x] Fälligkeitsdatum mit Erinnerungen
- [x] Kategorien/Tags
- [x] Subtasks (Unteraufgaben)
- [x] Drag & Drop Sortierung
- [x] Filterung und Suche
- [x] Wiederkehrende Aufgaben
- [x] Kanban-Board Ansicht (Todo, In Progress, Done)

### 2.2 Zeiterfassung
- [x] Start/Stop Timer
- [x] Manuelle Zeiteinträge
- [x] Zuordnung zu Projekten/Todos
- [x] Tages-, Wochen-, Monatsübersicht
- [x] Zeitberichte und Statistiken
- [x] Export (CSV, PDF)
- [x] Pausenzeiten tracken

### 2.3 Notizen
- [x] Rich-Text-Editor (WYSIWYG)
- [x] Markdown-Unterstützung
- [x] Ordner/Kategorien für Notizen
- [x] Volltextsuche
- [x] Notizen an Todos/Projekte anhängen
- [ ] Code-Snippets mit Syntax-Highlighting
- [x] Notizen teilen (öffentliche Links)

---

## Phase 3: Erweiterte Features (Woche 6-8)

### 3.1 Projektmanagement
- [ ] Projekte erstellen und verwalten
- [ ] Projektstatus und Fortschrittsanzeige
- [ ] Todos Projekten zuordnen
- [ ] Projektübersicht mit Statistiken
- [ ] Deadlines und Meilensteine
- [ ] Projektarchiv

### 3.2 Kalender-Integration
- [ ] Monats-, Wochen-, Tagesansicht
- [ ] Termine und Events erstellen
- [ ] Todos im Kalender anzeigen
- [ ] Drag & Drop für Termine
- [ ] Erinnerungen (Browser-Notifications)
- [ ] iCal-Export

### 3.3 Pomodoro-Timer
- [ ] Klassischer 25/5 Minuten Timer
- [ ] Anpassbare Intervalle
- [ ] Statistiken (Pomodoros pro Tag/Woche)
- [ ] Sound-Benachrichtigungen
- [ ] Integration mit Zeiterfassung

---

## Phase 4: Entwickler-spezifische Features (Woche 9-10)

### 4.1 Code-Snippet-Bibliothek
- [ ] Snippets speichern und kategorisieren
- [ ] Syntax-Highlighting für verschiedene Sprachen
- [ ] Tags und Suche
- [ ] Schnelles Kopieren in Zwischenablage
- [ ] Import/Export von Snippets

### 4.2 Link-/Bookmark-Manager
- [ ] Wichtige Links speichern
- [ ] Kategorisierung (Dokumentation, Tools, etc.)
- [ ] Favicon-Anzeige
- [ ] Schnellzugriff-Leiste

### 4.3 API-Request-Tester (Mini-Postman)
- [ ] HTTP-Requests senden (GET, POST, PUT, DELETE)
- [ ] Request-Historie speichern
- [ ] Response-Anzeige mit Formatierung
- [ ] Header und Body konfigurieren

### 4.4 Regex-Tester
- [ ] Regex-Pattern testen
- [ ] Match-Highlighting
- [ ] Pattern-Bibliothek speichern

---

## Phase 5: Zusätzliche Features (Woche 11-12)

### 5.1 Gewohnheits-Tracker (Habit Tracker)
- [ ] Tägliche Gewohnheiten definieren
- [ ] Streak-Anzeige (Tage in Folge)
- [ ] Visuelle Fortschrittsanzeige (Heatmap)
- [ ] Statistiken und Trends

### 5.2 Tages-/Wochenplanung
- [ ] Tägliche Ziele setzen
- [ ] Wochenrückblick
- [ ] Prioritäten für den Tag
- [ ] "Eat the Frog" - wichtigste Aufgabe zuerst

### 5.3 Schnelle Notizen (Quick Capture)
- [ ] Floating Widget für schnelle Eingaben
- [ ] Später sortieren und zuordnen
- [ ] Tastenkürzel für schnellen Zugriff

### 5.4 Fokus-Modus
- [ ] Ablenkungsfreie Ansicht
- [ ] Website-Blocker-Reminder
- [ ] Timer mit aktueller Aufgabe

---

## Phase 6: Reporting & Analytics (Woche 13)

### 6.1 Dashboard-Widgets
- [ ] Übersicht offene Todos
- [ ] Heute fällige Aufgaben
- [ ] Zeiterfassung diese Woche
- [ ] Projektfortschritt
- [ ] Streak-Anzeige
- [ ] Motivationszitate

### 6.2 Berichte
- [ ] Wöchentliche Produktivitätsberichte
- [ ] Zeitverteilung nach Projekten
- [ ] Erledigte Aufgaben Timeline
- [ ] PDF-Export

---

## Phase 7: Optimierung & Polish (Woche 14-15)

### 7.1 Performance
- [ ] Lazy Loading für Listen
- [ ] Caching-Strategie
- [ ] Datenbankoptimierung (Indizes)
- [ ] Minifizierung von CSS/JS

### 7.2 UX-Verbesserungen
- [ ] Tastenkürzel für alle Aktionen
- [ ] Drag & Drop überall
- [ ] Undo/Redo Funktionalität
- [ ] Offline-Unterstützung (Service Worker)
- [ ] Progressive Web App (PWA)

### 7.3 Personalisierung
- [ ] Farbschemas anpassen
- [ ] Widget-Anordnung speichern
- [ ] Benutzerdefinierte Kategorien
- [ ] Sprache (DE/EN)

---

## Datenbank-Schema (Übersicht)

```
users
├── id, username, email, password_hash, created_at, settings

projects
├── id, user_id, name, description, status, color, deadline, created_at

todos
├── id, user_id, project_id, title, description, priority, status
├── due_date, reminder, recurring, parent_id, position, created_at

time_entries
├── id, user_id, todo_id, project_id, start_time, end_time, duration, notes

notes
├── id, user_id, project_id, title, content, folder, tags, created_at, updated_at

calendar_events
├── id, user_id, title, description, start_datetime, end_datetime, all_day, reminder

snippets
├── id, user_id, title, code, language, tags, created_at

bookmarks
├── id, user_id, title, url, category, favicon, created_at

habits
├── id, user_id, name, frequency, created_at

habit_logs
├── id, habit_id, date, completed

tags
├── id, user_id, name, color

todo_tags (Pivot-Tabelle)
├── todo_id, tag_id
```

---

## Technologie-Stack

| Bereich | Technologie |
|---------|-------------|
| Frontend | HTML5, CSS3 (Flexbox/Grid), Vanilla JavaScript (ES6+) |
| Backend | PHP 8.x |
| Datenbank | MySQL 8.x |
| Icons | Font Awesome (cdn)|
| Editor | Quill.js|
| Syntax-Highlighting | Prism.js oder Highlight.js |
| Kalender | FullCalendar.js |
| Charts | Chart.js |
| Drag & Drop | SortableJS |
** Quill, Prism.js, Highlight.js, FullCalendar.js, Chart.js, Sortable.js are already installed via npm **
---

## Nice-to-Have (Zukunft)

- [ ] Team-Funktionen (Todos teilen, Zusammenarbeit)
- [ ] Mobile App (PWA optimiert)
- [ ] Browser-Extension für Quick Capture
- [ ] Integration mit GitHub (Issues importieren)
- [ ] Slack/Discord Notifications
- [ ] Backup/Restore Funktion
- [ ] Import von anderen Tools (Trello, Todoist)
- [ ] Sprachnotizen
- [ ] KI-gestützte Aufgabenpriorisierung
- [ ] Automatische Zeitschätzung basierend auf Historie

---

## Prioritäten-Matrix

| Feature | Wichtigkeit | Aufwand | Priorität |
|---------|-------------|---------|-----------|
| Todo-Management | ⭐⭐⭐⭐⭐ | Mittel | 🔴 Hoch |
| Zeiterfassung | ⭐⭐⭐⭐⭐ | Mittel | 🔴 Hoch |
| Notizen | ⭐⭐⭐⭐ | Mittel | 🔴 Hoch |
| Dashboard | ⭐⭐⭐⭐ | Niedrig | 🔴 Hoch |
| Kalender | ⭐⭐⭐⭐ | Hoch | 🟡 Mittel |
| Pomodoro | ⭐⭐⭐ | Niedrig | 🟡 Mittel |
| Code-Snippets | ⭐⭐⭐⭐ | Niedrig | 🟡 Mittel |
| Habit-Tracker | ⭐⭐⭐ | Mittel | 🟢 Niedrig |
| API-Tester | ⭐⭐ | Mittel | 🟢 Niedrig |

---

## Nächste Schritte

1. **Jetzt:** Datenbankschema finalisieren und erstellen
2. **Dann:** Basis-Layout und Authentifizierung implementieren
3. **Danach:** Mit Todo-Management als erstem Kernfeature starten

---

*Letzte Aktualisierung: Dezember 2025*
