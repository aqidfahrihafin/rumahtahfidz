<section class="page-intro"><div><p class="eyebrow green">Administrasi Santri</p><h2>Perpindahan Halaqoh</h2><p>Pindahkan santri tanpa menghilangkan riwayat Halaqoh sebelumnya.</p></div></section>

<section class="transfer-layout">
    <article class="data-card transfer-form-card">
        <div class="history-section-title"><div><p class="eyebrow green">Formulir</p><h3>Pindahkan Santri</h3></div></div>
        <div class="transfer-mode-tabs" role="tablist"><button class="active" type="button" data-transfer-mode="student"><span>1</span>Per Santri</button><i></i><button type="button" data-transfer-mode="halaqoh"><span>2</span>Satu Halaqoh</button></div>
        <div class="transfer-mode-panel active" data-transfer-panel="student">
        <div class="transfer-guide"><span>⇄</span><div><b>Perubahan tanpa kehilangan riwayat</b><p>Pilih santri dan Halaqoh tujuan. Data nilai sebelumnya tetap tersimpan.</p></div></div>
        <form method="post" class="transfer-form" data-confirm data-confirm-type="info" data-confirm-title="Pindahkan Halaqoh santri?" data-confirm-message="Halaqoh aktif santri akan diperbarui dan perubahan ini dicatat dalam riwayat." data-confirm-button="Ya, pindahkan">
            <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="transfer_student">
            <label>Nama Santri<select name="student_id" id="transferStudent" required><option value="">Pilih santri</option><?php foreach($transferStudents as $student):?><option value="<?=(int)$student['id']?>" data-halaqoh-id="<?=(int)$student['halaqoh_id']?>" data-halaqoh="<?=e($student['halaqoh']?:'Belum ditentukan')?>"><?=e($student['name'])?> — <?=e($student['student_code'])?></option><?php endforeach?></select></label>
            <label>Halaqoh Saat Ini<input type="text" id="transferCurrentHalaqoh" value="" placeholder="Terisi otomatis" readonly></label>
            <label>Halaqoh Tujuan<select name="to_halaqoh_id" id="transferDestination" required><option value="">Pilih Halaqoh tujuan</option><?php foreach($transferHalaqohs as $halaqoh):?><option value="<?=(int)$halaqoh['id']?>"><?=e($halaqoh['name'])?> — <?=e($halaqoh['level'])?></option><?php endforeach?></select></label>
            <label>Tanggal Perpindahan<input type="date" name="transfer_date" value="<?=date('Y-m-d')?>" required></label>
            <label class="full">Catatan<textarea name="notes" rows="3" placeholder="Alasan atau keterangan perpindahan (opsional)"></textarea></label>
            <div class="full transfer-submit"><button class="btn primary" type="submit">Simpan Perpindahan</button></div>
        </form>
        </div>

        <div class="transfer-mode-panel" data-transfer-panel="halaqoh" hidden>
        <div class="transfer-guide warning"><span>!</span><div><b>Seluruh santri akan dipindahkan</b><p>Semua santri pada Halaqoh asal berpindah ke Halaqoh tujuan dalam satu proses.</p></div></div>
        <form method="post" class="transfer-form" data-confirm data-confirm-type="warning" data-confirm-title="Pindahkan seluruh santri?" data-confirm-message="Semua santri pada Halaqoh asal akan dipindahkan dan masing-masing mendapatkan catatan riwayat." data-confirm-button="Ya, pindahkan semua">
            <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="transfer_halaqoh_bulk">
            <label>Halaqoh Asal<select name="from_halaqoh_id" id="bulkTransferSource" required><option value="">Pilih Halaqoh asal</option><?php foreach($transferHalaqohs as $halaqoh):$studentCount=(int)scalar('SELECT COUNT(*) FROM students WHERE halaqoh_id=?',array($halaqoh['id']));if($studentCount<1)continue;?><option value="<?=(int)$halaqoh['id']?>"><?=e($halaqoh['name'])?> — <?=$studentCount?> santri</option><?php endforeach?></select></label>
            <label>Halaqoh Tujuan<select name="to_halaqoh_id" id="bulkTransferDestination" required><option value="">Pilih Halaqoh tujuan</option><?php foreach($transferHalaqohs as $halaqoh):?><option value="<?=(int)$halaqoh['id']?>"><?=e($halaqoh['name'])?> — <?=e($halaqoh['level'])?></option><?php endforeach?></select></label>
            <label class="full">Tanggal Perpindahan<input type="date" name="transfer_date" value="<?=date('Y-m-d')?>" required></label>
            <label class="full">Catatan<textarea name="notes" rows="3" placeholder="Contoh: Kenaikan tingkat Halaqoh"></textarea></label>
            <div class="full transfer-submit"><button class="btn primary" type="submit">Pindahkan Semua Santri</button></div>
        </form>
        </div>
    </article>

    <article class="data-card transfer-history-card">
        <div class="history-section-title"><div><p class="eyebrow green">Riwayat</p><h3>Perpindahan Halaqoh</h3></div><span><?=count($transferHistory)?> perpindahan tercatat</span></div>
        <div class="table-wrap"><table data-datatable data-page-size="10"><thead><tr><th>Tanggal</th><th>Santri</th><th>Halaqoh Asal</th><th>Halaqoh Tujuan</th><th>Catatan</th><th>Dicatat Oleh</th></tr></thead><tbody><?php if(!$transferHistory):?><tr><td colspan="6"><div class="empty">Belum ada riwayat perpindahan.</div></td></tr><?php endif?><?php foreach($transferHistory as $item):?><tr><td><?=e(format_date($item['transfer_date']))?></td><td><span class="table-person"><b><?=e($item['student'])?></b><small><?=e($item['student_code'])?></small></span></td><td><?=e($item['from_halaqoh']?:'Belum ditentukan')?></td><td><b><?=e($item['to_halaqoh'])?></b></td><td><?=e($item['notes']?:'-')?></td><td><?=e($item['operator']?:'-')?></td></tr><?php endforeach?></tbody></table></div>
    </article>
</section>
