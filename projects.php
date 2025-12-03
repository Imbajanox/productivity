<?php
/**
 * Projektverwaltung
 */
require_once __DIR__ . '/includes/init.php';
requireAuth();

$pageTitle = 'Projekte';
$pageId = 'projects';
$pageStyles = ['css/projects.css'];
$pageScripts = ['js/projects.js'];

// Start output buffer for content
ob_start();
?>

<div class="projects-container">
    <!-- Header -->
    <div class="projects-header">
        <div class="header-left">
            <h1><i class="fas fa-folder-open"></i> Projekte</h1>
            <p class="text-secondary">Verwalte deine Projekte und organisiere deine Arbeit</p>
        </div>
        <div class="header-right">
            <button class="btn btn-primary" id="newProjectBtn">
                <i class="fas fa-plus"></i> Neues Projekt
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="projects-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--success);">
                <i class="fas fa-folder"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="totalProjects">0</span>
                <span class="stat-label">Projekte gesamt</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--primary);">
                <i class="fas fa-play-circle"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="activeProjects">0</span>
                <span class="stat-label">Aktive Projekte</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--warning);">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="totalTodos">0</span>
                <span class="stat-label">Todos</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--info);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value" id="totalTime">0h</span>
                <span class="stat-label">Erfasste Zeit</span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="projects-filters">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Projekte durchsuchen...">
        </div>
        <div class="filter-group">
            <select id="statusFilter" class="form-select">
                <option value="">Alle Status</option>
                <option value="active">Aktiv</option>
                <option value="paused">Pausiert</option>
                <option value="completed">Abgeschlossen</option>
                <option value="archived">Archiviert</option>
            </select>
        </div>
        <div class="view-toggle">
            <button class="btn btn-icon active" data-view="grid" title="Rasteransicht">
                <i class="fas fa-th-large"></i>
            </button>
            <button class="btn btn-icon" data-view="list" title="Listenansicht">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </div>

    <!-- Projects Grid -->
    <div class="projects-grid" id="projectsGrid">
        <!-- Projects loaded via JS -->
    </div>

    <!-- Empty State -->
    <div class="empty-state hidden" id="emptyState">
        <div class="empty-icon">
            <i class="fas fa-folder-plus"></i>
        </div>
        <h3>Keine Projekte vorhanden</h3>
        <p>Erstelle dein erstes Projekt, um loszulegen</p>
        <button class="btn btn-primary" id="emptyNewProjectBtn">
            <i class="fas fa-plus"></i> Projekt erstellen
        </button>
    </div>
</div>

<!-- Project Modal -->
<div class="modal" id="projectModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Neues Projekt</h2>
            <button class="btn btn-icon modal-close" aria-label="Schließen">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="projectForm">
            <div class="modal-body">
                <input type="hidden" id="projectId" name="id">
                
                <div class="form-group">
                    <label for="projectName">Projektname *</label>
                    <input type="text" id="projectName" name="name" required 
                           placeholder="z.B. Website Redesign">
                </div>
                
                <div class="form-group">
                    <label for="projectDescription">Beschreibung</label>
                    <textarea id="projectDescription" name="description" rows="3"
                              placeholder="Kurze Beschreibung des Projekts..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="projectColor">Farbe</label>
                        <div class="color-picker">
                            <input type="color" id="projectColor" name="color" value="#3498db">
                            <div class="color-presets">
                                <button type="button" class="color-preset" data-color="#3498db" style="background:#3498db"></button>
                                <button type="button" class="color-preset" data-color="#e74c3c" style="background:#e74c3c"></button>
                                <button type="button" class="color-preset" data-color="#27ae60" style="background:#27ae60"></button>
                                <button type="button" class="color-preset" data-color="#f39c12" style="background:#f39c12"></button>
                                <button type="button" class="color-preset" data-color="#9b59b6" style="background:#9b59b6"></button>
                                <button type="button" class="color-preset" data-color="#1abc9c" style="background:#1abc9c"></button>
                                <button type="button" class="color-preset" data-color="#e91e63" style="background:#e91e63"></button>
                                <button type="button" class="color-preset" data-color="#607d8b" style="background:#607d8b"></button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="projectStatus">Status</label>
                        <select id="projectStatus" name="status">
                            <option value="active">Aktiv</option>
                            <option value="paused">Pausiert</option>
                            <option value="completed">Abgeschlossen</option>
                            <option value="archived">Archiviert</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="projectDeadline">Deadline (optional)</label>
                    <input type="date" id="projectDeadline" name="deadline">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Abbrechen</button>
                <button type="submit" class="btn btn-primary" id="saveProjectBtn">
                    <i class="fas fa-save"></i> Speichern
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h2>Projekt löschen?</h2>
            <button class="btn btn-icon modal-close" aria-label="Schließen">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Möchtest du das Projekt <strong id="deleteProjectName"></strong> wirklich löschen?</p>
            <p class="text-warning"><i class="fas fa-exclamation-triangle"></i> Todos, Zeiteinträge und Notizen werden nicht gelöscht, aber die Projektzuordnung wird entfernt.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary modal-close">Abbrechen</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                <i class="fas fa-trash"></i> Löschen
            </button>
        </div>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/includes/layout.php';
?>
