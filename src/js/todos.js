/**
 * Produktivitätstool - Todos JavaScript
 * Handles todo CRUD operations, filtering, and drag-drop functionality
 */

(function() {
    'use strict';
    
    // State
    const state = {
        todos: [],
        projects: [],
        tags: [],
        filters: {
            status: '',
            priority: '',
            project: '',
            search: ''
        },
        currentView: 'list',
        editingTodo: null,
        sortable: null
    };
    
    // DOM Elements
    const elements = {
        listView: null,
        kanbanBoard: null,
        todosList: null,
        kanbanColumns: {},
        modal: null,
        form: null,
        filters: {},
        searchInput: null
    };
    
    // Initialize
    document.addEventListener('DOMContentLoaded', init);
    
    function init() {
        cacheElements();
        bindEvents();
        loadInitialData();
    }
    
    function cacheElements() {
        elements.listView = document.getElementById('listView');
        elements.kanbanBoard = document.getElementById('kanbanBoard');
        elements.todosList = document.querySelector('.todos-list');
        elements.modal = document.getElementById('todoModal');
        elements.form = document.getElementById('todoForm');
        elements.searchInput = document.getElementById('searchTodos');
        
        elements.filters = {
            status: document.getElementById('filterStatus'),
            priority: document.getElementById('filterPriority'),
            project: document.getElementById('filterProject')
        };
        
        elements.kanbanColumns = {
            todo: document.querySelector('[data-status="todo"] .kanban-cards'),
            in_progress: document.querySelector('[data-status="in_progress"] .kanban-cards'),
            done: document.querySelector('[data-status="done"] .kanban-cards')
        };
    }
    
    function bindEvents() {
        // View toggle
        document.querySelectorAll('.view-toggle .btn').forEach(btn => {
            btn.addEventListener('click', () => switchView(btn.dataset.view));
        });
        
        // Filters
        Object.entries(elements.filters).forEach(([key, el]) => {
            if (el) {
                el.addEventListener('change', () => {
                    state.filters[key] = el.value;
                    renderTodos();
                });
            }
        });
        
        // Search
        if (elements.searchInput) {
            let debounceTimer;
            elements.searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    state.filters.search = e.target.value.toLowerCase();
                    renderTodos();
                }, 300);
            });
        }
        
        // New todo button
        document.getElementById('newTodoBtn')?.addEventListener('click', () => openModal());
        
        // Modal
        elements.modal?.querySelector('#closeModal')?.addEventListener('click', closeModal);
        elements.modal?.querySelector('#cancelTodo')?.addEventListener('click', closeModal);
        elements.modal?.querySelector('.modal-backdrop')?.addEventListener('click', closeModal);
        elements.form?.addEventListener('submit', handleFormSubmit);
        
        // Add subtask button
        document.getElementById('addSubtask')?.addEventListener('click', addSubtaskInput);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboard);
    }
    
    async function loadInitialData() {
        try {
            // Load projects for filter dropdown
            if (window.todosConfig?.projects) {
                state.projects = window.todosConfig.projects;
            }
            
            // Load tags
            if (window.todosConfig?.tags) {
                state.tags = window.todosConfig.tags;
            }
            
            // Load todos
            await loadTodos();
            
            // Initialize drag-drop if SortableJS is available
            initSortable();
        } catch (error) {
            console.error('Error loading initial data:', error);
            window.NotificationManager?.error('Fehler beim Laden der Daten');
        }
    }
    
    async function loadTodos() {
        try {
            const response = await window.ApiClient.get('/api/todos/list.php');
            if (response.success) {
                state.todos = response.data || [];
                renderTodos();
                updateCounts();
            }
        } catch (error) {
            console.error('Error loading todos:', error);
            showEmptyState('Fehler beim Laden der Todos');
        }
    }
    
    function renderTodos() {
        const filteredTodos = filterTodos();
        
        if (state.currentView === 'list') {
            renderListView(filteredTodos);
        } else {
            renderKanbanView(filteredTodos);
        }
    }
    
    function filterTodos() {
        return state.todos.filter(todo => {
            if (state.filters.status && todo.status !== state.filters.status) return false;
            if (state.filters.priority && todo.priority !== state.filters.priority) return false;
            if (state.filters.project && todo.project_id != state.filters.project) return false;
            if (state.filters.search) {
                const searchText = state.filters.search.toLowerCase();
                const matchTitle = todo.title.toLowerCase().includes(searchText);
                const matchDesc = todo.description?.toLowerCase().includes(searchText);
                if (!matchTitle && !matchDesc) return false;
            }
            return true;
        });
    }
    
    function renderListView(todos) {
        if (!elements.todosList) return;
        
        if (todos.length === 0) {
            elements.todosList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">📝</div>
                    <h3>Keine Todos gefunden</h3>
                    <p>Erstelle dein erstes Todo, um loszulegen!</p>
                    <button class="btn btn-primary" onclick="document.getElementById('newTodoBtn').click()">
                        <i class="fas fa-plus"></i> Neues Todo
                    </button>
                </div>
            `;
            return;
        }
        
        elements.todosList.innerHTML = todos.map(todo => createTodoItem(todo)).join('');
        
        // Bind todo item events
        elements.todosList.querySelectorAll('.todo-item').forEach(item => {
            const id = item.dataset.id;
            
            item.querySelector('.todo-checkbox')?.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleTodo(id);
            });
            
            item.querySelector('.edit-btn')?.addEventListener('click', (e) => {
                e.stopPropagation();
                editTodo(id);
            });
            
            item.querySelector('.delete-btn')?.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteTodo(id);
            });
            
            item.addEventListener('click', () => editTodo(id));
        });
    }
    
    function createTodoItem(todo) {
        const isCompleted = todo.status === 'done';
        const dueDate = todo.due_date ? formatDate(todo.due_date) : null;
        const isOverdue = todo.due_date && new Date(todo.due_date) < new Date() && !isCompleted;
        
        return `
            <div class="todo-item ${isCompleted ? 'completed' : ''}" data-id="${todo.id}">
                <div class="todo-checkbox ${isCompleted ? 'checked' : ''}">
                    ${isCompleted ? '<i class="fas fa-check"></i>' : ''}
                </div>
                <div class="todo-content">
                    <div class="todo-header">
                        <h4 class="todo-title">${escapeHtml(todo.title)}</h4>
                        <span class="todo-priority priority-${todo.priority}">${getPriorityLabel(todo.priority)}</span>
                    </div>
                    <div class="todo-meta">
                        ${todo.project_name ? `
                            <span class="todo-meta-item">
                                <i class="fas fa-folder"></i> ${escapeHtml(todo.project_name)}
                            </span>
                        ` : ''}
                        ${dueDate ? `
                            <span class="todo-meta-item ${isOverdue ? 'text-danger' : ''}">
                                <i class="fas fa-calendar"></i> ${dueDate}
                            </span>
                        ` : ''}
                        ${todo.subtasks_count ? `
                            <span class="todo-meta-item">
                                <i class="fas fa-list-check"></i> ${todo.subtasks_completed || 0}/${todo.subtasks_count}
                            </span>
                        ` : ''}
                    </div>
                    ${todo.tags?.length ? `
                        <div class="todo-tags">
                            ${todo.tags.map(tag => `
                                <span class="todo-tag" style="background: ${tag.color}20; color: ${tag.color}">
                                    ${escapeHtml(tag.name)}
                                </span>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
                <div class="todo-actions">
                    <button class="todo-action-btn edit-btn" title="Bearbeiten">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="todo-action-btn delete-btn delete" title="Löschen">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    function renderKanbanView(todos) {
        const columns = {
            todo: todos.filter(t => t.status === 'todo'),
            in_progress: todos.filter(t => t.status === 'in_progress'),
            done: todos.filter(t => t.status === 'done')
        };
        
        Object.entries(columns).forEach(([status, items]) => {
            const container = elements.kanbanColumns[status];
            if (!container) return;
            
            if (items.length === 0) {
                container.innerHTML = '<div class="empty-column">Keine Todos</div>';
            } else {
                container.innerHTML = items.map(todo => createKanbanCard(todo)).join('');
            }
            
            // Update count
            const countEl = container.closest('.kanban-column')?.querySelector('.count');
            if (countEl) countEl.textContent = items.length;
            
            // Bind card events
            container.querySelectorAll('.kanban-card').forEach(card => {
                const id = card.dataset.id;
                card.addEventListener('click', () => editTodo(id));
            });
        });
        
        // Re-initialize sortable after render
        initSortable();
    }
    
    function createKanbanCard(todo) {
        const dueDate = todo.due_date ? formatDate(todo.due_date) : null;
        
        return `
            <div class="kanban-card" data-id="${todo.id}">
                <div class="kanban-card-title">${escapeHtml(todo.title)}</div>
                <div class="kanban-card-meta">
                    <span class="todo-priority priority-${todo.priority}">${getPriorityLabel(todo.priority)}</span>
                    ${dueDate ? `<span><i class="fas fa-calendar"></i> ${dueDate}</span>` : ''}
                </div>
            </div>
        `;
    }
    
    function initSortable() {
        if (typeof Sortable === 'undefined' || state.currentView !== 'kanban') return;
        
        Object.values(elements.kanbanColumns).forEach(container => {
            if (!container || container._sortable) return;
            
            container._sortable = new Sortable(container, {
                group: 'todos',
                animation: 150,
                ghostClass: 'dragging',
                onEnd: handleDragEnd
            });
        });
    }
    
    async function handleDragEnd(evt) {
        const todoId = evt.item.dataset.id;
        const newStatus = evt.to.closest('.kanban-column').dataset.status;
        
        try {
            const response = await window.ApiClient.put('/api/todos/update.php', {
                id: todoId,
                status: newStatus
            });
            
            if (response.success) {
                const todo = state.todos.find(t => t.id == todoId);
                if (todo) todo.status = newStatus;
                updateCounts();
                window.NotificationManager?.success('Status aktualisiert');
            }
        } catch (error) {
            console.error('Error updating todo status:', error);
            window.NotificationManager?.error('Fehler beim Aktualisieren');
            renderTodos(); // Revert
        }
    }
    
    function switchView(view) {
        state.currentView = view;
        
        document.querySelectorAll('.view-toggle .btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });
        
        if (view === 'list') {
            elements.listView?.classList.remove('hidden');
            elements.kanbanBoard?.classList.add('hidden');
        } else {
            elements.listView?.classList.add('hidden');
            elements.kanbanBoard?.classList.remove('hidden');
        }
        
        renderTodos();
    }
    
    async function toggleTodo(id) {
        const todo = state.todos.find(t => t.id == id);
        if (!todo) return;
        
        const newStatus = todo.status === 'done' ? 'todo' : 'done';
        
        try {
            const response = await window.ApiClient.put('/api/todos/update.php', {
                id: id,
                status: newStatus
            });
            
            if (response.success) {
                todo.status = newStatus;
                renderTodos();
                updateCounts();
            }
        } catch (error) {
            console.error('Error toggling todo:', error);
            window.NotificationManager?.error('Fehler beim Aktualisieren');
        }
    }
    
    function openModal(todo = null) {
        state.editingTodo = todo;
        
        const modalTitle = elements.modal?.querySelector('#modalTitle');
        if (modalTitle) {
            modalTitle.textContent = todo ? 'Todo bearbeiten' : 'Neues Todo';
        }
        
        if (elements.form) {
            elements.form.reset();
            
            if (todo) {
                elements.form.querySelector('[name="id"]').value = todo.id;
                elements.form.querySelector('[name="title"]').value = todo.title;
                elements.form.querySelector('[name="description"]').value = todo.description || '';
                elements.form.querySelector('[name="priority"]').value = todo.priority;
                elements.form.querySelector('[name="status"]').value = todo.status;
                elements.form.querySelector('[name="due_date"]').value = todo.due_date ? todo.due_date.split(' ')[0] : '';
                elements.form.querySelector('[name="project_id"]').value = todo.project_id || '';
                
                // Load subtasks
                loadSubtasks(todo.subtasks || []);
                
                // Load tags
                loadSelectedTags(todo.tags || []);
            } else {
                clearSubtasks();
                clearSelectedTags();
            }
        }
        
        elements.modal?.classList.add('show');
        elements.form?.querySelector('[name="title"]')?.focus();
    }
    
    function closeModal() {
        elements.modal?.classList.remove('show');
        state.editingTodo = null;
    }
    
    function editTodo(id) {
        const todo = state.todos.find(t => t.id == id);
        if (todo) {
            openModal(todo);
        }
    }
    
    async function deleteTodo(id) {
        if (!confirm('Möchtest du dieses Todo wirklich löschen?')) return;
        
        try {
            const response = await window.ApiClient.delete('/api/todos/delete.php', { id });
            
            if (response.success) {
                state.todos = state.todos.filter(t => t.id != id);
                renderTodos();
                updateCounts();
                window.NotificationManager?.success('Todo gelöscht');
            }
        } catch (error) {
            console.error('Error deleting todo:', error);
            window.NotificationManager?.error('Fehler beim Löschen');
        }
    }
    
    async function handleFormSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(elements.form);
        const data = {
            title: formData.get('title'),
            description: formData.get('description'),
            priority: formData.get('priority'),
            status: formData.get('status'),
            due_date: formData.get('due_date') || null,
            project_id: formData.get('project_id') || null,
            subtasks: getSubtasksFromForm(),
            tags: getSelectedTagIds()
        };
        
        const todoId = formData.get('id');
        
        try {
            let response;
            if (todoId) {
                data.id = todoId;
                response = await window.ApiClient.put('/api/todos/update.php', data);
            } else {
                response = await window.ApiClient.post('/api/todos/create.php', data);
            }
            
            if (response.success) {
                await loadTodos();
                closeModal();
                window.NotificationManager?.success(todoId ? 'Todo aktualisiert' : 'Todo erstellt');
            }
        } catch (error) {
            console.error('Error saving todo:', error);
            window.NotificationManager?.error('Fehler beim Speichern');
        }
    }
    
    // Subtasks
    function addSubtaskInput() {
        const container = document.getElementById('subtasksContainer');
        if (!container) return;
        
        const div = document.createElement('div');
        div.className = 'subtask-input';
        div.innerHTML = `
            <input type="checkbox" class="subtask-completed">
            <input type="text" class="form-control subtask-title" placeholder="Teilaufgabe eingeben...">
            <button type="button" class="remove-subtask">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        div.querySelector('.remove-subtask').addEventListener('click', () => div.remove());
        container.appendChild(div);
        div.querySelector('.subtask-title').focus();
    }
    
    function loadSubtasks(subtasks) {
        const container = document.getElementById('subtasksContainer');
        if (!container) return;
        
        container.innerHTML = '';
        subtasks.forEach(subtask => {
            const div = document.createElement('div');
            div.className = 'subtask-input';
            div.innerHTML = `
                <input type="checkbox" class="subtask-completed" ${subtask.is_completed ? 'checked' : ''}>
                <input type="text" class="form-control subtask-title" value="${escapeHtml(subtask.title)}">
                <input type="hidden" class="subtask-id" value="${subtask.id}">
                <button type="button" class="remove-subtask">
                    <i class="fas fa-times"></i>
                </button>
            `;
            div.querySelector('.remove-subtask').addEventListener('click', () => div.remove());
            container.appendChild(div);
        });
    }
    
    function clearSubtasks() {
        const container = document.getElementById('subtasksContainer');
        if (container) container.innerHTML = '';
    }
    
    function getSubtasksFromForm() {
        const container = document.getElementById('subtasksContainer');
        if (!container) return [];
        
        return Array.from(container.querySelectorAll('.subtask-input')).map(div => ({
            id: div.querySelector('.subtask-id')?.value || null,
            title: div.querySelector('.subtask-title').value,
            is_completed: div.querySelector('.subtask-completed').checked
        })).filter(s => s.title.trim());
    }
    
    // Tags
    function loadSelectedTags(tags) {
        const container = document.querySelector('.selected-tags');
        if (!container) return;
        
        container.innerHTML = tags.map(tag => `
            <span class="selected-tag" data-id="${tag.id}" style="background: ${tag.color}20; color: ${tag.color}">
                ${escapeHtml(tag.name)}
                <span class="remove-tag">&times;</span>
            </span>
        `).join('');
        
        container.querySelectorAll('.remove-tag').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.selected-tag').remove();
            });
        });
    }
    
    function clearSelectedTags() {
        const container = document.querySelector('.selected-tags');
        if (container) container.innerHTML = '';
    }
    
    function getSelectedTagIds() {
        const container = document.querySelector('.selected-tags');
        if (!container) return [];
        
        return Array.from(container.querySelectorAll('.selected-tag')).map(el => el.dataset.id);
    }
    
    function updateCounts() {
        // Update header counts if displayed
        const todoCount = state.todos.filter(t => t.status === 'todo').length;
        const progressCount = state.todos.filter(t => t.status === 'in_progress').length;
        const doneCount = state.todos.filter(t => t.status === 'done').length;
        
        // Update kanban column counts
        document.querySelector('[data-status="todo"] .count')?.textContent && 
            (document.querySelector('[data-status="todo"] .count').textContent = todoCount);
        document.querySelector('[data-status="in_progress"] .count')?.textContent && 
            (document.querySelector('[data-status="in_progress"] .count').textContent = progressCount);
        document.querySelector('[data-status="done"] .count')?.textContent && 
            (document.querySelector('[data-status="done"] .count').textContent = doneCount);
    }
    
    // Keyboard shortcuts
    function handleKeyboard(e) {
        // Escape to close modal
        if (e.key === 'Escape' && elements.modal?.classList.contains('show')) {
            closeModal();
        }
        
        // Ctrl+N for new todo
        if (e.ctrlKey && e.key === 'n' && !e.shiftKey) {
            e.preventDefault();
            openModal();
        }
    }
    
    // Utilities
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        if (date.toDateString() === today.toDateString()) {
            return 'Heute';
        } else if (date.toDateString() === tomorrow.toDateString()) {
            return 'Morgen';
        }
        
        return date.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
    
    function getPriorityLabel(priority) {
        const labels = {
            urgent: 'Dringend',
            high: 'Hoch',
            medium: 'Mittel',
            low: 'Niedrig'
        };
        return labels[priority] || priority;
    }
    
    function showEmptyState(message) {
        if (elements.todosList) {
            elements.todosList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">❌</div>
                    <h3>${message}</h3>
                </div>
            `;
        }
    }
    
    // Expose for global access
    window.TodosManager = {
        refresh: loadTodos,
        openModal,
        closeModal
    };
})();
