/**
 * Produktivitätstool - Time Tracking JavaScript
 * Handles timer functionality, time entry CRUD, reports and export
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
        },
        charts: {
            daily: null,
            project: null
        }
    };
    
    // DOM Elements
    const elements = {
        timerDisplay: null,
        timerProject: null,
        startBtn: null,
        stopBtn: null,
        breakBtn: null,
        modal: null,
        form: null,
        entriesList: null,
        reportsModal: null,
        exportModal: null
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
        
        // Load entries
        loadEntries();
        
        // Update running durations
        updateRunningDurations();
        setInterval(updateRunningDurations, 1000);
    }
    
    function cacheElements() {
        elements.timerDisplay = document.getElementById('timerDisplay');
        elements.timerProject = document.getElementById('timerProject');
        elements.startBtn = document.getElementById('startTimerBtn');
        elements.stopBtn = document.getElementById('stopTimerBtn');
        elements.breakBtn = document.getElementById('startBreakBtn');
        elements.modal = document.getElementById('timeEntryModal');
        elements.form = document.getElementById('timeEntryForm');
        elements.entriesList = document.getElementById('timeEntriesList');
        elements.reportsModal = document.getElementById('reportsModal');
        elements.exportModal = document.getElementById('exportModal');
    }
    
    function bindEvents() {
        // Timer controls
        elements.startBtn?.addEventListener('click', () => startTimer(false));
        elements.stopBtn?.addEventListener('click', stopTimer);
        elements.breakBtn?.addEventListener('click', () => startTimer(true));
        
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
        
        // Reports modal
        document.getElementById('reportsBtn')?.addEventListener('click', openReportsModal);
        elements.reportsModal?.querySelector('.modal-close')?.addEventListener('click', closeReportsModal);
        elements.reportsModal?.querySelector('.modal-backdrop')?.addEventListener('click', closeReportsModal);
        document.getElementById('reportPeriod')?.addEventListener('change', loadReportsData);
        document.getElementById('reportProject')?.addEventListener('change', loadReportsData);
        
        // Export modal
        document.getElementById('exportBtn')?.addEventListener('click', openExportModal);
        elements.exportModal?.querySelector('.modal-close')?.addEventListener('click', closeExportModal);
        elements.exportModal?.querySelector('.modal-cancel')?.addEventListener('click', closeExportModal);
        elements.exportModal?.querySelector('.modal-backdrop')?.addEventListener('click', closeExportModal);
        document.getElementById('exportPeriod')?.addEventListener('change', handleExportPeriodChange);
        document.getElementById('doExportBtn')?.addEventListener('click', doExport);
    }
    
    async function startTimer(isBreak = false) {
        const projectId = document.getElementById('timerProjectSelect')?.value || null;
        const description = document.getElementById('timerDescription')?.value || '';
        
        try {
            const response = await window.ApiClient.post('/api/time/start.php', {
                project_id: isBreak ? null : projectId,
                description: isBreak ? 'Pause' : description,
                is_break: isBreak
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
                document.getElementById('todayTotal').textContent = response.data.today || '00:00';
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
            console.log(response);
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
            const total = entries.reduce((sum, e) => sum + (e.duration_seconds || 0), 0);
            
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
        const isBreak = entry.is_break === 1 || entry.is_break === true;
        
        let projectBadge;
        if (isBreak) {
            projectBadge = `<span class="project-badge break-badge">
                <i class="fas fa-coffee"></i> Pause
            </span>`;
        } else if (entry.project_name) {
            projectBadge = `<span class="project-badge" style="background: ${entry.project_color || 'var(--primary)'}">${escapeHtml(entry.project_name)}</span>`;
        } else {
            projectBadge = '<span class="project-badge no-project">Kein Projekt</span>';
        }
        
        return `
            <div class="time-entry ${isBreak ? 'is-break' : ''}" data-id="${entry.id}" style="margin-bottom: 10px;">
                <div class="time-entry-project">
                    ${projectBadge}
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
                    ${entry.duration_seconds
                        ? formatDuration(entry.duration_seconds)
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
        
        elements.modal?.classList.add('active');
    }
    
    function closeModal() {
        elements.modal?.classList.remove('active');
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
        let startTime = formData.get('start_time');
        let endTime = formData.get('end_time');
        
        // Wenn keine Sekunden angegeben, füge :00 hinzu
        if (startTime.split(':').length === 2) startTime += ':00';
        if (endTime.split(':').length === 2) endTime += ':00';
        
        const data = {
            project_id: formData.get('project_id') || null,
            description: formData.get('description'),
            start_time: `${date} ${startTime}`,
            end_time: `${date} ${endTime}`
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
        
        const startParts = startTime.split(':').map(Number);
        const endParts = endTime.split(':').map(Number);
        
        const startSec = (startParts[0] || 0) * 3600 + (startParts[1] || 0) * 60 + (startParts[2] || 0);
        const endSec = (endParts[0] || 0) * 3600 + (endParts[1] || 0) * 60 + (endParts[2] || 0);
        
        let duration = endSec - startSec;
        
        if (duration < 0) {
            display.textContent = 'Ungültig';
            return;
        }
        
        const hours = Math.floor(duration / 3600);
        const mins = Math.floor((duration % 3600) / 60);
        const secs = duration % 60;
        display.textContent = `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
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
        return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    
    function formatTimeInput(date) {
        return `${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}:${date.getSeconds().toString().padStart(2, '0')}`;
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
    
    // =====================================================
    // Reports Functions
    // =====================================================
    
    function openReportsModal() {
        elements.reportsModal?.classList.add('active');
        loadReportsData();
    }
    
    function closeReportsModal() {
        elements.reportsModal?.classList.remove('active');
    }
    
    async function loadReportsData() {
        const period = document.getElementById('reportPeriod')?.value || 'week';
        const projectId = document.getElementById('reportProject')?.value || '';
        
        try {
            const params = new URLSearchParams();
            params.append('period', period);
            if (projectId) params.append('project_id', projectId);
            
            const response = await window.ApiClient.get(`/api/time/reports.php?${params}`);
            
            if (response.success) {
                renderReportsSummary(response.data);
                renderDailyChart(response.data.daily);
                renderProjectChart(response.data.by_project);
            }
        } catch (error) {
            console.error('Error loading reports:', error);
            window.NotificationManager?.error('Fehler beim Laden der Berichte');
        }
    }
    
    function renderReportsSummary(data) {
        const container = document.getElementById('reportsSummary');
        if (!container) return;
        
        const summary = data.summary;
        container.innerHTML = `
            <div class="reports-summary-grid">
                <div class="summary-card">
                    <div class="summary-value">${summary.total_long}</div>
                    <div class="summary-label">Gesamtzeit</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">${summary.work_formatted}</div>
                    <div class="summary-label">Arbeitszeit</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">${summary.break_formatted}</div>
                    <div class="summary-label">Pausenzeit</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">${summary.days_worked}</div>
                    <div class="summary-label">Arbeitstage</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">${summary.avg_per_day}</div>
                    <div class="summary-label">Durchschnitt/Tag</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">${summary.total_entries}</div>
                    <div class="summary-label">Einträge</div>
                </div>
            </div>
        `;
    }
    
    function renderDailyChart(dailyData) {
        const canvas = document.getElementById('dailyChart');
        if (!canvas || typeof Chart === 'undefined') return;
        
        // Destroy existing chart
        if (state.charts.daily) {
            state.charts.daily.destroy();
        }
        
        const labels = dailyData.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('de-DE', { weekday: 'short', day: 'numeric', month: 'short' });
        });
        
        state.charts.daily = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Arbeitszeit (Std)',
                        data: dailyData.map(d => (d.work_seconds / 3600).toFixed(2)),
                        backgroundColor: 'rgba(52, 152, 219, 0.8)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Pause (Std)',
                        data: dailyData.map(d => (d.break_seconds / 3600).toFixed(2)),
                        backgroundColor: 'rgba(241, 196, 15, 0.8)',
                        borderColor: 'rgba(241, 196, 15, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Stunden'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }
    
    function renderProjectChart(projectData) {
        const canvas = document.getElementById('projectChart');
        if (!canvas || typeof Chart === 'undefined') return;
        
        // Destroy existing chart
        if (state.charts.project) {
            state.charts.project.destroy();
        }
        
        if (projectData.length === 0) {
            state.charts.project = null;
            return;
        }
        
        state.charts.project = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: projectData.map(p => p.project_name),
                datasets: [{
                    data: projectData.map(p => (p.total_seconds / 3600).toFixed(2)),
                    backgroundColor: projectData.map(p => p.project_color || '#6c757d'),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                return `${context.label}: ${value} Std`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // =====================================================
    // Export Functions
    // =====================================================
    
    function openExportModal() {
        elements.exportModal?.classList.add('active');
    }
    
    function closeExportModal() {
        elements.exportModal?.classList.remove('active');
    }
    
    function handleExportPeriodChange(e) {
        const period = e.target.value;
        const customDates = document.getElementById('exportCustomDates');
        
        if (period === 'custom') {
            customDates?.classList.remove('hidden');
        } else {
            customDates?.classList.add('hidden');
        }
    }
    
    async function doExport() {
        const form = document.getElementById('exportForm');
        if (!form) return;
        
        const formData = new FormData(form);
        const format = formData.get('format');
        const period = formData.get('period');
        const projectId = formData.get('project_id');
        const dateFrom = formData.get('date_from');
        const dateTo = formData.get('date_to');
        
        const params = new URLSearchParams();
        params.append('format', format);
        params.append('period', period);
        if (projectId) params.append('project_id', projectId);
        if (period === 'custom') {
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);
        }
        
        if (format === 'csv') {
            // Direct download for CSV
            window.location.href = `/productivity/api/time/export.php?${params}`;
            closeExportModal();
        } else if (format === 'pdf') {
            // For PDF, we get the data and generate client-side
            try {
                const response = await window.ApiClient.get(`/api/time/export.php?${params}`);
                
                if (response.success) {
                    generatePDF(response.data);
                    closeExportModal();
                }
            } catch (error) {
                console.error('Error exporting:', error);
                window.NotificationManager?.error('Fehler beim Export');
            }
        }
    }
    
    function generatePDF(data) {
        // Simple PDF generation using print
        const printWindow = window.open('', '_blank');
        
        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${data.title}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h1 { color: #333; margin-bottom: 5px; }
                    .subtitle { color: #666; margin-bottom: 20px; }
                    .meta { color: #999; font-size: 12px; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background: #f5f5f5; }
                    .summary { display: flex; gap: 20px; margin-bottom: 20px; }
                    .summary-item { background: #f5f5f5; padding: 10px 15px; border-radius: 5px; }
                    .summary-value { font-size: 24px; font-weight: bold; color: #333; }
                    .summary-label { font-size: 12px; color: #666; }
                    @media print {
                        body { padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <h1>${data.title}</h1>
                <p class="subtitle">${data.subtitle}</p>
                <p class="meta">Erstellt von: ${data.user} | ${data.generated}</p>
                
                <div class="summary">
                    <div class="summary-item">
                        <div class="summary-value">${data.summary.total_duration}</div>
                        <div class="summary-label">Gesamtzeit</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">${data.summary.total_entries}</div>
                        <div class="summary-label">Einträge</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">${data.summary.days_worked}</div>
                        <div class="summary-label">Tage</div>
                    </div>
                </div>
                
                <h2>Nach Projekt</h2>
                <table>
                    <tr><th>Projekt</th><th>Dauer</th><th>Stunden</th></tr>
                    ${data.by_project.map(p => `
                        <tr><td>${p.name}</td><td>${p.duration}</td><td>${p.hours}</td></tr>
                    `).join('')}
                </table>
                
                <h2>Nach Tag</h2>
                <table>
                    <tr><th>Datum</th><th>Einträge</th><th>Dauer</th></tr>
                    ${data.by_date.map(d => `
                        <tr><td>${d.date}</td><td>${d.entries}</td><td>${d.duration}</td></tr>
                    `).join('')}
                </table>
                
                <button class="no-print" onclick="window.print()">Drucken / Als PDF speichern</button>
            </body>
            </html>
        `;
        
        printWindow.document.write(html);
        printWindow.document.close();
    }
    
    // Expose for global access
    window.TimeTrackingManager = {
        startTimer,
        stopTimer,
        refresh: loadEntries,
        openReports: openReportsModal,
        openExport: openExportModal
    };
})();
