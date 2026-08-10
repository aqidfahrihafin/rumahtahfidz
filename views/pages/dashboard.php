<?php
$dashboardTeacherId = $role === 'ustadzah' ? (int) (scalar('SELECT id FROM teachers WHERE user_id = ?', array(user()['id'])) ?: 0) : 0;
if ($role === 'admin') {
    $cards = array(
        array('Ustadzah', scalar('SELECT COUNT(*) FROM teachers'), '♚'),
        array('Santri', scalar('SELECT COUNT(*) FROM students'), '♙'),
        array('Halaqoh', scalar('SELECT COUNT(*) FROM halaqoh'), '◉'),
        array('Penilaian', scalar('SELECT COUNT(*) FROM assessments'), '▣'),
    );
} elseif ($role === 'ustadzah') {
    $cards = array(
        array('Santri Bimbingan', scalar('SELECT COUNT(*) FROM students s JOIN halaqoh h ON h.id = s.halaqoh_id WHERE h.teacher_id = ?', array($dashboardTeacherId)), '♙'),
        array('Setoran Tercatat', scalar('SELECT COUNT(*) FROM assessments WHERE teacher_id = ?', array($dashboardTeacherId)), '▣'),
        array('Rata-rata Nilai', round((float) scalar('SELECT AVG(memorization + murojaah) FROM assessments WHERE teacher_id = ?', array($dashboardTeacherId)), 1), '★'),
    );
} else {
    $guardianStudents = rows('SELECT s.*, h.name AS halaqoh FROM students s LEFT JOIN halaqoh h ON h.id = s.halaqoh_id WHERE s.guardian_user_id = ? ORDER BY s.name', array(user()['id']));
    $requestedStudentId = (int) ($_GET['student_id'] ?? 0);
    $student = null;
    foreach ($guardianStudents as $guardianStudent) {
        if ((int) $guardianStudent['id'] === $requestedStudentId) $student = $guardianStudent;
    }
    if (!$student && $guardianStudents) $student = $guardianStudents[0];
    $studentId = $student ? (int) $student['id'] : 0;
    $guardianTransfers = rows('SELECT sh.*, hf.name AS from_halaqoh, ht.name AS to_halaqoh FROM student_halaqoh_history sh LEFT JOIN halaqoh hf ON hf.id = sh.from_halaqoh_id LEFT JOIN halaqoh ht ON ht.id = sh.to_halaqoh_id WHERE sh.student_id = ? ORDER BY sh.transfer_date DESC, sh.id DESC LIMIT 5', array($studentId));
    $latest = row('SELECT * FROM assessments WHERE student_id = ? ORDER BY date DESC LIMIT 1', array($studentId));
    $cards = array(
        array('Halaqoh', $student ? $student['halaqoh'] : '-', '◉'),
        array('Setoran Terakhir', $latest ? $latest['surah'] . ' ' . $latest['verse_range'] : '-', '☾'),
        array('Rata-rata Nilai', scalar('SELECT COUNT(*) FROM assessments WHERE student_id = ?', array($studentId)) ? round((float) scalar('SELECT AVG(memorization + murojaah) FROM assessments WHERE student_id = ?', array($studentId)), 1) : '-', '★'),
    );
}

$recentWhere = '';
if ($role === 'wali') {
    $recentWhere = 'WHERE s.guardian_user_id = ' . (int) user()['id'] . ' AND s.id = ' . (int) $studentId;
} elseif ($role === 'ustadzah') {
    $recentWhere = 'WHERE a.teacher_id = ' . $dashboardTeacherId;
}
$recent = rows('SELECT a.*, s.name AS student, h.name AS halaqoh FROM assessments a JOIN students s ON s.id = a.student_id JOIN halaqoh h ON h.id = s.halaqoh_id ' . $recentWhere . ' ORDER BY a.date DESC LIMIT 5');
$progress = rows('SELECT s.name AS student, h.name AS halaqoh, ROUND(AVG(a.memorization + a.murojaah), 1) AS score, COUNT(a.id) AS assessment_count, MAX(a.date) AS latest_date FROM assessments a JOIN students s ON s.id = a.student_id JOIN halaqoh h ON h.id = s.halaqoh_id ' . $recentWhere . ' GROUP BY s.id, s.name, h.name ORDER BY latest_date DESC LIMIT 5');
?>
<section class="welcome">
    <div>
        <p>Assalamu'alaikum, <?= e(user()['name']) ?></p>
        <h2>Semoga hari ini penuh keberkahan.</h2>
        <span>Berikut ringkasan perkembangan tahfidz terbaru.</span>
    </div>
    <div class="date-card"><small>Hari ini</small><b><?= e(format_date(date('Y-m-d'))) ?></b></div>
</section>

<?php if ($role === 'wali' && !empty($guardianStudents)): ?>
<section class="guardian-child-panel" aria-label="Perkembangan anak">
    <div class="guardian-child-head">
        <div class="guardian-child-current"><span><?=e(strtoupper(substr($student['name'],0,1)))?></span><div><p class="eyebrow green">Data Anak</p><h3><?=e($student['name'])?></h3><small><?=e($student['student_code'])?> · <?=e($student['halaqoh']?:'Belum masuk Halaqoh')?></small></div></div>
        <?php if(count($guardianStudents)>1):?><label>Pilih Anak<select onchange="if(this.value)location.href='index.php?page=dashboard&student_id='+this.value"><?php foreach($guardianStudents as $guardianStudent):?><option value="<?=(int)$guardianStudent['id']?>" <?=(int)$guardianStudent['id']===$studentId?'selected':''?>><?=e($guardianStudent['name'])?> — <?=e($guardianStudent['student_code'])?></option><?php endforeach?></select></label><?php endif?>
    </div>
    <div class="guardian-transfer-history"><div class="guardian-transfer-title"><b>Riwayat Perpindahan Halaqoh</b><span><?=count($guardianTransfers)?> riwayat terakhir</span></div><?php if(!$guardianTransfers):?><p class="guardian-transfer-empty">Belum ada riwayat perpindahan Halaqoh.</p><?php else:?><div class="guardian-transfer-list"><?php foreach($guardianTransfers as $transfer):?><article><time><?=e(format_date($transfer['transfer_date']))?></time><div><b><?=e($transfer['from_halaqoh']?:'Belum ditentukan')?> <i>→</i> <?=e($transfer['to_halaqoh'])?></b><small><?=e($transfer['notes']?:'Perpindahan Halaqoh')?></small></div></article><?php endforeach?></div><?php endif?></div>
</section>
<?php endif; ?>

<nav class="dashboard-shortcuts" aria-label="Aksi cepat">
    <?php if ($role === 'admin'): ?>
        <a href="index.php?page=assessments"><i>＋</i><span><b>Tambah Penilaian</b><small>Catat perkembangan santri</small></span></a>
        <a href="index.php?page=students"><i>♙</i><span><b>Kelola Santri</b><small>Perbarui data santri dan wali</small></span></a>
        <a href="index.php?page=reports"><i>▣</i><span><b>Lihat Laporan</b><small>Pantau hasil penilaian</small></span></a>
    <?php elseif ($role === 'ustadzah'): ?>
        <a href="index.php?page=assessments"><i>＋</i><span><b>Tambah Penilaian</b><small>Catat setoran terbaru</small></span></a>
        <a href="index.php?page=students"><i>♙</i><span><b>Santri Bimbingan</b><small>Lihat daftar santri</small></span></a>
        <a href="index.php?page=reports"><i>▣</i><span><b>Lihat Laporan</b><small>Periksa perkembangan</small></span></a>
    <?php else: ?>
        <a href="index.php?page=reports"><i>▣</i><span><b>Laporan Anak</b><small>Lihat perkembangan terbaru</small></span></a>
        <a href="index.php?page=quran&from=dashboard"><i>☾</i><span><b>Baca Al-Qur’an</b><small>Lanjutkan bacaan Anda</small></span></a>
        <a href="index.php?page=profile"><i>●</i><span><b>Profil Saya</b><small>Kelola informasi akun</small></span></a>
    <?php endif; ?>
</nav>

<section class="stats" aria-label="Ringkasan data">
    <?php foreach ($cards as $card): ?>
        <article class="stat">
            <div class="stat-icon"><?= $card[2] ?></div>
            <div><span><?= e($card[0]) ?></span><strong><?= e($card[1]) ?></strong></div>
        </article>
    <?php endforeach; ?>
</section>

<section class="grid two">
    <article class="card">
        <div class="card-head">
            <div><p class="eyebrow green">Aktivitas</p><h3>Penilaian terbaru</h3></div>
            <a href="index.php?page=<?= $role === 'admin' ? 'assessments' : 'reports' ?>">Lihat semua →</a>
        </div>
        <div class="table-wrap compact-table">
            <table data-datatable data-page-size="5">
                <thead><tr><th>Santri</th><th>Surah</th><th>Nilai</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (!$recent): ?><tr><td colspan="4"><div class="empty">Belum ada penilaian.</div></td></tr><?php endif; ?>
                    <?php foreach ($recent as $item): ?>
                        <tr><td><b><?= e($item['student']) ?></b><small><?= e($item['halaqoh']) ?></small></td><td><?= e($item['surah'] . ' (ayat ' . $item['verse_range'] . ')') ?></td><td><b><?= (int)$item['memorization'] + (int)$item['murojaah'] ?> / 100</b></td><td><?= status_badge($item['status']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card">
        <div class="card-head"><div><p class="eyebrow green">Progres</p><h3>Perkembangan hafalan</h3></div></div>
        <div class="progress-list">
            <?php if (!$progress): ?><div class="dashboard-chart-empty">Belum ada data perkembangan.</div><?php endif; ?>
            <?php foreach ($progress as $item): $score = (float)$item['score']; $level = $score >= 85 ? 'Sangat Baik' : ($score >= 75 ? 'Baik' : 'Perlu Ditingkatkan'); ?>
                <article class="progress-student"><div class="progress-avatar"><?= e(strtoupper(substr($item['student'], 0, 1))) ?></div><div class="progress-main"><div class="progress-name"><span><b><?= e($item['student']) ?></b><small><?= e($item['halaqoh']) ?> • <?= (int)$item['assessment_count'] ?> penilaian</small></span><strong><?= e($score) ?><small>/100</small></strong></div><div class="progress-track"><i style="width:<?= min(100, $score) ?>%"></i></div><span class="progress-level <?= $score >= 85 ? 'excellent' : ($score >= 75 ? 'good' : 'attention') ?>"><?= e($level) ?></span></div></article>
            <?php endforeach; ?>
        </div>
        <div class="dashboard-verse"><span>“</span><p>Bacalah Al-Qur'an, karena ia akan datang memberi syafaat.</p></div>
    </article>
</section>
