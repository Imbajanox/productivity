<?php
/**
 * Produktivitätstool - Login-Seite
 */

require_once __DIR__ . '/includes/init.php';

// Bereits eingeloggt?
if (isLoggedIn()) {
    redirect('/index.php');
}

// Flash-Nachricht abrufen
$flash = getFlash();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-path" content="<?php echo BASE_PATH; ?>">
    <title>Login - <?php echo APP_NAME; ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/framework.css'); ?>">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
        }

        .login-container {
            background: var(--bg-color);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-xxl);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .login-header h1 {
            color: var(--primary);
            margin-bottom: var(--spacing-sm);
        }

        .login-header p {
            color: var(--text-secondary);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-group label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }

        .theme-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: var(--spacing-xs);
            border-radius: var(--radius-md);
            transition: background-color var(--transition-fast);
        }

        .theme-toggle:hover {
            background-color: var(--bg-secondary);
        }

        .login-footer {
            text-align: center;
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--border-color);
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: var(--spacing-md);
                padding: var(--spacing-lg);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-tasks"></i> <?php echo APP_NAME; ?></h1>
            <p>Melde dich an, um fortzufahren</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : 'info'; ?> mb-4">
                <?php echo e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="<?php echo url('api/auth/login.php'); ?>">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label for="identifier">Benutzername oder E-Mail</label>
                <input type="text" id="identifier" name="identifier" required
                       value="<?php echo e(post('identifier', '')); ?>">
            </div>

            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-options">
                <label>
                    <input type="checkbox" name="remember" value="1">
                    Angemeldet bleiben
                </label>

                <button type="button" class="theme-toggle" title="Theme wechseln">
                    
                </button>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Anmelden
            </button>
        </form>

        <div class="login-footer">
            <p>
                <a href="<?php echo url('forgot-password.php'); ?>">
                    <i class="fas fa-key"></i> Passwort vergessen?
                </a>
            </p>
            <p>
                Noch kein Konto?
                <a href="<?php echo url('register.php'); ?>">
                    <i class="fas fa-user-plus"></i> Jetzt registrieren
                </a>
            </p>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo asset('js/utils.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('loginBtn');

            // Form Validation
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Button deaktivieren
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<div class="spinner"></div> Anmelden...';

                try {
                    const formData = new FormData(form);
                    const response = await fetch('<?php echo url('api/auth/login.php'); ?>', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        notifications.success('Erfolgreich angemeldet!');
                        setTimeout(() => {
                            window.location.href = '<?php echo url('index.php'); ?>';
                        }, 1000);
                    } else {
                        notifications.error(result.message || 'Anmeldung fehlgeschlagen');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Anmelden';
                    }
                } catch (error) {
                    notifications.error('Netzwerkfehler. Bitte versuche es erneut.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Anmelden';
                }
            });

            // Enter-Taste für Submit
            form.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form.dispatchEvent(new Event('submit'));
                }
            });
        });
    </script>
</body>
</html>