<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guildId = trim($_GET['guildId'] ?? '');
$clientId = getenv('DISCORD_CLIENT_ID') ?: '1545456084754628658';
$inviteUrl = 'https://discord.com/api/oauth2/authorize?' . http_build_query([
    'client_id' => $clientId,
    'permissions' => '1099798277142',
    'scope' => 'bot applications.commands',
    'guild_id' => $guildId,
    'disable_guild_select' => 'true',
]);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>EselModerator einladen</h1>
<div class="card">
  <p>Der Bot ist auf diesem Server noch nicht aktiv. Lade ihn ein, um Module zu konfigurieren.</p>
  <a class="btn" href="<?= esc($inviteUrl) ?>" target="_blank" rel="noopener">Jetzt einladen →</a>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
