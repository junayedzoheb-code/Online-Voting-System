<?php
// ------------------------------------------------------------
// Small helpers used on every page.
// ------------------------------------------------------------
require_once __DIR__ . '/config.php';

/**
 * Start a session with its own cookie name, so the voter side and the
 * admin side never share a login.
 */
function start_session(string $name): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name($name);
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/** Escape text for safe HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/** One-time messages shown on the next page load. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function show_flash(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $html .= '<div class="alert alert-' . e($f['type']) . '" role="alert">' . e($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/** CSRF protection: every form includes this hidden field. */
function csrf_field(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}

function csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST'
        || empty($_POST['csrf'])
        || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(400);
        exit('Your session expired. Go back, reload the page and try again.');
    }
}

/** True when a PDOException is a duplicate / constraint violation. */
function is_duplicate(PDOException $e): bool
{
    return $e->getCode() === '23000';
}

/** Random voter ID like VTR-7K3QPA (no look-alike characters). */
function new_voter_id(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $id = 'VTR-';
    for ($i = 0; $i < 6; $i++) {
        $id .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $id;
}

/** Random 10-character password. */
function new_password(): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $pw = '';
    for ($i = 0; $i < 10; $i++) {
        $pw .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pw;
}

/**
 * Insert a voter with a freshly generated voter ID, retrying in the rare
 * case the random ID is already taken. Returns [row id, voter ID].
 */
function create_voter_with_generated_id(PDO $pdo, string $firstname, string $lastname, string $hash): array
{
    $insert = $pdo->prepare('INSERT INTO voters (voters_id, password, firstname, lastname) VALUES (?, ?, ?, ?)');
    for ($try = 0; ; $try++) {
        $vid = new_voter_id();
        try {
            $insert->execute([$vid, $hash, $firstname, $lastname]);
            return [(int) $pdo->lastInsertId(), $vid];
        } catch (PDOException $e) {
            if (!is_duplicate($e) || $try >= 5) {
                throw $e;
            }
        }
    }
}
