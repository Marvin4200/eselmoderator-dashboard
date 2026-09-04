<?php
$__current = currentPage();

function __navIcon($name) {
    $icons = [
        'home' => '<path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V9.5Z"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'shield' => '<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>',
        'ban' => '<circle cx="12" cy="12" r="9"/><line x1="5.5" y1="5.5" x2="18.5" y2="18.5"/>',
        'wave' => '<path d="M8 13V7a2 2 0 1 1 4 0"/><path d="M12 13V5a2 2 0 1 1 4 0v8"/><path d="M16 13V8a2 2 0 1 1 4 0v6c0 4-3 7-7 7h-1a6 6 0 0 1-5-2.7L4.2 17a1.7 1.7 0 0 1 2.6-2.2L8 16"/>',
        'smile' => '<circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>',
        'trend' => '<polyline points="3 17 9 11 13 15 21 6"/><polyline points="14 6 21 6 21 13"/>',
        'volume' => '<polygon points="4 9 8 9 12 5 12 19 8 15 4 15 4 9"/><path d="M16 8a5 5 0 0 1 0 8"/>',
        'ticket' => '<path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8Z"/>',
        'broadcast' => '<circle cx="12" cy="12" r="2"/><path d="M8.5 8.5a5 5 0 0 0 0 7"/><path d="M15.5 8.5a5 5 0 0 0 0 7"/><path d="M5.5 5.5a9 9 0 0 0 0 13"/><path d="M18.5 5.5a9 9 0 0 0 0 13"/>',
        'gift' => '<rect x="3" y="9" width="18" height="12" rx="1"/><path d="M3 9v12"/><path d="M12 9v12"/><path d="M12 9c-1.5-4-6-4.5-6-1.5S9 9 12 9Z"/><path d="M12 9c1.5-4 6-4.5 6-1.5S15 9 12 9Z"/>',
        'archive' => '<rect x="3" y="4" width="18" height="5" rx="1"/><path d="M4 9v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9"/><path d="M10 13h4"/>',
        'star' => '<polygon points="12 2 15.1 8.6 22 9.3 17 14.1 18.2 21 12 17.6 5.8 21 7 14.1 2 9.3 8.9 8.6 12 2"/>',
    ];
    $path = $icons[$name] ?? '';
    return '<svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;">' . $path . '</svg>';
}

$__navItems = [
    ['page' => 'portal', 'label' => 'Übersicht', 'icon' => 'home'],
    ['page' => 'modules', 'label' => 'Module', 'icon' => 'grid'],
    ['page' => 'moderation', 'label' => 'Moderation', 'icon' => 'shield'],
    ['page' => 'automod', 'label' => 'AutoMod', 'icon' => 'ban'],
    ['page' => 'welcome', 'label' => 'Willkommen', 'icon' => 'wave'],
    ['page' => 'reaction-roles', 'label' => 'Reaction-Roles', 'icon' => 'smile'],
    ['page' => 'leveling', 'label' => 'Leveling', 'icon' => 'trend'],
    ['page' => 'temp-voice', 'label' => 'Temp-Voice', 'icon' => 'volume'],
    ['page' => 'tickets', 'label' => 'Tickets', 'icon' => 'ticket'],
    ['page' => 'social', 'label' => 'Social Alerts', 'icon' => 'broadcast'],
    ['page' => 'freegames', 'label' => 'Freegames', 'icon' => 'gift'],
    ['page' => 'server-backup', 'label' => 'Server-Backup', 'icon' => 'archive'],
];
?>
<aside class="sidebar">
  <nav>
    <?php foreach ($__navItems as $item): ?>
      <a class="nav-link <?= $__current === $item['page'] ? 'active' : '' ?>" style="display:flex;align-items:center;gap:11px;"
         href="<?= dashboardPageUrl($item['page']) ?>"><?= __navIcon($item['icon']) ?><span><?= esc($item['label']) ?></span></a>
    <?php endforeach; ?>
    <div style="height:1px;background:var(--border);margin:12px 4px;"></div>
    <a class="nav-link <?= $__current === 'premium-info' ? 'active' : '' ?>" style="display:flex;align-items:center;gap:11px;"
       href="<?= dashboardPageUrl('premium-info') ?>"><?= __navIcon('star') ?><span>Premium</span></a>
  </nav>
</aside>
<main class="content">
