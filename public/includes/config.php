<?php
/**
 * EselModerator Dashboard - Configuration & Helper Functions
 *
 * Eigene Subdomain (eselmoderator.eselbande.com) statt Pfad-Prefix wie bei fahrstuhl --
 * deshalb ist BASE_URL einfach "/", kein Praefix-Handling in Redirects noetig (vermeidet
 * die dort dokumentierten doppelten-Redirect-Bugs).
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Env-Datei laden (Docker env_file deckt das zur Laufzeit ab, das hier ist der lokale Fallback).
foreach ([__DIR__ . '/../../.env'] as $env_file) {
    if (!file_exists($env_file)) continue;
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        if (getenv($key) !== false) continue;
        putenv($key . '=' . trim($value));
    }
}

define('API_BASE', getenv('ESELMODERATOR_API_BASE') ?: 'http://localhost:3003');
define('BASE_URL', '');
define('SESSION_TIMEOUT', 3600);

if (session_status() === PHP_SESSION_NONE) {
    // Sessions muessen auf dem gemounteten Host-Volume liegen, nicht im Container-Dateisystem --
    // sonst loggt jeder Rebuild alle aus (Lehre aus fahrstuhls Dashboard, siehe dessen config.php).
    $sessionPath = __DIR__ . '/../../data/sessions';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0700, true);
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
    session_start();
}

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function dashboardCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function dashboardCsrfInput() {
    return '<input type="hidden" name="csrf_token" value="' . esc(dashboardCsrfToken()) . '">';
}

function dashboardCsrfProvidedToken() {
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($headerToken !== '') return $headerToken;
    $postToken = $_POST['csrf_token'] ?? '';
    if ($postToken !== '') return $postToken;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $body = json_decode(file_get_contents('php://input'), true);
        if (is_array($body) && !empty($body['csrf_token'])) {
            return (string)$body['csrf_token'];
        }
    }
    return '';
}

function verifyDashboardCsrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (!isset($_SESSION['user'])) return;
    $expected = $_SESSION['csrf_token'] ?? '';
    $provided = dashboardCsrfProvidedToken();
    if (!$expected || !$provided || !hash_equals($expected, $provided)) {
        http_response_code(419);
        exit('Invalid CSRF token');
    }
}

function dashboardInjectCsrf($html) {
    if (stripos($html, '<form') === false || !preg_match('/<form\b[^>]*\bmethod\s*=\s*["\']?post["\']?/i', $html)) {
        return $html;
    }
    $tokenInput = dashboardCsrfInput();
    return preg_replace_callback('/<form\b([^>]*)>/i', function ($matches) use ($tokenInput) {
        $attrs = $matches[1] ?? '';
        if (!preg_match('/\bmethod\s*=\s*["\']?post["\']?/i', $attrs)) return $matches[0];
        if (preg_match('/\bdata-no-csrf\b/i', $attrs)) return $matches[0];
        return $matches[0] . $tokenInput;
    }, $html);
}

if (!defined('DASHBOARD_CSRF_BUFFER_STARTED')) {
    define('DASHBOARD_CSRF_BUFFER_STARTED', true);
    ob_start('dashboardInjectCsrf');
}

if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}
if (isset($_SESSION['user'])) {
    $_SESSION['last_activity'] = time();
}
verifyDashboardCsrf();

function selfPath($query = []) {
    $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $url = $path;
    if ($query) $url .= '?' . http_build_query($query);
    return $url;
}

function isLoggedIn() { return isset($_SESSION['user']); }
function requireLogin() { if (!isLoggedIn()) { header('Location: ' . BASE_URL . '/index.php'); exit(); } }
function getUser() { return $_SESSION['user'] ?? null; }
function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Sicher einen PHP-String als JS-String in einem HTML-Attribut einbetten (z.B. onclick="fn(...)")
// -- htmlspecialchars() reicht dafuer NICHT, der Browser dekodiert das Attribut vor dem
// JS-Parsing, ein escapetes ' wird wieder zu einem echten ' und bricht aus dem JS-String aus.
// json_encode mit den JSON_HEX_*-Flags escaped sowohl JS- als auch HTML-Sonderzeichen in
// \uXXXX-Sequenzen, die beides ueberleben. Relevant, weil Discord-Namen angreifbarer Content sind.
function jsAttr($s) {
    return json_encode((string)($s ?? ''), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
}
function formatNum($n) { return number_format((int)$n, 0, ',', '.'); }
function formatDate($ts) { return $ts ? date('d.m.Y H:i', strtotime($ts)) : 'N/A'; }

function isOwner() {
    $u = getUser();
    $ownerId = getenv('OWNER_ID') ?: '740958995887685696';
    return $u && $u['id'] === $ownerId;
}

// Vereinfacht gegenueber fahrstuhl: kein Dashboard-Admin-Rollen-Delegationssystem in Phase 1.
// Zugriff hat der Owner immer, sonst wer auf dem gewaehlten Server Discord-seitig
// Administrator oder "Server verwalten" hat (siehe isServerAdmin()).
function isAdmin() { return isOwner(); }
function requireAdmin() { requireLogin(); if (!isAdmin()) { header('Location: ' . BASE_URL . '/pages/portal.php'); exit(); } }

function isServerAdmin($guildId) {
    $guilds = getUserGuilds();
    foreach ($guilds as $g) {
        if ($g['id'] === $guildId) {
            return ($g['permissions'] & 0x8) === 0x8 || ($g['permissions'] & 0x20) === 0x20;
        }
    }
    return false;
}

function refreshUserGuildsIfNeeded() {
    if (!isLoggedIn()) return;
    $token = $_SESSION['discord_access_token'] ?? '';
    if ($token === '') return;
    if (time() - (int)($_SESSION['user_guilds_fetched_at'] ?? 0) < 300) return;

    $ch = curl_init('https://discord.com/api/users/@me/guilds');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status >= 200 && $status < 300) {
        $guilds = json_decode($response ?: '[]', true);
        if (is_array($guilds)) {
            $_SESSION['user_guilds'] = $guilds;
            $_SESSION['user_guilds_fetched_at'] = time();
        }
    }
}

function getUserGuilds() {
    refreshUserGuildsIfNeeded();
    return $_SESSION['user_guilds'] ?? [];
}

function dashboardSelectedGuildId($guilds = []) {
    $requested = trim($_GET['guildId'] ?? ($_POST['guildId'] ?? ''));
    $validIds = [];
    foreach ($guilds as $g) {
        if (!empty($g['id'])) $validIds[$g['id']] = true;
    }
    // Kein "empty($validIds) || ..."-Fallback mehr -- das akzeptierte frueher JEDE
    // beliebige Guild-ID, sobald die Discord-Guild-Liste aus irgendeinem Grund leer war
    // (z.B. ein fehlgeschlagener Token-Refresh), komplett ohne Zugehoerigkeitspruefung.
    if ($requested !== '' && isset($validIds[$requested])) {
        $_SESSION['selected_guild_id'] = $requested;
        return $requested;
    }
    $saved = trim($_SESSION['selected_guild_id'] ?? '');
    if ($saved !== '' && isset($validIds[$saved])) {
        return $saved;
    }
    if (!empty($guilds[0]['id'])) {
        $_SESSION['selected_guild_id'] = $guilds[0]['id'];
        return $guilds[0]['id'];
    }
    unset($_SESSION['selected_guild_id']);
    return '';
}

function dashboardSelectedGuildQuery($params = []) {
    $guildId = trim($params['guildId'] ?? ($_SESSION['selected_guild_id'] ?? ''));
    if ($guildId !== '') $params['guildId'] = $guildId;
    return $params ? '?' . http_build_query($params) : '';
}

function dashboardPageUrl($page, $params = [], $withGuild = true) {
    $query = $withGuild ? dashboardSelectedGuildQuery($params) : ($params ? '?' . http_build_query($params) : '');
    return BASE_URL . '/pages/' . $page . '.php' . $query;
}

function dashboardHeaders($json = false) {
    $headers = $json ? ['Content-Type: application/json'] : [];
    $token = getenv('ESELMODERATOR_BOT_API_TOKEN') ?: '';
    if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;
    $user = getUser();
    if ($user) {
        if (!empty($user['id'])) $headers[] = 'X-Dashboard-User-Id: ' . $user['id'];
        if (!empty($user['username'])) $headers[] = 'X-Dashboard-User: ' . $user['username'];
    }
    return $headers;
}

function getAPI($endpoint, $timeout = 10) {
    $ch = curl_init(API_BASE . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $headers = dashboardHeaders(false);
    if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $r = curl_exec($ch);
    $err = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($r === false) return ['success' => false, 'error' => $err ?: 'API request failed', 'status' => 0];
    $decoded = json_decode($r, true);
    if (!is_array($decoded)) return ['success' => false, 'error' => 'Invalid API response', 'status' => $status];
    if ($status >= 400 && !isset($decoded['success'])) { $decoded['success'] = false; $decoded['status'] = $status; }
    return $decoded;
}

function api($endpoint, $method = 'GET', $data = null, $timeout = 10) {
    $ch = curl_init(API_BASE . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, dashboardHeaders(true));
    $method = strtoupper($method);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method !== 'GET') {
        // Ohne das faellt curl still auf GET zurueck (Lehre aus fahrstuhls Dashboard).
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $r = curl_exec($ch);
    $s = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($r === false) return ['status' => 0, 'data' => ['success' => false, 'message' => $err ?: 'API request failed']];
    $decoded = json_decode($r, true);
    if (!is_array($decoded)) $decoded = ['success' => false, 'message' => 'Invalid API response'];
    return ['status' => $s, 'data' => $decoded];
}

function currentPage() {
    return basename($_SERVER['PHP_SELF'], '.php');
}

// Ist der Bot auf dem gewaehlten Server? Ueber GET /guilds (Bot-Guild-Cache), kurz in der
// Session gehalten, damit nicht jeder Seitenaufruf den Bot fragt.
define('BOT_GUILD_CACHE_TTL', 120);

function botGuildIds() {
    $cache = $_SESSION['bot_guild_ids'] ?? null;
    if (is_array($cache) && (time() - ($cache['at'] ?? 0)) < BOT_GUILD_CACHE_TTL) {
        return $cache['ids'];
    }
    $ids = [];
    $raw = getAPI('/guilds', 6);
    foreach (($raw['data']['guilds'] ?? []) as $g) {
        if (!empty($g['id'])) $ids[] = (string)$g['id'];
    }
    if (!$ids && is_array($cache) && !empty($cache['ids'])) {
        return $cache['ids'];
    }
    $_SESSION['bot_guild_ids'] = ['at' => time(), 'ids' => $ids];
    return $ids;
}

function guildHasBot($guildId) {
    $guildId = trim((string)$guildId);
    if ($guildId === '') return true;
    return in_array($guildId, botGuildIds(), true);
}

$GLOBALS['BOT_REQUIRED_PAGES'] = [
    'portal', 'modules', 'moderation', 'automod', 'welcome', 'reaction-roles',
    'leveling', 'temp-voice', 'tickets', 'social', 'freegames', 'server-backup',
    'premium-info',
];

function requireBotOnGuild() {
    if (!isLoggedIn()) return;
    $page = currentPage();
    if (!in_array($page, $GLOBALS['BOT_REQUIRED_PAGES'], true)) return;
    $guildId = trim($_GET['guildId'] ?? ($_SESSION['selected_guild_id'] ?? ''));
    if ($guildId === '') return;

    // Zugriffskontrolle: nur der Owner oder wer auf DIESEM Server Discord-seitig
    // Administrator/"Server verwalten" hat, darf die Modul-Seiten dieses Servers ueberhaupt
    // sehen. Ohne diese Pruefung koennte jedes normale Mitglied eines Servers, auf dem
    // EselModerator laeuft, per ?guildId=<fremde-id> dessen Moderation/AutoMod/Tickets/etc.
    // lesen und aendern -- die einzelnen Seiten selbst pruefen das nirgends nach.
    if (!isOwner() && !isServerAdmin($guildId)) {
        http_response_code(403);
        exit('Kein Zugriff auf diesen Server.');
    }

    if (guildHasBot($guildId)) return;
    $_SESSION['selected_guild_id'] = $guildId;
    if (!headers_sent()) {
        header('Location: ' . BASE_URL . '/pages/invite.php?guildId=' . urlencode($guildId));
        exit();
    }
}

// Muss ganz am Ende stehen -- alle oben definierten Helfer werden gebraucht.
requireBotOnGuild();
