<?php $navigation = allowed_pages($role); ?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-icon"><img src="assets/images/logo.jpeg" alt="Logo As-Sakinah"></div>
        <div><b>Rumah Tahfidz</b><span>As-Sakinah</span></div>
    </div>
    <div class="brand-note">Monitoring Tahfidz Al-Qur'an</div>

    <nav aria-label="Navigasi utama">
        <?php foreach ($navigation as $navPage): ?>
            <?php if ($navPage === 'teachers'): ?><span class="nav-label">Pengguna</span><?php endif; ?>
            <?php if ($navPage === 'students'): ?><span class="nav-label">Master Data</span><?php endif; ?>
            <?php if ($navPage === 'assessments'): ?><span class="nav-label">Monitoring</span><?php endif; ?>
            <?php if ($navPage === 'profile'): ?><span class="nav-label">Akun</span><?php endif; ?>
            <a href="index.php?page=<?= e($navPage) ?>" class="<?= $page === $navPage || ($page === 'history' && isset($historyBackPage) && $historyBackPage === $navPage) ? 'active' : '' ?>">
                <i><?= $pages[$navPage]['icon'] ?></i>
                <span><?= e($pages[$navPage]['title']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-foot">
        <a href="index.php?page=logout"><i>↪</i><span>Keluar</span></a>
    </div>
</aside>
