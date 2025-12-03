/**
 * Produktivitätstool - Notes JavaScript
 * Rich-text notes with Quill.js editor
 */

(function() {
    'use strict';
    
    // State
    const state = {
        notes: [],
        currentNote: null,
        quill: null,
        saveTimeout: null,
        filters: {
            project: '',
            search: ''
        }
    };
    
    // DOM Elements
    const elements = {
        notesList: null,
        noteEditor: null,
        noteEmptyState: null,
        noteTitle: null,
        noteProject: null,
        noteUpdated: null,
        noteId: null,
        pinBtn: null,
        deleteBtn: null
    };
    
    // Initialize
    document.addEventListener('DOMContentLoaded', init);
    
    function init() {
        cacheElements();
        initQuill();
        bindEvents();
    }
    
    function cacheElements() {
        elements.notesList = document.getElementById('notesList');
        elements.noteEditor = document.getElementById('noteEditor');
        elements.noteEmptyState = document.getElementById('noteEmptyState');
        elements.noteTitle = document.getElementById('noteTitle');
        elements.noteProject = document.getElementById('noteProject');
        elements.noteUpdated = document.getElementById('noteUpdated');
        elements.noteId = document.getElementById('noteId');
        elements.pinBtn = document.getElementById('pinNoteBtn');
        elements.deleteBtn = document.getElementById('deleteNoteBtn');
    }
    
    function initQuill() {
        if (typeof Quill === 'undefined') {
            console.error('Quill.js not loaded');
            return;
        }
        
        // Configure syntax highlighting if available
        if (typeof hljs !== 'undefined') {
            Quill.register('modules/syntax', true);
        }
        
        state.quill = new Quill('#quillEditor', {
            theme: 'snow',
            placeholder: 'Beginne hier zu schreiben...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'indent': '-1' }, { 'indent': '+1' }],
                    ['link', 'image'],
                    ['clean']
                ],
                syntax: typeof hljs !== 'undefined' ? { hljs } : false
            }
        });
        
        // Auto-save on content change
        state.quill.on('text-change', () => {
            if (state.currentNote) {
                scheduleAutoSave();
            }
        });
    }
    
    function bindEvents() {
        // New note button
        document.getElementById('newNoteBtn')?.addEventListener('click', createNewNote);
        
        // Note list items
        elements.notesList?.addEventListener('click', (e) => {
            const noteItem = e.target.closest('.note-item');
            if (noteItem) {
                loadNote(noteItem.dataset.id);
            }
        });
        
        // Title change
        elements.noteTitle?.addEventListener('input', scheduleAutoSave);
        
        // Project change
        elements.noteProject?.addEventListener('change', scheduleAutoSave);
        
        // Pin button
        elements.pinBtn?.addEventListener('click', togglePin);
        
        // Delete button
        elements.deleteBtn?.addEventListener('click', deleteNote);
        
        // Search
        document.getElementById('searchNotes')?.addEventListener('input', (e) => {
            state.filters.search = e.target.value.toLowerCase();
            filterNotes();
        });
        
        // Project filter
        document.getElementById('filterProject')?.addEventListener('change', (e) => {
            state.filters.project = e.target.value;
            filterNotes();
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl+S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                saveNote();
            }
            
            // Ctrl+N for new note
            if (e.ctrlKey && e.key === 'n' && !e.shiftKey) {
                e.preventDefault();
                createNewNote();
            }
        });
    }
    
    async function createNewNote() {
        try {
            const response = await window.ApiClient.post('/api/notes/create.php', {
                title: 'Neue Notiz',
                content: ''
            });
            
            if (response.success) {
                // Add to list
                addNoteToList(response.data);
                
                // Load the new note
                loadNote(response.data.id);
                
                // Focus title
                elements.noteTitle?.focus();
                elements.noteTitle?.select();
            }
        } catch (error) {
            console.error('Error creating note:', error);
            window.NotificationManager?.error('Fehler beim Erstellen der Notiz');
        }
    }
    
    async function loadNote(id) {
        try {
            const response = await window.ApiClient.get(`/api/notes/get.php?id=${id}`);
            
            if (response.success) {
                state.currentNote = response.data;
                displayNote(response.data);
                
                // Mark as active in list
                document.querySelectorAll('.note-item').forEach(item => {
                    item.classList.toggle('active', item.dataset.id == id);
                });
            }
        } catch (error) {
            console.error('Error loading note:', error);
            window.NotificationManager?.error('Fehler beim Laden der Notiz');
        }
    }
    
    function displayNote(note) {
        elements.noteEmptyState?.classList.add('hidden');
        elements.noteEditor?.classList.remove('hidden');
        
        elements.noteId.value = note.id;
        elements.noteTitle.value = note.title;
        elements.noteProject.value = note.project_id || '';
        elements.noteUpdated.textContent = `Aktualisiert: ${formatRelativeTime(note.updated_at)}`;
        
        // Update pin button state
        elements.pinBtn?.classList.toggle('active', note.is_pinned == 1);
        
        // Set Quill content
        if (state.quill) {
            if (note.content) {
                try {
                    // Try to parse as Delta (JSON)
                    const delta = JSON.parse(note.content);
                    state.quill.setContents(delta);
                } catch {
                    // If not JSON, set as HTML
                    state.quill.root.innerHTML = note.content;
                }
            } else {
                state.quill.setContents([]);
            }
        }
    }
    
    function scheduleAutoSave() {
        if (state.saveTimeout) {
            clearTimeout(state.saveTimeout);
        }
        
        state.saveTimeout = setTimeout(saveNote, 1000);
    }
    
    async function saveNote() {
        if (!state.currentNote || !elements.noteId.value) return;
        
        const data = {
            id: elements.noteId.value,
            title: elements.noteTitle.value || 'Unbenannte Notiz',
            content: JSON.stringify(state.quill.getContents()),
            content_html: state.quill.root.innerHTML,
            project_id: elements.noteProject.value || null
        };
        
        try {
            const response = await window.ApiClient.put('/api/notes/update.php', data);
            
            if (response.success) {
                // Update timestamp
                elements.noteUpdated.textContent = `Aktualisiert: gerade eben`;
                
                // Update list item
                updateNoteInList(response.data);
            }
        } catch (error) {
            console.error('Error saving note:', error);
            window.NotificationManager?.error('Fehler beim Speichern');
        }
    }
    
    async function togglePin() {
        if (!state.currentNote) return;
        
        const newPinState = state.currentNote.is_pinned != 1;
        
        try {
            const response = await window.ApiClient.put('/api/notes/update.php', {
                id: state.currentNote.id,
                is_pinned: newPinState ? 1 : 0
            });
            
            if (response.success) {
                state.currentNote.is_pinned = newPinState ? 1 : 0;
                elements.pinBtn?.classList.toggle('active', newPinState);
                
                // Reload list to reorder
                window.location.reload();
            }
        } catch (error) {
            console.error('Error toggling pin:', error);
            window.NotificationManager?.error('Fehler beim Anheften');
        }
    }
    
    async function deleteNote() {
        if (!state.currentNote) return;
        
        if (!confirm('Möchtest du diese Notiz wirklich löschen?')) return;
        
        try {
            const response = await window.ApiClient.delete('/api/notes/delete.php', {
                id: state.currentNote.id
            });
            
            if (response.success) {
                // Remove from list
                document.querySelector(`.note-item[data-id="${state.currentNote.id}"]`)?.remove();
                
                // Reset editor
                state.currentNote = null;
                elements.noteEditor?.classList.add('hidden');
                elements.noteEmptyState?.classList.remove('hidden');
                
                window.NotificationManager?.success('Notiz gelöscht');
            }
        } catch (error) {
            console.error('Error deleting note:', error);
            window.NotificationManager?.error('Fehler beim Löschen');
        }
    }
    
    function addNoteToList(note) {
        const html = createNoteItemHtml(note);
        
        // Add at top (after pinned notes)
        const firstUnpinned = elements.notesList?.querySelector('.note-item:not(.pinned)');
        if (firstUnpinned) {
            firstUnpinned.insertAdjacentHTML('beforebegin', html);
        } else {
            elements.notesList?.insertAdjacentHTML('beforeend', html);
        }
        
        // Remove empty state if present
        elements.notesList?.querySelector('.empty-state')?.remove();
    }
    
    function updateNoteInList(note) {
        const item = document.querySelector(`.note-item[data-id="${note.id}"]`);
        if (!item) return;
        
        item.querySelector('.note-title').textContent = note.title;
        item.querySelector('.note-preview').textContent = stripHtml(note.content_html || '').substring(0, 100);
        item.querySelector('.note-date').textContent = formatRelativeTime(note.updated_at);
    }
    
    function createNoteItemHtml(note) {
        return `
            <div class="note-item ${note.is_pinned ? 'pinned' : ''}" data-id="${note.id}">
                ${note.is_pinned ? '<i class="fas fa-thumbtack pin-icon"></i>' : ''}
                <h4 class="note-title">${escapeHtml(note.title)}</h4>
                <p class="note-preview">${escapeHtml(stripHtml(note.content_html || '').substring(0, 100))}</p>
                <div class="note-meta">
                    <span class="note-date">${formatRelativeTime(note.updated_at)}</span>
                    ${note.project_name ? `
                        <span class="note-project" style="background: ${note.project_color || 'var(--primary)'}">
                            ${escapeHtml(note.project_name)}
                        </span>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    function filterNotes() {
        const items = elements.notesList?.querySelectorAll('.note-item');
        
        items?.forEach(item => {
            const title = item.querySelector('.note-title')?.textContent.toLowerCase() || '';
            const preview = item.querySelector('.note-preview')?.textContent.toLowerCase() || '';
            const projectId = item.dataset.projectId || '';
            
            let visible = true;
            
            if (state.filters.search && !title.includes(state.filters.search) && !preview.includes(state.filters.search)) {
                visible = false;
            }
            
            if (state.filters.project && projectId !== state.filters.project) {
                visible = false;
            }
            
            item.style.display = visible ? '' : 'none';
        });
    }
    
    // Utilities
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    function stripHtml(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }
    
    function formatRelativeTime(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return 'gerade eben';
        if (diff < 3600) return `vor ${Math.floor(diff / 60)} Min.`;
        if (diff < 86400) return `vor ${Math.floor(diff / 3600)} Std.`;
        if (diff < 604800) return `vor ${Math.floor(diff / 86400)} Tagen`;
        
        return date.toLocaleDateString('de-DE');
    }
    
    // Expose for global access
    window.NotesManager = {
        create: createNewNote,
        save: saveNote,
        refresh: () => window.location.reload()
    };
})();
