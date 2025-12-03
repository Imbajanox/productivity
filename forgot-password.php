<?php
/**
 * Produktivitätstool - Passwort vergessen
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
    <title>Passwort vergessen - <?php echo APP_NAME; ?></title>

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
            background: linear-gradient(135deg, var(--warning) 0%, var(--danger) 100%);
        }

        .forgot-container {
            background: var(--bg-color);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-xxl);
            width: 100%;
            max-width: 400px;
        }

        .forgot-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .forgot-header h1 {
            color: var(--warning);
            margin-bottom: var(--spacing-sm);
        }

        .forgot-header p {
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

        .forgot-footer {
            text-align: center;
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--border-color);
        }

        .forgot-footer a {
            color: var(--primary);
            text-decoration: none;
        }

        .forgot-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .forgot-container {
                margin: var(--spacing-md);
                padding: var(--spacing-lg);
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1><i class="fas fa-key"></i> Passwort vergessen</h1>
            <p>Gib deine E-Mail-Adresse ein, um dein Passwort zurückzusetzen</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : 'info'; ?> mb-4">
                <?php echo e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <form id="forgotForm" method="POST" action="<?php echo url('api/auth/forgot-password.php'); ?>">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label for="email">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" required
                       placeholder="deine@email.com"
                       value="<?php echo e(post('email', '')); ?>">
                <small class="text-muted">
                    Wir senden dir einen Link zum Zurücksetzen deines Passworts.
                </small>
            </div>

            <div class="d-flex justify-between align-center mb-4">
                <a href="<?php echo url('login.php'); ?>" class="text-muted">
                    <i class="fas fa-arrow-left"></i> Zurück zur Anmeldung
                </a>

                <button type="button" class="theme-toggle" title="Theme wechseln">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <button type="submit" class="btn btn-warning w-100" id="forgotBtn">
                <i class="fas fa-envelope"></i> Reset-Link senden
            </button>
        </form>

        <div class="forgot-footer">
            <p class="text-muted">
                Neu hier?
                <a href="<?php echo url('register.php'); ?>">
                    <i class="fas fa-user-plus"></i> Konto erstellen
                </a>
            </p>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo asset('js/utils.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forgotForm');
            const submitBtn = document.getElementById('forgotBtn');

            // Form Validation
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Button deaktivieren
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<div class="spinner"></div> Wird gesendet...';

                try {
                    const formData = new FormData(form);
                    const response = await fetch('<?php echo url('api/auth/forgot-password.php'); ?>', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        notifications.success('Reset-Link wurde gesendet! Prüfe dein E-Mail-Postfach.');
                        form.reset();
                    } else {
                        notifications.error(result.message || 'Fehler beim Senden des Reset-Links');
                    }
                } catch (error) {
                    notifications.error('Netzwerkfehler. Bitte versuche es erneut.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-envelope"></i> Reset-Link senden';
                }
            });

            // Enter-Taste für Submit
            form.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    form.dispatchEvent(new Event('submit'));
                }
            });
        });
    </script>
</body>
</html>