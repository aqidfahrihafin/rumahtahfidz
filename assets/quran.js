(function () {
    const storageKey = "as_sakinah_last_reading";
    const search = document.getElementById("surahSearch");
    const filter = document.getElementById("juzFilter");
    const cards = Array.from(document.querySelectorAll(".surah-card"));

    function filterSurahs() {
        if (!cards.length) return;
        const keyword = (search ? search.value : "").trim().toLocaleLowerCase("id");
        const juz = filter ? filter.value : "";
        let visible = 0;
        cards.forEach(function (card) {
            const matchesText = !keyword || card.dataset.name.includes(keyword) || card.dataset.number === keyword;
            const matchesJuz = !juz || card.dataset.juz === juz;
            card.hidden = !(matchesText && matchesJuz);
            if (!card.hidden) visible++;
        });
        const count = document.getElementById("surahCount");
        const empty = document.getElementById("quranEmpty");
        if (count) count.textContent = visible;
        if (empty) empty.hidden = visible !== 0;
    }
    if (search) search.addEventListener("input", filterSurahs);
    if (filter) filter.addEventListener("change", filterSurahs);

    const lastReading = document.getElementById("lastReading");
    if (lastReading) {
        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || "null");
            if (saved && saved.surah && saved.verse) {
                lastReading.href = "index.php?page=quran-read&surah=" + encodeURIComponent(saved.surah) + "#ayat-" + encodeURIComponent(saved.verse);
                document.getElementById("lastReadingName").textContent = saved.name;
                document.getElementById("lastReadingVerse").textContent = "Ayat " + saved.verse;
                lastReading.hidden = false;
            }
        } catch (error) { localStorage.removeItem(storageKey); }
    }

    const reader = document.querySelector(".reader[data-surah]");
    const verses = Array.from(document.querySelectorAll(".reader-verse"));
    if (reader && verses.length && "IntersectionObserver" in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                localStorage.setItem(storageKey, JSON.stringify({ surah: reader.dataset.surah, name: reader.dataset.name, verse: entry.target.dataset.verse }));
            });
        }, { rootMargin: "-25% 0px -60% 0px", threshold: 0 });
        verses.forEach(function (verse) { observer.observe(verse); });
    }
})();
