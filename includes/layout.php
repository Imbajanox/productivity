<?php
/**
 * Produktivitätstool - Layout Template
 * 
 * Verwendung:
 * $pageTitle = 'Seitentitel';
 * $pageId = 'todos'; // Für aktive Navigation
 * $extraCss = ['css/todos.css'];
 * $extraJs = ['js/todos.js'];
 * include 'includes/layout.php';
 */

if (!isset($pageTitle)) $pageTitle = 'Dashboard';
if (!isset($pageId)) $pageId = 'dashboard';
if (!isset($pageStyles)) $pageStyles = [];
if (!isset($pageScripts)) $pageScripts = [];
if (!isset($extraCss)) $extraCss = [];
if (!isset($extraJs)) $extraJs = [];

// Merge pageStyles/pageScripts with extraCss/extraJs
$extraCss = array_merge($pageStyles, $extraCss);
$extraJs = array_merge($pageScripts, $extraJs);

$user = currentUser();
?>
<!DOCTYPE html>
<html lang="de" data-theme="<?php echo e($user['theme'] ?? DEFAULT_THEME); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-path" content="<?php echo BASE_PATH; ?>">
    <title><?php echo e($pageTitle); ?> - <?php echo APP_NAME; ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/framework.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/layout.css'); ?>">
    
    <!-- Extra CSS -->
    <?php foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?php echo asset($css); ?>">
    <?php endforeach; ?>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo url('index.php'); ?>" class="sidebar-logo">
                    <i class="fas fa-tasks"></i> 
                    <span><?php echo APP_NAME; ?></span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="<?php echo url('index.php'); ?>" class="nav-link <?php echo $pageId === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('todos.php'); ?>" class="nav-link <?php echo $pageId === 'todos' ? 'active' : ''; ?>">
                        <i class="fas fa-check-square"></i>
                        <span>Todos</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('time-tracking.php'); ?>" class="nav-link <?php echo $pageId === 'time-tracking' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i>
                        <span>Zeiterfassung</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('notes.php'); ?>" class="nav-link <?php echo $pageId === 'notes' ? 'active' : ''; ?>">
                        <i class="fas fa-sticky-note"></i>
                        <span>Notizen</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('projects.php'); ?>" class="nav-link <?php echo $pageId === 'projects' ? 'active' : ''; ?>">
                        <i class="fas fa-folder"></i>
                        <span>Projekte</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('calendar.php'); ?>" class="nav-link <?php echo $pageId === 'calendar' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar"></i>
                        <span>Kalender</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('snippets.php'); ?>" class="nav-link <?php echo $pageId === 'snippets' ? 'active' : ''; ?>">
                        <i class="fas fa-code"></i>
                        <span>Code-Snippets</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('bookmarks.php'); ?>" class="nav-link <?php echo $pageId === 'bookmarks' ? 'active' : ''; ?>">
                        <i class="fas fa-bookmark"></i>
                        <span>Bookmarks</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?php echo url('habits.php'); ?>" class="nav-link <?php echo $pageId === 'habits' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Gewohnheiten</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo e(strtoupper(substr($user['username'], 0, 1))); ?>
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
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><?php echo e($pageTitle); ?></h1>
                </div>

                <div class="header-right">
                    <div class="header-actions">
                        <button class="btn-icon theme-toggle" title="Theme wechseln">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>

                    <div class="user-menu">
                        <div class="user-menu-toggle" id="userMenuToggle">
                            <div class="user-menu-avatar">
                                <?php echo e(strtoupper(substr($user['username'], 0, 1))); ?>
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
                            <div class="user-menu-divider"></div>
                            <a href="<?php echo url('api/auth/logout.php'); ?>" class="user-menu-item danger">
                                <i class="fas fa-sign-out-alt"></i> Abmelden
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <?php echo $content ?? $pageContent ?? ''; ?>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo asset('js/utils.js'); ?>"></script>
    <script src="<?php echo asset('js/layout.js'); ?>"></script>
    
    <!-- Extra JS -->
    <?php foreach ($extraJs as $js): ?>
    <script src="<?php echo asset($js); ?>"></script>
    <?php endforeach; ?>
    
    <?php if (isset($inlineJs)): ?>
    <script><?php echo $inlineJs; ?></script>
    <?php endif; ?>
</body>
</html>