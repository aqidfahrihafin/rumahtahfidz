<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#155c43"><title><?=e($publicSurah ? $publicSurah['name'] : 'Baca Al-Qur’an')?> — Rumah Tahfidz As-Sakinah</title><link rel="stylesheet" href="assets/quran.css?v=20260811c"></head>
<body>
<header class="quran-nav"><div class="quran-wrap"><a href="index.php?page=quran">← Daftar Surat</a><b>Baca Al-Qur’an</b><a href="index.php?page=home">Beranda</a></div></header>
<main class="quran-wrap reading-main">
<?php if($publicSurahError):?><section class="read-error"><b>Ayat belum dapat ditampilkan</b><p><?=e($publicSurahError)?> Periksa koneksi internet lalu coba kembali.</p><a href="index.php?page=quran-read&surah=<?=(int)($publicSurah['surah_number']??0)?>">Coba Lagi</a></section>
<?php else:?>
<article class="reader" data-surah="<?=(int)$publicSurahDetail['nomor']?>" data-name="<?=e($publicSurahDetail['namaLatin'])?>">
    <header><div><p>Surat ke-<?=(int)$publicSurahDetail['nomor']?> • <?=(int)$publicSurahDetail['jumlahAyat']?> ayat</p><h1><?=e($publicSurahDetail['namaLatin'])?></h1><span><?=e($publicSurahDetail['arti'])?></span></div><strong lang="ar" dir="rtl"><?=e($publicSurahDetail['nama'])?></strong></header>
    <div class="reader-verses"><?php if((int)$publicSurahDetail['nomor']!==1&&(int)$publicSurahDetail['nomor']!==9):?><div class="reader-bismillah" lang="ar" dir="rtl">بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ</div><?php endif?><?php foreach($publicSurahDetail['ayat'] as $verse):?><section class="reader-verse" id="ayat-<?=(int)$verse['nomorAyat']?>" data-verse="<?=(int)$verse['nomorAyat']?>"><span><?=(int)$verse['nomorAyat']?></span><div><p lang="ar" dir="rtl"><?=e($verse['teksArab'])?></p><small><b><?=(int)$verse['nomorAyat']?>.</b> <?=e($verse['teksIndonesia'])?></small></div></section><?php endforeach?></div>
    <nav class="reader-pagination" aria-label="Navigasi surat"><?php if($previousSurah):?><a class="previous" href="index.php?page=quran-read&surah=<?=(int)$previousSurah['surah_number']?>"><i>←</i><span><small>Surat Sebelumnya</small><b><?=e($previousSurah['name'])?></b></span></a><?php else:?><span class="reader-nav-placeholder"></span><?php endif?><?php if($nextSurah):?><a class="next" href="index.php?page=quran-read&surah=<?=(int)$nextSurah['surah_number']?>"><span><small>Surat Berikutnya</small><b><?=e($nextSurah['name'])?></b></span><i>→</i></a><?php endif?></nav>
</article><?php endif?>
</main><script src="assets/quran.js?v=20260811b"></script></body></html>
