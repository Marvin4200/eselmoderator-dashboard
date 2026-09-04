<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);
$flash = null;

if ($guildId === '') {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<h1>Freegames</h1><div class="card"><p>Kein Server ausgewählt.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_settings';
    if ($action === 'save_settings') {
        $payload = [
            'enabled' => isset($_POST['enabled']),
            'channelId' => trim($_POST['channelId'] ?? ''),
            'mentionRoleId' => trim($_POST['mentionRoleId'] ?? ''),
            'filter' => $_POST['filter'] ?? 'all',
        ];
        $resp = api('/guilds/' . urlencode($guildId) . '/freegames', 'POST', $payload);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Einstellungen gespeichert.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Speichern fehlgeschlagen.'];
    } elseif ($action === 'post_now') {
        $resp = api('/guilds/' . urlencode($guildId) . '/freegames/post-now', 'POST', []);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => ($resp['data']['data']['posted'] ?? 0) . ' Spiele gepostet.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Posten fehlgeschlagen.'];
    }
}

$resp = getAPI('/guilds/' . urlencode($guildId) . '/freegames');
$fg = $resp['data']['freeGames'] ?? [];

$contextResp = getAPI('/guilds/' . urlencode($guildId) . '/context');
$channels = $contextResp['data']['channels'] ?? [];
$roles = $contextResp['data']['roles'] ?? [];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Freegames</h1>
<p class="subtitle">Postet automatisch kostenlose Spiele-Angebote von Epic Games, GOG, Steam, Humble Bundle und itch.io.</p>

<?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= esc($flash['text']) ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <input type="hidden" name="action" value="save_settings">
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="enabled" <?= !empty($fg['enabled']) ? 'checked' : '' ?>>
      Freegames-Ankündigungen aktiv
    </label>
    <label>Kanal</label>
    <select name="channelId">
      <option value="">– keiner –</option>
      <?php foreach ($channels as $c): ?>
        <option value="<?= esc($c['id']) ?>" <?= ($fg['channelId'] ?? '') === $c['id'] ? 'selected' : '' ?>>#<?= esc($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Erwähnungs-Rolle (optional)</label>
    <select name="mentionRoleId">
      <option value="">– keine –</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= esc($r['id']) ?>" <?= ($fg['mentionRoleId'] ?? '') === $r['id'] ? 'selected' : '' ?>><?= esc($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Filter</label>
    <select name="filter">
      <option value="all" <?= ($fg['filter'] ?? 'all') === 'all' ? 'selected' : '' ?>>Alle Stores</option>
      <option value="serious" <?= ($fg['filter'] ?? 'all') === 'serious' ? 'selected' : '' ?>>Nur Epic, GOG, Steam</option>
    </select>
    <button type="submit">Speichern</button>
  </form>
</div>

<div class="card">
  <h2>Status</h2>
  <table>
    <tr><th>Bereits gepostet</th><td><?= formatNum(count($fg['postedIds'] ?? [])) ?> Spiele</td></tr>
    <tr><th>Erstlauf abgeschlossen</th><td><?= !empty($fg['firstRunDone']) ? 'Ja' : 'Nein (nächster Fund wird als erstes gepostet)' ?></td></tr>
  </table>
  <form method="post" style="margin-top:14px;" onsubmit="return confirm('Aktuelle Freegames jetzt manuell posten?')">
    <input type="hidden" name="action" value="post_now">
    <button type="submit">Jetzt manuell posten</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
