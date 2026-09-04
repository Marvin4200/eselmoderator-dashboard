<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);
$flash = null;

if ($guildId === '') {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<h1>Tickets</h1><div class="card"><p>Kein Server ausgewählt.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_settings';

    if ($action === 'save_settings') {
        $payload = [
            'categoryId' => trim($_POST['categoryId'] ?? ''),
            'staffRoleId' => trim($_POST['staffRoleId'] ?? ''),
            'transcriptChannelId' => trim($_POST['transcriptChannelId'] ?? ''),
            'defaultPriority' => $_POST['defaultPriority'] ?? 'normal',
            'closeDelaySeconds' => (int)($_POST['closeDelaySeconds'] ?? 5),
            'slaMinutes' => (int)($_POST['slaMinutes'] ?? 240),
            'requireCloseReason' => isset($_POST['requireCloseReason']),
            'enableClaiming' => isset($_POST['enableClaiming']),
            'panelTitle' => $_POST['panelTitle'] ?? '',
            'panelDescription' => $_POST['panelDescription'] ?? '',
            'panelButtonLabel' => $_POST['panelButtonLabel'] ?? '',
            'panelColor' => $_POST['panelColor'] ?? '',
            'panelShowLiveStatus' => isset($_POST['panelShowLiveStatus']),
            'panelShowStaffOnline' => isset($_POST['panelShowStaffOnline']),
            'panelShowQueue' => isset($_POST['panelShowQueue']),
            'panelShowRating' => isset($_POST['panelShowRating']),
        ];
        $resp = api('/guilds/' . urlencode($guildId) . '/tickets', 'POST', $payload);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Ticket-Einstellungen gespeichert.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Speichern fehlgeschlagen.'];
    } elseif ($action === 'deploy_panel') {
        $channelId = trim($_POST['deployChannelId'] ?? '');
        $resp = api('/guilds/' . urlencode($guildId) . '/tickets/panel', 'POST', ['channelId' => $channelId]);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Panel wurde gesendet.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Panel senden fehlgeschlagen.'];
    } elseif ($action === 'remove_panel') {
        $channelId = trim($_POST['removeChannelId'] ?? '');
        $resp = api('/guilds/' . urlencode($guildId) . '/tickets/panel/remove', 'DELETE', ['channelId' => $channelId]);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Panel entfernt.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Entfernen fehlgeschlagen.'];
    }
}

$ticketsResp = getAPI('/guilds/' . urlencode($guildId) . '/tickets');
$tickets = $ticketsResp['data']['tickets'] ?? [];
$panels = $ticketsResp['data']['panels'] ?? [];

$contextResp = getAPI('/guilds/' . urlencode($guildId) . '/context');
$categories = $contextResp['data']['categories'] ?? [];
$channels = $contextResp['data']['channels'] ?? [];
$roles = $contextResp['data']['roles'] ?? [];

$premiumResp = getAPI('/guilds/' . urlencode($guildId) . '/premium');
$panelLimit = $premiumResp['data']['featureLimits']['ticketPanels'] ?? 1;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Tickets</h1>

<?php if ($flash): ?>
  <div class="flash <?= $flash['type'] ?>"><?= esc($flash['text']) ?></div>
<?php endif; ?>

<div class="card">
  <h2>Verhalten</h2>
  <form method="post">
    <input type="hidden" name="action" value="save_settings">

    <label>Ticket-Kategorie</label>
    <select name="categoryId">
      <option value="">– keine –</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= esc($c['id']) ?>" <?= ($tickets['categoryId'] ?? '') === $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Staff-Rolle</label>
    <select name="staffRoleId">
      <option value="">– keine –</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= esc($r['id']) ?>" <?= ($tickets['staffRoleId'] ?? '') === $r['id'] ? 'selected' : '' ?>><?= esc($r['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Transkript-Kanal</label>
    <select name="transcriptChannelId">
      <option value="">– keiner –</option>
      <?php foreach ($channels as $c): ?>
        <option value="<?= esc($c['id']) ?>" <?= ($tickets['transcriptChannelId'] ?? '') === $c['id'] ? 'selected' : '' ?>>#<?= esc($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Standard-Priorität</label>
    <select name="defaultPriority">
      <?php foreach (['low' => 'Niedrig', 'normal' => 'Normal', 'high' => 'Hoch'] as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($tickets['defaultPriority'] ?? 'normal') === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>

    <label>Schließ-Verzögerung (Sekunden)</label>
    <input type="number" name="closeDelaySeconds" min="1" max="30" value="<?= (int)($tickets['closeDelaySeconds'] ?? 5) ?>">

    <label>SLA (Minuten bis Eskalation)</label>
    <input type="number" name="slaMinutes" min="1" value="<?= (int)($tickets['slaMinutes'] ?? 240) ?>">

    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="requireCloseReason" <?= !empty($tickets['requireCloseReason']) ? 'checked' : '' ?>>
      Schließen-Grund verpflichtend
    </label>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="enableClaiming" <?= ($tickets['enableClaiming'] ?? true) ? 'checked' : '' ?>>
      Staff kann Tickets übernehmen ("Claim")
    </label>

    <h2 style="margin-top:28px;">Panel-Design</h2>
    <label>Titel</label>
    <input type="text" name="panelTitle" value="<?= esc($tickets['panelTitle'] ?? '') ?>">
    <label>Beschreibung</label>
    <textarea name="panelDescription" rows="3"><?= esc($tickets['panelDescription'] ?? '') ?></textarea>
    <label>Button-Text</label>
    <input type="text" name="panelButtonLabel" value="<?= esc($tickets['panelButtonLabel'] ?? '') ?>">
    <label>Farbe (Hex)</label>
    <input type="text" name="panelColor" value="<?= esc($tickets['panelColor'] ?? '#667EEA') ?>">
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="panelShowLiveStatus" <?= ($tickets['panelShowLiveStatus'] ?? true) ? 'checked' : '' ?>>
      Live-Status anzeigen
    </label>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="panelShowStaffOnline" <?= ($tickets['panelShowStaffOnline'] ?? true) ? 'checked' : '' ?>>
      Staff-Online anzeigen
    </label>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="panelShowQueue" <?= ($tickets['panelShowQueue'] ?? true) ? 'checked' : '' ?>>
      Warteschlange anzeigen
    </label>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="panelShowRating" <?= ($tickets['panelShowRating'] ?? true) ? 'checked' : '' ?>>
      Bewertung anzeigen
    </label>

    <button type="submit">Speichern</button>
  </form>
</div>

<div class="card">
  <h2>Panels (<?= count($panels) ?>/<?= $panelLimit === -1 || $panelLimit === null ? '∞' : (int)$panelLimit ?>)</h2>
  <?php if ($panels): ?>
    <table>
      <tr><th>Kanal</th><th></th></tr>
      <?php foreach ($panels as $p): ?>
        <tr>
          <td>
            <?php $ch = array_values(array_filter($channels, fn($c) => $c['id'] === $p['channelId'])); ?>
            #<?= esc($ch[0]['name'] ?? $p['channelId']) ?>
          </td>
          <td>
            <form method="post" style="display:inline;">
              <input type="hidden" name="action" value="remove_panel">
              <input type="hidden" name="removeChannelId" value="<?= esc($p['channelId']) ?>">
              <button type="submit" class="btn danger" style="margin:0;padding:6px 12px;font-size:.8rem;">Entfernen</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p style="color:var(--text-secondary);">Noch kein Panel gesendet.</p>
  <?php endif; ?>

  <form method="post" style="margin-top:18px;">
    <input type="hidden" name="action" value="deploy_panel">
    <label>Panel senden an</label>
    <select name="deployChannelId">
      <?php foreach ($channels as $c): ?>
        <option value="<?= esc($c['id']) ?>">#<?= esc($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Panel senden</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
