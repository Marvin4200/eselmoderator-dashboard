<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$manageableGuilds = array_values(array_filter($guilds, function ($g) {
    return isOwner() || (($g['permissions'] ?? 0) & 0x8) || (($g['permissions'] ?? 0) & 0x20);
}));
$guildId = dashboardSelectedGuildId($manageableGuilds);

$currentGuild = null;
foreach ($manageableGuilds as $g) if ($g['id'] === $guildId) { $currentGuild = $g; break; }

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
    ['key' => 'moderation', 'page' => 'moderation', 'label' => 'Moderation', 'icon' => 'shield', 'desc' => 'Warn, Timeout, Kick, Ban'],
    ['key' => 'automod', 'page' => 'automod', 'label' => 'AutoMod', 'icon' => 'ban', 'desc' => 'Spam, Invites, Begriffe'],
    ['key' => 'welcome', 'page' => 'welcome', 'label' => 'Willkommen', 'icon' => 'wave', 'desc' => 'Begrüßung & Autorolle'],
    ['key' => 'reactionRoles', 'page' => 'reaction-roles', 'label' => 'Reaction-Roles', 'icon' => 'smile', 'desc' => 'Rollen per Klick'],
    ['key' => 'leveling', 'page' => 'leveling', 'label' => 'Leveling', 'icon' => 'trend', 'desc' => 'XP & Rangliste'],
    ['key' => 'tempVoice', 'page' => 'temp-voice', 'label' => 'Temp-Voice', 'icon' => 'volume', 'desc' => 'Private Sprachkanäle'],
    ['key' => 'tickets', 'page' => 'tickets', 'label' => 'Tickets', 'icon' => 'ticket', 'desc' => 'Support-System'],
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$__u = getUser();
?>
<h1>Willkommen zurück, <?= esc($__u['username'] ?? '') ?> <span style="font-size:1.3rem;">👋</span></h1>
<p class="subtitle"><?= $currentGuild ? 'Du verwaltest gerade ' . esc($currentGuild['name']) : 'Wähle oben rechts einen Server aus.' ?></p>

<?php if (!$manageableGuilds): ?>
  <div class="card">
    <div class="empty-state">
      <h2 style="justify-content:center;">Kein verwaltbarer Server gefunden</h2>
      <p>Du brauchst auf einem Server "Administrator" oder "Server verwalten", damit er hier auftaucht.</p>
    </div>
  </div>
<?php elseif (!$botInGuild): ?>
  <div class="card">
    <div class="empty-state">
      <h2 style="justify-content:center;">EselModerator ist hier noch nicht eingeladen</h2>
      <p>Lade den Bot ein, um Module für diesen Server freizuschalten.</p>
      <a class="btn" href="<?= dashboardPageUrl('invite', ['guildId' => $guildId], false) ?>">Jetzt einladen →</a>
    </div>
  </div>
<?php else: ?>
  <?php
    $activeCount = $modulesData ? count(array_filter($modulesData['modules'] ?? [])) : 0;
    $totalCount = $modulesData ? count($modulesData['modules'] ?? []) : 0;
    $tier = $premiumData['tier'] ?? 'free';
    $tierLabel = ['free' => 'Kostenlos', 'basic' => 'Basic', 'pro' => 'Pro'][$tier] ?? $tier;
  ?>
  <div class="grid" style="margin-bottom:22px;">
    <div class="stat">
      <div class="value"><?= $activeCount ?>/<?= $totalCount ?></div>
      <div class="label">Aktive Module</div>
    </div>
    <div class="stat">
      <div class="value"><?= esc($tierLabel) ?></div>
      <div class="label">Premium-Tier <?= $tier === 'free' ? '· <a href="https://shop.eselbande.com" target="_blank" style="color:var(--accent-3);">Upgrade</a>' : '' ?></div>
    </div>
    <div class="stat">
      <div class="value" style="color:var(--success);">✓</div>
      <div class="label">Bot online auf diesem Server</div>
    </div>
  </div>

  <div class="card">
    <h2>Module</h2>
    <div class="grid">
      <?php foreach ($moduleHubLinks as $item): ?>
        <?php $active = $modulesData['modules'][$item['key']] ?? false; ?>
        <a class="stat interactive" style="text-decoration:none;color:inherit;display:flex;gap:14px;align-items:flex-start;" href="<?= dashboardPageUrl($item['page']) ?>">
          <span style="width:38px;height:38px;border-radius:11px;background:var(--accent-grad);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;">
            <?= __navIcon($item['icon']) ?>
          </span>
          <span>
            <span style="display:block;font-weight:700;color:#fff;font-size:.94rem;"><?= esc($item['label']) ?></span>
            <span style="display:block;color:var(--text-tertiary);font-size:.78rem;margin-top:2px;"><?= esc($item['desc']) ?></span>
            <span class="badge <?= $active ? 'on' : 'off' ?>" style="margin-top:8px;"><?= $active ? 'Aktiv' : 'Inaktiv' ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
    <a class="btn" href="<?= dashboardPageUrl('modules') ?>">Module verwalten</a>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
