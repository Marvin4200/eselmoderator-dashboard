<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$manageableGuilds = array_values(array_filter($guilds, function ($g) {
    return isOwner() || (($g['permissions'] ?? 0) & 0x8) || (($g['permissions'] ?? 0) & 0x20);
}));
$guildId = dashboardSelectedGuildId($manageableGuilds);

$modulesData = null;
$premiumData = null;
$botInGuild = $guildId !== '' && guildHasBot($guildId);
if ($botInGuild) {
    $modulesResp = getAPI('/guilds/' . urlencode($guildId) . '/modules');
    if (!empty($modulesResp['success'])) $modulesData = $modulesResp['data'];
    $premiumResp = getAPI('/guilds/' . urlencode($guildId) . '/premium');
    if (!empty($premiumResp['success'])) $premiumData = $premiumResp['data'];
}

$moduleHubLinks = [
    ['key' => 'moderation', 'page' => 'moderation', 'label' => 'Moderation', 'icon' => '🛡️'],
    ['key' => 'automod', 'page' => 'automod', 'label' => 'AutoMod', 'icon' => '🚫'],
    ['key' => 'welcome', 'page' => 'welcome', 'label' => 'Willkommen', 'icon' => '👋'],
    ['key' => 'reactionRoles', 'page' => 'reaction-roles', 'label' => 'Reaction-Roles', 'icon' => '🎭'],
    ['key' => 'leveling', 'page' => 'leveling', 'label' => 'Leveling', 'icon' => '📈'],
    ['key' => 'tempVoice', 'page' => 'temp-voice', 'label' => 'Temp-Voice', 'icon' => '🔊'],
    ['key' => 'tickets', 'page' => 'tickets', 'label' => 'Tickets', 'icon' => '🎫'],
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Übersicht</h1>

<?php if (!$manageableGuilds): ?>
  <div class="card">
    <h2>Kein verwaltbarer Server gefunden</h2>
    <p>Du brauchst auf einem Server "Administrator" oder "Server verwalten", damit er hier auftaucht.</p>
  </div>
<?php elseif (!$botInGuild): ?>
  <div class="card">
    <h2>EselModerator ist auf diesem Server noch nicht eingeladen</h2>
    <p><a class="btn" href="<?= dashboardPageUrl('invite', ['guildId' => $guildId], false) ?>">Jetzt einladen →</a></p>
  </div>
<?php else: ?>
  <div class="grid">
    <div class="stat">
      <div class="value"><?= $premiumData ? esc($premiumData['tier'] ?? 'free') : '–' ?></div>
      <div class="label">Premium-Tier</div>
    </div>
    <div class="stat">
      <div class="value"><?= $modulesData ? count(array_filter($modulesData['modules'] ?? [])) : 0 ?>/<?= $modulesData ? count($modulesData['modules'] ?? []) : 0 ?></div>
      <div class="label">Aktive Module</div>
    </div>
  </div>

  <div class="card">
    <h2>Module</h2>
    <div class="grid">
      <?php foreach ($moduleHubLinks as $item): ?>
        <?php $active = $modulesData['modules'][$item['key']] ?? false; ?>
        <a class="stat" style="text-decoration:none;color:inherit;display:block;" href="<?= dashboardPageUrl($item['page']) ?>">
          <div class="value"><?= $item['icon'] ?></div>
          <div class="label"><?= esc($item['label']) ?>
            <span class="badge <?= $active ? 'on' : 'off' ?>"><?= $active ? 'An' : 'Aus' ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <a class="btn" href="<?= dashboardPageUrl('modules') ?>">Module verwalten</a>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
