<?php
/**
 * Produktivitätstool - Todo Management
 */

require_once __DIR__ . '/includes/init.php';
requireAuth();

$pageTitle = 'Todos';
$pageId = 'todos';
$extraCss = ['css/todos.css'];
$extraJs = ['js/sortable.min.js', 'js/todos.js'];

// Projekte für Dropdown laden
$projects = dbFetchAll(
    "SELECT id, name, color FROM projects WHERE user_id = ? ORDER BY name",
    [currentUserId()]
);

// Tags für Dropdown laden
$tags = dbFetchAll(
    "SELECT id, name, color FROM tags WHERE user_id = ? ORDER BY name",
    [currentUserId()]
);

ob_start();
?>

<div class="todos-container">
    <!-- Toolbar -->
    <div class="todos-toolbar">
        <div class="toolbar-left">
            <div class="view-toggle">
                <button class="btn btn-secondary active" data-view="list" title="Listenansicht">
                    <i class="fas fa-list"></i>
                </button>
                <button class="btn btn-secondary" data-view="kanban" title="Kanban-Board">
                    <i class="fas fa-columns"></i>
                </button>
            </div>
            
            <div class="filter-group">
                <select id="filterStatus" class="form-select">
                    <option value="">Alle Status</option>
                    <option value="todo">Offen</option>
                    <option value="in_progress">In Bearbeitung</option>
                    <option value="done">Erledigt</option>
                </select>
                
                <select id="filterPriority" class="form-select">
                    <option value="">Alle Prioritäten</option>
                    <option value="urgent">Dringend</option>
                    <option value="high">Hoch</option>
                    <option value="medium">Mittel</option>
                    <option value="low">Niedrig</option>
                </select>
                
                <select id="filterProject" class="form-select">
                    <option value="">Alle Projekte</option>
                    <?php foreach ($projects as $project): ?>
                    <option value="<?php echo $project['id']; ?>"><?php echo e($project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="toolbar-right">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchTodos" placeholder="Todos suchen...">
            </div>
            
            <button class="btn btn-primary" id="newTodoBtn">
                <i class="fas fa-plus"></i> Neues Todo
            </button>
        </div>
    </div>

    <!-- List View -->
    <div class="todos-list-view" id="listView">
        <div class="todos-list" id="todosList">
            <!-- Todos werden per JavaScript geladen -->
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Todos werden geladen...</p>
            </div>
        </div>
    </div>

    <!-- Kanban View -->
    <div class="todos-kanban-view hidden" id="kanbanBoard">
        <div class="kanban-board">
            <div class="kanban-column" data-status="todo">
                <div class="kanban-column-header">
                    <h3><i class="fas fa-circle text-primary"></i> Offen</h3>
                    <span class="count">0</span>
                </div>
                <div class="kanban-column-content kanban-cards"></div>
            </div>
            
            <div class="kanban-column" data-status="in_progress">
                <div class="kanban-column-header">
                    <h3><i class="fas fa-circle text-warning"></i> In Bearbeitung</h3>
                    <span class="count">0</span>
                </div>
                <div class="kanban-column-content kanban-cards"></div>
            </div>
            
            <div class="kanban-column" data-status="done">
                <div class="kanban-column-header">
                    <h3><i class="fas fa-circle text-success"></i> Erledigt</h3>
                    <span class="count">0</span>
                </div>
                <div class="kanban-column-content kanban-cards"></div>
            </div>
        </div>
    </div>
</div>

<!-- Todo Modal -->
<div class="modal" id="todoModal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Neues Todo</h3>
            <button class="modal-close" id="closeModal">&times;</button>
        </div>
        <form id="todoForm">
            <div class="modal-body">
                <input type="hidden" id="todoId" name="id">
                
                <div class="form-group">
                    <label for="todoTitle">Titel *</label>
                    <input type="text" id="todoTitle" name="title" required placeholder="Was möchtest du erledigen?">
                </div>
                
                <div class="form-group">
                    <label for="todoDescription">Beschreibung</label>
                    <textarea id="todoDescription" name="description" rows="3" placeholder="Weitere Details..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="todoPriority">Priorität</label>
                        <select id="todoPriority" name="priority">
                            <option value="low">Niedrig</option>
                            <option value="medium" selected>Mittel</option>
                            <option value="high">Hoch</option>
                            <option value="urgent">Dringend</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="todoStatus">Status</label>
                        <select id="todoStatus" name="status">
                            <option value="todo">Offen</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="done">Erledigt</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="todoDueDate">Fälligkeitsdatum</label>
                        <input type="date" id="todoDueDate" name="due_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="todoDueTime">Uhrzeit</label>
                        <input type="time" id="todoDueTime" name="due_time">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="todoProject">Projekt</label>
                    <select id="todoProject" name="project_id">
                        <option value="">Kein Projekt</option>
                        <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>"><?php echo e($project['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tags</label>
                    <div class="tags-input" id="tagsInput">
                        <div class="selected-tags" id="selectedTags"></div>
                        <input type="text" id="tagSearch" placeholder="Tags hinzufügen...">
                        <div class="tags-dropdown d-none" id="tagsDropdown">
                            <?php foreach ($tags as $tag): ?>
                            <div class="tag-option" data-id="<?php echo $tag['id']; ?>" data-color="<?php echo e($tag['color']); ?>">
                                <span class="tag-color" style="background: <?php echo e($tag['color']); ?>"></span>
                                <?php echo e($tag['name']); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <input type="hidden" id="todoTags" name="tags">
                </div>
                
                <!-- Subtasks -->
                <div class="form-group">
                    <label>Unteraufgaben</label>
                    <div class="subtasks-container" id="subtasksContainer">
                        <!-- Subtasks werden hier eingefügt -->
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" id="addSubtask">
                        <i class="fas fa-plus"></i> Unteraufgabe hinzufügen
                    </button>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelTodo">Abbrechen</button>
                <button type="submit" class="btn btn-primary" id="saveTodo">Speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- Inline Data for JavaScript -->
<script>
    window.todosConfig = {
        basePath: '<?php echo BASE_PATH; ?>',
        projects: <?php echo json_encode($projects); ?>,
        tags: <?php echo json_encode($tags); ?>
    };
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
?>