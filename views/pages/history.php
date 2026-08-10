<?php $backPage = $historyBackPage; ?>
<section class="history-page-head">
    <div><a href="index.php?page=<?=e($backPage)?>">← Kembali</a><p class="eyebrow green"><?= $historyType === 'report' ? 'Laporan Santri' : 'Data Penilaian' ?></p><h2><?=e($historyStudent ? $historyStudent['student'] : 'Riwayat tidak ditemukan')?></h2><span><?=e($historyStudent ? $historyStudent['halaqoh'] : 'Data santri tidak tersedia')?></span></div>
    <div class="history-head-actions"><?php if ($historyType === 'assessment' && $role !== 'wali'): ?><button class="btn primary" type="button" onclick="openForm()">＋ Tambah Penilaian</button><?php elseif ($historyType === 'report' && $historyRows): ?><a class="btn primary" href="index.php?page=print-student-reports&student_id=<?=$historyStudentId?>" target="_blank">▣ Cetak Semua</a><?php endif; ?></div>
</section>

<?php if (!$historyRows): ?>
    <section class="data-card history-page-empty"><b>Belum ada riwayat</b><p>Penilaian santri belum tersedia atau tidak dapat diakses oleh akun ini.</p></section>
<?php else: ?>
    <section class="history-overview">
        <article><span>Total Riwayat</span><b><?=$historyTotal?></b><small>penilaian tercatat</small></article>
        <article><span>Rata-rata Nilai</span><b><?=e($historyAverage)?><em>/100</em></b><small>seluruh penilaian</small></article>
        <article><span>Nilai Terbaik</span><b><?=$historyBestScore?><em>/100</em></b><small>capaian tertinggi</small></article>
        <article><span>Penilaian Terakhir</span><b class="history-date-value"><?=e(format_date($historyRows[0]['date']))?></b><small><?=e($historyRows[0]['teacher'] ?: 'Ustadzah belum tercatat')?></small></article>
    </section>

    <section class="data-card history-page-card">
        <div class="history-section-title"><div><p class="eyebrow green">Perjalanan Belajar</p><h3>Daftar Riwayat</h3></div><span>Urut dari penilaian terbaru</span></div>
        <div class="table-wrap"><table data-datatable data-page-size="10"><thead><tr><th>Tanggal</th><th>Setoran Hafalan</th><th>Murojaah</th><th>Penilai</th><th>Hafalan</th><th>Murojaah</th><th>Nilai Akhir</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach($historyRows as $item):?><tr><td><b><?=e(format_date($item['date']))?></b></td><td><?=e($item['surah'].' ayat '.$item['verse_range'])?></td><td><?=e(($item['murojaah_start']?:'-').' – '.($item['murojaah_end']?:'-'))?><small class="history-juz">Juz <?=e($item['murojaah_juz']?:'-')?></small></td><td><?=e($item['teacher']?:'-')?></td><td><?=(int)$item['memorization']*2?> / 100</td><td><?=(int)$item['murojaah']*2?> / 100</td><td><strong class="history-final-score"><?=(int)$item['final_score']?></strong></td><td><?=status_badge($item['status'])?></td><td><div class="history-row-actions"><?php if($historyType==='assessment'&&$role!=='wali'):?><button class="history-action edit edit-assessment" type="button" data-id="<?=(int)$item['id']?>">Edit</button><?php endif?><?php if($historyType==='report'):?><a class="history-action print" href="index.php?page=print-report&id=<?=(int)$item['id']?>" target="_blank">Cetak</a><?php if($role!=='wali'):?><a class="history-action wa" href="<?=e(whatsapp_report_url($item))?>" target="_blank" rel="noopener">WA</a><form method="post" data-confirm data-confirm-type="info" data-confirm-title="Kirim laporan via email?" data-confirm-message="Laporan akan dikirim ke email wali yang terdaftar." data-confirm-button="Ya, kirim"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="send_report_email"><input type="hidden" name="id" value="<?=(int)$item['id']?>"><button class="history-action email" type="submit">Email</button></form><?php endif?><?php endif?></div></td></tr><?php endforeach?>
        </tbody></table></div>
    </section>
<?php endif; ?>

<?php if ($historyType === 'assessment' && $role !== 'wali'): include __DIR__ . '/../forms/assessment.php'; ?>
<script>
document.querySelector('#dataForm input[name="return"]').value = <?=json_encode('history&type='.$historyType.'&student_id='.$historyStudentId)?>;
</script>
<?php endif; ?>
<?php if ($historyType === 'report'): ?>
<script>
document.querySelectorAll('input[name="action"][value="send_report_email"]').forEach(function (actionInput) {
    var returnInput = document.createElement('input');
    returnInput.type = 'hidden';
    returnInput.name = 'return';
    returnInput.value = 'history';

    var studentInput = document.createElement('input');
    studentInput.type = 'hidden';
    studentInput.name = 'student_id';
    studentInput.value = <?=json_encode((string)$historyStudentId)?>;

    actionInput.form.appendChild(returnInput);
    actionInput.form.appendChild(studentInput);
});
</script>
<?php endif; ?>
