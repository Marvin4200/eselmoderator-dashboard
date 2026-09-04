<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);

if ($guildId === '') {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<h1>Moderation</h1><div class="card"><p>Kein Server ausgewählt.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

$page = max(1, (int)($_GET['page'] ?? 1));
$typeFilter = trim($_GET['type'] ?? '');
$userFilter = trim($_GET['userId'] ?? '');

$query = ['page' => $page, 'pageSize' => 25];
if ($typeFilter !== '') $query['type'] = $typeFilter;
if ($userFilter !== '') $query['userId'] = $userFilter;

$resp = getAPI('/guilds/' . urlencode($guildId) . '/moderation/cases?' . http_build_query($query));
$cases = $resp['data']['cases'] ?? [];
$total = $resp['data']['total'] ?? 0;
$pageSize = $resp['data']['pageSize'] ?? 25;
$totalPages = max(1, (int)ceil($total / $pageSize));

$TYPE_LABELS = ['warn' => 'Warn', 'timeout' => 'Timeout', 'kick' => 'Kick', 'ban' => 'Ban', 'unban' => 'Unban', 'untimeout' => 'Untimeout'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Moderation – Fall-Historie</h1>

<div class="card">
  <form method="get" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
    <input type="hidden" name="guildId" value="<?= esc($guildId) ?>">
    <div>
      <label>Typ</label>
      <select name="type">
        <option value="">Alle</option>
        <?php foreach ($TYPE_LABELS as $val => $label): ?>
          <option value="<?= $val ?>" <?= $typeFilter === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>User-ID</label>
      <input type="text" name="userId" value="<?= esc($userFilter) ?>" placeholder="Discord-ID">
    </div>
    <button type="submit" style="margin-top:0;">Filtern</button>
  </form>
</div>

<div class="card">
  <h2><?= formatNum($total) ?> Fälle</h2>
  <?php if ($cases): ?>
    <table>
      <tr><th>ID</th><th>Typ</th><th>User</th><th>Moderator</th><th>Grund</th><th>Status</th><th>Datum</th></tr>
      <?php foreach ($cases as $c): ?>
        <tr>
          <td>#<?= (int)$c['id'] ?></td>
          <td><?= esc($TYPE_LABELS[$c['type']] ?? $c['type']) ?></td>
          <td><?= esc($c['user_id']) ?></td>
          <td><?= esc($c['moderator_id']) ?></td>
          <td><?= esc($c['reason'] ?? '–') ?></td>
          <td><?= esc($c['status'] ?? '–') ?></td>
          <td><?= formatDate($c['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php if ($totalPages > 1): ?>
      <div style="margin-top:16px;display:flex;gap:8px;">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a class="btn" style="margin:0;padding:6px 12px;font-size:.8rem;<?= $p === $page ? 'opacity:.5;' : '' ?>"
             href="<?= dashboardPageUrl('moderation', ['page' => $p, 'type' => $typeFilter, 'userId' => $userFilter]) ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <p style="color:var(--text-secondary);">Keine Fälle gefunden.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
