<?php
$__user = getUser();
$__guilds = isLoggedIn() ? getUserGuilds() : [];
$__selectedGuildId = isLoggedIn() ? dashboardSelectedGuildId($__guilds) : '';
$__avatarUrl = null;
if ($__user && !empty($__user['avatar'])) {
    $__avatarUrl = 'https://cdn.discordapp.com/avatars/' . $__user['id'] . '/' . $__user['avatar'] . '.png?size=64';
}
$__initial = $__user ? strtoupper(mb_substr($__user['username'] ?? '?', 0, 1)) : '?';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EselModerator Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css?v=<?= @filemtime(__DIR__ . '/../assets/style.css') ?: time() ?>">
</head>
<body>
<header class="topbar">
  <a class="brand" href="<?= dashboardPageUrl('portal') ?>">
    <span class="brand-badge">🤖</span>
    EselModerator
  </a>
  <?php if ($__guilds): ?>
  <form method="get" action="" class="guild-switch" data-no-csrf>
    <select name="guildId" onchange="this.form.submit()">
      <?php foreach ($__guilds as $g): ?>
        <?php if (!(($g['permissions'] ?? 0) & 0x8) && !(($g['permissions'] ?? 0) & 0x20) && !isOwner()) continue; ?>
        <option value="<?= esc($g['id']) ?>" <?= $g['id'] === $__selectedGuildId ? 'selected' : '' ?>>
          <?= esc($g['name']) ?><?= guildHasBot($g['id']) ? '' : ' (Bot fehlt)' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php endif; ?>
  <div class="user-box">
    <?php if ($__user): ?>
      <?php if ($__avatarUrl): ?>
        <img class="avatar" src="<?= esc($__avatarUrl) ?>" alt="">
      <?php else: ?>
        <span class="avatar"><?= esc($__initial) ?></span>
      <?php endif; ?>
      <span class="username"><?= esc($__user['username']) ?></span>
      <a class="logout" href="<?= BASE_URL ?>/logout.php" title="Logout">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    <?php endif; ?>
  </div>
</header>
<a href="https://eselbande.com/blog/eselbande-bot-zusammenfuehrung/" target="_blank" rel="noopener" style="display:block; text-align:center; background:linear-gradient(135deg, rgba(129,140,248,0.18), rgba(240,147,251,0.14)); border-bottom:1px solid rgba(129,140,248,0.3); color:#e2e8f0; font-size:0.85rem; padding:8px 16px; text-decoration:none;">
  🚧 EselModerator wächst gerade mit EselMusic und Eselbuilder zu einem einzigen Bot (EselBande) zusammen — aktuell im Testbetrieb, hier ändert sich für dich noch nichts. Mehr erfahren →
</a>
<div class="layout">
