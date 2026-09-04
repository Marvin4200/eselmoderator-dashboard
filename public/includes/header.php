<?php
$__user = getUser();
$__guilds = isLoggedIn() ? getUserGuilds() : [];
$__selectedGuildId = isLoggedIn() ? dashboardSelectedGuildId($__guilds) : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EselModerator Dashboard</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="<?= dashboardPageUrl('portal') ?>">🤖 EselModerator</a>
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
      <span><?= esc($__user['username']) ?></span>
      <a href="<?= BASE_URL ?>/logout.php">Logout</a>
    <?php endif; ?>
  </div>
</header>
<div class="layout">
