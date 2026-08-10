<section class="surah-page-head">
    <a class="btn ghost" href="index.php?page=surahs">← Kembali</a>
    <span>Sumber bacaan: <a href="https://equran.id/apidev/v2" target="_blank" rel="noopener">EQuran.id ↗</a></span>
</section>

<?php if ($surahError): ?>
    <section class="data-card surah-page-error">
        <b>Ayat belum dapat ditampilkan</b>
        <p><?= e($surahError) ?> Periksa koneksi internet lalu coba kembali.</p>
        <a class="btn secondary" href="index.php?page=surah-detail&id=<?= (int) ($surahRecord['id'] ?? 0) ?>">Coba Lagi</a>
    </section>
<?php else: ?>
    <article class="data-card surah-reading-page">
        <header class="surah-reading-header">
            <div>
                <p>Surat ke-<?= (int) $surahDetail['nomor'] ?> • <?= (int) $surahDetail['jumlahAyat'] ?> ayat</p>
                <h2><?= e($surahDetail['namaLatin']) ?></h2>
                <span><?= e($surahDetail['arti']) ?></span>
            </div>
            <strong lang="ar" dir="rtl"><?= e($surahDetail['nama']) ?></strong>
        </header>

        <div class="surah-reading-list">
            <?php if ((int) $surahDetail['nomor'] !== 1 && (int) $surahDetail['nomor'] !== 9): ?>
                <div class="surah-bismillah" lang="ar" dir="rtl">بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ</div>
            <?php endif; ?>
            <?php foreach ($surahDetail['ayat'] as $verse): ?>
                <section class="surah-reading-verse" tabindex="0" role="button" aria-expanded="false">
                    <div class="verse-number"><?= (int) $verse['nomorAyat'] ?></div>
                    <div>
                        <p class="verse-arabic" lang="ar" dir="rtl"><?= e($verse['teksArab']) ?></p>
                        <button class="verse-translation-toggle" type="button">Lihat terjemahan</button>
                        <p class="verse-translation"><b><?= (int) $verse['nomorAyat'] ?>.</b> <?= e($verse['teksIndonesia']) ?></p>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </article>
<?php endif; ?>
