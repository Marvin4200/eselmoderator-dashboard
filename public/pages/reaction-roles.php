<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);
$flash = null;

if ($guildId === '') {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<h1>Reaction-Roles</h1><div class="card"><p>Kein Server ausgewählt.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

function fetchPanels($guildId) {
    $resp = getAPI('/guilds/' . urlencode($guildId) . '/reaction-roles');
    return $resp['data']['panels'] ?? [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_panel';
    $panels = fetchPanels($guildId);

    if ($action === 'save_panel') {
        $panelId = trim($_POST['panelId'] ?? '') ?: ('panel-' . substr(bin2hex(random_bytes(4)), 0, 8));
        $roles = [];
        foreach (explode("\n", $_POST['roles'] ?? '') as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $parts = array_map('trim', explode(':', $line, 3));
            if (empty($parts[0])) continue;
            $roles[] = ['roleId' => $parts[0], 'label' => $parts[1] ?? '', 'emoji' => $parts[2] ?? ''];
        }
        $newPanel = [
            'id' => $panelId,
            'channelId' => trim($_POST['channelId'] ?? ''),
            'title' => $_POST['title'] ?? 'Choose your roles',
            'description' => $_POST['description'] ?? '',
            'mode' => $_POST['mode'] ?? 'buttons',
            'exclusive' => isset($_POST['exclusive']),
            'roles' => $roles,
        ];
        $found = false;
        foreach ($panels as &$p) {
            if ($p['id'] === $panelId) { $p = array_merge($p, $newPanel); $found = true; break; }
        }
        unset($p);
        if (!$found) $panels[] = $newPanel;

        $resp = api('/guilds/' . urlencode($guildId) . '/reaction-roles', 'POST', ['panels' => $panels]);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Panel gespeichert.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Speichern fehlgeschlagen.'];
    } elseif ($action === 'delete_panel') {
        $panelId = $_POST['panelId'] ?? '';
        $panels = array_values(array_filter($panels, fn($p) => $p['id'] !== $panelId));
        $resp = api('/guilds/' . urlencode($guildId) . '/reaction-roles', 'POST', ['panels' => $panels]);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Panel gelöscht.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Löschen fehlgeschlagen.'];
    } elseif ($action === 'send_panel') {
        $panelId = $_POST['panelId'] ?? '';
        $resp = api('/guilds/' . urlencode($guildId) . '/reaction-roles/send', 'POST', ['panelId' => $panelId]);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Panel gesendet.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Senden fehlgeschlagen.'];
    }
}

$panels = fetchPanels($guildId);
$editId = $_GET['edit'] ?? '';
$editing = null;
foreach ($panels as $p) if ($p['id'] === $editId) { $editing = $p; break; }

$contextResp = getAPI('/guilds/' . urlencode($guildId) . '/context');
$channels = $contextResp['data']['channels'] ?? [];
$roles = $contextResp['data']['roles'] ?? [];

$rolesText = '';
if ($editing) {
    foreach (($editing['roles'] ?? []) as $r) $rolesText .= $r['roleId'] . ':' . $r['label'] . ':' . $r['emoji'] . "\n";
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Reaction-Roles</h1>

<?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= esc($flash['text']) ?></div><?php endif; ?>

<div class="card">
  <h2>Panels</h2>
  <?php if ($panels): ?>
    <table>
      <tr><th>Titel</th><th>Modus</th><th>Rollen</th><th></th></tr>
      <?php foreach ($panels as $p): ?>
        <tr>
          <td><?= esc($p['title']) ?></td>
          <td><?= $p['mode'] === 'select' ? 'Auswahlmenü' : 'Buttons' ?></td>
          <td><?= count($p['roles'] ?? []) ?></td>
          <td style="display:flex;gap:6px;">
            <a class="btn" style="margin:0;padding:6px 12px;font-size:.8rem;" href="<?= dashboardPageUrl('reaction-roles', ['edit' => $p['id']]) ?>">Bearbeiten</a>
            <form method="post" style="display:inline;">
              <input type="hidden" name="action" value="send_panel">
              <input type="hidden" name="panelId" value="<?= esc($p['id']) ?>">
              <button type="submit" style="margin:0;padding:6px 12px;font-size:.8rem;">Senden</button>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('Panel wirklich löschen?')">
              <input type="hidden" name="action" value="delete_panel">
              <input type="hidden" name="panelId" value="<?= esc($p['id']) ?>">
              <button type="submit" class="btn danger" style="margin:0;padding:6px 12px;font-size:.8rem;">Löschen</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p style="color:var(--text-secondary);">Noch kein Panel angelegt.</p>
  <?php endif; ?>
</div>

<div class="card">
  <h2><?= $editing ? 'Panel bearbeiten' : 'Neues Panel' ?></h2>
  <form method="post">
    <input type="hidden" name="action" value="save_panel">
    <input type="hidden" name="panelId" value="<?= esc($editing['id'] ?? '') ?>">

    <label>Titel</label>
    <input type="text" name="title" value="<?= esc($editing['title'] ?? 'Choose your roles') ?>">
    <label>Beschreibung</label>
    <textarea name="description" rows="3"><?= esc($editing['description'] ?? '') ?></textarea>
    <label>Kanal</label>
    <select name="channelId">
      <option value="">– keiner –</option>
      <?php foreach ($channels as $c): ?>
        <option value="<?= esc($c['id']) ?>" <?= ($editing['channelId'] ?? '') === $c['id'] ? 'selected' : '' ?>>#<?= esc($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Modus</label>
    <select name="mode">
      <option value="buttons" <?= ($editing['mode'] ?? 'buttons') === 'buttons' ? 'selected' : '' ?>>Buttons (max. 5 Rollen)</option>
      <option value="select" <?= ($editing['mode'] ?? 'buttons') === 'select' ? 'selected' : '' ?>>Auswahlmenü (max. 25 Rollen)</option>
    </select>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="exclusive" <?= !empty($editing['exclusive']) ? 'checked' : '' ?>>
      Nur eine Rolle gleichzeitig (exklusiv)
    </label>
    <label>Rollen (ein Eintrag pro Zeile, Format <code>roleId:Label:Emoji</code>)</label>
    <textarea name="roles" rows="6" placeholder="123456789012345678:Gamer:🎮"><?= esc($rolesText) ?></textarea>
    <?php if ($roles): ?>
      <p style="color:var(--text-secondary);font-size:.82rem;margin-top:6px;">
        Verfügbare Rollen-IDs:
        <?php foreach (array_slice($roles, 0, 15) as $r): ?>
          <?= esc($r['name']) ?>=<code><?= esc($r['id']) ?></code>&nbsp;
        <?php endforeach; ?>
      </p>
    <?php endif; ?>

    <button type="submit">Speichern</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
