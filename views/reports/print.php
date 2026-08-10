<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Perkembangan Santri — Rumah Tahfidz As-Sakinah</title>
    <link rel="icon" href="assets/images/logo.jpeg" type="image/jpeg">
    <link rel="stylesheet" href="assets/print-report.css?v=20260811d">
</head>
<body>
    <div class="print-toolbar">
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        <button type="button" class="secondary" onclick="window.close()">Tutup</button>
    </div>

    <?php if (!$printReports): ?>
        <main class="empty-print"><h1>Laporan tidak ditemukan</h1><p>Data mungkin sudah dihapus atau tidak dapat diakses oleh akun ini.</p></main>
    <?php endif; ?>

    <?php foreach ($printReports as $report): ?>
        <main class="report-sheet">
            <header class="report-header">
                <div class="report-logo"><img src="assets/images/logo.jpeg" alt="Logo Rumah Tahfidz As-Sakinah"></div>
                <div>
                    <h1>Rumah Tahfidz As-Sakinah</h1>
                    <p>Sistem Informasi Monitoring Tahfidz Al-Qur'an</p>
                </div>
                <div class="report-period"><span>Laporan</span><b><?= e(format_date($report['date'])) ?></b></div>
            </header>

            <section class="report-title">
                <p>Laporan Perkembangan Santri</p>
                <h2><?= e($report['student']) ?></h2>
            </section>

            <section class="identity-grid">
                <div><span>Halaqoh</span><b><?= e($report['halaqoh']) ?></b></div>
                <div><span>Ustadzah Pembimbing</span><b><?= e($report['teacher']) ?></b></div>
                <div><span>Nama Wali</span><b><?= e($report['guardian_name']) ?></b></div>
                <div><span>Kontak Wali</span><b><?= e($report['guardian_phone']) ?></b></div>
            </section>

            <section class="assessment-section">
                <h3>Ringkasan Penilaian</h3>
                <table>
                    <thead><tr><th>Setoran Hafalan</th><th>Murojaah</th><th>Nilai Hafalan</th><th>Nilai Murojaah</th><th>Nilai Akhir</th><th>Status</th></tr></thead>
                    <tbody><tr><td><?= e($report['surah'] . ' ayat ' . $report['verse_range']) ?></td><td><?= e(($report['murojaah_start'] ?: '-') . ' – ' . ($report['murojaah_end'] ?: '-') . ($report['murojaah_juz'] ? ' (Juz ' . $report['murojaah_juz'] . ')' : '')) ?></td><td><?= (int) $report['memorization'] * 2 ?> / 100</td><td><?= (int) $report['murojaah'] * 2 ?> / 100</td><td class="final-score"><?= report_score($report) ?> / 100</td><td><?= e($report['status']) ?></td></tr></tbody>
                </table>
            </section>

            <?php if (!empty($report['score_groups'])): ?>
                <section class="assessment-section score-details">
                    <h3>Rincian Nilai Indikator</h3>
                    <div class="report-score-groups">
                        <?php foreach ($report['score_groups'] as $groupName => $groupScores): ?>
                            <article class="report-score-group">
                                <h4><?= e($groupName) ?></h4>
                                <?php foreach ($groupScores as $score): ?>
                                    <div><span><?= e($score['indicator'] ?: 'Indikator') ?></span><b><?= (int) $score['score'] ?> / 100</b></div>
                                <?php endforeach; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($report['characters'])): ?>
                <section class="assessment-section character-report">
                    <h3>Penilaian Karakter</h3>
                    <div class="character-report-grid">
                        <?php foreach ($report['characters'] as $character): ?>
                            <?php $gradeLabels = array('SB' => 'Sangat Baik', 'B' => 'Baik', 'KB' => 'Perlu Bimbingan'); ?>
                            <div><span><?= e($character['aspect']) ?></span><b><?= e(isset($gradeLabels[$character['grade']]) ? $gradeLabels[$character['grade']] : $character['grade']) ?></b></div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="teacher-note"><span>Catatan Ustadzah</span><p><?= e($report['message']) ?></p></section>

            <footer class="signatures">
                <div><span>Orang Tua / Wali</span><i></i><b><?= e($report['guardian_name']) ?></b></div>
                <div><span>Ustadzah Pembimbing</span><i></i><b><?= e($report['teacher']) ?></b></div>
            </footer>
        </main>
    <?php endforeach; ?>

    <script>if (new URLSearchParams(location.search).get('autoprint') === '1') window.print();</script>
</body>
</html>
