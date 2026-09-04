<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);
$flash = null;

if ($guildId === '') {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<h1>Leveling</h1><div class="card"><p>Kein Server ausgewählt.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

$contextResp = getAPI('/guilds/' . urlencode($guildId) . '/context');
$channels = $contextResp['data']['channels'] ?? [];
$roles = $contextResp['data']['roles'] ?? [];
$rolesById = [];
foreach ($roles as $r) $rolesById[$r['id']] = $r['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleRewards = [];
    foreach (explode("\n", $_POST['roleRewards'] ?? '') as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, ':') === false) continue;
        [$lvl, $roleId] = array_map('trim', explode(':', $line, 2));
        if ((int)$lvl > 0 && isset($rolesById[$roleId])) {
            $roleRewards[] = ['level' => (int)$lvl, 'roleId' => $roleId, 'roleName' => $rolesById[$roleId]];
        }
    }
    $payload = [
        'enabled' => isset($_POST['enabled']),
        'messageXpMin' => (int)($_POST['messageXpMin'] ?? 8),
        'messageXpMax' => (int)($_POST['messageXpMax'] ?? 15),
        'cooldownSeconds' => (int)($_POST['cooldownSeconds'] ?? 60),
        'announceLevelUp' => isset($_POST['announceLevelUp']),
        'announceChannelId' => trim($_POST['announceChannelId'] ?? ''),
        'announceMessage' => $_POST['announceMessage'] ?? '',
        'roleMode' => $_POST['roleMode'] ?? 'stack',
        'autoCreateRoles' => isset($_POST['autoCreateRoles']),
        'autoRolePrefix' => $_POST['autoRolePrefix'] ?? 'Level',
        'removeLowerLevelRoles' => isset($_POST['removeLowerLevelRoles']),
        'roleRewards' => $roleRewards,
        'minMessageLength' => (int)($_POST['minMessageLength'] ?? 5),
        'blockDuplicateMessages' => isset($_POST['blockDuplicateMessages']),
        'voiceXpEnabled' => isset($_POST['voiceXpEnabled']),
        'voiceXpPerMinute' => (int)($_POST['voiceXpPerMinute'] ?? 2),
    ];
    $resp = api('/guilds/' . urlencode($guildId) . '/leveling', 'POST', $payload);
    $flash = !empty($resp['data']['success'])
        ? ['type' => 'success', 'text' => 'Leveling-Einstellungen gespeichert.']
        : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Speichern fehlgeschlagen.'];
}

$resp = getAPI('/guilds/' . urlencode($guildId) . '/leveling');
$l = $resp['data']['leveling'] ?? [];

$lbResp = getAPI('/guilds/' . urlencode($guildId) . '/leveling/leaderboard?limit=10');
$leaderboard = $lbResp['data']['leaderboard'] ?? [];

$rewardsText = '';
foreach (($l['roleRewards'] ?? []) as $r) $rewardsText .= $r['level'] . ':' . $r['roleId'] . "\n";

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Leveling</h1>

<?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= esc($flash['text']) ?></div><?php endif; ?>

<form method="post">
  <div class="card">
    <h2>Grundeinstellungen</h2>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="enabled" <?= !empty($l['enabled']) ? 'checked' : '' ?>>
      Leveling aktiv
    </label>
    <label>XP pro Nachricht (min)</label>
    <input type="number" name="messageXpMin" min="1" max="500" value="<?= (int)($l['messageXpMin'] ?? 8) ?>">
    <label>XP pro Nachricht (max)</label>
    <input type="number" name="messageXpMax" min="1" max="500" value="<?= (int)($l['messageXpMax'] ?? 15) ?>">
    <label>Cooldown zwischen XP-Vergaben (Sekunden)</label>
    <input type="number" name="cooldownSeconds" min="0" max="3600" value="<?= (int)($l['cooldownSeconds'] ?? 60) ?>">
    <label>Mindestlänge einer Nachricht für XP</label>
    <input type="number" name="minMessageLength" min="1" max="100" value="<?= (int)($l['minMessageLength'] ?? 5) ?>">
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="blockDuplicateMessages" <?= !empty($l['blockDuplicateMessages']) ? 'checked' : '' ?>>
      Doppelte Nachrichten für XP ignorieren
    </label>
  </div>

  <div class="card">
    <h2>Level-Up-Ankündigung</h2>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="announceLevelUp" <?= !empty($l['announceLevelUp']) ? 'checked' : '' ?>>
      Level-Up ankündigen
    </label>
    <label>Kanal (leer = im aktuellen Kanal)</label>
    <select name="announceChannelId">
      <option value="">– aktueller Kanal –</option>
      <?php foreach ($channels as $c): ?>
        <option value="<?= esc($c['id']) ?>" <?= ($l['announceChannelId'] ?? '') === $c['id'] ? 'selected' : '' ?>>#<?= esc($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Nachricht ({user}, {level} werden ersetzt)</label>
    <input type="text" name="announceMessage" value="<?= esc($l['announceMessage'] ?? '{user} reached Level {level}!') ?>">
  </div>

  <div class="card">
    <h2>Rollen-Belohnungen</h2>
    <label>Rollen-Modus</label>
    <select name="roleMode">
      <option value="stack" <?= ($l['roleMode'] ?? 'stack') === 'stack' ? 'selected' : '' ?>>Alle behalten (stapeln)</option>
      <option value="highest" <?= ($l['roleMode'] ?? 'stack') === 'highest' ? 'selected' : '' ?>>Nur höchste Rolle</option>
    </select>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="removeLowerLevelRoles" <?= !empty($l['removeLowerLevelRoles']) ? 'checked' : '' ?>>
      Niedrigere Level-Rollen entfernen
    </label>
    <label>Belohnungen (ein Eintrag pro Zeile, Format <code>level:roleId</code>)</label>
    <textarea name="roleRewards" rows="5" placeholder="5:123456789012345678"><?= esc($rewardsText) ?></textarea>
    <?php if ($roles): ?>
      <p style="color:var(--text-secondary);font-size:.82rem;margin-top:6px;">
        Verfügbare Rollen-IDs:
        <?php foreach (array_slice($roles, 0, 15) as $r): ?>
          <?= esc($r['name']) ?>=<code><?= esc($r['id']) ?></code>&nbsp;
        <?php endforeach; ?>
      </p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Voice-XP</h2>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="voiceXpEnabled" <?= !empty($l['voiceXpEnabled']) ? 'checked' : '' ?>>
      XP für Zeit in Voice-Kanälen
    </label>
    <label>XP pro Minute</label>
    <input type="number" name="voiceXpPerMinute" min="1" max="100" value="<?= (int)($l['voiceXpPerMinute'] ?? 2) ?>">
  </div>

  <button type="submit">Speichern</button>
</form>

<div class="card">
  <h2>Top 10 Leaderboard</h2>
  <?php if ($leaderboard): ?>
    <table>
      <tr><th>#</th><th>User-ID</th><th>Level</th><th>XP</th></tr>
      <?php foreach ($leaderboard as $i => $row): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= esc($row['userId'] ?? $row['user_id'] ?? '') ?></td>
          <td><?= (int)($row['level'] ?? 0) ?></td>
          <td><?= formatNum($row['xp'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p style="color:var(--text-secondary);">Noch keine Daten.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
