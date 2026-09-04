<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);

$premium = null;
if ($guildId !== '') {
    $resp = getAPI('/guilds/' . urlencode($guildId) . '/premium');
    if (!empty($resp['success'])) $premium = $resp['data'];
}

$TIER_LABELS = ['free' => 'Kostenlos', 'basic' => 'Basic', 'pro' => 'Pro'];
$LIMIT_LABELS = [
    'ticketPanels' => 'Ticket-Panels',
    'reactionRolePanels' => 'Reaction-Role-Panels',
    'automodRules' => 'AutoMod-Regeln',
    'logGroups' => 'Log-Gruppen',
    'welcomeMessages' => 'Willkommensnachrichten',
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>Premium</h1>

<?php if ($guildId === ''): ?>
  <div class="card"><p>Kein Server ausgewählt.</p></div>
<?php else: ?>
  <div class="card">
    <h2>Aktueller Tier: <?= esc($TIER_LABELS[$premium['tier'] ?? 'free'] ?? ($premium['tier'] ?? 'free')) ?></h2>
    <?php if (($premium['tier'] ?? 'free') === 'free'): ?>
      <p>Hol dir mehr Limits und Funktionen für diesen Server.</p>
      <a class="btn" href="https://shop.eselbande.com" target="_blank" rel="noopener">Im Shop ansehen →</a>
    <?php else: ?>
      <p>Vielen Dank für die Unterstützung! 🎉</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Limits</h2>
    <table>
      <tr><th>Funktion</th><th>Limit</th></tr>
      <?php foreach ($LIMIT_LABELS as $key => $label): ?>
        <?php $val = $premium['featureLimits'][$key] ?? null; ?>
        <tr>
          <td><?= esc($label) ?></td>
          <td><?= ($val === -1 || $val === null) ? 'Unbegrenzt' : formatNum($val) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
