<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);
$flash = null;

if ($guildId === '') {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<h1>Willkommen</h1><div class="card"><p>Kein Server ausgewählt.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'welcomeEnabled' => isset($_POST['welcomeEnabled']),
        'welcomeChannelId' => trim($_POST['welcomeChannelId'] ?? ''),
        'welcomeMessage' => $_POST['welcomeMessage'] ?? '',
        'welcomeAsEmbed' => isset($_POST['welcomeAsEmbed']),
        'welcomeEmbedTitle' => $_POST['welcomeEmbedTitle'] ?? '',
        'welcomeEmbedColor' => $_POST['welcomeEmbedColor'] ?? '',
        'welcomeCardEnabled' => isset($_POST['welcomeCardEnabled']),
        'welcomeCardTitle' => $_POST['welcomeCardTitle'] ?? '',
        'welcomeCardSubtitle' => $_POST['welcomeCardSubtitle'] ?? '',
        'aiWelcomeEnabled' => isset($_POST['aiWelcomeEnabled']),
        'aiCharacter' => $_POST['aiCharacter'] ?? 'friendly',
        'goodbyeEnabled' => isset($_POST['goodbyeEnabled']),
        'goodbyeChannelId' => trim($_POST['goodbyeChannelId'] ?? ''),
        'goodbyeMessage' => $_POST['goodbyeMessage'] ?? '',
        'goodbyeAsEmbed' => isset($_POST['goodbyeAsEmbed']),
        'autoroleEnabled' => isset($_POST['autoroleEnabled']),
        'autoroleId' => trim($_POST['autoroleId'] ?? ''),
    ];
    $resp = api('/guilds/' . urlencode($guildId) . '/welcome', 'POST', $payload);
    $flash = !empty($resp['data']['success'])
        ? ['type' => 'success', 'text' => 'Willkommen-Einstellungen gespeichert.']
        : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Speichern fehlgeschlagen.'];
}

$resp = getAPI('/guilds/' . urlencode($guildId) . '/welcome');
$w = $resp['data']['welcome'] ?? [];

$contextResp = getAPI('/guilds/' . urlencode($guildId) . '/context');
$channels = $contextResp['data']['channels'] ?? [];
$roles = $contextResp['data']['roles'] ?? [];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

function channelSelect($name, $channels, $selected) {
    echo '<select name="' . htmlspecialchars($name) . '"><option value="">– keiner –</option>';
    foreach ($channels as $c) {
        $sel = $c['id'] === $selected ? 'selected' : '';
        echo '<option value="' . esc($c['id']) . '" ' . $sel . '>#' . esc($c['name']) . '</option>';
    }
    echo '</select>';
}
?>
<h1>Willkommen &amp; Abschied</h1>

<?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= esc($flash['text']) ?></div><?php endif; ?>

<form method="post">
  <div class="card">
    <h2>Willkommen</h2>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="welcomeEnabled" <?= !empty($w['welcomeEnabled']) ? 'checked' : '' ?>>
      Willkommensnachricht aktiv
    </label>
    <label>Kanal</label>
    <?php channelSelect('welcomeChannelId', $channels, $w['welcomeChannelId'] ?? ''); ?>
    <label>Nachricht ({user}, {server} werden ersetzt)</label>
    <textarea name="welcomeMessage" rows="3"><?= esc($w['welcomeMessage'] ?? 'Willkommen {user} auf {server}!') ?></textarea>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="welcomeAsEmbed" <?= !empty($w['welcomeAsEmbed']) ? 'checked' : '' ?>>
      Als Embed senden
    </label>
    <label>Embed-Titel</label>
    <input type="text" name="welcomeEmbedTitle" value="<?= esc($w['welcomeEmbedTitle'] ?? '') ?>">
    <label>Embed-Farbe (Hex)</label>
    <input type="text" name="welcomeEmbedColor" value="<?= esc($w['welcomeEmbedColor'] ?? '#5865F2') ?>">
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="welcomeCardEnabled" <?= !empty($w['welcomeCardEnabled']) ? 'checked' : '' ?>>
      Willkommens-Grafik-Karte aktiv
    </label>
    <label>Karten-Titel</label>
    <input type="text" name="welcomeCardTitle" value="<?= esc($w['welcomeCardTitle'] ?? '') ?>">
    <label>Karten-Untertitel</label>
    <input type="text" name="welcomeCardSubtitle" value="<?= esc($w['welcomeCardSubtitle'] ?? '') ?>">
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="aiWelcomeEnabled" <?= !empty($w['aiWelcomeEnabled']) ? 'checked' : '' ?>>
      Zufällige Vorlagen-Nachrichten statt fester Text
    </label>
    <label>Charakter der Vorlagen</label>
    <select name="aiCharacter">
      <?php foreach (['friendly' => 'Freundlich', 'funny' => 'Witzig', 'formal' => 'Förmlich'] as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($w['aiCharacter'] ?? 'friendly') === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="card">
    <h2>Abschied</h2>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="goodbyeEnabled" <?= !empty($w['goodbyeEnabled']) ? 'checked' : '' ?>>
      Abschiedsnachricht aktiv
    </label>
    <label>Kanal</label>
    <?php channelSelect('goodbyeChannelId', $channels, $w['goodbyeChannelId'] ?? ''); ?>
    <label>Nachricht</label>
    <textarea name="goodbyeMessage" rows="3"><?= esc($w['goodbyeMessage'] ?? '{user} hat den Server verlassen.') ?></textarea>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="goodbyeAsEmbed" <?= !empty($w['goodbyeAsEmbed']) ? 'checked' : '' ?>>
      Als Embed senden
    </label>
  </div>

  <div class="card">
    <h2>Autorolle</h2>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="autoroleEnabled" <?= !empty($w['autoroleEnabled']) ? 'checked' : '' ?>>
      Automatisch eine Rolle vergeben
    </label>
    <label>Rolle</label>
    <select name="autoroleId">
      <option value="">– keine –</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= esc($r['id']) ?>" <?= ($w['autoroleId'] ?? '') === $r['id'] ? 'selected' : '' ?>><?= esc($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <button type="submit">Speichern</button>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
