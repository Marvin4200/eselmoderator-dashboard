<?php
require __DIR__ . '/includes/config.php';
requireLogin();

$guildId = preg_replace('/[^0-9]/', '', $_GET['guildId'] ?? '');
$backupId = (int)($_GET['backupId'] ?? 0);
$type = $_GET['type'] ?? '';

if ($type !== 'backup' || $guildId === '' || $backupId < 1) {
    http_response_code(400);
    exit('Invalid request');
}

$guilds = getUserGuilds();
$allowed = isOwner() || isServerAdmin($guildId);
if (!$allowed) {
    http_response_code(403);
    exit('No access');
}

$ch = curl_init(API_BASE . '/guilds/' . urlencode($guildId) . '/discord-backups/' . $backupId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, dashboardHeaders(false));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$body = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status !== 200) {
    http_response_code($status ?: 502);
    exit('Download failed');
}

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="discord-backup-' . $guildId . '-' . $backupId . '.json"');
echo $body;
