/**
 * Produktivitätstool - Time Tracking JavaScript
 * Handles timer functionality and time entry CRUD
 */

(function() {
    'use strict';
    
    // State
    const state = {
        activeTimer: null,
        timerInterval: null,
        entries: [],
        filters: {
            period: 'today',
            project: '',
            dateFrom: '',
            dateTo: ''
        }
    };
    
    // DOM Elements
    const elements = {
        timerDisplay: null,
        timerProject: null,
        startBtn: null,
        stopBtn: null,
        modal: null,
        form: null,
        entriesList: null
    };
    
    // Initialize
    document.addEventListener('DOMContentLoaded', init);
    
    function init() {
        cacheElements();
        bindEvents();
        
        // Load initial state from config
        if (window.timeTrackingConfig?.activeTimer) {
            state.activeTimer = window.timeTrackingConfig.activeTimer;
            startTimerDisplay();
        }
        
        // Load stats
        loadStats();
        
        // Update running durations
        updateRunningDurations();
        setInterval(updateRunningDurations, 1000);
    }
    
    function cacheElements() {
        elements.timerDisplay = document.getElementById('timerDisplay');
        elements.timerProject = document.getElementById('timerProject');
        elements.startBtn = document.getElementById('startTimerBtn');
        elements.stopBtn = document.getElementById('stopTimerBtn');
        elements.modal = document.getElementById('timeEntryModal');
        elements.form = document.getElementById('timeEntryForm');
        elements.entriesList = document.getElementById('timeEntriesList');
    }
    
    function bindEvents() {
        // Timer controls
        elements.startBtn?.addEventListener('click', startTimer);
        elements.stopBtn?.addEventListener('click', stopTimer);
        
        // Manual entry button
        document.getElementById('manualEntryBtn')?.addEventListener('click', () => openModal());
        
        // Modal events
        elements.modal?.querySelector('.modal-close')?.addEventListener('click', closeModal);
        elements.modal?.querySelector('.modal-cancel')?.addEventListener('click', closeModal);
        elements.modal?.querySelector('.modal-backdrop')?.addEventListener('click', closeModal);
        elements.form?.addEventListener('submit', handleFormSubmit);
        
        // Calculate duration on time change
        document.getElementById('entryStartTime')?.addEventListener('change', calculateDuration);
        document.getElementById('entryEndTime')?.addEventListener('change', calculateDuration);
        
        // Filters
        document.getElementById('filterPeriod')?.addEventListener('change', handlePeriodChange);
        document.getElementById('filterProject')?.addEventListener('change', () => loadEntries());
        document.getElementById('filterDateFrom')?.addEventListener('change', () => loadEntries());
        document.getElementById('filterDateTo')?.addEventListener('change', () => loadEntries());
        
        // Entry actions
        elements.entriesList?.addEventListener('click', handleEntryAction);
    }
    
    async function startTimer() {
        const projectId = document.getElementById('timerProjectSelect')?.value || null;
        const description = document.getElementById('timerDescription')?.value || '';
        
        try {
            const response = await window.ApiClient.post('/api/time/start.php', {
                project_id: projectId,
                description: description
            });
            
            if (response.success) {
                state.activeTimer = response.data;
                
                // Reload page to update UI
                window.location.reload();
            }
        } catch (error) {
            console.error('Error starting timer:', error);
            window.NotificationManager?.error('Fehler beim Starten des Timers');
        }
    }
    
    async function stopTimer() {
        if (!state.activeTimer) return;
        
        const description = document.getElementById('timerDescription')?.value || '';
        
        try {
            const response = await window.ApiClient.post('/api/time/stop.php', {
                id: state.activeTimer.id,
                description: description
            });
            
            if (response.success) {
                state.activeTimer = null;
                stopTimerDisplay();
                
                // Reload page to update UI
                window.location.reload();
            }
        } catch (error) {
            console.error('Error stopping timer:', error);
            window.NotificationManager?.error('Fehler beim Stoppen des Timers');
        }
    }
    
    function startTimerDisplay() {
        if (!state.activeTimer) return;
        
        const startTime = new Date(state.activeTimer.start_time).getTime();
        
        state.timerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            elements.timerDisplay.textContent = formatDuration(elapsed);
        }, 1000);
        
        // Initial update
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        elements.timerDisplay.textContent = formatDuration(elapsed);
    }
    
    function stopTimerDisplay() {
        if (state.timerInterval) {
            clearInterval(state.timerInterval);
            state.timerInterval = null;
        }
        elements.timerDisplay.textContent = '00:00:00';
    }
    
    function updateRunningDurations() {
        document.querySelectorAll('.running-duration').forEach(el => {
            const startTime = parseInt(el.dataset.start) * 1000;
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            el.textContent = formatDuration(elapsed);
        });
    }
    
    async function loadStats() {
        try {
            const response = await window.ApiClient.get('/api/time/stats.php');
            
            if (response.success) {
                document.getElementById('weekTotal').textContent = response.data.week || '00:00';
                document.getElementById('monthTotal').textContent = response.data.month || '00:00';
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }
    
    async function loadEntries() {
        const period = document.getElementById('filterPeriod')?.value || 'today';
        const projectId = document.getElementById('filterProject')?.value || '';
        const dateFrom = document.getElementById('filterDateFrom')?.value || '';
        const dateTo = document.getElementById('filterDateTo')?.value || '';
        
        try {
            const params = new URLSearchParams();
            params.append('period', period);
            if (projectId) params.append('project_id', projectId);
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);
            
            const response = await window.ApiClient.get(`/api/time/list.php?${params}`);
            
            if (response.success) {
                state.entries = response.data || [];
                renderEntries();
            }
        } catch (error) {
            console.error('Error loading entries:', error);
            window.NotificationManager?.error('Fehler beim Laden der Einträge');
        }
    }
    
    function renderEntries() {
        if (!elements.entriesList) return;
        
        if (state.entries.length === 0) {
            elements.entriesList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⏱️</div>
                    <h3>Keine Einträge gefunden</h3>
                    <p>Starte den Timer oder erstelle einen manuellen Eintrag.</p>
                </div>
            `;
            return;
        }
        
        // Group by date
        const grouped = groupByDate(state.entries);
        
        let html = '';
        for (const [date, entries] of Object.entries(grouped)) {
            const total = entries.reduce((sum, e) => sum + (e.duration || 0), 0);
            
            html += `
                <div class="time-entry-group">
                    <div class="time-entry-group-header">
                        <span class="time-entry-group-date">${formatDateLabel(date)}</span>
                        <span class="time-entry-group-total">${formatDuration(total)}</span>
                    </div>
                    ${entries.map(entry => createEntryHtml(entry)).join('')}
                </div>
            `;
        }
        
        elements.entriesList.innerHTML = html;
    }
    
    function createEntryHtml(entry) {
        const startTime = new Date(entry.start_time);
        const endTime = entry.end_time ? new Date(entry.end_time) : null;
        
        return `
            <div class="time-entry" data-id="${entry.id}">
                <div class="time-entry-project">
                    ${entry.project_name 
                        ? `<span class="project-badge" style="background: ${entry.project_color || 'var(--primary)'}">${escapeHtml(entry.project_name)}</span>`
                        : '<span class="project-badge no-project">Kein Projekt</span>'
                    }
                </div>
                <div class="time-entry-description">
                    ${escapeHtml(entry.description || 'Keine Beschreibung')}
                </div>
                <div class="time-entry-times">
                    <span class="start-time">${formatTime(startTime)}</span>
                    <span class="time-separator">-</span>
                    <span class="end-time">${endTime ? formatTime(endTime) : 'Läuft...'}</span>
                </div>
                <div class="time-entry-duration ${!endTime ? 'running' : ''}">
                    ${entry.duration 
                        ? formatDuration(entry.duration)
                        : `<span class="running-duration" data-start="${Math.floor(startTime.getTime() / 1000)}">--:--</span>`
                    }
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
        `;
    }
    
    function handleEntryAction(e) {
        const btn = e.target.closest('.action-btn');
        if (!btn) return;
        
        const entry = btn.closest('.time-entry');
        const id = entry?.dataset.id;
        if (!id) return;
        
        if (btn.classList.contains('edit-btn')) {
            editEntry(id);
        } else if (btn.classList.contains('delete-btn')) {
            deleteEntry(id);
        }
    }
    
    function openModal(entry = null) {
        if (!elements.form) return;
        
        elements.form.reset();
        
        const modalTitle = elements.modal?.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = entry ? 'Eintrag bearbeiten' : 'Manueller Eintrag';
        }
        
        if (entry) {
            const startTime = new Date(entry.start_time);
            const endTime = entry.end_time ? new Date(entry.end_time) : null;
            
            elements.form.querySelector('[name="id"]').value = entry.id;
            elements.form.querySelector('[name="project_id"]').value = entry.project_id || '';
            elements.form.querySelector('[name="description"]').value = entry.description || '';
            elements.form.querySelector('[name="date"]').value = startTime.toISOString().split('T')[0];
            elements.form.querySelector('[name="start_time"]').value = formatTimeInput(startTime);
            if (endTime) {
                elements.form.querySelector('[name="end_time"]').value = formatTimeInput(endTime);
            }
            
            calculateDuration();
        } else {
            elements.form.querySelector('[name="date"]').value = new Date().toISOString().split('T')[0];
            document.getElementById('calculatedDuration').textContent = '--:--';
        }
        
        elements.modal?.classList.add('show');
    }
    
    function closeModal() {
        elements.modal?.classList.remove('show');
    }
    
    async function editEntry(id) {
        try {
            const response = await window.ApiClient.get(`/api/time/get.php?id=${id}`);
            if (response.success) {
                openModal(response.data);
            }
        } catch (error) {
            console.error('Error loading entry:', error);
            window.NotificationManager?.error('Fehler beim Laden des Eintrags');
        }
    }
    
    async function deleteEntry(id) {
        if (!confirm('Möchtest du diesen Eintrag wirklich löschen?')) return;
        
        try {
            const response = await window.ApiClient.delete('/api/time/delete.php', { id });
            
            if (response.success) {
                window.NotificationManager?.success('Eintrag gelöscht');
                loadEntries();
            }
        } catch (error) {
            console.error('Error deleting entry:', error);
            window.NotificationManager?.error('Fehler beim Löschen');
        }
    }
    
    async function handleFormSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(elements.form);
        const date = formData.get('date');
        const startTime = formData.get('start_time');
        const endTime = formData.get('end_time');
        
        const data = {
            project_id: formData.get('project_id') || null,
            description: formData.get('description'),
            start_time: `${date} ${startTime}:00`,
            end_time: `${date} ${endTime}:00`
        };
        
        const entryId = formData.get('id');
        
        try {
            let response;
            if (entryId) {
                data.id = entryId;
                response = await window.ApiClient.put('/api/time/update.php', data);
            } else {
                response = await window.ApiClient.post('/api/time/create.php', data);
            }
            
            if (response.success) {
                closeModal();
                window.NotificationManager?.success(entryId ? 'Eintrag aktualisiert' : 'Eintrag erstellt');
                window.location.reload();
            }
        } catch (error) {
            console.error('Error saving entry:', error);
            window.NotificationManager?.error('Fehler beim Speichern');
        }
    }
    
    function calculateDuration() {
        const startTime = document.getElementById('entryStartTime')?.value;
        const endTime = document.getElementById('entryEndTime')?.value;
        const display = document.getElementById('calculatedDuration');
        
        if (!startTime || !endTime || !display) return;
        
        const [startHour, startMin] = startTime.split(':').map(Number);
        const [endHour, endMin] = endTime.split(':').map(Number);
        
        let duration = (endHour * 60 + endMin) - (startHour * 60 + startMin);
        
        if (duration < 0) {
            display.textContent = 'Ungültig';
            return;
        }
        
        const hours = Math.floor(duration / 60);
        const mins = duration % 60;
        display.textContent = `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
    }
    
    function handlePeriodChange(e) {
        const period = e.target.value;
        const customDates = document.getElementById('customDates');
        
        if (period === 'custom') {
            customDates?.classList.remove('hidden');
        } else {
            customDates?.classList.add('hidden');
            loadEntries();
        }
    }
    
    // Utilities
    function formatDuration(seconds) {
        const hours = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    
    function formatTime(date) {
        return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
    }
    
    function formatTimeInput(date) {
        return `${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
    }
    
    function formatDateLabel(dateStr) {
        const date = new Date(dateStr);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        if (date.toDateString() === today.toDateString()) {
            return 'Heute';
        } else if (date.toDateString() === yesterday.toDateString()) {
            return 'Gestern';
        }
        
        return date.toLocaleDateString('de-DE', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }
    
    function groupByDate(entries) {
        const grouped = {};
        entries.forEach(entry => {
            const date = entry.start_time.split(' ')[0];
            if (!grouped[date]) {
                grouped[date] = [];
            }
            grouped[date].push(entry);
        });
        return grouped;
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    // Expose for global access
    window.TimeTrackingManager = {
        startTimer,
        stopTimer,
        refresh: loadEntries
    };
})();
