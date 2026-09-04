<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);
$flash = null;

$MODULE_LABELS = [
    'moderation' => 'Moderation (Warn/Timeout/Kick/Ban)',
    'automod' => 'AutoMod (Invites, Spam, verbotene Begriffe)',
    'welcome' => 'Willkommen & Abschied',
    'reactionRoles' => 'Reaction-Roles',
    'leveling' => 'Leveling / XP',
    'tempVoice' => 'Temporäre Voice-Kanäle',
    'tickets' => 'Ticket-System',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $guildId !== '') {
    $payload = [];
    foreach (array_keys($MODULE_LABELS) as $key) {
        $payload[$key] = isset($_POST['module_' . $key]);
    }
    $resp = api('/guilds/' . urlencode($guildId) . '/modules', 'POST', $payload);
    $flash = !empty($resp['data']['success'])
        ? ['type' => 'success', 'text' => 'Module gespeichert.']
        : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Speichern fehlgeschlagen.'];
}

$modules = [];
if ($guildId !== '') {
    $resp = getAPI('/guilds/' . urlencode($guildId) . '/modules');
    if (!empty($resp['success'])) $modules = $resp['data']['modules'] ?? [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Module</h1>

<?php if ($flash): ?>
  <div class="flash <?= $flash['type'] ?>"><?= esc($flash['text']) ?></div>
<?php endif; ?>

<?php if ($guildId === ''): ?>
  <div class="card"><p>Kein Server ausgewählt.</p></div>
<?php else: ?>
  <div class="card">
    <form method="post">
      <?php foreach ($MODULE_LABELS as $key => $label): ?>
        <label style="display:flex;align-items:center;gap:10px;margin:14px 0;">
          <input type="checkbox" name="module_<?= esc($key) ?>" <?= !empty($modules[$key]) ? 'checked' : '' ?>>
          <?= esc($label) ?>
        </label>
      <?php endforeach; ?>
      <button type="submit">Speichern</button>
    </form>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
