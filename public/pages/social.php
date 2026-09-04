<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);
$flash = null;

if ($guildId === '') {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<h1>Social Alerts</h1><div class="card"><p>Kein Server ausgewählt.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

function fetchSocial($guildId) {
    $resp = getAPI('/guilds/' . urlencode($guildId) . '/social');
    return $resp['data']['social'] ?? [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_settings';
    $current = fetchSocial($guildId);

    if ($action === 'save_settings') {
        $payload = [
            'enabled' => isset($_POST['enabled']),
            'announcementChannelId' => trim($_POST['announcementChannelId'] ?? ''),
            'mentionText' => $_POST['mentionText'] ?? '',
            'pollMinutes' => (int)($_POST['pollMinutes'] ?? 5),
            'feeds' => $current['feeds'] ?? [],
        ];
        $resp = api('/guilds/' . urlencode($guildId) . '/social', 'POST', $payload);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Einstellungen gespeichert.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Speichern fehlgeschlagen.'];
    } elseif ($action === 'add_feed') {
        $feeds = $current['feeds'] ?? [];
        $type = in_array($_POST['type'] ?? 'rss', ['youtube', 'twitch', 'rss'], true) ? $_POST['type'] : 'rss';
        $source = trim($_POST['source'] ?? '');
        if ($source !== '') {
            $feeds[] = [
                'id' => 'feed-' . substr(bin2hex(random_bytes(4)), 0, 8),
                'enabled' => true,
                'type' => $type,
                'label' => trim($_POST['label'] ?? '') ?: ($type === 'twitch' ? 'Twitch' : ($type === 'youtube' ? 'YouTube' : 'RSS Feed')),
                'source' => $source,
                'messageTemplate' => '{mention}{source}: {title}' . "\n" . '{url}',
            ];
        }
        $payload = array_merge($current, ['feeds' => $feeds]);
        $resp = api('/guilds/' . urlencode($guildId) . '/social', 'POST', $payload);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Feed hinzugefügt.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Hinzufügen fehlgeschlagen.'];
    } elseif ($action === 'remove_feed') {
        $feedId = $_POST['feedId'] ?? '';
        $feeds = array_values(array_filter($current['feeds'] ?? [], fn($f) => $f['id'] !== $feedId));
        $payload = array_merge($current, ['feeds' => $feeds]);
        $resp = api('/guilds/' . urlencode($guildId) . '/social', 'POST', $payload);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Feed entfernt.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Entfernen fehlgeschlagen.'];
    } elseif ($action === 'toggle_feed') {
        $feedId = $_POST['feedId'] ?? '';
        $feeds = $current['feeds'] ?? [];
        foreach ($feeds as &$f) if ($f['id'] === $feedId) $f['enabled'] = !$f['enabled'];
        unset($f);
        $payload = array_merge($current, ['feeds' => $feeds]);
        api('/guilds/' . urlencode($guildId) . '/social', 'POST', $payload);
    }
}

$social = fetchSocial($guildId);
$contextResp = getAPI('/guilds/' . urlencode($guildId) . '/context');
$channels = $contextResp['data']['channels'] ?? [];

$TYPE_LABELS = ['twitch' => '🟣 Twitch', 'youtube' => '🔴 YouTube', 'rss' => '🟠 RSS'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Social Alerts</h1>
<p class="subtitle">Benachrichtigt einen Kanal, wenn ein Twitch-Stream live geht, ein neues YouTube-Video erscheint oder ein RSS-Feed sich aktualisiert.</p>

<?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= esc($flash['text']) ?></div><?php endif; ?>

<div class="card">
  <h2>Grundeinstellungen</h2>
  <form method="post">
    <input type="hidden" name="action" value="save_settings">
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="enabled" <?= !empty($social['enabled']) ? 'checked' : '' ?>>
      Social Alerts aktiv
    </label>
    <label>Ankündigungs-Kanal</label>
    <select name="announcementChannelId">
      <option value="">– keiner –</option>
      <?php foreach ($channels as $c): ?>
        <option value="<?= esc($c['id']) ?>" <?= ($social['announcementChannelId'] ?? '') === $c['id'] ? 'selected' : '' ?>>#<?= esc($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Erwähnung (optional, z.B. @everyone oder eine Rollen-Mention)</label>
    <input type="text" name="mentionText" value="<?= esc($social['mentionText'] ?? '') ?>" placeholder="@here">
    <label>Prüfintervall (Minuten)</label>
    <input type="number" name="pollMinutes" min="2" max="60" value="<?= (int)($social['pollMinutes'] ?? 5) ?>">
    <button type="submit">Speichern</button>
  </form>
</div>

<div class="card">
  <h2>Feeds (<?= count($social['feeds'] ?? []) ?>/10)</h2>
  <?php if (!empty($social['feeds'])): ?>
    <table>
      <tr><th>Typ</th><th>Label</th><th>Quelle</th><th>Status</th><th></th></tr>
      <?php foreach ($social['feeds'] as $f): ?>
        <tr>
          <td><?= $TYPE_LABELS[$f['type']] ?? esc($f['type']) ?></td>
          <td><?= esc($f['label']) ?></td>
          <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($f['source']) ?></td>
          <td>
            <form method="post" style="display:inline;">
              <input type="hidden" name="action" value="toggle_feed">
              <input type="hidden" name="feedId" value="<?= esc($f['id']) ?>">
              <button type="submit" class="badge <?= $f['enabled'] ? 'on' : 'off' ?>" style="border:none;cursor:pointer;margin:0;"><?= $f['enabled'] ? 'Aktiv' : 'Pausiert' ?></button>
            </form>
          </td>
          <td>
            <form method="post" style="display:inline;" onsubmit="return confirm('Feed wirklich entfernen?')">
              <input type="hidden" name="action" value="remove_feed">
              <input type="hidden" name="feedId" value="<?= esc($f['id']) ?>">
              <button type="submit" class="btn danger" style="margin:0;padding:6px 12px;font-size:.8rem;">Entfernen</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p style="color:var(--text-secondary);">Noch kein Feed angelegt.</p>
  <?php endif; ?>

  <form method="post" style="margin-top:18px;">
    <input type="hidden" name="action" value="add_feed">
    <label>Typ</label>
    <select name="type">
      <option value="twitch">Twitch (Nutzername)</option>
      <option value="youtube">YouTube (Kanal-Link, @handle oder Channel-ID)</option>
      <option value="rss">RSS-Feed (URL)</option>
    </select>
    <label>Label (optional)</label>
    <input type="text" name="label" placeholder="z.B. Mein Twitch-Kanal">
    <label>Quelle</label>
    <input type="text" name="source" placeholder="twitch.tv-Name, YouTube-@handle oder Feed-URL" required>
    <button type="submit">Feed hinzufügen</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
