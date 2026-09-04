<?php
require __DIR__ . '/../includes/config.php';
requireLogin();

$guilds = getUserGuilds();
$guildId = dashboardSelectedGuildId($guilds);
$flash = null;

if ($guildId === '') {
    include __DIR__ . '/../includes/header.php';
    include __DIR__ . '/../includes/sidebar.php';
    echo '<h1>AutoMod</h1><div class="card"><p>Kein Server ausgewählt.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit();
}

$RULE_ACTIONS = ['fallback' => 'Standard', 'none' => 'Nichts', 'delete' => 'Löschen', 'warn' => 'Verwarnen', 'timeout' => 'Timeout', 'kick' => 'Kick', 'ban' => 'Ban'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'blockInvites' => isset($_POST['blockInvites']),
        'blockLinks' => isset($_POST['blockLinks']),
        'blockMassMentions' => isset($_POST['blockMassMentions']),
        'blockCaps' => isset($_POST['blockCaps']),
        'blockSpam' => isset($_POST['blockSpam']),
        'blockRepeatedText' => isset($_POST['blockRepeatedText']),
        'deleteMessage' => isset($_POST['deleteMessage']),
        'warnUser' => isset($_POST['warnUser']),
        'exemptAdmins' => isset($_POST['exemptAdmins']),
        'mentionLimit' => (int)($_POST['mentionLimit'] ?? 6),
        'capsMinLength' => (int)($_POST['capsMinLength'] ?? 12),
        'capsPercent' => (int)($_POST['capsPercent'] ?? 70),
        'duplicateThreshold' => (int)($_POST['duplicateThreshold'] ?? 4),
        'duplicateWindowSeconds' => (int)($_POST['duplicateWindowSeconds'] ?? 20),
        'autoPunishStrikes' => (int)($_POST['autoPunishStrikes'] ?? 0),
        'timeoutMinutes' => (int)($_POST['timeoutMinutes'] ?? 10),
        'punishmentAction' => $_POST['punishmentAction'] ?? 'timeout',
        'punishmentMode' => $_POST['punishmentMode'] ?? 'fixed',
        'warnMessage' => $_POST['warnMessage'] ?? '',
        'blockedTermsWholeWord' => isset($_POST['blockedTermsWholeWord']),
        'blockedTermsRegex' => isset($_POST['blockedTermsRegex']),
        'blockedTerms' => array_values(array_filter(array_map('trim', explode("\n", $_POST['blockedTerms'] ?? '')))),
        'ruleActions' => [
            'invite' => $_POST['ruleAction_invite'] ?? 'fallback',
            'link' => $_POST['ruleAction_link'] ?? 'fallback',
            'blocked_term' => $_POST['ruleAction_blocked_term'] ?? 'fallback',
            'mass_mentions' => $_POST['ruleAction_mass_mentions'] ?? 'fallback',
            'caps' => $_POST['ruleAction_caps'] ?? 'fallback',
            'repeated_text' => $_POST['ruleAction_repeated_text'] ?? 'fallback',
            'message_spam' => $_POST['ruleAction_message_spam'] ?? 'fallback',
        ],
    ];
    $resp = api('/guilds/' . urlencode($guildId) . '/automod', 'POST', $payload);
    $flash = !empty($resp['data']['success'])
        ? ['type' => 'success', 'text' => 'AutoMod-Einstellungen gespeichert.']
        : ['type' => 'error', 'text' => $resp['data']['message'] ?? 'Speichern fehlgeschlagen.'];
}

$resp = getAPI('/guilds/' . urlencode($guildId) . '/automod');
$a = $resp['data']['automod'] ?? [];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>AutoMod</h1>

<?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= esc($flash['text']) ?></div><?php endif; ?>

<form method="post">
  <div class="card">
    <h2>Regeln</h2>
    <?php
    $toggles = [
        'blockInvites' => 'Discord-Invites blocken',
        'blockLinks' => 'Links blocken',
        'blockMassMentions' => 'Massen-Erwähnungen blocken',
        'blockCaps' => 'Übermäßige Großschreibung blocken',
        'blockSpam' => 'Nachrichten-Spam blocken',
        'blockRepeatedText' => 'Wiederholten Text blocken',
        'deleteMessage' => 'Verstoßende Nachricht löschen',
        'warnUser' => 'Nutzer per DM warnen',
        'exemptAdmins' => 'Admins ausnehmen',
    ];
    foreach ($toggles as $key => $label): ?>
      <label style="display:flex;align-items:center;gap:10px;">
        <input type="checkbox" name="<?= $key ?>" <?= !empty($a[$key]) ? 'checked' : '' ?>>
        <?= esc($label) ?>
      </label>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>Schwellwerte</h2>
    <label>Max. Erwähnungen pro Nachricht</label>
    <input type="number" name="mentionLimit" min="2" max="25" value="<?= (int)($a['mentionLimit'] ?? 6) ?>">
    <label>Mindestlänge für Caps-Check</label>
    <input type="number" name="capsMinLength" min="8" max="200" value="<?= (int)($a['capsMinLength'] ?? 12) ?>">
    <label>Caps-Anteil in % ab dem geblockt wird</label>
    <input type="number" name="capsPercent" min="50" max="100" value="<?= (int)($a['capsPercent'] ?? 70) ?>">
    <label>Duplikate-Schwelle (Nachrichten)</label>
    <input type="number" name="duplicateThreshold" min="2" max="10" value="<?= (int)($a['duplicateThreshold'] ?? 4) ?>">
    <label>Duplikate-Zeitfenster (Sekunden)</label>
    <input type="number" name="duplicateWindowSeconds" min="5" max="300" value="<?= (int)($a['duplicateWindowSeconds'] ?? 20) ?>">
  </div>

  <div class="card">
    <h2>Strafen</h2>
    <label>Automatische Strafe ab X Strikes (0 = aus)</label>
    <input type="number" name="autoPunishStrikes" min="0" max="20" value="<?= (int)($a['autoPunishStrikes'] ?? 0) ?>">
    <label>Timeout-Dauer (Minuten)</label>
    <input type="number" name="timeoutMinutes" min="1" max="40320" value="<?= (int)($a['timeoutMinutes'] ?? 10) ?>">
    <label>Strafaktion</label>
    <select name="punishmentAction">
      <?php foreach (['none' => 'Keine', 'timeout' => 'Timeout', 'kick' => 'Kick', 'ban' => 'Ban'] as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($a['punishmentAction'] ?? 'timeout') === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <label>Strafmodus</label>
    <select name="punishmentMode">
      <option value="fixed" <?= ($a['punishmentMode'] ?? 'fixed') === 'fixed' ? 'selected' : '' ?>>Fest</option>
      <option value="escalate" <?= ($a['punishmentMode'] ?? 'fixed') === 'escalate' ? 'selected' : '' ?>>Eskalierend</option>
    </select>
    <label>Warn-Nachricht ({reason} wird ersetzt)</label>
    <input type="text" name="warnMessage" value="<?= esc($a['warnMessage'] ?? 'AutoMod blocked your message: {reason}') ?>">
  </div>

  <div class="card">
    <h2>Verbotene Begriffe</h2>
    <label>Ein Begriff pro Zeile</label>
    <textarea name="blockedTerms" rows="6"><?= esc(implode("\n", $a['blockedTerms'] ?? [])) ?></textarea>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="blockedTermsWholeWord" <?= !empty($a['blockedTermsWholeWord']) ? 'checked' : '' ?>>
      Nur ganze Wörter
    </label>
    <label style="display:flex;align-items:center;gap:10px;">
      <input type="checkbox" name="blockedTermsRegex" <?= !empty($a['blockedTermsRegex']) ? 'checked' : '' ?>>
      Als Regex behandeln
    </label>
  </div>

  <div class="card">
    <h2>Aktion pro Regel</h2>
    <?php
    $ruleLabels = [
        'invite' => 'Invites', 'link' => 'Links', 'blocked_term' => 'Verbotene Begriffe',
        'mass_mentions' => 'Massen-Erwähnungen', 'caps' => 'Großschreibung',
        'repeated_text' => 'Wiederholter Text', 'message_spam' => 'Nachrichten-Spam',
    ];
    foreach ($ruleLabels as $key => $label): ?>
      <label><?= esc($label) ?></label>
      <select name="ruleAction_<?= $key ?>">
        <?php foreach ($RULE_ACTIONS as $val => $actionLabel): ?>
          <option value="<?= $val ?>" <?= ($a['ruleActions'][$key] ?? 'fallback') === $val ? 'selected' : '' ?>><?= $actionLabel ?></option>
        <?php endforeach; ?>
      </select>
    <?php endforeach; ?>
  </div>

  <button type="submit">Speichern</button>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
