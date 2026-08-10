<?php
$canManage = $role !== 'wali' && $dataConfig['entity'] !== '';
$hasReportActions = $page === 'reports';
$headers = array_merge(array('No'), $dataConfig['headers'], ($canManage || $hasReportActions) ? array('Aksi') : array());
?>
<section class="page-intro">
    <div>
        <p class="eyebrow green">Kelola Data</p>
        <h2><?= e($pageMeta['title']) ?></h2>
        <p><?= e($pageMeta['description']) ?></p>
    </div>
    <?php if ($canManage): ?>
        <div class="page-actions"><?php if($page==='surahs'):?><form method="post" data-confirm data-confirm-type="info" data-confirm-title="Sinkronkan Data Surah?" data-confirm-message="Data surat akan diambil dari EQuran.id. Data yang sudah ada akan diperbarui tanpa menghapus data manual." data-confirm-button="Ya, sinkronkan"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="sync_surahs"><button class="btn secondary" type="submit">↻ Sinkronkan dari API</button></form><?php endif?><?php if($page==='students'):?><a class="btn secondary" href="index.php?page=print-students" target="_blank">⇩ Ekspor PDF</a><?php endif?><button class="btn primary" type="button" onclick="openForm()">＋ Tambah Data</button></div>
    <?php elseif ($page === 'students'): ?>
        <a class="btn secondary" href="index.php?page=print-students" target="_blank">⇩ Ekspor PDF</a>
    <?php elseif ($page === 'reports'): ?>
        <a class="btn secondary" href="index.php?page=print-reports" target="_blank">⇩ Cetak Semua</a>
    <?php endif; ?>
</section>

<?php if ($page === 'students'): ?>
<form class="student-filter" method="get">
    <input type="hidden" name="page" value="students">
    <label>Filter Halaqoh<select name="halaqoh_id" onchange="this.form.submit()"><option value="0">Semua Halaqoh</option><?php foreach(rows('SELECT id,name FROM halaqoh ORDER BY name') as $filterHalaqoh):?><option value="<?=(int)$filterHalaqoh['id']?>" <?=(int)$selectedHalaqohId===(int)$filterHalaqoh['id']?'selected':''?>><?=e($filterHalaqoh['name'])?></option><?php endforeach?></select></label>
    <?php if($selectedHalaqohId):?><a href="index.php?page=students">Hapus filter</a><?php endif?>
</form>
<?php endif; ?>

<section class="data-card">
    <?php if($page==='surahs'):?><div class="api-source-note"><div><b>Sumber data Al-Qur’an</b><span>Nama dan jumlah ayat: EQuran.id v2 • Juz awal surat: Al Quran Cloud</span></div><div><a href="https://equran.id/apidev/v2" target="_blank" rel="noopener">Dokumentasi EQuran.id ↗</a><a href="https://alquran.cloud/api" target="_blank" rel="noopener">Dokumentasi Juz ↗</a></div></div><?php endif?>
    <div class="table-wrap">
        <table data-datatable data-page-size="10">
            <thead><tr><?php foreach ($headers as $header): ?><th><?= e($header) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
                <?php if (!$data): ?><tr><td colspan="<?= count($headers) ?>"><div class="empty"><b>Data tidak ditemukan</b><span>Coba ubah kata kunci pencarian atau tambahkan data baru.</span></div></td></tr><?php endif; ?>
                <?php foreach ($data as $index => $record): ?>
                    <tr>
                        <td class="row-number"><?= $index + 1 ?></td>
                        <?php foreach ($dataConfig['cells']($record) as $cell): ?><td><?= $cell ?></td><?php endforeach; ?>
                        <?php if ($canManage): ?>
                            <td class="actions">
                                <?php if (!empty($dataConfig['group_history'])): ?>
                                    <a class="btn history-button" href="index.php?page=history&type=assessment&student_id=<?= (int)$record['student_id'] ?>">Riwayat <span><?= (int)$record['history_count'] ?></span></a>
                                <?php else: ?>
                                <?php if ($page === 'surahs'): ?>
                                    <a class="icon-btn surah-read-link" href="index.php?page=surah-detail&id=<?= (int) $record['id'] ?>" title="Baca ayat surat">◉</a>
                                <?php else: ?>
                                    <button class="icon-btn view-detail" type="button" data-entity="<?= e($page) ?>" data-row='<?= e(json_encode($record)) ?>' title="Lihat detail">◉</button>
                                <?php endif; ?>
                                <?php if ($page === 'assessments'): ?><button class="icon-btn edit-assessment" type="button" data-id="<?= (int)$record['id'] ?>" title="Edit penilaian">✎</button><?php else:?><button class="icon-btn edit-row" type="button" data-row='<?= e(json_encode($record)) ?>' title="Edit data">✎</button><?php endif; ?>
                                <form method="post" data-confirm data-confirm-type="danger" data-confirm-title="Hapus data?" data-confirm-message="Data <?= e($record['name'] ?? $record['student'] ?? 'ini') ?> akan dihapus dan tidak dapat dikembalikan." data-confirm-button="Ya, hapus">
                                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="table" value="<?= e($dataConfig['table']) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                                    <input type="hidden" name="return" value="<?= e($page) ?>">
                                    <button class="icon-btn danger" type="submit" title="Hapus data">⌫</button>
                                </form>
                                <?php
                                $accountUserId = 0;
                                if ($role === 'admin' && $page === 'teachers') $accountUserId = (int) $record['user_id'];
                                if ($role === 'admin' && $page === 'students') $accountUserId = (int) $record['guardian_user_id'];
                                ?>
                                <?php if ($accountUserId): ?>
                                    <form method="post" data-confirm data-confirm-type="<?= (int) $record['account_active'] === 1 ? 'warning' : 'success' ?>" data-confirm-title="<?= (int) $record['account_active'] === 1 ? 'Nonaktifkan akun?' : 'Aktifkan akun?' ?>" data-confirm-message="<?= (int) $record['account_active'] === 1 ? 'Pengguna tidak akan dapat masuk sampai akun diaktifkan kembali.' : 'Pengguna akan dapat masuk kembali menggunakan akun ini.' ?>" data-confirm-button="<?= (int) $record['account_active'] === 1 ? 'Ya, nonaktifkan' : 'Ya, aktifkan' ?>">
                                        <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                        <input type="hidden" name="action" value="toggle_account">
                                        <input type="hidden" name="user_id" value="<?= $accountUserId ?>">
                                        <input type="hidden" name="return" value="<?= e($page) ?>">
                                        <?php if ((int) $record['account_active'] === 1): ?>
                                            <button class="icon-btn account-off" type="submit" title="Nonaktifkan akun">⊘</button>
                                        <?php else: ?>
                                            <button class="icon-btn account-on" type="submit" title="Aktifkan akun">✓</button>
                                        <?php endif; ?>
                                    </form>
                                <?php elseif ($role === 'admin' && in_array($page, array('teachers', 'students'), true)): ?>
                                    <form method="post" data-confirm data-confirm-type="success" data-confirm-title="Buat akun baru?" data-confirm-message="Akun akan langsung aktif menggunakan password awal <?= e(DEFAULT_ACCOUNT_PASSWORD) ?>." data-confirm-button="Buat dan aktifkan">
                                        <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                        <input type="hidden" name="action" value="create_account">
                                        <input type="hidden" name="entity" value="<?= $page === 'teachers' ? 'teacher' : 'student' ?>">
                                        <input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>">
                                        <input type="hidden" name="return" value="<?= e($page) ?>">
                                        <button class="icon-btn account-on" type="submit" title="Buat dan aktifkan akun">＋</button>
                                    </form>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php elseif ($hasReportActions): ?>
                            <td class="actions report-actions">
                                <?php if (!empty($dataConfig['group_history'])): ?>
                                    <a class="btn history-button" href="index.php?page=history&type=report&student_id=<?= (int)$record['student_id'] ?>">Riwayat <span><?= (int)$record['history_count'] ?></span></a>
                                <?php else: ?>
                                <button class="icon-btn view-detail" type="button" data-entity="<?= e($page) ?>" data-row='<?= e(json_encode($record)) ?>' title="Lihat detail">◉</button>
                                <a class="icon-btn print" href="index.php?page=print-report&id=<?= (int) $record['id'] ?>" target="_blank" title="Cetak laporan">▣</a>
                                <?php if ($role !== 'wali'): ?>
                                    <a class="icon-btn whatsapp" href="<?= e(whatsapp_report_url($record)) ?>" target="_blank" rel="noopener" title="Kirim ke WhatsApp">WA</a>
                                    <form method="post" data-confirm data-confirm-type="info" data-confirm-title="Kirim laporan via email?" data-confirm-message="Laporan <?= e($record['student']) ?> akan dikirim ke email wali yang terdaftar." data-confirm-button="Ya, kirim email">
                                        <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                        <input type="hidden" name="action" value="send_report_email">
                                        <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
                                        <button class="icon-btn email" type="submit" title="Kirim ke email">✉</button>
                                    </form>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
if ($canManage) {
    $entity = $dataConfig['entity'];
    include __DIR__ . '/../modal_form.php';
}
?>
