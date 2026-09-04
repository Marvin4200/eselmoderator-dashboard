<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);
$flash = null;

if ($guildId === '') {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<h1>Temp-Voice</h1><div class="card"><p>Kein Server ausgewählt.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'enabled' => isset($_POST['enabled']),
        'hubChannelId' => trim($_POST['hubChannelId'] ?? ''),
        'categoryId' => trim($_POST['categoryId'] ?? ''),
        'channelNameTemplate' => $_POST['channelNameTemplate'] ?? "{username}'s Channel",
        'userLimit' => (int)($_POST['userLimit'] ?? 0),
        'bitrate' => (int)($_POST['bitrate'] ?? 0),
        'allowRename' => isset($_POST['allowRename']),
        'allowLock' => isset($_POST['allowLock']),
        'allowLimit' => isset($_POST['allowLimit']),
        'deleteWhenEmpty' => isset($_POST['deleteWhenEmpty']),
    ];
    $resp = api('/guilds/' . urlencode($guildId) . '/tempvoice', 'POST', $payload);
    $flash = !empty($resp['data']['success'])
        ? ['type' => 'success', 'text' => 'Temp-Voice-Einstellungen gespeichert.']
        : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Speichern fehlgeschlagen.'];
}

$resp = getAPI('/guilds/' . urlencode($guildId) . '/tempvoice');
$tv = $resp['data']['tempVoice'] ?? [];

$contextResp = getAPI('/guilds/' . urlencode($guildId) . '/context');
$categories = $contextResp['data']['categories'] ?? [];
$voiceChannels = $contextResp['data']['voiceChannels'] ?? [];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Temporäre Voice-Kanäle</h1>

<?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= esc($flash['text']) ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="enabled" <?= !empty($tv['enabled']) ? 'checked' : '' ?>>
      Temp-Voice aktiv
    </label>

    <label>Hub-Kanal (beitreten erstellt neuen Kanal)</label>
    <select name="hubChannelId">
      <option value="">– keiner –</option>
      <?php foreach ($voiceChannels as $c): ?>
        <option value="<?= esc($c['id']) ?>" <?= ($tv['hubChannelId'] ?? '') === $c['id'] ? 'selected' : '' ?>>🔊 <?= esc($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Kategorie für neue Kanäle</label>
    <select name="categoryId">
      <option value="">– keine –</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= esc($c['id']) ?>" <?= ($tv['categoryId'] ?? '') === $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Namensvorlage ({username} wird ersetzt)</label>
    <input type="text" name="channelNameTemplate" value="<?= esc($tv['channelNameTemplate'] ?? "{username}'s Channel") ?>">

    <label>Standard-Nutzerlimit (0 = unbegrenzt)</label>
    <input type="number" name="userLimit" min="0" max="99" value="<?= (int)($tv['userLimit'] ?? 0) ?>">

    <label>Bitrate in kbps (0 = Server-Standard)</label>
    <input type="number" name="bitrate" min="0" max="384" value="<?= (int)($tv['bitrate'] ?? 0) ?>">

    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="allowRename" <?= ($tv['allowRename'] ?? true) ? 'checked' : '' ?>>
      Nutzer dürfen ihren Kanal umbenennen
    </label>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="allowLock" <?= ($tv['allowLock'] ?? true) ? 'checked' : '' ?>>
      Nutzer dürfen ihren Kanal sperren
    </label>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="allowLimit" <?= ($tv['allowLimit'] ?? true) ? 'checked' : '' ?>>
      Nutzer dürfen das Nutzerlimit ändern
    </label>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="deleteWhenEmpty" <?= ($tv['deleteWhenEmpty'] ?? true) ? 'checked' : '' ?>>
      Kanal löschen, wenn leer
    </label>

    <button type="submit">Speichern</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
