<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Santri — Rumah Tahfidz As-Sakinah</title>
    <link rel="icon" href="assets/images/logo.jpeg" type="image/jpeg">
    <link rel="stylesheet" href="assets/print-students.css?v=20260811b">
</head>
<body>
    <div class="print-toolbar"><button type="button" onclick="window.print()">Simpan sebagai PDF</button><button class="secondary" type="button" onclick="window.close()">Tutup</button></div>
    <main class="student-sheet">
        <header><img src="assets/images/logo.jpeg" alt="Logo"><div><h1>Rumah Tahfidz As-Sakinah</h1><p>Sistem Informasi Monitoring Tahfidz Al-Qur'an</p></div><span><small>Dicetak</small><b><?= e(format_date(date('Y-m-d'))) ?></b></span></header>
        <section class="document-title"><h2>Data Santri</h2><p>Total <?= count($printStudents) ?> santri sesuai akses akun.</p></section>
        <table>
            <thead><tr><th>No.</th><th>Nomor Induk / Santri</th><th>JK</th><th>Halaqoh</th><th>Ustadzah</th><th>Wali / Kontak</th><th>Alamat</th></tr></thead>
            <tbody>
                <?php if (!$printStudents): ?><tr><td colspan="7" class="empty">Data santri belum tersedia.</td></tr><?php endif; ?>
                <?php foreach ($printStudents as $index => $student): ?>
                    <tr><td><?= $index + 1 ?></td><td><b><?= e($student['name']) ?></b><small><?= e($student['student_code']) ?></small></td><td><?= e($student['gender']) ?></td><td><?= e($student['halaqoh'] ?: '-') ?></td><td><?= e($student['teacher'] ?: '-') ?></td><td><b><?= e($student['guardian_name']) ?></b><small><?= e($student['email']) ?><br>WA: <?= e($student['guardian_phone']) ?></small></td><td><?= e($student['address']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <footer>Dokumen ini dihasilkan oleh Sistem Informasi Rumah Tahfidz As-Sakinah.</footer>
    </main>
</body>
</html>
