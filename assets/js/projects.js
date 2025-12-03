/**
 * Projects Page JavaScript
 */
(function() {
    'use strict';

    // State
    const state = {
        projects: [],
        currentView: 'grid',
        searchTerm: '',
        statusFilter: '',
        deleteProjectId: null
    };

    // DOM Elements
    const elements = {};

    // Initialize
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        cacheElements();
        bindEvents();
        loadProjects();
    }

    function cacheElements() {
        elements.projectsGrid = document.getElementById('projectsGrid');
        elements.emptyState = document.getElementById('emptyState');
        elements.searchInput = document.getElementById('searchInput');
        elements.statusFilter = document.getElementById('statusFilter');
        elements.newProjectBtn = document.getElementById('newProjectBtn');
        elements.emptyNewProjectBtn = document.getElementById('emptyNewProjectBtn');
        elements.projectModal = document.getElementById('projectModal');
        elements.projectForm = document.getElementById('projectForm');
        elements.modalTitle = document.getElementById('modalTitle');
        elements.deleteModal = document.getElementById('deleteModal');
        elements.deleteProjectName = document.getElementById('deleteProjectName');
        elements.confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        
        // Stats
        elements.totalProjects = document.getElementById('totalProjects');
        elements.activeProjects = document.getElementById('activeProjects');
        elements.totalTodos = document.getElementById('totalTodos');
        elements.totalTime = document.getElementById('totalTime');
    }

    function bindEvents() {
        // New Project buttons
        elements.newProjectBtn?.addEventListener('click', () => openProjectModal());
        elements.emptyNewProjectBtn?.addEventListener('click', () => openProjectModal());

        // Search
        elements.searchInput?.addEventListener('input', debounce(filterProjects, 300));

        // Status filter
        elements.statusFilter?.addEventListener('change', filterProjects);

        // View toggle
        document.querySelectorAll('.view-toggle .btn-icon').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.view-toggle .btn-icon').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                state.currentView = btn.dataset.view;
                elements.projectsGrid.classList.toggle('list-view', state.currentView === 'list');
            });
        });

        // Modal close buttons
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', closeModals);
        });

        // Modal backdrop click
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.addEventListener('click', closeModals);
        });

        // Project form submit
        elements.projectForm?.addEventListener('submit', handleProjectSubmit);

        // Color presets
        document.querySelectorAll('.color-preset').forEach(preset => {
            preset.addEventListener('click', () => {
                const color = preset.dataset.color;
                document.getElementById('projectColor').value = color;
                document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
                preset.classList.add('active');
            });
        });

        // Delete confirmation
        elements.confirmDeleteBtn?.addEventListener('click', handleDeleteProject);

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModals();
            }
        });
    }

    async function loadProjects() {
        try {
            const response = await window.ApiClient.get('/api/projects/list.php');
            
            if (response.success) {
                state.projects = response.data;
                renderProjects();
                updateStats();
            }
        } catch (error) {
            console.error('Error loading projects:', error);
            window.NotificationManager?.error('Fehler beim Laden der Projekte');
        }
    }

    function renderProjects() {
        const filtered = getFilteredProjects();
        
        if (filtered.length === 0) {
            elements.projectsGrid.innerHTML = '';
            elements.emptyState.classList.remove('hidden');
            return;
        }

        elements.emptyState.classList.add('hidden');
        elements.projectsGrid.innerHTML = filtered.map(project => createProjectCard(project)).join('');

        // Bind card events
        elements.projectsGrid.querySelectorAll('.project-card').forEach(card => {
            const projectId = parseInt(card.dataset.id);
            
            card.querySelector('.edit-btn')?.addEventListener('click', (e) => {
                e.stopPropagation();
                const project = state.projects.find(p => p.id == projectId);
                if (project) openProjectModal(project);
            });

            card.querySelector('.delete-btn')?.addEventListener('click', (e) => {
                e.stopPropagation();
                const project = state.projects.find(p => p.id == projectId);
                if (project) openDeleteModal(project);
            });
        });
    }

    function createProjectCard(project) {
        const deadline = project.deadline ? new Date(project.deadline) : null;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        let deadlineClass = '';
        if (deadline) {
            const diffDays = Math.ceil((deadline - today) / (1000 * 60 * 60 * 24));
            if (diffDays < 0) deadlineClass = 'overdue';
            else if (diffDays <= 7) deadlineClass = 'soon';
        }

        const statusLabels = {
            'active': 'Aktiv',
            'paused': 'Pausiert',
            'completed': 'Abgeschlossen',
            'archived': 'Archiviert'
        };

        return `
            <div class="project-card" data-id="${project.id}">
                <div class="project-card-header">
                    <div class="project-color-indicator" style="background-color: ${project.color || '#3498db'}"></div>
                    <div class="project-card-info">
                        <h3 class="project-card-title">
                            ${escapeHtml(project.name)}
                            <span class="status-badge ${project.status || 'active'}">${statusLabels[project.status] || 'Aktiv'}</span>
                        </h3>
                        ${project.description ? `<p class="project-card-description">${escapeHtml(project.description)}</p>` : ''}
                    </div>
                    <div class="project-card-actions">
                        <button class="btn btn-icon edit-btn" title="Bearbeiten">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-icon delete-btn" title="Löschen">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="project-card-body">
                    <div class="project-stats">
                        <div class="project-stat">
                            <span class="project-stat-value">${project.todo_count || 0}</span>
                            <span class="project-stat-label">Todos</span>
                        </div>
                        <div class="project-stat">
                            <span class="project-stat-value">${project.completed_count || 0}</span>
                            <span class="project-stat-label">Erledigt</span>
                        </div>
                        <div class="project-stat">
                            <span class="project-stat-value">${formatDuration(project.time_seconds || 0)}</span>
                            <span class="project-stat-label">Zeit</span>
                        </div>
                    </div>
                </div>
                <div class="project-card-footer">
                    ${deadline ? `
                        <span class="project-deadline ${deadlineClass}">
                            <i class="fas fa-calendar"></i>
                            ${formatDate(deadline)}
                        </span>
                    ` : '<span></span>'}
                    <span class="project-updated">
                        <i class="fas fa-clock"></i>
                        ${formatRelativeTime(project.updated_at)}
                    </span>
                </div>
            </div>
        `;
    }

    function getFilteredProjects() {
        return state.projects.filter(project => {
            // Search filter
            if (state.searchTerm) {
                const search = state.searchTerm.toLowerCase();
                if (!project.name.toLowerCase().includes(search) && 
                    !(project.description || '').toLowerCase().includes(search)) {
                    return false;
                }
            }

            // Status filter
            if (state.statusFilter) {
                if ((project.status || 'active') !== state.statusFilter) {
                    return false;
                }
            }

            return true;
        });
    }

    function filterProjects() {
        state.searchTerm = elements.searchInput?.value || '';
        state.statusFilter = elements.statusFilter?.value || '';
        renderProjects();
    }

    function updateStats() {
        const total = state.projects.length;
        const active = state.projects.filter(p => p.status === 'active' || !p.status).length;
        const todos = state.projects.reduce((sum, p) => sum + (parseInt(p.todo_count) || 0), 0);
        const timeSeconds = state.projects.reduce((sum, p) => sum + (parseInt(p.time_seconds) || 0), 0);

        elements.totalProjects.textContent = total;
        elements.activeProjects.textContent = active;
        elements.totalTodos.textContent = todos;
        elements.totalTime.textContent = formatDuration(timeSeconds);
    }

    function openProjectModal(project = null) {
        elements.modalTitle.textContent = project ? 'Projekt bearbeiten' : 'Neues Projekt';
        elements.projectForm.reset();

        if (project) {
            document.getElementById('projectId').value = project.id;
            document.getElementById('projectName').value = project.name;
            document.getElementById('projectDescription').value = project.description || '';
            document.getElementById('projectColor').value = project.color || '#3498db';
            document.getElementById('projectStatus').value = project.status || 'active';
            document.getElementById('projectDeadline').value = project.deadline || '';
        } else {
            document.getElementById('projectId').value = '';
            document.getElementById('projectColor').value = '#3498db';
        }

        // Update color preset selection
        const currentColor = document.getElementById('projectColor').value;
        document.querySelectorAll('.color-preset').forEach(preset => {
            preset.classList.toggle('active', preset.dataset.color === currentColor);
        });

        elements.projectModal.classList.add('active');
        document.getElementById('projectName').focus();
    }

    function openDeleteModal(project) {
        state.deleteProjectId = project.id;
        elements.deleteProjectName.textContent = project.name;
        elements.deleteModal.classList.add('active');
    }

    function closeModals() {
        elements.projectModal.classList.remove('active');
        elements.deleteModal.classList.remove('active');
        state.deleteProjectId = null;
    }

    async function handleProjectSubmit(e) {
        e.preventDefault();

        const formData = new FormData(elements.projectForm);
        const data = {
            name: formData.get('name'),
            description: formData.get('description'),
            color: formData.get('color'),
            status: formData.get('status'),
            deadline: formData.get('deadline') || null
        };

        const projectId = formData.get('id');
        const isEdit = !!projectId;

        if (isEdit) {
            data.id = parseInt(projectId);
        }

        try {
            const endpoint = isEdit ? '/api/projects/update.php' : '/api/projects/create.php';
            const method = isEdit ? 'put' : 'post';
            
            const response = await window.ApiClient[method](endpoint, data);

            if (response.success) {
                window.NotificationManager?.success(response.message || (isEdit ? 'Projekt aktualisiert' : 'Projekt erstellt'));
                closeModals();
                loadProjects();
            }
        } catch (error) {
            console.error('Error saving project:', error);
            window.NotificationManager?.error('Fehler beim Speichern des Projekts');
        }
    }

    async function handleDeleteProject() {
        if (!state.deleteProjectId) return;

        try {
            const response = await window.ApiClient.delete('/api/projects/delete.php', {
                id: state.deleteProjectId
            });

            if (response.success) {
                window.NotificationManager?.success('Projekt gelöscht');
                closeModals();
                loadProjects();
            }
        } catch (error) {
            console.error('Error deleting project:', error);
            window.NotificationManager?.error('Fehler beim Löschen des Projekts');
        }
    }

    // Utility functions
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(date) {
        if (!(date instanceof Date)) date = new Date(date);
        return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function formatRelativeTime(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'gerade eben';
        if (diffMins < 60) return `vor ${diffMins} Min.`;
        if (diffHours < 24) return `vor ${diffHours} Std.`;
        if (diffDays < 7) return `vor ${diffDays} Tag${diffDays > 1 ? 'en' : ''}`;
        return formatDate(date);
    }

    function formatDuration(seconds) {
        if (!seconds) return '0h';
        const hours = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        if (hours === 0) return `${mins}m`;
        if (mins === 0) return `${hours}h`;
        return `${hours}h ${mins}m`;
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
})();
