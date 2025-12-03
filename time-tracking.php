<?php
/**
 * Produktivitätstool - Zeiterfassung
 * Time tracking with start/stop timer and manual entries
 */

require_once 'includes/init.php';

// Require authentication
requireAuth();

$pageTitle = 'Zeiterfassung';
$pageStyles = ['css/time-tracking.css'];
$pageScripts = ['js/time-tracking.js'];

// Get current user data
$user = getCurrentUser();
$userId = getCurrentUserId();

// Get projects for dropdown
$projects = dbFetchAll(
    "SELECT id, name, color FROM projects WHERE user_id = ? ORDER BY name",
    [$userId]
);

// Get today's time entries
$today = date('Y-m-d');
$todayEntries = dbFetchAll(
    "SELECT te.*, p.name as project_name, p.color as project_color
     FROM time_entries te
     LEFT JOIN projects p ON te.project_id = p.id
     WHERE te.user_id = ? AND DATE(te.start_time) = ?
     ORDER BY te.start_time DESC",
    [$userId, $today]
);

// Calculate today's total
$todayTotal = 0;
foreach ($todayEntries as $entry) {
    if (!empty($entry['duration_seconds'])) {
        $todayTotal += $entry['duration_seconds'];
    } elseif ($entry['start_time'] && !$entry['end_time']) {
        // Running timer
        $todayTotal += time() - strtotime($entry['start_time']);
    }
}

// Check for active timer
$activeTimer = dbFetchOne(
    "SELECT te.*, p.name as project_name, p.color as project_color
     FROM time_entries te
     LEFT JOIN projects p ON te.project_id = p.id
     WHERE te.user_id = ? AND te.end_time IS NULL
     ORDER BY te.start_time DESC
     LIMIT 1",
    [$userId]
);

// Start layout
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Zeiterfassung</h1>
        <p class="text-muted">Erfasse deine Arbeitszeit für Projekte</p>
    </div>
    <div class="page-header-right">
        <button class="btn btn-secondary" id="reportsBtn">
            <i class="fas fa-chart-bar"></i> Berichte
        </button>
        <button class="btn btn-secondary" id="exportBtn">
            <i class="fas fa-download"></i> Export
        </button>
        <button class="btn btn-secondary" id="manualEntryBtn">
            <i class="fas fa-plus"></i> Manueller Eintrag
        </button>
    </div>
</div>

<!-- Timer Section -->
<div class="timer-section card">
    <div class="timer-display">
        <div class="timer-time" id="timerDisplay">00:00:00</div>
        <div class="timer-project" id="timerProject">
            <?php if ($activeTimer): ?>
                <?php if ($activeTimer['is_break']): ?>
                    <span class="badge badge-warning">Pause</span>
                <?php endif; ?>
                <?php echo htmlspecialchars($activeTimer['project_name'] ?? 'Kein Projekt'); ?>
            <?php else: ?>
                Kein aktiver Timer
            <?php endif; ?>
        </div>
    </div>
    
    <div class="timer-controls">
        <?php if (!$activeTimer): ?>
            <div class="timer-start-form">
                <select class="form-control" id="timerProjectSelect">
                    <option value="">Projekt wählen (optional)</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>">
                            <?php echo htmlspecialchars($project['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" class="form-control" id="timerDescription" placeholder="Beschreibung (optional)">
                <button class="btn btn-success btn-lg" id="startTimerBtn">
                    <i class="fas fa-play"></i> Start
                </button>
                <button class="btn btn-warning btn-lg" id="startBreakBtn" title="Pause starten">
                    <i class="fas fa-coffee"></i>
                </button>
            </div>
        <?php else: ?>
            <div class="timer-running-info">
                <input type="text" class="form-control" id="timerDescription" 
                       value="<?php echo htmlspecialchars($activeTimer['description'] ?? ''); ?>"
                       placeholder="Beschreibung hinzufügen...">
            </div>
            <button class="btn btn-danger btn-lg" id="stopTimerBtn">
                <i class="fas fa-stop"></i> Stop
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="time-stats">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value" id="todayTotal">--:--</div>
            <div class="stat-label">Heute</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-calendar-week"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value" id="weekTotal">--:--</div>
            <div class="stat-label">Diese Woche</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value" id="monthTotal">--:--</div>
            <div class="stat-label">Dieser Monat</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-list-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value" id="entriesCount"><?php echo count($todayEntries); ?></div>
            <div class="stat-label">Einträge heute</div>
        </div>
    </div>
</div>

<!-- Date Filter -->
<div class="time-filter card">
    <div class="filter-row">
        <div class="filter-group">
            <label>Zeitraum:</label>
            <select class="form-select" id="filterPeriod">
                <option value="today">Heute</option>
                <option value="yesterday">Gestern</option>
                <option value="week">Diese Woche</option>
                <option value="month">Dieser Monat</option>
                <option value="custom">Benutzerdefiniert</option>
            </select>
        </div>
        <div class="filter-group custom-dates hidden" id="customDates">
            <label>Von:</label>
            <input type="date" class="form-control" id="filterDateFrom">
            <label>Bis:</label>
            <input type="date" class="form-control" id="filterDateTo">
        </div>
        <div class="filter-group">
            <label>Projekt:</label>
            <select class="form-select" id="filterProject">
                <option value="">Alle Projekte</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?php echo $project['id']; ?>">
                        <?php echo htmlspecialchars($project['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- Time Entries List -->
<div class="time-entries-section">
    <h2>Zeiteinträge</h2>
    
    <div class="time-entries-list" id="timeEntriesList">
        <?php if (empty($todayEntries)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">⏱️</div>
                <h3>Keine Einträge für heute</h3>
                <p>Starte den Timer oder erstelle einen manuellen Eintrag.</p>
            </div>
        <?php else: ?>
            <?php foreach ($todayEntries as $entry): ?>
                <div class="time-entry <?php echo $entry['is_break'] ? 'is-break' : ''; ?>" data-id="<?php echo $entry['id']; ?>">
                    <div class="time-entry-project">
                        <?php if ($entry['is_break']): ?>
                            <span class="project-badge break-badge">
                                <i class="fas fa-coffee"></i> Pause
                            </span>
                        <?php elseif ($entry['project_name']): ?>
                            <span class="project-badge" style="background: <?php echo $entry['project_color'] ?? 'var(--primary)'; ?>">
                                <?php echo htmlspecialchars($entry['project_name']); ?>
                            </span>
                        <?php else: ?>
                            <span class="project-badge no-project">Kein Projekt</span>
                        <?php endif; ?>
                    </div>
                    <div class="time-entry-description">
                        <?php echo htmlspecialchars($entry['description'] ?: 'Keine Beschreibung'); ?>
                    </div>
                    <div class="time-entry-times">
                        <span class="start-time"><?php echo date('H:i:s', strtotime($entry['start_time'])); ?></span>
                        <span class="time-separator">-</span>
                        <span class="end-time">
                            <?php echo $entry['end_time'] ? date('H:i:s', strtotime($entry['end_time'])) : 'Läuft...'; ?>
                        </span>
                    </div>
                    <div class="time-entry-duration <?php echo !$entry['end_time'] ? 'running' : ''; ?>">
                        <?php 
                        if ($entry['duration_seconds']) {
                            echo formatDuration($entry['duration_seconds']);
                        } elseif ($entry['start_time'] && !$entry['end_time']) {
                            echo '<span class="running-duration" data-start="' . strtotime($entry['start_time']) . '">--:--</span>';
                        }
                        ?>
                    </div>
                    <div class="time-entry-actions">
                        <button class="action-btn edit-btn" title="Bearbeiten">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete-btn" title="Löschen">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Manual Entry Modal -->
<div class="modal" id="timeEntryModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Zeiteintrag</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="timeEntryForm">
                <input type="hidden" name="id">
                
                <div class="form-group">
                    <label for="entryProject">Projekt</label>
                    <select class="form-control" name="project_id" id="entryProject">
                        <option value="">Kein Projekt</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="entryDescription">Beschreibung</label>
                    <textarea class="form-control" name="description" id="entryDescription" rows="2" 
                              placeholder="Was hast du gemacht?"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="entryDate">Datum</label>
                        <input type="date" class="form-control" name="date" id="entryDate" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_break" id="entryIsBreak">
                            <span>Pauseneintrag</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="entryStartTime">Startzeit</label>
                        <input type="time" class="form-control" name="start_time" id="entryStartTime" step="1" required>
                    </div>
                    <div class="form-group">
                        <label for="entryEndTime">Endzeit</label>
                        <input type="time" class="form-control" name="end_time" id="entryEndTime" step="1" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Dauer</label>
                    <div class="calculated-duration" id="calculatedDuration">--:--</div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary modal-cancel">Abbrechen</button>
            <button type="submit" form="timeEntryForm" class="btn btn-primary">Speichern</button>
        </div>
    </div>
</div>

<!-- Reports Modal -->
<div class="modal" id="reportsModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Zeitberichte</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="reports-controls">
                <select class="form-select" id="reportPeriod">
                    <option value="week">Diese Woche</option>
                    <option value="last_week">Letzte Woche</option>
                    <option value="month">Dieser Monat</option>
                    <option value="last_month">Letzter Monat</option>
                    <option value="year">Dieses Jahr</option>
                </select>
                <select class="form-select" id="reportProject">
                    <option value="">Alle Projekte</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>">
                            <?php echo htmlspecialchars($project['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="reports-summary" id="reportsSummary">
                <!-- Loaded via JS -->
            </div>
            
            <div class="reports-charts">
                <div class="chart-container">
                    <h4>Tägliche Arbeitszeit</h4>
                    <canvas id="dailyChart"></canvas>
                </div>
                <div class="chart-container">
                    <h4>Nach Projekt</h4>
                    <canvas id="projectChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal" id="exportModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Zeiteinträge exportieren</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="exportForm">
                <div class="form-group">
                    <label>Zeitraum</label>
                    <select class="form-control" name="period" id="exportPeriod">
                        <option value="week">Diese Woche</option>
                        <option value="month" selected>Dieser Monat</option>
                        <option value="year">Dieses Jahr</option>
                        <option value="custom">Benutzerdefiniert</option>
                    </select>
                </div>
                
                <div class="form-row hidden" id="exportCustomDates">
                    <div class="form-group">
                        <label>Von</label>
                        <input type="date" class="form-control" name="date_from" id="exportDateFrom">
                    </div>
                    <div class="form-group">
                        <label>Bis</label>
                        <input type="date" class="form-control" name="date_to" id="exportDateTo">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Projekt</label>
                    <select class="form-control" name="project_id" id="exportProject">
                        <option value="">Alle Projekte</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Format</label>
                    <div class="export-formats">
                        <label class="radio-card">
                            <input type="radio" name="format" value="csv" checked>
                            <span class="radio-card-content">
                                <i class="fas fa-file-csv"></i>
                                <span>CSV</span>
                            </span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="format" value="pdf">
                            <span class="radio-card-content">
                                <i class="fas fa-file-pdf"></i>
                                <span>PDF</span>
                            </span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary modal-cancel">Abbrechen</button>
            <button type="button" class="btn btn-primary" id="doExportBtn">
                <i class="fas fa-download"></i> Exportieren
            </button>
        </div>
    </div>
</div>

<!-- Load Chart.js -->
<script src="<?php echo url('node_modules/chart.js/dist/chart.umd.js'); ?>"></script>

<script>
    window.timeTrackingConfig = {
        activeTimer: <?php echo $activeTimer ? json_encode([
            'id' => $activeTimer['id'],
            'start_time' => $activeTimer['start_time'],
            'project_id' => $activeTimer['project_id'],
            'project_name' => $activeTimer['project_name'],
            'description' => $activeTimer['description'],
            'is_break' => (bool)$activeTimer['is_break']
        ]) : 'null'; ?>,
        projects: <?php echo json_encode($projects); ?>
    };
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
