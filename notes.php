<?php
/**
 * Produktivitätstool - Notizen
 * Rich-text notes with Quill.js editor
 */

require_once 'includes/init.php';

// Require authentication
requireAuth();

$pageTitle = 'Notizen';
$pageStyles = ['css/notes.css'];
$pageScripts = ['js/notes.js'];

// Get current user data
$user = getCurrentUser();
$userId = getCurrentUserId();

// Get projects for filter
$projects = dbFetchAll(
    "SELECT id, name, color FROM projects WHERE user_id = ? ORDER BY name",
    [$userId]
);

// Get notes
$notes = dbFetchAll(
    "SELECT n.*, p.name as project_name, p.color as project_color, nf.name as folder_name
     FROM notes n
     LEFT JOIN projects p ON n.project_id = p.id
     LEFT JOIN note_folders nf ON n.folder_id = nf.id
     WHERE n.user_id = ?
     ORDER BY n.is_pinned DESC, n.updated_at DESC",
    [$userId]
);

// Start layout
ob_start();
?>

<div class="notes-layout">
    <!-- Sidebar: Notes List -->
    <aside class="notes-sidebar">
        <div class="notes-sidebar-header">
            <button class="btn btn-primary btn-sm" id="newNoteBtn">
                <i class="fas fa-plus"></i> Neue Notiz
            </button>
            <div class="notes-search">
                <i class="fas fa-search"></i>
                <input type="text" id="searchNotes" placeholder="Suchen...">
            </div>
        </div>
        
        <div class="notes-filters">
            <select class="form-select form-select-sm" id="filterProject">
                <option value="">Alle Projekte</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?php echo $project['id']; ?>">
                        <?php echo htmlspecialchars($project['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm" id="filterFolder">
                <option value="">Alle Ordner</option>
            </select>
        </div>
        
        <!-- Folders Section -->
        <div class="notes-folders" id="noteFolders">
            <div class="folders-header">
                <span>Ordner</span>
                <button class="btn btn-ghost btn-xs" id="addFolderBtn" title="Neuer Ordner">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="folders-list" id="foldersList">
                <div class="folder-item active" data-folder="">
                    <i class="fas fa-folder"></i>
                    <span>Alle Notizen</span>
                </div>
            </div>
        </div>
        
        <div class="notes-list" id="notesList">
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <p>Keine Notizen vorhanden</p>
                </div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-item <?php echo $note['is_pinned'] ? 'pinned' : ''; ?>" 
                         data-id="<?php echo $note['id']; ?>"
                         data-project-id="<?php echo $note['project_id'] ?? ''; ?>"
                         data-folder-id="<?php echo $note['folder_id'] ?? ''; ?>">
                        <?php if ($note['is_pinned']): ?>
                            <i class="fas fa-thumbtack pin-icon"></i>
                        <?php endif; ?>
                        <h4 class="note-title"><?php echo htmlspecialchars($note['title']); ?></h4>
                        <p class="note-preview"><?php echo htmlspecialchars(strip_tags(substr($note['content'], 0, 100))); ?></p>
                        <div class="note-meta">
                            <span class="note-date"><?php echo formatRelativeTime($note['updated_at']); ?></span>
                            <?php if ($note['project_name']): ?>
                                <span class="note-project" style="background: <?php echo $note['project_color'] ?? 'var(--primary)'; ?>">
                                    <?php echo htmlspecialchars($note['project_name']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($note['folder_name']): ?>
                                <span class="note-folder">
                                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($note['folder_name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>
    
    <!-- Main: Note Editor -->
    <main class="notes-main">
        <div class="note-editor-container" id="noteEditorContainer">
            <!-- Empty state -->
            <div class="note-empty-state" id="noteEmptyState">
                <div class="empty-icon">📝</div>
                <h2>Keine Notiz ausgewählt</h2>
                <p>Wähle eine Notiz aus der Liste oder erstelle eine neue.</p>
                <button class="btn btn-primary" onclick="document.getElementById('newNoteBtn').click()">
                    <i class="fas fa-plus"></i> Neue Notiz erstellen
                </button>
            </div>
            
            <!-- Editor -->
            <div class="note-editor hidden" id="noteEditor">
                <div class="note-editor-header">
                    <input type="text" class="note-title-input" id="noteTitle" placeholder="Titel eingeben...">
                    <div class="note-actions">
                        <button class="btn btn-ghost" id="shareNoteBtn" title="Teilen">
                            <i class="fas fa-share-alt"></i>
                        </button>
                        <button class="btn btn-ghost" id="pinNoteBtn" title="Anheften">
                            <i class="fas fa-thumbtack"></i>
                        </button>
                        <button class="btn btn-ghost" id="deleteNoteBtn" title="Löschen">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="note-editor-meta">
                    <select class="form-select form-select-sm" id="noteProject">
                        <option value="">Kein Projekt</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-select form-select-sm" id="noteFolder">
                        <option value="">Kein Ordner</option>
                    </select>
                    <select class="form-select form-select-sm" id="noteContentType">
                        <option value="html">Rich Text</option>
                        <option value="markdown">Markdown</option>
                    </select>
                    <span class="note-updated" id="noteUpdated"></span>
                </div>
                
                <!-- Share Info -->
                <div class="note-share-info hidden" id="noteShareInfo">
                    <i class="fas fa-link"></i>
                    <input type="text" id="shareUrl" readonly>
                    <button class="btn btn-xs btn-secondary" id="copyShareUrl">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button class="btn btn-xs btn-danger" id="disableShare">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Quill Editor -->
                <div id="quillEditor"></div>
                
                <input type="hidden" id="noteId">
            </div>
        </div>
    </main>
</div>

<!-- Load Quill.js, Highlight.js and Marked.js -->
<link href="<?php echo url('node_modules/quill/dist/quill.snow.css'); ?>" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<!-- Load common programming languages for highlighting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/bash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/json.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/typescript.min.js"></script>
<script src="<?php echo url('node_modules/quill/dist/quill.js'); ?>"></script>
<!-- Marked.js for Markdown parsing -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<script>
    window.notesConfig = {
        projects: <?php echo json_encode($projects); ?>
    };
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
