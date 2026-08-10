<?php
declare(strict_types=1);

const CONTENT_EDITOR_AUTH_KEY = 'content_editor_auth';
const CONTENT_EDITOR_AUTH_TTL = 28800;
const CONTENT_EDITOR_MAX_LOGIN_ATTEMPTS = 5;
const CONTENT_EDITOR_LOGIN_COOLDOWN = 60;

function content_editor_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $httpsEnabled = isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_name('portfolio_v3_content');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $httpsEnabled || $forwardedProto === 'https',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function content_editor_send_security_headers(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('Permissions-Policy: camera=(), geolocation=(), microphone=()');
}

function content_editor_credentials(array $content): array
{
    $config = $content[CONTENT_EDITOR_AUTH_KEY] ?? [];
    if (!is_array($config)) {
        return ['username' => '', 'password' => ''];
    }

    return [
        'username' => trim((string) ($config['username'] ?? '')),
        'password' => (string) ($config['password'] ?? ''),
    ];
}

function content_editor_auth_is_configured(array $credentials): bool
{
    return $credentials['username'] !== '' && $credentials['password'] !== '';
}

function content_editor_auth_fingerprint(array $credentials): string
{
    return hash('sha256', $credentials['username'] . "\0" . $credentials['password']);
}

function content_editor_clear_authentication(): void
{
    unset(
        $_SESSION['content_editor_fingerprint'],
        $_SESSION['content_editor_last_activity']
    );
}

function content_editor_is_authenticated(array $credentials): bool
{
    if (!content_editor_auth_is_configured($credentials)) {
        content_editor_clear_authentication();
        return false;
    }

    $storedFingerprint = (string) ($_SESSION['content_editor_fingerprint'] ?? '');
    $lastActivity = (int) ($_SESSION['content_editor_last_activity'] ?? 0);
    $isExpired = $lastActivity === 0 || time() - $lastActivity > CONTENT_EDITOR_AUTH_TTL;

    if ($isExpired || $storedFingerprint === '' || !hash_equals(content_editor_auth_fingerprint($credentials), $storedFingerprint)) {
        content_editor_clear_authentication();
        return false;
    }

    $_SESSION['content_editor_last_activity'] = time();
    return true;
}

function content_editor_csrf_token(): string
{
    $token = $_SESSION['content_editor_csrf'] ?? null;
    if (!is_string($token) || strlen($token) !== 64) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['content_editor_csrf'] = $token;
    }
    return $token;
}

function content_editor_csrf_is_valid(mixed $submittedToken): bool
{
    return is_string($submittedToken)
        && $submittedToken !== ''
        && hash_equals(content_editor_csrf_token(), $submittedToken);
}

function content_editor_login(array $credentials, string $username, string $password): bool
{
    if (!content_editor_auth_is_configured($credentials)) {
        return false;
    }

    $usernameMatches = hash_equals(hash('sha256', $credentials['username']), hash('sha256', $username));
    $passwordMatches = hash_equals(hash('sha256', $credentials['password']), hash('sha256', $password));
    if (!$usernameMatches || !$passwordMatches) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['content_editor_fingerprint'] = content_editor_auth_fingerprint($credentials);
    $_SESSION['content_editor_last_activity'] = time();
    $_SESSION['content_editor_csrf'] = bin2hex(random_bytes(32));
    unset($_SESSION['content_editor_login_attempts'], $_SESSION['content_editor_locked_until']);
    return true;
}

function content_editor_login_wait_seconds(): int
{
    $lockedUntil = (int) ($_SESSION['content_editor_locked_until'] ?? 0);
    if ($lockedUntil <= time()) {
        unset($_SESSION['content_editor_locked_until']);
        return 0;
    }
    return $lockedUntil - time();
}

function content_editor_record_failed_login(): void
{
    $attempts = (int) ($_SESSION['content_editor_login_attempts'] ?? 0) + 1;
    if ($attempts >= CONTENT_EDITOR_MAX_LOGIN_ATTEMPTS) {
        $_SESSION['content_editor_login_attempts'] = 0;
        $_SESSION['content_editor_locked_until'] = time() + CONTENT_EDITOR_LOGIN_COOLDOWN;
        return;
    }
    $_SESSION['content_editor_login_attempts'] = $attempts;
}

function content_editor_logout(): void
{
    content_editor_clear_authentication();
    unset($_SESSION['content_editor_login_attempts'], $_SESSION['content_editor_locked_until']);
    session_regenerate_id(true);
    $_SESSION['content_editor_csrf'] = bin2hex(random_bytes(32));
}
