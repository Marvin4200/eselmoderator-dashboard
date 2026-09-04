<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);

// AJAX: Job-Status-Proxy fuer das Live-Polling per JS (kein volles Reload noetig).
if (isset($_GET['ajax_job']) && $guildId !== '') {
    header('Content-Type: application/json');
    $jobId = (int)$_GET['ajax_job'];
    $type = ($_GET['type'] ?? 'backup') === 'restore' ? 'restore' : 'backup';
    $endpoint = $type === 'restore'
        ? '/guilds/' . urlencode($guildId) . '/restore-jobs/' . $jobId
        : '/guilds/' . urlencode($guildId) . '/backup-jobs/' . $jobId;
    echo json_encode(getAPI($endpoint, 10));
    exit();
}

if ($guildId === '') {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<h1>Server-Backup</h1><div class="card"><p>Kein Server ausgewählt.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

$flash = null;
$activeJob = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_schedule') {
        $payload = [
            'enabled' => isset($_POST['enabled']),
            'intervalHours' => (int)($_POST['intervalHours'] ?? 24),
            'retentionCount' => (int)($_POST['retentionCount'] ?? 10),
            'backupMode' => in_array($_POST['backupMode'] ?? 'full', ['full', 'incremental'], true) ? $_POST['backupMode'] : 'full',
        ];
        $resp = api('/guilds/' . urlencode($guildId) . '/discord-backups/schedule', 'POST', $payload);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Zeitplan gespeichert.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Speichern fehlgeschlagen.'];
    } elseif ($action === 'create_backup') {
        $resp = api('/guilds/' . urlencode($guildId) . '/discord-backups/create', 'POST', []);
        if (!empty($resp['data']['success'])) {
            $activeJob = ['id' => $resp['data']['data']['jobId'], 'type' => 'backup'];
        } else {
            $flash = ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Backup konnte nicht gestartet werden.'];
        }
    } elseif ($action === 'delete_backup') {
        $backupId = (int)($_POST['backupId'] ?? 0);
        $resp = api('/guilds/' . urlencode($guildId) . '/discord-backups/' . $backupId, 'DELETE', null);
        $flash = !empty($resp['data']['success'])
            ? ['type' => 'success', 'text' => 'Backup gelöscht.']
            : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Löschen fehlgeschlagen.'];
    } elseif ($action === 'restore_backup') {
        $backupId = (int)($_POST['backupId'] ?? 0);
        $payload = [
            'backupId' => $backupId,
            'options' => [
                'roles' => isset($_POST['optRoles']),
                'channels' => isset($_POST['optChannels']),
                'settings' => isset($_POST['optSettings']),
                'emojis' => isset($_POST['optEmojis']),
                'messages' => isset($_POST['optMessages']),
                'autoVerify' => true,
                'wipeExisting' => isset($_POST['optWipe']),
            ],
        ];
        $resp = api('/guilds/' . urlencode($guildId) . '/discord-backups/restore', 'POST', $payload);
        if (!empty($resp['data']['success'])) {
            $activeJob = ['id' => $resp['data']['data']['jobId'], 'type' => 'restore'];
        } else {
            $flash = ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Restore konnte nicht gestartet werden.'];
        }
    }
}

$scheduleResp = getAPI('/guilds/' . urlencode($guildId) . '/discord-backups/schedule');
$schedule = $scheduleResp['data'] ?? [];

$backupsResp = getAPI('/guilds/' . urlencode($guildId) . '/discord-backups');
$backups = $backupsResp['data']['backups'] ?? [];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Server-Backup</h1>
<p class="subtitle">Sichert die Struktur deines Servers — Rollen, Kanäle, Berechtigungen, Emojis und mehr — und kann sie bei Bedarf wiederherstellen.</p>

<?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= esc($flash['text']) ?></div><?php endif; ?>

<?php if ($activeJob): ?>
<div class="card" id="job-progress-card">
  <h2 id="job-phase">Wird gestartet…</h2>
  <div style="background:var(--surface-2);border-radius:999px;height:10px;overflow:hidden;margin:10px 0;">
    <div id="job-bar" style="background:var(--accent-grad);height:100%;width:0%;transition:width .3s;"></div>
  </div>
  <pre id="job-log" style="max-height:200px;overflow-y:auto;font-size:.78rem;color:var(--text-secondary);white-space:pre-wrap;margin:0;"></pre>
</div>
<script>
(function() {
  const jobId = <?= (int)$activeJob['id'] ?>;
  const type = <?= json_encode($activeJob['type']) ?>;
  const phaseEl = document.getElementById('job-phase');
  const barEl = document.getElementById('job-bar');
  const logEl = document.getElementById('job-log');
  function poll() {
    fetch('?ajax_job=' + jobId + '&type=' + type + '&guildId=<?= urlencode($guildId) ?>')
      .then(r => r.json())
      .then(res => {
        const d = res.data || {};
        phaseEl.textContent = (type === 'restore' ? '🔄 ' : '💾 ') + (d.phase || d.status || '…');
        const pct = d.progressTotal ? Math.round((d.progressCurrent / d.progressTotal) * 100) : 0;
        barEl.style.width = pct + '%';
        logEl.textContent = d.log || '';
        logEl.scrollTop = logEl.scrollHeight;
        if (d.status === 'running') {
          setTimeout(poll, 2000);
        } else {
          setTimeout(() => location.reload(), 1500);
        }
      })
      .catch(() => setTimeout(poll, 3000));
  }
  poll();
})();
</script>
<?php endif; ?>

<div class="card">
  <h2>Zeitplan</h2>
  <form method="post">
    <input type="hidden" name="action" value="save_schedule">
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="enabled" <?= !empty($schedule['enabled']) ? 'checked' : '' ?>>
      Automatische Backups aktiv
    </label>
    <label>Intervall (Stunden)</label>
    <input type="number" name="intervalHours" min="1" max="720" value="<?= (int)($schedule['intervalHours'] ?? 24) ?>">
    <label>Aufbewahrung (Anzahl Backups)</label>
    <input type="number" name="retentionCount" min="1" max="100" value="<?= (int)($schedule['retentionCount'] ?? 10) ?>">
    <label>Modus</label>
    <select name="backupMode">
      <option value="full" <?= ($schedule['backupMode'] ?? 'full') === 'full' ? 'selected' : '' ?>>Voll (immer alles)</option>
      <option value="incremental" <?= ($schedule['backupMode'] ?? 'full') === 'incremental' ? 'selected' : '' ?>>Inkrementell (nur Änderungen)</option>
    </select>
    <button type="submit">Zeitplan speichern</button>
  </form>
</div>

<div class="card">
  <h2>Backups (<?= count($backups) ?>)</h2>
  <?php if ($backups): ?>
    <table>
      <tr><th>ID</th><th>Erstellt</th><th>Modus</th><th>Rollen</th><th>Channels</th><th>Nachrichten</th><th></th></tr>
      <?php foreach ($backups as $b): ?>
        <tr>
          <td>#<?= (int)$b['id'] ?></td>
          <td><?= formatDate(date('Y-m-d H:i:s', (int)($b['createdAt'] / 1000))) ?></td>
          <td><?= esc($b['backupMode'] ?? 'full') ?></td>
          <td><?= (int)($b['stats']['roles'] ?? 0) ?></td>
          <td><?= (int)($b['stats']['channels'] ?? 0) ?></td>
          <td><?= formatNum($b['stats']['messages'] ?? 0) ?></td>
          <td style="display:flex;gap:6px;">
            <a class="btn" style="margin:0;padding:6px 12px;font-size:.8rem;" href="<?= BASE_URL ?>/api-download.php?type=backup&guildId=<?= urlencode($guildId) ?>&backupId=<?= (int)$b['id'] ?>">Download</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('Diesen Server-Stand jetzt wiederherstellen? Bestehende Kanäle/Rollen können dabei überschrieben werden.')">
              <input type="hidden" name="action" value="restore_backup">
              <input type="hidden" name="backupId" value="<?= (int)$b['id'] ?>">
              <input type="hidden" name="optRoles" value="1">
              <input type="hidden" name="optChannels" value="1">
              <input type="hidden" name="optMessages" value="1">
              <button type="submit" style="margin:0;padding:6px 12px;font-size:.8rem;">Wiederherstellen</button>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('Backup #<?= (int)$b['id'] ?> wirklich löschen?')">
              <input type="hidden" name="action" value="delete_backup">
              <input type="hidden" name="backupId" value="<?= (int)$b['id'] ?>">
              <button type="submit" class="btn danger" style="margin:0;padding:6px 12px;font-size:.8rem;">Löschen</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p style="color:var(--text-secondary);">Noch kein Backup vorhanden.</p>
  <?php endif; ?>

  <form method="post" style="margin-top:18px;">
    <input type="hidden" name="action" value="create_backup">
    <button type="submit">Jetzt Backup erstellen</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
