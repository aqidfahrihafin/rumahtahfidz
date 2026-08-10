<?php $quranContext = !empty($quranFromDashboard) ? '&from=dashboard' : ''; ?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#155c43"><title>Baca Al-Qur’an — Rumah Tahfidz As-Sakinah</title><link rel="stylesheet" href="assets/quran.css?v=20260811c"><link rel="stylesheet" href="assets/quran-enhancements.css?v=20260811a"></head>
<body>
<header class="quran-nav"><div class="quran-wrap"><a href="index.php?page=<?=!empty($quranFromDashboard)?'dashboard':'home'?>">← <?=!empty($quranFromDashboard)?'Dashboard':'Beranda'?></a><b>Baca Al-Qur’an</b><a href="index.php?page=<?=!empty($quranFromDashboard)?'profile':'login'?>"><?=!empty($quranFromDashboard)?'Profil':'Masuk'?></a></div></header>
<main class="quran-wrap quran-main">
    <section class="quran-intro"><p>Al-Qur’an Digital</p><h1>Temukan dan baca surat pilihanmu.</h1><span>Teks Arab dan terjemahan Bahasa Indonesia dari EQuran.id.</span></section>
    <a class="last-reading" id="lastReading" data-context="<?=e($quranContext)?>" hidden><small>Terakhir Dibaca</small><b id="lastReadingName"></b><span id="lastReadingVerse"></span><i>Lanjutkan →</i></a>
    <section class="quran-tools"><label><span>⌕</span><input type="search" id="surahSearch" placeholder="Cari nama atau nomor surat..."></label><select id="juzFilter" aria-label="Filter berdasarkan Juz"><option value="">Semua Juz</option><?php foreach(range(1,30) as $juz):?><option value="<?=$juz?>">Juz <?=$juz?></option><?php endforeach?></select></section>
    <div class="surah-count"><b id="surahCount"><?=count($publicSurahs)?></b> surat ditemukan</div>
    <section class="surah-grid" id="surahGrid">
        <?php foreach($publicSurahs as $surah):?><a class="surah-card" href="index.php?page=quran-read&surah=<?=(int)$surah['surah_number']?><?=e($quranContext)?>" data-name="<?=e(strtolower($surah['name']))?>" data-number="<?=(int)$surah['surah_number']?>" data-juz="<?=(int)$surah['juz']?>"><span class="surah-no"><?=(int)$surah['surah_number']?></span><div><b><?=e($surah['name'])?></b><small><?=(int)$surah['verses']?> ayat</small></div><em>Juz <?=(int)$surah['juz']?></em><i>→</i></a><?php endforeach?>
    </section>
    <div class="quran-empty" id="quranEmpty" hidden>Surat yang dicari tidak ditemukan.</div>
</main>
<script src="assets/quran.js?v=20260811d"></script>
</body></html>
