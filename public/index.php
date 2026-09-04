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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    color-scheme: dark;
    --accent-1: #7c5cff; --accent-2: #4f8bff; --accent-3: #38d0ff;
    --accent-grad: linear-gradient(120deg, var(--accent-1), var(--accent-2));
  }
  * { box-sizing: border-box; }
  body {
    margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
    background:#08080f; color:#f4f4f8; font-family:'Inter','Segoe UI',sans-serif;
    position: relative; overflow: hidden;
  }
  body::before {
    content:""; position:fixed; inset:0; pointer-events:none;
    background:
      radial-gradient(650px 500px at 15% 15%, rgba(124,92,255,.24), transparent 60%),
      radial-gradient(650px 550px at 85% 85%, rgba(56,208,255,.16), transparent 60%),
      radial-gradient(500px 400px at 85% 15%, rgba(79,139,255,.14), transparent 60%);
    animation: drift 16s ease-in-out infinite alternate;
  }
  @keyframes drift {
    from { transform: scale(1) translate(0,0); }
    to { transform: scale(1.08) translate(-1%, 1%); }
  }
  .card {
    position: relative; z-index: 1;
    background: rgba(18,18,30,.75);
    backdrop-filter: blur(20px) saturate(160%);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    border:1px solid rgba(255,255,255,.1); border-radius:24px; padding:48px 40px;
    text-align:center; max-width:380px; box-shadow: 0 30px 80px -20px rgba(0,0,0,.6);
    animation: rise .5s cubic-bezier(.16,1,.3,1);
  }
  @keyframes rise { from { opacity:0; transform: translateY(16px); } to { opacity:1; transform: translateY(0); } }
  .badge {
    width:56px; height:56px; border-radius:16px; margin:0 auto 20px;
    background: var(--accent-grad); display:flex; align-items:center; justify-content:center;
    font-size:1.7rem; box-shadow: 0 8px 30px -6px rgba(124,92,255,.5);
  }
  .card h1 { margin:0 0 8px; font-size:1.5rem; font-weight:800; letter-spacing:-.02em; color:#fff; }
  .card p { color:#9a9ab0; font-size:.92rem; margin:0 0 28px; line-height:1.6; }
  .login-btn {
    display:inline-flex; align-items:center; justify-content:center; gap:10px; width:100%;
    background:#5865F2; color:#fff; text-decoration:none; font-weight:700; font-size:.95rem;
    padding:14px 22px; border-radius:999px; transition: transform .15s, box-shadow .15s, filter .15s;
    box-shadow: 0 6px 20px -6px rgba(88,101,242,.6);
  }
  .login-btn:hover { transform: translateY(-1px); filter: brightness(1.08); box-shadow: 0 10px 28px -6px rgba(88,101,242,.7); }
  .footer-note { position:relative; z-index:1; margin-top:22px; color:#6b6b82; font-size:.78rem; text-align:center; }
</style>
</head>
<body>
  <div class="card">
    <div class="badge">🤖</div>
    <h1>EselModerator</h1>
    <p>Melde dich mit Discord an, um deine Server zu verwalten.</p>
    <a class="login-btn" href="<?= esc($loginUrl) ?>">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.3 4.4A19.7 19.7 0 0 0 15.6 3l-.3.5a13.9 13.9 0 0 1 4 1.4 15.8 15.8 0 0 0-15 0 13 13 0 0 1 4.1-1.4L8 3a19.5 19.5 0 0 0-4.7 1.4C1 9 .3 13.5.6 18a20 20 0 0 0 5.9 3l1-1.5a12.8 12.8 0 0 1-2-1l.5-.4a14.4 14.4 0 0 0 12 0l.5.4a12.8 12.8 0 0 1-2 1l1 1.5a19.9 19.9 0 0 0 5.9-3c.4-5.2-.9-9.6-3.1-13.6ZM8.5 15.3c-1 0-1.8-.9-1.8-2s.8-2 1.8-2 1.9.9 1.8 2c0 1.1-.8 2-1.8 2Zm7 0c-1 0-1.8-.9-1.8-2s.8-2 1.8-2 1.9.9 1.8 2c0 1.1-.8 2-1.8 2Z"/></svg>
      Mit Discord einloggen
    </a>
  </div>
  <p class="footer-note">EselModerator · Teil der Eselbande</p>
</body>
</html>
