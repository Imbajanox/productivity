<?php
/**
 * Produktivitätstool - Registrierungsseite
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
    <title>Registrieren - <?php echo APP_NAME; ?></title>

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
            background: linear-gradient(135deg, var(--success) 0%, var(--primary) 100%);
        }

        .register-container {
            background: var(--bg-color);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-xxl);
            width: 100%;
            max-width: 450px;
        }

        .register-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .register-header h1 {
            color: var(--success);
            margin-bottom: var(--spacing-sm);
        }

        .register-header p {
            color: var(--text-secondary);
        }

        .form-row {
            display: flex;
            gap: var(--spacing-md);
        }

        .form-row .form-group {
            flex: 1;
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

        .password-strength {
            margin-top: var(--spacing-xs);
            font-size: 0.875rem;
        }

        .password-strength.weak { color: var(--danger); }
        .password-strength.medium { color: var(--warning); }
        .password-strength.strong { color: var(--success); }

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

        .register-footer {
            text-align: center;
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--border-color);
        }

        .register-footer a {
            color: var(--primary);
            text-decoration: none;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .register-container {
                margin: var(--spacing-md);
                padding: var(--spacing-lg);
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1><i class="fas fa-user-plus"></i> Registrieren</h1>
            <p>Erstelle dein Konto für <?php echo APP_NAME; ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : 'info'; ?> mb-4">
                <?php echo e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="<?php echo url('api/auth/register.php'); ?>">
            <?php echo csrfField(); ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Vorname</label>
                    <input type="text" id="first_name" name="first_name"
                           value="<?php echo e(post('first_name', '')); ?>">
                </div>

                <div class="form-group">
                    <label for="last_name">Nachname</label>
                    <input type="text" id="last_name" name="last_name"
                           value="<?php echo e(post('last_name', '')); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="username">Benutzername *</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo e(post('username', '')); ?>">
                <small class="text-muted">3-50 Zeichen, nur Buchstaben, Zahlen und Unterstriche</small>
            </div>

            <div class="form-group">
                <label for="email">E-Mail-Adresse *</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo e(post('email', '')); ?>">
            </div>

            <div class="form-group">
                <label for="password">Passwort *</label>
                <input type="password" id="password" name="password" required>
                <div class="password-strength" id="passwordStrength"></div>
                <small class="text-muted">Mindestens 8 Zeichen</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">Passwort bestätigen *</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="accept_terms" required>
                    Ich akzeptiere die <a href="#" target="_blank">Nutzungsbedingungen</a> und die <a href="#" target="_blank">Datenschutzrichtlinie</a>
                </label>
            </div>

            <div class="d-flex justify-between align-center mb-4">
                <a href="<?php echo url('login.php'); ?>" class="text-muted">
                    <i class="fas fa-arrow-left"></i> Zurück zur Anmeldung
                </a>

                <button type="button" class="theme-toggle" title="Theme wechseln">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <button type="submit" class="btn btn-success w-100" id="registerBtn">
                <i class="fas fa-user-plus"></i> Konto erstellen
            </button>
        </form>

        <div class="register-footer">
            <p class="text-muted">
                Bereits ein Konto?
                <a href="<?php echo url('login.php'); ?>">
                    <i class="fas fa-sign-in-alt"></i> Jetzt anmelden
                </a>
            </p>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo asset('js/utils.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('registerBtn');
            const passwordInput = document.getElementById('password');
            const passwordConfirmInput = document.getElementById('password_confirm');
            const passwordStrength = document.getElementById('passwordStrength');

            // Passwort-Stärke prüfen
            function checkPasswordStrength(password) {
                let strength = 0;
                let feedback = [];

                if (password.length >= 8) strength++;
                else feedback.push('Mindestens 8 Zeichen');

                if (/[a-z]/.test(password)) strength++;
                else feedback.push('Kleinbuchstaben');

                if (/[A-Z]/.test(password)) strength++;
                else feedback.push('Großbuchstaben');

                if (/[0-9]/.test(password)) strength++;
                else feedback.push('Zahlen');

                if (/[^A-Za-z0-9]/.test(password)) strength++;
                else feedback.push('Sonderzeichen');

                let strengthText = '';
                let strengthClass = '';

                switch (strength) {
                    case 0:
                    case 1:
                        strengthText = 'Sehr schwach';
                        strengthClass = 'weak';
                        break;
                    case 2:
                        strengthText = 'Schwach';
                        strengthClass = 'weak';
                        break;
                    case 3:
                        strengthText = 'Mittel';
                        strengthClass = 'medium';
                        break;
                    case 4:
                        strengthText = 'Stark';
                        strengthClass = 'strong';
                        break;
                    case 5:
                        strengthText = 'Sehr stark';
                        strengthClass = 'strong';
                        break;
                }

                passwordStrength.textContent = strengthText;
                passwordStrength.className = `password-strength ${strengthClass}`;

                return strength >= 3;
            }

            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });

            // Form Validation
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Clientseitige Validierung
                const password = passwordInput.value;
                const passwordConfirm = passwordConfirmInput.value;

                if (password !== passwordConfirm) {
                    notifications.error('Passwörter stimmen nicht überein');
                    return;
                }

                if (!checkPasswordStrength(password)) {
                    notifications.error('Passwort ist zu schwach');
                    return;
                }

                // Button deaktivieren
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<div class="spinner"></div> Konto wird erstellt...';

                try {
                    const formData = new FormData(form);
                    const response = await fetch('<?php echo url('api/auth/register.php'); ?>', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        notifications.success('Konto erfolgreich erstellt! Du wirst weitergeleitet...');
                        setTimeout(() => {
                            window.location.href = '<?php echo url('index.php'); ?>';
                        }, 2000);
                    } else {
                        notifications.error(result.message || 'Registrierung fehlgeschlagen');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Konto erstellen';
                    }
                } catch (error) {
                    notifications.error('Netzwerkfehler. Bitte versuche es erneut.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Konto erstellen';
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