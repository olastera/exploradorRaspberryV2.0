<?php
namespace App\Auth;

use App\Security\PathValidator;

class Auth
{
    public static function requireAuth()
    {
        $authEnabled = true;
        if (!$authEnabled) {
            return ['role' => 'admin', 'user' => 'invitado'];
        }

        $token = $_GET['token'] ?? '';
        if (!empty($token)) {
            $tokenFile = Csrf::tokenVerify($token);
            if ($tokenFile !== null) {
                return ['role' => 'user', 'user' => 'token', 'token_file' => $tokenFile];
            }
        }

        if (isset($_GET['logout'])) {
            self::logout();
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['login_user'])
            && isset($_POST['login_pass'])
        ) {
            if (!Turnstile::verify($_POST['cf-turnstile-response'] ?? '')) {
                $error = "Usuari o contrasenya incorrectes.";
            } else {
                $users = UserManager::load();
                foreach ($users as $u) {
                    if ($_POST['login_user'] === $u['user']
                        && password_verify($_POST['login_pass'], $u['pass'])
                    ) {
                        $_SESSION['auth_user'] = $u;
                        $_SESSION['last_activity'] = time();
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        $redirect = 'index.php';
                        if (!empty($_GET['path'])) {
                            $redirect .= '?path=' . urlencode($_GET['path']);
                        }
                        header("Location: $redirect");
                        exit;
                    }
                }
                $error = "Usuari o contrasenya incorrectes.";
            }
        }

        if (isset($_SESSION['auth_user'])) {
            self::checkSessionTimeout();
            return $_SESSION['auth_user'];
        }

        self::showLoginForm($error);
        exit;
    }

    public static function checkSessionTimeout()
    {
        $timeout = 7200;
        if (isset($_SESSION['last_activity'])
            && (time() - $_SESSION['last_activity']) > $timeout
        ) {
            $_SESSION = [];
            session_destroy();
            header('Location: index.php');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }

    public static function logout()
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: index.php");
        exit;
    }

    public static function showLoginForm($error = '')
    {
        $siteKey = TURNSTILE_SITE_KEY;
        ?>
        <!DOCTYPE html>
        <html lang="ca">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="color-scheme" content="dark">
            <meta name="theme-color" content="#0F172A">
            <title>Inici de sessió - Explorador de mitjans</title>
            <link href="assets/vendor/bootstrap/bootstrap.min.css?v=5.3.0" rel="stylesheet">
            <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css?v=1.10.0">
            <link rel="stylesheet" href="assets/css/app.css?v=3">
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <style>
                body {
                    background: var(--bg);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                }
                .login-card {
                    width: 100%;
                    max-width: 400px;
                    padding: 2.5rem;
                    border-radius: 1rem;
                    background: var(--card-bg);
                    border: 1px solid rgba(255,255,255,0.08);
                }
                .login-icon {
                    font-size: 3rem;
                    color: var(--accent);
                    margin-bottom: 1rem;
                }
                .form-control {
                    background: var(--bg);
                    border-color: rgba(255,255,255,0.1);
                    color: var(--text);
                }
                .form-control:focus {
                    background: var(--bg);
                    border-color: var(--accent);
                    color: var(--text);
                }
                .form-floating label {
                    color: var(--text-muted);
                }
            </style>
        </head>
        <body>
            <div class="login-card text-center">
                <i aria-hidden="true" class="bi bi-film login-icon"></i>
                <h2 class="mb-4" style="color:var(--text);">Explorador de mitjans</h2>
                <p class="text-muted mb-4" style="color:var(--text-muted) !important;">Accedeix a la teva biblioteca</p>
                <?php if ($error): ?>
                    <div class="alert alert-danger mb-3 py-2 small"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" name="login_user" class="form-control" id="uInput" placeholder="Usuari" required autofocus autocomplete="username">
                        <label for="uInput">Usuari</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" name="login_pass" class="form-control" id="pInput" placeholder="Contrasenya" required autocomplete="current-password">
                        <label for="pInput">Contrasenya</label>
                    </div>
                    <?php if ($siteKey): ?>
                    <div class="cf-turnstile mb-3" data-sitekey="<?php echo $siteKey; ?>"></div>
                    <?php endif; ?>
                    <button class="btn btn-primary w-100 py-2" type="submit" style="background:var(--accent);border:none;">Entra</button>
                </form>
            </div>
        </body>
        </html>
        <?php
    }
}
