<header class="topbar">
    <button class="menu-btn" type="button" aria-label="Buka menu" onclick="document.body.classList.toggle('menu-open')">☰</button>
    <div class="topbar-title">
        <span class="eyebrow">Sistem Informasi Tahfidz</span>
        <h1><?= e($pageMeta['title']) ?></h1>
    </div>
    <div class="profile">
        <div class="avatar"><?= e(strtoupper(substr(user()['name'], 0, 1))) ?></div>
        <div><b><?= e(user()['name']) ?></b><span><?= e(ucfirst($role)) ?></span></div>
    </div>
</header>
