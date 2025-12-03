<?php
/**
 * Produktivitätstool - Dashboard
 */

require_once __DIR__ . '/includes/init.php';

// Nicht eingeloggt?
requireAuth();

// Aktueller Benutzer
$user = currentUser();
?>

<!DOCTYPE html>
<html lang="de" data-theme="<?php echo e($user['theme'] ?? DEFAULT_THEME); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-path" content="<?php echo BASE_PATH; ?>">
    <title>Dashboard - <?php echo APP_NAME; ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/framework.css'); ?>">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--primary);
        }

        .sidebar-nav {
            flex: 1;
            padding: var(--spacing-lg) 0;
        }

        .nav-item {
            margin: 0 var(--spacing-sm);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            color: var(--text-color);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
        }

        .nav-link:hover {
            background-color: var(--bg-tertiary);
            color: var(--primary);
        }

        .nav-link.active {
            background-color: var(--primary);
            color: white;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: var(--spacing-lg);
            border-top: 1px solid var(--border-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            margin: 0;
            font-size: 0.875rem;
        }

        .user-email {
            margin: 0;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: var(--bg-color);
            border-bottom: 1px solid var(--border-color);
            padding: 0 var(--spacing-lg);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            color: var(--text-color);
            transition: background-color var(--transition-fast);
        }

        .menu-toggle:hover {
            background-color: var(--bg-secondary);
        }

        .page-title {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-color);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .btn-icon {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            color: var(--text-color);
            transition: all var(--transition-fast);
        }

        .btn-icon:hover {
            background-color: var(--bg-secondary);
        }

        .user-menu {
            position: relative;
        }

        .user-menu-toggle {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: background-color var(--transition-fast);
        }

        .user-menu-toggle:hover {
            background-color: var(--bg-secondary);
        }

        .user-menu-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.875rem;
        }

        .user-menu-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all var(--transition-fast);
            z-index: 1000;
        }

        .user-menu-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-menu-item {
            display: block;
            padding: var(--spacing-md);
            color: var(--text-color);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: background-color var(--transition-fast);
        }

        .user-menu-item:hover {
            background-color: var(--bg-secondary);
        }

        .user-menu-item.danger {
            color: var(--danger);
        }

        .user-menu-item.danger:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .content {
            flex: 1;
            padding: var(--spacing-lg);
            overflow-y: auto;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .dashboard-widget {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow);
            transition: box-shadow var(--transition-normal);
        }

        .dashboard-widget:hover {
            box-shadow: var(--shadow-lg);
        }

        .widget-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--spacing-md);
        }

        .widget-title {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .widget-actions {
            display: flex;
            gap: var(--spacing-xs);
        }

        .widget-content {
            color: var(--text-secondary);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }

        .quick-action-card {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            text-align: center;
            text-decoration: none;
            color: var(--text-color);
            transition: all var(--transition-fast);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--spacing-md);
        }

        .quick-action-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .quick-action-icon {
            font-size: 2rem;
            color: var(--primary);
        }

        .quick-action-title {
            font-weight: 600;
            margin: 0;
        }

        .quick-action-desc {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin: 0;
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 1000;
            }

            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                opacity: 0;
                visibility: hidden;
                transition: all var(--transition-fast);
            }

            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        /* Loading States */
        .widget-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-xl);
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-tasks"></i> <?php echo APP_NAME; ?></h2>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="<?php echo url('index.php'); ?>" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('todos.php'); ?>" class="nav-link">
                        <i class="fas fa-check-square"></i>
                        <span>Todos</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('time-tracking.php'); ?>" class="nav-link">
                        <i class="fas fa-clock"></i>
                        <span>Zeiterfassung</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('notes.php'); ?>" class="nav-link">
                        <i class="fas fa-sticky-note"></i>
                        <span>Notizen</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('calendar.php'); ?>" class="nav-link">
                        <i class="fas fa-calendar"></i>
                        <span>Kalender</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('projects.php'); ?>" class="nav-link">
                        <i class="fas fa-folder"></i>
                        <span>Projekte</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('snippets.php'); ?>" class="nav-link">
                        <i class="fas fa-code"></i>
                        <span>Code-Snippets</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('bookmarks.php'); ?>" class="nav-link">
                        <i class="fas fa-bookmark"></i>
                        <span>Bookmarks</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('habits.php'); ?>" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Gewohnheiten</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo e(substr($user['username'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <p class="user-name"><?php echo e($user['username']); ?></p>
                        <p class="user-email"><?php echo e($user['email']); ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="menu-toggle d-block d-md-none" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Dashboard</h1>
                </div>

                <div class="header-right">
                    <div class="header-actions">
                        <button class="btn-icon" title="Schnellnotiz" id="quickNoteBtn">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="btn-icon theme-toggle" title="Theme wechseln">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>

                    <div class="user-menu">
                        <div class="user-menu-toggle" id="userMenuToggle">
                            <div class="user-menu-avatar">
                                <?php echo e(substr($user['username'], 0, 1)); ?>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>

                        <div class="user-menu-dropdown" id="userMenuDropdown">
                            <a href="<?php echo url('profile.php'); ?>" class="user-menu-item">
                                <i class="fas fa-user"></i> Profil
                            </a>
                            <a href="<?php echo url('settings.php'); ?>" class="user-menu-item">
                                <i class="fas fa-cog"></i> Einstellungen
                            </a>
                            <div class="user-menu-item" style="height: 1px; background: var(--border-color); margin: var(--spacing-sm) 0;"></div>
                            <a href="<?php echo url('api/auth/logout.php'); ?>" class="user-menu-item danger">
                                <i class="fas fa-sign-out-alt"></i> Abmelden
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Welcome Message -->
                <div class="mb-4">
                    <h2>Guten Tag, <?php echo e($user['first_name'] ?: $user['username']); ?>! 👋</h2>
                    <p class="text-muted">Hier ist dein persönliches Dashboard. Was möchtest du heute erledigen?</p>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="<?php echo url('todos.php?action=new'); ?>" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h3 class="quick-action-title">Neues Todo</h3>
                        <p class="quick-action-desc">Erstelle eine neue Aufgabe</p>
                    </a>

                    <a href="<?php echo url('time-tracking.php?action=start'); ?>" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <h3 class="quick-action-title">Zeit starten</h3>
                        <p class="quick-action-desc">Beginne mit der Zeiterfassung</p>
                    </a>

                    <a href="<?php echo url('notes.php?action=new'); ?>" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-sticky-note"></i>
                        </div>
                        <h3 class="quick-action-title">Notiz erstellen</h3>
                        <p class="quick-action-desc">Schreibe eine neue Notiz</p>
                    </a>

                    <a href="<?php echo url('calendar.php'); ?>" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h3 class="quick-action-title">Termin planen</h3>
                        <p class="quick-action-desc">Füge einen neuen Termin hinzu</p>
                    </a>
                </div>

                <!-- Dashboard Widgets -->
                <div class="dashboard-grid">
                    <!-- Today's Todos -->
                    <div class="dashboard-widget">
                        <div class="widget-header">
                            <h3 class="widget-title">
                                <i class="fas fa-calendar-day"></i> Heute fällig
                            </h3>
                            <div class="widget-actions">
                                <button class="btn-icon" title="Aktualisieren">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="widget-content">
                            <div class="widget-loading" id="todayTodosLoading">
                                <div class="spinner"></div>
                            </div>
                            <div id="todayTodosContent" class="d-none">
                                <!-- Wird per JavaScript geladen -->
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="dashboard-widget">
                        <div class="widget-header">
                            <h3 class="widget-title">
                                <i class="fas fa-history"></i> Letzte Aktivitäten
                            </h3>
                        </div>
                        <div class="widget-content">
                            <div class="widget-loading" id="recentActivityLoading">
                                <div class="spinner"></div>
                            </div>
                            <div id="recentActivityContent" class="d-none">
                                <!-- Wird per JavaScript geladen -->
                            </div>
                        </div>
                    </div>

                    <!-- Time Tracking Summary -->
                    <div class="dashboard-widget">
                        <div class="widget-header">
                            <h3 class="widget-title">
                                <i class="fas fa-clock"></i> Zeiterfassung diese Woche
                            </h3>
                        </div>
                        <div class="widget-content">
                            <div class="widget-loading" id="timeTrackingLoading">
                                <div class="spinner"></div>
                            </div>
                            <div id="timeTrackingContent" class="d-none">
                                <!-- Wird per JavaScript geladen -->
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="dashboard-widget">
                        <div class="widget-header">
                            <h3 class="widget-title">
                                <i class="fas fa-chart-bar"></i> Schnellübersicht
                            </h3>
                        </div>
                        <div class="widget-content">
                            <div class="widget-loading" id="quickStatsLoading">
                                <div class="spinner"></div>
                            </div>
                            <div id="quickStatsContent" class="d-none">
                                <!-- Wird per JavaScript geladen -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo asset('js/utils.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile Menu Toggle
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            function toggleSidebar() {
                sidebar.classList.toggle('collapsed');
                sidebarOverlay.classList.toggle('show');
            }

            menuToggle.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);

            // User Menu Toggle
            const userMenuToggle = document.getElementById('userMenuToggle');
            const userMenuDropdown = document.getElementById('userMenuDropdown');

            userMenuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenuDropdown.classList.toggle('show');
            });

            document.addEventListener('click', function() {
                userMenuDropdown.classList.remove('show');
            });

            // Quick Note Button
            const quickNoteBtn = document.getElementById('quickNoteBtn');
            quickNoteBtn.addEventListener('click', function() {
                const note = prompt('Schnellnotiz eingeben:');
                if (note && note.trim()) {
                    // Hier würde die API-Anfrage erfolgen
                    notifications.success('Notiz gespeichert!');
                }
            });

            // Load Dashboard Data
            loadDashboardData();

            async function loadDashboardData() {
                try {
                    // Today's Todos
                    loadTodayTodos();

                    // Recent Activity
                    loadRecentActivity();

                    // Time Tracking
                    loadTimeTracking();

                    // Quick Stats
                    loadQuickStats();

                } catch (error) {
                    console.error('Fehler beim Laden der Dashboard-Daten:', error);
                    notifications.error('Fehler beim Laden der Daten');
                }
            }

            async function loadTodayTodos() {
                try {
                    const response = await api.get('/dashboard/today-todos');
                    const content = document.getElementById('todayTodosContent');
                    const loading = document.getElementById('todayTodosLoading');

                    loading.classList.add('d-none');
                    content.classList.remove('d-none');

                    if (response.data && response.data.length > 0) {
                        content.innerHTML = response.data.map(todo => `
                            <div class="d-flex align-center mb-2">
                                <input type="checkbox" class="me-2" ${todo.completed ? 'checked' : ''}>
                                <span class="${todo.completed ? 'text-muted text-decoration-line-through' : ''}">
                                    ${todo.title}
                                </span>
                            </div>
                        `).join('');
                    } else {
                        content.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">📝</div>
                                <p>Keine Todos für heute</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    document.getElementById('todayTodosContent').innerHTML =
                        '<p class="text-danger">Fehler beim Laden der Todos</p>';
                }
            }

            async function loadRecentActivity() {
                try {
                    const response = await api.get('/dashboard/recent-activity');
                    const content = document.getElementById('recentActivityContent');
                    const loading = document.getElementById('recentActivityLoading');

                    loading.classList.add('d-none');
                    content.classList.remove('d-none');

                    if (response.data && response.data.length > 0) {
                        content.innerHTML = response.data.map(activity => `
                            <div class="d-flex align-center mb-2">
                                <i class="fas fa-circle text-primary me-2" style="font-size: 0.5rem;"></i>
                                <span class="text-sm">${activity.description}</span>
                                <small class="text-muted ms-auto">${activity.time}</small>
                            </div>
                        `).join('');
                    } else {
                        content.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">📊</div>
                                <p>Noch keine Aktivitäten</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    document.getElementById('recentActivityContent').innerHTML =
                        '<p class="text-danger">Fehler beim Laden der Aktivitäten</p>';
                }
            }

            async function loadTimeTracking() {
                try {
                    const response = await api.get('/dashboard/time-tracking');
                    const content = document.getElementById('timeTrackingContent');
                    const loading = document.getElementById('timeTrackingLoading');

                    loading.classList.add('d-none');
                    content.classList.remove('d-none');

                    const totalTime = response.data?.totalTime || 0;
                    const hours = Math.floor(totalTime / 3600);
                    const minutes = Math.floor((totalTime % 3600) / 60);

                    content.innerHTML = `
                        <div class="text-center">
                            <div style="font-size: 2rem; font-weight: bold; color: var(--primary);">
                                ${hours}h ${minutes}m
                            </div>
                            <p class="text-muted mb-0">Diese Woche gearbeitet</p>
                        </div>
                    `;
                } catch (error) {
                    document.getElementById('timeTrackingContent').innerHTML =
                        '<p class="text-danger">Fehler beim Laden der Zeiterfassung</p>';
                }
            }

            async function loadQuickStats() {
                try {
                    const response = await api.get('/dashboard/quick-stats');
                    const content = document.getElementById('quickStatsContent');
                    const loading = document.getElementById('quickStatsLoading');

                    loading.classList.add('d-none');
                    content.classList.remove('d-none');

                    const stats = response.data || {};
                    content.innerHTML = `
                        <div class="row">
                            <div class="col-6 text-center mb-3">
                                <div style="font-size: 1.5rem; font-weight: bold;">${stats.totalTodos || 0}</div>
                                <small class="text-muted">Todos gesamt</small>
                            </div>
                            <div class="col-6 text-center mb-3">
                                <div style="font-size: 1.5rem; font-weight: bold;">${stats.completedTodos || 0}</div>
                                <small class="text-muted">Erledigt</small>
                            </div>
                            <div class="col-6 text-center">
                                <div style="font-size: 1.5rem; font-weight: bold;">${stats.totalNotes || 0}</div>
                                <small class="text-muted">Notizen</small>
                            </div>
                            <div class="col-6 text-center">
                                <div style="font-size: 1.5rem; font-weight: bold;">${stats.totalProjects || 0}</div>
                                <small class="text-muted">Projekte</small>
                            </div>
                        </div>
                    `;
                } catch (error) {
                    document.getElementById('quickStatsContent').innerHTML =
                        '<p class="text-danger">Fehler beim Laden der Statistiken</p>';
                }
            }
        });
    </script>
</body>
</html>