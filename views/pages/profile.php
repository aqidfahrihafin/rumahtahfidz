<?php $account = user(); ?>
<section class="page-intro">
    <div><p class="eyebrow green">Akun</p><h2>Profil Saya</h2><p>Perbarui informasi pribadi dan keamanan akun Anda.</p></div>
</section>

<div class="profile-layout">
    <aside class="profile-summary card">
        <div class="profile-avatar-large"><?= e(strtoupper(substr($account['name'], 0, 1))) ?></div>
        <h3><?= e($account['name']) ?></h3>
        <span class="role-pill"><?= e(ucfirst($account['role'])) ?></span>
        <dl class="profile-facts">
            <div><dt>Email</dt><dd><?= e($account['email']) ?></dd></div>
            <div><dt>Nomor telepon</dt><dd><?= e($account['phone'] ?: '-') ?></dd></div>
            <div><dt>Status akun</dt><dd><span class="badge active-account">Aktif</span></dd></div>
        </dl>
    </aside>

    <div class="profile-forms">
        <section class="card form-card">
            <div class="section-heading"><div><h3>Informasi pribadi</h3><p>Informasi ini digunakan pada profil dan laporan.</p></div></div>
            <form method="post" class="form-grid profile-form">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <input type="hidden" name="action" value="update_profile">
                <label>Nama lengkap<input type="text" name="name" value="<?= e($account['name']) ?>" required></label>
                <label>Email<input type="email" name="email" value="<?= e($account['email']) ?>" required></label>
                <label class="full">Nomor telepon<input type="text" name="phone" value="<?= e($account['phone']) ?>" placeholder="08xxxxxxxxxx"></label>
                <div class="form-actions full"><button class="btn primary" type="submit">Simpan Perubahan</button></div>
            </form>
        </section>

        <section class="card form-card">
            <div class="section-heading"><div><h3>Ubah kata sandi</h3><p>Gunakan minimal 8 karakter dan jangan bagikan kepada orang lain.</p></div></div>
            <form method="post" class="form-grid profile-form">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <input type="hidden" name="action" value="change_password">
                <label class="full">Kata sandi saat ini<input type="password" name="current_password" required autocomplete="current-password"></label>
                <label>Kata sandi baru<input type="password" name="new_password" minlength="8" required autocomplete="new-password"></label>
                <label>Ulangi kata sandi baru<input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password"></label>
                <div class="form-actions full"><button class="btn primary" type="submit">Ubah Kata Sandi</button></div>
            </form>
        </section>
    </div>
</div>
