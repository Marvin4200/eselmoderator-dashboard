<?php
require __DIR__ . '/includes/config.php';

$clientId = getenv('DISCORD_CLIENT_ID') ?: '1545456084754628658';
$clientSecret = getenv('DISCORD_CLIENT_SECRET') ?: '';
$redirectUri = getenv('DISCORD_REDIRECT_URI') ?: 'https://eselmoderator.eselbande.com/index.php';

// OAuth-Callback: Discord haengt ?code=...&state=... an dieselbe index.php an.
if (isset($_GET['code']) && !isLoggedIn()) {
    $state = $_GET['state'] ?? '';
    $expectedState = $_SESSION['oauth_state'] ?? '';
    unset($_SESSION['oauth_state']);

    if ($expectedState === '' || !hash_equals($expectedState, $state)) {
        http_response_code(400);
        exit('Ungueltiger OAuth-State. Bitte erneut einloggen.');
    }

    $ch = curl_init('https://discord.com/api/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => $redirectUri,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $tokenResponse = curl_exec($ch);
    $tokenStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $tokenData = json_decode($tokenResponse ?: '{}', true);
    if ($tokenStatus !== 200 || empty($tokenData['access_token'])) {
        http_response_code(502);
        exit('Discord-Login fehlgeschlagen. Bitte spaeter erneut versuchen.');
    }

    $accessToken = $tokenData['access_token'];

    $ch = curl_init('https://discord.com/api/users/@me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    $userResponse = curl_exec($ch);
    curl_close($ch);
    $user = json_decode($userResponse ?: '{}', true);

    if (empty($user['id'])) {
        http_response_code(502);
        exit('Discord-Profil konnte nicht geladen werden.');
    }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'] ?? 'Unbekannt',
        'avatar' => $user['avatar'] ?? null,
    ];
    $_SESSION['discord_access_token'] = $accessToken;
    $_SESSION['last_activity'] = time();
    unset($_SESSION['user_guilds'], $_SESSION['user_guilds_fetched_at']);

    header('Location: ' . BASE_URL . '/pages/portal.php');
    exit();
}

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/portal.php');
    exit();
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$loginUrl = 'https://discord.com/api/oauth2/authorize?' . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'identify guilds',
    'state' => $state,
]);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EselModerator Dashboard – Login</title>
<style>
  :root { color-scheme: dark; }
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
         background:#0d0d1a; color:#e0e0e0; font-family:'Segoe UI',sans-serif; }
  .card { background:#1a1a2e; border:1px solid #2a2a45; border-radius:14px; padding:40px 36px;
          text-align:center; max-width:360px; box-shadow:0 10px 30px rgba(0,0,0,.4); }
  .card h1 { margin:0 0 8px; font-size:1.4rem; color:#fff; }
  .card p { color:#999; font-size:.9rem; margin:0 0 24px; line-height:1.5; }
  .login-btn { display:inline-flex; align-items:center; gap:10px; background:#5865F2; color:#fff;
               text-decoration:none; font-weight:600; padding:12px 22px; border-radius:8px;
               transition:background .15s; }
  .login-btn:hover { background:#4752c4; }
</style>
</head>
<body>
  <div class="card">
    <h1>🤖 EselModerator</h1>
    <p>Melde dich mit Discord an, um deine Server zu verwalten.</p>
    <a class="login-btn" href="<?= esc($loginUrl) ?>">Mit Discord einloggen</a>
  </div>
</body>
</html>
