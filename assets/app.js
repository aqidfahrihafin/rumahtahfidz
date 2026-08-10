function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.remove("show");
    modal.setAttribute("aria-hidden", "true");
}

function openForm(row = null) {
    const modal = document.getElementById("formModal");
    const form = document.getElementById("dataForm");
    if (!modal || !form) return;

    form.reset();
    document.getElementById("recordId").value = row ? row.id : "";
    document.getElementById("formTitle").textContent = (row ? "Edit" : "Tambah") + " data";

    if (!row && form.dataset.entity === "assessment" && form.dataset.defaultStudentId) {
        const studentField = form.elements.namedItem("student_id");
        if (studentField) {
            studentField.value = form.dataset.defaultStudentId;
            studentField.dispatchEvent(new Event("change"));
        }
    }

    if (row) {
        form.querySelectorAll("[data-field]").forEach(function (field) {
            if (row[field.dataset.field] !== undefined) {
                if (field.multiple) {
                    const values = String(row[field.dataset.field] || "").split(",");
                    Array.from(field.options).forEach(function (option) { option.selected = values.includes(option.value); });
                } else {
                    field.value = row[field.dataset.field] || "";
                }
            }
        });
    }

    if (form.dataset.entity === "assessment") {
        const studentField = form.elements.namedItem("student_id");
        if (studentField) studentField.disabled = form.dataset.lockStudent === "1";
    }

    modal.classList.add("show");
    modal.setAttribute("aria-hidden", "false");
    if (form.dataset.entity === "student") showStudentStep(1);
}

const studentForm = document.querySelector('#dataForm[data-entity="student"]');
if (studentForm) {
    const guardianEmail = studentForm.elements.namedItem("email");
    const guardianName = studentForm.elements.namedItem("guardian_name");
    const guardianPhone = studentForm.elements.namedItem("guardian_phone");
    const guardianAccounts = JSON.parse(studentForm.dataset.guardianAccounts || "[]");
    const guardianNotice = document.createElement("div");
    guardianNotice.className = "guardian-account-notice full";
    guardianNotice.hidden = true;
    studentForm.querySelector(".form-actions").before(guardianNotice);

    guardianEmail.addEventListener("input", function () {
        const email = guardianEmail.value.trim().toLowerCase();
        const account = guardianAccounts.find(function (item) { return String(item.email).toLowerCase() === email; });
        guardianNotice.hidden = !account;
        if (!account) return;
        guardianNotice.textContent = "Email ini sudah menjadi akun wali " + account.name + " dan terhubung dengan " + Number(account.child_count) + " anak. Santri baru akan ditambahkan ke akun wali yang sama.";
        if (!document.getElementById("recordId").value) {
            guardianName.value = account.name || guardianName.value;
            guardianPhone.value = account.phone || guardianPhone.value;
        }
    });
}

function showStudentStep(step) {
    const form = document.getElementById("dataForm");
    if (!form || form.dataset.entity !== "student") return;

    if (step === 2) {
        const firstStepFields = form.querySelectorAll('[data-form-step="1"] input, [data-form-step="1"] select');
        for (let index = 0; index < firstStepFields.length; index++) {
            if (!firstStepFields[index].reportValidity()) return;
        }
    }

    form.querySelectorAll("[data-form-step]").forEach(function (field) {
        field.classList.toggle("step-visible", field.dataset.formStep === String(step));
    });
    document.querySelectorAll("[data-step-button]").forEach(function (button) {
        button.classList.toggle("active", button.dataset.stepButton === String(step));
    });
    form.querySelector(".student-back").style.display = step === 2 ? "inline-flex" : "none";
    form.querySelector(".student-next").style.display = step === 1 ? "inline-flex" : "none";
    form.querySelector(".form-submit").style.display = step === 2 ? "inline-flex" : "none";
}

document.querySelectorAll("[data-step-button]").forEach(function (button) {
    button.addEventListener("click", function () { showStudentStep(Number(button.dataset.stepButton)); });
});

const assessmentStudent = document.getElementById("assessmentStudent");
function assessmentAllowedSurahs() {
    if (!assessmentStudent || !assessmentStudent.value) return [];
    return (window.assessmentCoverage && window.assessmentCoverage[assessmentStudent.value]) || [];
}
if (assessmentStudent) {
    assessmentStudent.addEventListener("change", function () {
        const option = assessmentStudent.options[assessmentStudent.selectedIndex];
        document.getElementById("assessmentHalaqoh").value = option ? option.dataset.halaqoh || "" : "";
        document.getElementById("assessmentTeacher").value = option ? option.dataset.teacherName || "" : "";
        document.getElementById("assessmentTeacherId").value = option ? option.dataset.teacherId || "" : "";
        const allowedSurahs = assessmentAllowedSurahs();
        [document.getElementById("assessmentSurah"), document.getElementById("murojaahStart"), document.getElementById("murojaahEnd")].forEach(function (field) {
            if (!field) return;
            Array.from(field.options).forEach(function (surahOption, index) {
                if (index > 0) surahOption.hidden = !allowedSurahs.includes(surahOption.value);
            });
            if (field.value && !allowedSurahs.includes(field.value)) field.value = "";
        });
        const juzField = document.getElementById("murojaahJuz");
        if (juzField && juzField.value) juzField.dispatchEvent(new Event("change"));
    });

    assessmentStudent.form.addEventListener("submit", function () {
        assessmentStudent.disabled = false;
    });
}

const assessmentSurah = document.getElementById("assessmentSurah");
if (assessmentSurah) {
    assessmentSurah.addEventListener("change", function () {
        const option = assessmentSurah.options[assessmentSurah.selectedIndex];
        const maximum = option ? option.dataset.verses || "" : "";
        const verseFields = document.querySelectorAll('[name="verse_start"], [name="verse_end"]');
        verseFields.forEach(function (field) { field.max = maximum; });
        document.getElementById("verseEnd").placeholder = maximum ? "Maksimal " + maximum : "";
    });
}

const murojaahJuz = document.getElementById("murojaahJuz");
if (murojaahJuz) {
    const murojaahSurahFields = [document.getElementById("murojaahStart"), document.getElementById("murojaahEnd")];
    murojaahJuz.addEventListener("change", function () {
        const selectedJuz = murojaahJuz.value;
        murojaahSurahFields.forEach(function (field) {
            field.value = "";
            field.disabled = !selectedJuz;
            field.options[0].textContent = selectedJuz ? "Pilih surat pada Juz " + selectedJuz : "Pilih Juz terlebih dahulu";
            Array.from(field.options).forEach(function (option, index) {
                if (index === 0) return;
                const matches = String(option.dataset.juz || "").split(",").includes(selectedJuz) && assessmentAllowedSurahs().includes(option.value);
                option.hidden = !matches;
                option.disabled = !matches;
            });
        });
    });
}

const transferStudent = document.getElementById("transferStudent");
if (transferStudent) {
    transferStudent.addEventListener("change", function () {
        const option = transferStudent.options[transferStudent.selectedIndex];
        const currentId = option ? option.dataset.halaqohId || "" : "";
        document.getElementById("transferCurrentHalaqoh").value = option ? option.dataset.halaqoh || "" : "";
        const destination = document.getElementById("transferDestination");
        Array.from(destination.options).forEach(function (halaqohOption, index) {
            halaqohOption.disabled = index > 0 && halaqohOption.value === currentId;
        });
        if (destination.value === currentId) destination.value = "";
    });
}

document.querySelectorAll(".edit-row").forEach(function (button) {
    button.addEventListener("click", function () {
        openForm(JSON.parse(button.dataset.row));
    });
});

async function editAssessment(assessmentId, button) {
        const originalText = button ? button.textContent : "";
        if (button) { button.disabled = true; button.textContent = "…"; }
        try {
            const response = await fetch("index.php?page=api-assessment-detail&id=" + encodeURIComponent(assessmentId), { headers: { "Accept": "application/json" } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || "Data penilaian tidak dapat dimuat.");
            const assessment = result.assessment;
            openForm();
            const form = document.getElementById("dataForm");
            document.getElementById("formTitle").textContent = "Edit Penilaian Santri";
            document.getElementById("recordId").value = assessment.id;
            const studentField = form.elements.namedItem("student_id");
            studentField.value = assessment.student_id;
            studentField.dispatchEvent(new Event("change"));
            form.elements.namedItem("date").value = assessment.date;
            const surahField = form.elements.namedItem("surah");
            surahField.value = assessment.surah;
            surahField.dispatchEvent(new Event("change"));
            const verseRange = String(assessment.verse_range || "").split("-");
            form.elements.namedItem("verse_start").value = verseRange[0] || "";
            form.elements.namedItem("verse_end").value = verseRange[1] || "";
            const juzField = form.elements.namedItem("murojaah_juz");
            juzField.value = assessment.murojaah_juz || "";
            juzField.dispatchEvent(new Event("change"));
            form.elements.namedItem("murojaah_start").value = assessment.murojaah_start || "";
            form.elements.namedItem("murojaah_end").value = assessment.murojaah_end || "";
            form.elements.namedItem("message").value = assessment.message || "";
            (result.scores || []).forEach(function (score) {
                let fieldName = "";
                if (score.section === "hafalan") fieldName = "hafalan_scores[" + score.indicator_id + "]";
                else if (score.section === "murojaah") fieldName = "murojaah_scores[" + score.indicator_id + "]";
                else if (String(score.section).startsWith("kategori:")) fieldName = "category_scores[" + String(score.section).split(":")[1] + "][" + score.indicator_id + "]";
                const scoreField = fieldName ? form.elements.namedItem(fieldName) : null;
                if (scoreField) scoreField.value = score.score;
            });
            (result.characters || []).forEach(function (character) {
                const characterFields = form.elements.namedItem("characters[" + character.aspect + "]");
                if (!characterFields) return;
                Array.from(characterFields.length === undefined ? [characterFields] : characterFields).forEach(function (field) { field.checked = field.value === character.grade; });
            });
        } catch (error) {
            window.alert(error.message);
        } finally {
            if (button) { button.disabled = false; button.textContent = originalText; }
        }
}

document.querySelectorAll(".edit-assessment").forEach(function (button) {
    button.addEventListener("click", function () {
        editAssessment(button.dataset.id, button);
    });
});

function historyText(value) {
    const element = document.createElement("span");
    element.textContent = String(value === null || value === undefined || value === "" ? "-" : value);
    return element.innerHTML;
}

document.querySelectorAll(".view-history").forEach(function (button) {
    button.addEventListener("click", async function () {
        const detail = document.getElementById("detailContent");
        const modal = document.getElementById("detailModal");
        const modalBox = modal.querySelector(".modal-box");
        document.getElementById("detailTitle").textContent = button.dataset.historyType === "report" ? "Riwayat Laporan" : "Riwayat Penilaian";
        modalBox.classList.add("history-modal-box");
        detail.innerHTML = '<div class="history-loading">Memuat riwayat santri...</div>';
        modal.classList.add("show"); modal.setAttribute("aria-hidden", "false");
        try {
            const response = await fetch("index.php?page=api-student-history&student_id=" + encodeURIComponent(button.dataset.studentId), { headers: { "Accept": "application/json" } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || "Riwayat tidak dapat dimuat.");
            const isReport = button.dataset.historyType === "report";
            detail.innerHTML = '<section class="history-summary"><div><small>Nama Santri</small><b>' + historyText(result.student) + '</b></div><div><small>Halaqoh</small><b>' + historyText(result.halaqoh) + '</b></div><div><small>Total Riwayat</small><b>' + result.history.length + ' penilaian</b></div></section><section class="assessment-history">' + result.history.map(function (item) {
                const statusClass = String(item.status || "").toLowerCase().replaceAll(" ", "-");
                let actions = '<a class="history-action print" href="' + historyText(item.print_url) + '" target="_blank">Cetak</a>';
                if (result.can_manage) {
                    actions = '<button type="button" class="history-action edit history-edit" data-id="' + Number(item.id) + '">Edit</button>' + actions;
                    if (isReport) actions += '<a class="history-action wa" href="' + historyText(item.whatsapp_url) + '" target="_blank" rel="noopener">WhatsApp</a><form method="post"><input type="hidden" name="csrf" value="' + historyText(result.csrf) + '"><input type="hidden" name="action" value="send_report_email"><input type="hidden" name="id" value="' + Number(item.id) + '"><button class="history-action email" type="submit">Email</button></form>';
                }
                return '<article class="history-item"><header><div><small>' + historyText(item.formatted_date) + '</small><b>' + historyText(item.surah) + ' ayat ' + historyText(item.verse_range) + '</b></div><span class="badge ' + historyText(statusClass) + '">' + historyText(item.status) + '</span></header><div class="history-scores"><div><small>Hafalan</small><b>' + (Number(item.memorization) * 2) + '</b></div><div><small>Murojaah</small><b>' + (Number(item.murojaah) * 2) + '</b></div><div class="final"><small>Nilai Akhir</small><b>' + Number(item.final_score) + '<em>/100</em></b></div></div><div class="history-meta"><span>Murojaah: <b>' + historyText(item.murojaah_start) + ' – ' + historyText(item.murojaah_end) + '</b></span><span>Penilai: <b>' + historyText(item.teacher) + '</b></span></div><p>' + historyText(item.message || "Belum ada catatan.") + '</p><footer>' + actions + '</footer></article>';
            }).join("") + '</section>';
            detail.querySelectorAll(".history-edit").forEach(function (editButton) {
                editButton.addEventListener("click", function () { closeModal("detailModal"); editAssessment(editButton.dataset.id, editButton); });
            });
        } catch (error) {
            detail.innerHTML = '<div class="history-loading error">' + historyText(error.message) + '</div>';
        }
    });
});

document.querySelectorAll(".view-detail").forEach(function (button) {
    button.addEventListener("click", async function () {
        document.querySelector("#detailModal .modal-box")?.classList.remove("history-modal-box");
        const row = JSON.parse(button.dataset.row);
        const detail = document.getElementById("detailContent");
        const hiddenFields = ["id", "password", "user_id", "guardian_user_id", "teacher_id", "student_id", "halaqoh_id", "category_id", "surah_ids", "juz_list", "surah_count"];

        detail.innerHTML = "";
        const detailLabels = {
            name: "Nama", student_code: "Nomor induk santri", nickname: "Nama panggilan", birth_date: "Tanggal lahir",
            gender: "Jenis kelamin", address: "Alamat", email: "Email",
            phone: "Nomor telepon", guardian_name: "Nama wali", guardian_phone: "WhatsApp wali",
            guardian_email: "Email wali", halaqoh: "Halaqoh", level: "Tingkat",
            coverage: "Cakupan surat", linked_surah_count: "Jumlah surat", teacher: "Ustadzah pembimbing",
            category: "Kategori", description: "Deskripsi", surah_number: "Nomor surat",
            verses: "Jumlah ayat", juz: "Juz", student: "Nama santri", date: "Tanggal penilaian",
            surah: "Surat", verse_range: "Rentang ayat", memorization: "Nilai hafalan",
            murojaah: "Nilai murojaah", murojaah_start: "Awal murojaah", murojaah_end: "Akhir murojaah",
            murojaah_juz: "Juz murojaah", status: "Status", message: "Pesan ustadzah",
            student_email: "Email santri", guardian_child_count: "Jumlah anak terhubung", account_active: "Status akun"
        };
        function safeText(text) {
            const element = document.createElement("span");
            element.textContent = String(text);
            return element.innerHTML;
        }

        if (button.dataset.entity === "surahs") {
            document.getElementById("detailTitle").textContent = "Detail Surat";
            detail.innerHTML = '<div class="surah-loading"><span></span><b>Memuat ayat Al-Qur’an...</b><small>Mengambil data dari EQuran.id</small></div>';
            const surahModal = document.getElementById("detailModal");
            surahModal.classList.add("show"); surahModal.setAttribute("aria-hidden", "false");
            if (!row.surah_number) {
                detail.innerHTML = '<div class="surah-error"><b>Ayat belum tersedia</b><span>Nomor resmi surat belum diisi. Sinkronkan Data Surat terlebih dahulu.</span></div>';
                return;
            }
            try {
                const response = await fetch("index.php?page=api-surah-verses&number=" + encodeURIComponent(row.surah_number), { headers: { "Accept": "application/json" } });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || "Data ayat tidak dapat dimuat.");
                const surah = result.data;
                const verses = Array.isArray(surah.ayat) ? surah.ayat : [];
                detail.innerHTML = '<section class="surah-detail"><header><div><small>Surat ke-' + safeText(surah.nomor || row.surah_number) + ' • ' + safeText(surah.jumlahAyat || row.verses) + ' ayat</small><h4>' + safeText(surah.namaLatin || row.name) + '</h4><span>' + safeText(surah.arti || "") + '</span></div><strong dir="rtl">' + safeText(surah.nama || "") + '</strong></header><div class="surah-source">Teks dan terjemahan dari <a href="https://equran.id/apidev/v2" target="_blank" rel="noopener">EQuran.id</a></div><div class="verse-list">' + verses.map(function (verse) {
                    return '<article class="verse-item"><div class="verse-number">' + safeText(verse.nomorAyat) + '</div><div><p class="verse-arabic" dir="rtl" lang="ar">' + safeText(verse.teksArab || "") + '</p><p class="verse-translation">' + safeText(verse.teksIndonesia || "") + '</p></div></article>';
                }).join("") + '</div></section>';
            } catch (error) {
                detail.innerHTML = '<div class="surah-error"><b>Ayat belum dapat dimuat</b><span>' + safeText(error.message) + ' Periksa koneksi internet lalu coba kembali.</span></div>';
            }
            return;
        }

        if (button.dataset.entity === "halaqoh") {
            const juzNumbers = String(row.juz_list || "").split(",").map(Number).filter(Boolean).filter(function (number, index, list) {
                return list.indexOf(number) === index;
            }).sort(function (a, b) { return a - b; });
            const juzBadges = juzNumbers.length
                ? juzNumbers.map(function (number) { return '<span class="halaqoh-juz">Juz ' + number + '</span>'; }).join("")
                : '<span class="muted">Cakupan Juz belum ditentukan</span>';
            detail.innerHTML =
                '<section class="halaqoh-detail">' +
                    '<div class="halaqoh-detail-hero"><span class="halaqoh-detail-icon" aria-hidden="true">◉</span><div><small>Kelompok Tahfidz</small><h4>' + safeText(row.name || "-") + '</h4><span>' + safeText(row.level || "Tingkat belum ditentukan") + '</span></div></div>' +
                    '<div class="halaqoh-detail-grid">' +
                        '<article><small>Ustadzah Pembimbing</small><strong>' + safeText(row.teacher || "Belum ditentukan") + '</strong></article>' +
                        '<article><small>Jumlah Surat</small><strong>' + safeText(row.linked_surah_count || 0) + ' surat</strong></article>' +
                    '</div>' +
                    '<div class="halaqoh-coverage"><small>Cakupan Juz</small><div>' + juzBadges + '</div></div>' +
                '</section>';
            document.getElementById("detailTitle").textContent = "Detail Halaqoh";
            const halaqohModal = document.getElementById("detailModal");
            halaqohModal.classList.add("show");
            halaqohModal.setAttribute("aria-hidden", "false");
            return;
        }

        if (button.dataset.entity === "reports") {
            const finalScore = Number(row.memorization || 0) + Number(row.murojaah || 0);
            detail.innerHTML =
                '<section class="report-detail">' +
                    '<div class="report-detail-head"><div><small>Laporan Perkembangan</small><h4>' + safeText(row.student || "-") + '</h4><span>' + safeText(row.halaqoh || "-") + ' • ' + safeText(row.date || "-") + '</span></div><strong>' + finalScore + '<small>/100</small></strong></div>' +
                    '<div class="report-detail-grid"><article><small>Setoran Hafalan</small><b>' + safeText((row.surah || "-") + " ayat " + (row.verse_range || "-")) + '</b></article><article><small>Murojaah</small><b>' + safeText((row.murojaah_start || "-") + " – " + (row.murojaah_end || "-")) + '</b><span>Juz ' + safeText(row.murojaah_juz || "-") + '</span></article><article><small>Nilai Hafalan</small><b>' + (Number(row.memorization || 0) * 2) + ' / 100</b></article><article><small>Nilai Murojaah</small><b>' + (Number(row.murojaah || 0) * 2) + ' / 100</b></article></div>' +
                    '<div class="report-detail-meta"><div><small>Ustadzah Penilai</small><b>' + safeText(row.teacher || "-") + '</b></div><span class="badge ' + safeText(String(row.status || "").toLowerCase().replaceAll(" ", "-")) + '">' + safeText(row.status || "-") + '</span></div>' +
                    '<div class="report-detail-note"><small>Catatan Ustadzah</small><p>' + safeText(row.message || "Belum ada catatan.") + '</p></div>' +
                '</section>';
            document.getElementById("detailTitle").textContent = "Detail Laporan";
            const reportModal = document.getElementById("detailModal");
            reportModal.classList.add("show"); reportModal.setAttribute("aria-hidden", "false");
            return;
        }

        document.getElementById("detailTitle").textContent = "Detail Data";
        Object.entries(row).filter(function (entry) {
            return !hiddenFields.includes(entry[0]);
        }).forEach(function (entry) {
            const label = detailLabels[entry[0]] || entry[0].replaceAll("_", " ").replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
            let value = entry[1] || "-";
            if (entry[0] === "gender") value = value === "L" ? "Laki-laki" : (value === "P" ? "Perempuan" : value);
            if (entry[0] === "account_active") value = String(value) === "1" ? "Aktif" : "Nonaktif";
            if (["date", "birth_date"].includes(entry[0]) && value !== "-") {
                value = new Date(value + "T00:00:00").toLocaleDateString("id-ID", { day: "2-digit", month: "long", year: "numeric" });
            }
            detail.innerHTML += "<div><dt>" + safeText(label) + "</dt><dd>" + safeText(value) + "</dd></div>";
        });

        const modal = document.getElementById("detailModal");
        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");
    });
});

function initializeDataTable(table) {
    const body = table.tBodies[0];
    if (!body) return;
    const originalRows = Array.from(body.rows).filter(function (row) { return !row.querySelector(".empty"); });
    if (!originalRows.length) return;

    let filteredRows = originalRows.slice();
    let currentPage = 1;
    let pageSize = Number(table.dataset.pageSize || 10);
    let sortColumn = -1;
    let sortAscending = true;
    const wrap = table.closest(".table-wrap");
    const container = document.createElement("div");
    container.className = "datatable";
    wrap.parentNode.insertBefore(container, wrap);
    container.appendChild(wrap);

    const controls = document.createElement("div");
    controls.className = "datatable-controls";
    controls.innerHTML = '<label class="datatable-search"><span aria-hidden="true">⌕</span><input type="search" placeholder="Cari dalam tabel..." aria-label="Cari dalam tabel"></label><label class="datatable-length">Tampilkan <select aria-label="Jumlah data per halaman"><option>5</option><option selected>10</option><option>25</option><option>50</option></select> data</label>';
    container.insertBefore(controls, wrap);
    const searchInput = controls.querySelector("input");
    const lengthSelect = controls.querySelector("select");
    lengthSelect.value = String(pageSize);

    const footer = document.createElement("div");
    footer.className = "datatable-footer";
    footer.innerHTML = '<span class="datatable-info"></span><div class="datatable-pages"></div>';
    container.appendChild(footer);

    function render() {
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * pageSize;
        originalRows.forEach(function (row) { row.hidden = true; });
        filteredRows.forEach(function (row) { body.appendChild(row); });
        filteredRows.slice(start, start + pageSize).forEach(function (row) { row.hidden = false; });
        footer.querySelector(".datatable-info").textContent = filteredRows.length
            ? "Menampilkan " + (start + 1) + "–" + Math.min(start + pageSize, filteredRows.length) + " dari " + filteredRows.length + " data"
            : "Tidak ada data yang sesuai";
        const pages = footer.querySelector(".datatable-pages");
        pages.innerHTML = "";
        [{ text: "‹", page: currentPage - 1, disabled: currentPage === 1 }].concat(
            Array.from({ length: totalPages }, function (_, index) { return { text: index + 1, page: index + 1, active: currentPage === index + 1 }; }),
            [{ text: "›", page: currentPage + 1, disabled: currentPage === totalPages }]
        ).forEach(function (item) {
            const button = document.createElement("button");
            button.type = "button"; button.textContent = item.text;
            button.disabled = item.disabled; button.className = item.active ? "active" : "";
            button.addEventListener("click", function () { currentPage = item.page; render(); });
            pages.appendChild(button);
        });
    }

    searchInput.addEventListener("input", function () {
        const keyword = searchInput.value.trim().toLocaleLowerCase("id");
        filteredRows = originalRows.filter(function (row) { return row.textContent.toLocaleLowerCase("id").includes(keyword); });
        currentPage = 1; render();
    });
    lengthSelect.addEventListener("change", function () { pageSize = Number(lengthSelect.value); currentPage = 1; render(); });
    Array.from(table.tHead ? table.tHead.rows[0].cells : []).forEach(function (header, columnIndex) {
        if (header.textContent.trim().toLowerCase() === "aksi") return;
        header.classList.add("sortable"); header.tabIndex = 0;
        function sortRows() {
            sortAscending = sortColumn === columnIndex ? !sortAscending : true; sortColumn = columnIndex;
            filteredRows.sort(function (a, b) { return a.cells[columnIndex].textContent.trim().localeCompare(b.cells[columnIndex].textContent.trim(), "id", { numeric: true }) * (sortAscending ? 1 : -1); });
            Array.from(table.tHead.rows[0].cells).forEach(function (cell) { cell.classList.remove("sort-asc", "sort-desc"); });
            header.classList.add(sortAscending ? "sort-asc" : "sort-desc"); currentPage = 1; render();
        }
        header.addEventListener("click", sortRows);
        header.addEventListener("keydown", function (event) { if (event.key === "Enter") sortRows(); });
    });
    render();
}

document.querySelectorAll("table[data-datatable]").forEach(initializeDataTable);

document.querySelectorAll(".modal").forEach(function (modal) {
    modal.addEventListener("click", function (event) {
        if (event.target === modal) closeModal(modal.id);
    });
});

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        document.querySelectorAll(".modal.show").forEach(function (modal) {
            closeModal(modal.id);
        });
    }
});

const confirmModal = document.getElementById("confirmModal");
let pendingForm = null;

document.querySelectorAll("form[data-confirm]").forEach(function (form) {
    form.addEventListener("submit", function (event) {
        if (form.dataset.confirmed === "true") return;
        event.preventDefault();
        pendingForm = form;

        const type = form.dataset.confirmType || "info";
        const icons = { danger: "!", warning: "!", success: "✓", info: "i" };
        const icon = document.getElementById("confirmIcon");
        icon.className = "confirm-icon " + type;
        icon.textContent = icons[type] || "?";
        document.getElementById("confirmTitle").textContent = form.dataset.confirmTitle || "Konfirmasi tindakan";
        document.getElementById("confirmMessage").textContent = form.dataset.confirmMessage || "Apakah Anda yakin ingin melanjutkan?";

        const submitButton = document.getElementById("confirmSubmit");
        submitButton.textContent = form.dataset.confirmButton || "Ya, lanjutkan";
        submitButton.className = "btn confirm-button " + type;
        confirmModal.classList.add("show");
        confirmModal.setAttribute("aria-hidden", "false");
    });
});

document.getElementById("confirmCancel")?.addEventListener("click", function () {
    pendingForm = null;
    closeModal("confirmModal");
});

document.getElementById("confirmSubmit")?.addEventListener("click", function () {
    if (!pendingForm) return;
    pendingForm.dataset.confirmed = "true";
    pendingForm.submit();
});
