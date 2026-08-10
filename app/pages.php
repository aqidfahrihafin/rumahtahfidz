<?php

function page_definitions($role)
{
    $definitions = array(
        'dashboard' => array('title' => 'Dashboard', 'description' => 'Ringkasan aktivitas dan perkembangan tahfidz.', 'icon' => '⌂', 'template' => 'dashboard'),
        'students' => array('title' => 'Data Santri', 'description' => 'Kelola identitas santri dan wali.', 'icon' => '♙', 'template' => 'data'),
        'teachers' => array('title' => 'Data Ustadzah', 'description' => 'Kelola ustadzah pembimbing halaqoh.', 'icon' => '♚', 'template' => 'data'),
        'halaqoh' => array('title' => 'Data Halaqoh', 'description' => 'Atur kelompok, tingkat, dan cakupan hafalan.', 'icon' => '◉', 'template' => 'data'),
        'transfers' => array('title' => 'Perpindahan Halaqoh', 'description' => 'Pindahkan santri dan simpan riwayat perubahan halaqoh.', 'icon' => '⇄', 'template' => 'transfers'),
        'categories' => array('title' => 'Kategori Penilaian', 'description' => 'Kelola kelompok kriteria evaluasi.', 'icon' => '◇', 'template' => 'data'),
        'indicators' => array('title' => 'Indikator Penilaian', 'description' => 'Kelola indikator dan deskripsi evaluasi.', 'icon' => '✓', 'template' => 'data'),
        'surahs' => array('title' => 'Data Surah', 'description' => 'Kelola referensi surah, ayat, dan juz.', 'icon' => '☾', 'template' => 'data'),
        'assessments' => array('title' => 'Data Penilaian', 'description' => 'Catat nilai hafalan dan murojaah santri.', 'icon' => '▣', 'template' => 'data'),
        'reports' => array('title' => 'Laporan Perkembangan', 'description' => 'Lihat dan cetak perkembangan santri.', 'icon' => '▤', 'template' => 'data'),
        'profile' => array('title' => 'Profil Saya', 'description' => 'Kelola informasi dan keamanan akun.', 'icon' => '●', 'template' => 'profile'),
    );

    if ($role === 'wali') {
        $definitions['students']['title'] = 'Data Anak';
        $definitions['students']['description'] = 'Lihat identitas dan halaqoh anak.';
    } elseif ($role === 'ustadzah') {
        $definitions['students']['title'] = 'Santri Bimbingan';
        $definitions['students']['description'] = 'Lihat santri dalam halaqoh bimbingan Anda.';
    }

    return $definitions;
}

function allowed_pages($role)
{
    if ($role === 'admin') {
        return array('dashboard', 'teachers', 'students', 'halaqoh', 'transfers', 'categories', 'indicators', 'surahs', 'assessments', 'reports', 'profile');
    }

    if ($role === 'ustadzah') {
        return array('dashboard', 'students', 'assessments', 'reports', 'profile');
    }

    return array('dashboard', 'students', 'reports', 'profile');
}

function data_page_config($page)
{
    $configs = array(
        'students' => array(
            'from' => 'students s LEFT JOIN halaqoh h ON h.id = s.halaqoh_id LEFT JOIN users account ON account.id = s.guardian_user_id',
            'select' => 's.*, h.name AS halaqoh, account.is_active AS account_active, (SELECT COUNT(*) FROM students sibling WHERE sibling.guardian_user_id = s.guardian_user_id) AS guardian_child_count',
            'headers' => array('Santri', 'JK', 'Alamat', 'Halaqoh', 'Wali', 'Kontak Wali', 'Status Akun'),
            'cells' => function ($row) { return array('<span class="table-person"><b>' . e($row['name']) . '</b><small>' . e($row['student_code']) . '</small></span>', e($row['gender']), e($row['address']), e($row['halaqoh']), '<span class="table-person"><b>' . e($row['guardian_name']) . '</b><small>' . (int)$row['guardian_child_count'] . ' anak terhubung</small></span>', '<span class="table-person table-contact"><a href="mailto:' . e($row['email']) . '">' . e($row['email']) . '</a><small>WA: ' . e($row['guardian_phone']) . '</small></span>', account_status_badge($row['guardian_user_id'], $row['account_active'])); },
            'table' => 'students', 'entity' => 'student', 'search' => 's.name'
        ),
        'teachers' => array(
            'from' => 'teachers t LEFT JOIN users account ON account.id = t.user_id', 'select' => 't.*, account.is_active AS account_active',
            'headers' => array('Nama Ustadzah', 'Alamat', 'Email', 'Kontak', 'Status Akun'),
            'cells' => function ($row) { return array(e($row['name']), e($row['address']), '<a href="mailto:' . e($row['email']) . '">' . e($row['email']) . '</a>', e($row['phone']), account_status_badge($row['user_id'], $row['account_active'])); },
            'table' => 'teachers', 'entity' => 'teacher', 'search' => 'name'
        ),
        'halaqoh' => array(
            'from' => 'halaqoh h LEFT JOIN teachers t ON t.id = h.teacher_id', 'select' => 'h.*, t.name AS teacher, (SELECT GROUP_CONCAT(s.juz) FROM halaqoh_surahs hs JOIN surahs s ON s.id=hs.surah_id WHERE hs.halaqoh_id=h.id) AS juz_list, (SELECT GROUP_CONCAT(s.id) FROM halaqoh_surahs hs JOIN surahs s ON s.id=hs.surah_id WHERE hs.halaqoh_id=h.id) AS surah_ids, (SELECT COUNT(*) FROM halaqoh_surahs hs WHERE hs.halaqoh_id=h.id) AS linked_surah_count',
            'headers' => array('Nama Halaqoh', 'Tingkat', 'Cakupan', 'Jumlah Surah', 'Pembimbing'),
            'cells' => function ($row) { return array(e($row['name']), e($row['level']), e(format_halaqoh_coverage($row['juz_list'])), e($row['linked_surah_count']), e($row['teacher'])); },
            'table' => 'halaqoh', 'entity' => 'halaqoh', 'search' => 'h.name'
        ),
        'categories' => array(
            'from' => 'categories', 'select' => '*', 'headers' => array('Nama Kategori'),
            'cells' => function ($row) { return array(e($row['name'])); },
            'table' => 'categories', 'entity' => 'category', 'search' => 'name'
        ),
        'indicators' => array(
            'from' => 'indicators i LEFT JOIN categories c ON c.id = i.category_id', 'select' => 'i.*, c.name AS category',
            'headers' => array('Kategori', 'Indikator', 'Deskripsi'),
            'cells' => function ($row) { return array(e($row['category']), e($row['name']), e($row['description'])); },
            'table' => 'indicators', 'entity' => 'indicator', 'search' => 'i.name'
        ),
        'surahs' => array(
            'from' => 'surahs', 'select' => '*', 'headers' => array('No. Surat', 'Nama Surat', 'Jumlah Ayat', 'Juz Awal'),
            'cells' => function ($row) { return array((int)$row['surah_number'] > 0 ? e($row['surah_number']) : '<span class="muted">—</span>', e($row['name']), e($row['verses']), (int)$row['juz'] > 0 ? e($row['juz']) : '<span class="muted">—</span>'); },
            'table' => 'surahs', 'entity' => 'surah', 'search' => 'name',
            'order' => 'CASE WHEN surah_number IS NULL OR surah_number = 0 THEN 1 ELSE 0 END ASC, juz ASC, surah_number ASC'
        ),
        'assessments' => array(
            'from' => 'assessments a JOIN students s ON s.id = a.student_id JOIN halaqoh h ON h.id = s.halaqoh_id',
            'select' => 'a.*, s.name AS student, s.email AS student_email, s.guardian_name, s.guardian_phone, h.name AS halaqoh',
            'headers' => array('Santri', 'Halaqoh', 'Jumlah Penilaian', 'Penilaian Terakhir', 'Setoran Terakhir', 'Rata-rata', 'Status Terakhir'),
            'cells' => function ($row) { return array(e($row['student']), e($row['halaqoh']), '<b>' . (int)$row['history_count'] . '</b> kali', e(format_date($row['date'])), e($row['surah'] . ' ayat ' . $row['verse_range']), '<b>' . e($row['average_score']) . ' / 100</b>', status_badge($row['status'])); },
            'table' => 'assessments', 'entity' => 'assessment', 'search' => 's.name', 'group_history' => true, 'order' => 'a.date DESC, a.id DESC'
        ),
        'reports' => array(
            'from' => 'assessments a JOIN students s ON s.id = a.student_id JOIN halaqoh h ON h.id = s.halaqoh_id LEFT JOIN teachers t ON t.id = a.teacher_id LEFT JOIN users u ON u.id = s.guardian_user_id',
            'select' => 'a.*, s.name AS student, COALESCE(u.email, s.email) AS guardian_email, s.guardian_name, s.guardian_phone, h.name AS halaqoh, t.name AS teacher',
            'headers' => array('Santri', 'Halaqoh', 'Jumlah Laporan', 'Laporan Terakhir', 'Ustadzah Penilai', 'Rata-rata', 'Status Terakhir'),
            'cells' => function ($row) { return array(e($row['student']), e($row['halaqoh']), '<b>' . (int)$row['history_count'] . '</b> laporan', e(format_date($row['date'])), e($row['teacher']), '<b>' . e($row['average_score']) . ' / 100</b>', status_badge($row['status'])); },
            'table' => '', 'entity' => '', 'search' => 's.name', 'group_history' => true, 'order' => 'a.date DESC, a.id DESC'
        ),
    );

    return isset($configs[$page]) ? $configs[$page] : null;
}

function format_date($date)
{
    $months = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

function status_badge($status)
{
    $class = strtolower(str_replace(' ', '-', $status));
    return '<span class="badge ' . e($class) . '">' . e($status) . '</span>';
}

function account_status_badge($userId, $isActive)
{
    if (!$userId) return '<span class="badge no-account">Belum ada akun</span>';
    return (int) $isActive === 1
        ? '<span class="badge active-account">Aktif</span>'
        : '<span class="badge inactive-account">Nonaktif</span>';
}
