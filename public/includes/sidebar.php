<?php
$__current = currentPage();
$__navItems = [
    ['page' => 'portal', 'label' => '🏠 Übersicht'],
    ['page' => 'modules', 'label' => '🧩 Module'],
    ['page' => 'moderation', 'label' => '🛡️ Moderation'],
    ['page' => 'automod', 'label' => '🚫 AutoMod'],
    ['page' => 'welcome', 'label' => '👋 Willkommen'],
    ['page' => 'reaction-roles', 'label' => '🎭 Reaction-Roles'],
    ['page' => 'leveling', 'label' => '📈 Leveling'],
    ['page' => 'temp-voice', 'label' => '🔊 Temp-Voice'],
    ['page' => 'tickets', 'label' => '🎫 Tickets'],
    ['page' => 'premium-info', 'label' => '⭐ Premium'],
];
?>
<aside class="sidebar">
  <nav>
    <?php foreach ($__navItems as $item): ?>
      <a class="nav-link <?= $__current === $item['page'] ? 'active' : '' ?>"
         href="<?= dashboardPageUrl($item['page']) ?>"><?= $item['label'] ?></a>
    <?php endforeach; ?>
  </nav>
</aside>
<main class="content">
