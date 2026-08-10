<?php
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/pages.php';
require __DIR__ . '/app/reports.php';
require __DIR__ . '/app/accounts.php';
require __DIR__ . '/app/assessments.php';
require __DIR__ . '/app/quran_api.php';
require __DIR__ . '/app/halaqoh.php';

$page = $_GET['page'] ?? (user() ? 'dashboard' : 'home');
$action = $_POST['action'] ?? '';

if ($page === 'logout') { session_destroy(); redirect('index.php?page=login'); }
if ($page === 'home') {
    include __DIR__ . '/views/landing.php';
    exit;
}
if ($page === 'quran') {
    $publicSurahs = rows('SELECT id, surah_number, name, verses, juz FROM surahs WHERE surah_number BETWEEN 1 AND 114 ORDER BY surah_number');
    include __DIR__ . '/views/quran.php';
    exit;
}
if ($page === 'quran-read') {
    $publicSurah = row('SELECT * FROM surahs WHERE surah_number = ?', array((int) ($_GET['surah'] ?? 0)));
    $previousSurah = null;
    $nextSurah = null;
    $publicSurahDetail = null;
    $publicSurahError = '';
    if (!$publicSurah) {
        $publicSurahError = 'Surat tidak ditemukan.';
    } else {
        $previousSurah = row('SELECT surah_number, name FROM surahs WHERE surah_number < ? ORDER BY surah_number DESC LIMIT 1', array((int) $publicSurah['surah_number']));
        $nextSurah = row('SELECT surah_number, name FROM surahs WHERE surah_number > ? ORDER BY surah_number ASC LIMIT 1', array((int) $publicSurah['surah_number']));
        try {
            $publicResponse = fetch_json_url(QURAN_API_URL . '/' . (int) $publicSurah['surah_number']);
            if (empty($publicResponse['data']['ayat'])) throw new RuntimeException('Daftar ayat tidak ditemukan.');
            $publicSurahDetail = $publicResponse['data'];
        } catch (Throwable $exception) {
            $publicSurahError = $exception->getMessage();
        }
    }
    include __DIR__ . '/views/quran_read.php';
    exit;
}
if ($action === 'login') {
    verify_csrf();
    $account=row('SELECT * FROM users WHERE email=?', [trim($_POST['email'] ?? '')]);
    if ($account && (int) ($account['is_active'] ?? 1) === 0) {
        flash('error', 'Akun Anda belum aktif. Silakan hubungi administrator.');
        redirect('index.php?page=login');
    }
    if ($account && password_verify($_POST['password'] ?? '', $account['password'])) {
        unset($account['password']); $_SESSION['user']=$account; redirect('index.php');
    }
    flash('error','Email atau kata sandi tidak sesuai.'); redirect('index.php?page=login');
}
if ($page === 'login') { if(user()) redirect('index.php'); $flash=take_flash(); include __DIR__.'/views/login.php'; exit; }
require_login();

if ($page === 'api-student-history') {
    header('Content-Type: application/json; charset=UTF-8');
    $studentId = (int) ($_GET['student_id'] ?? 0);
    $historySql = 'SELECT a.*, s.name AS student, s.guardian_name, s.guardian_phone, COALESCE(u.email, s.email) AS guardian_email, h.name AS halaqoh, t.name AS teacher FROM assessments a JOIN students s ON s.id=a.student_id LEFT JOIN halaqoh h ON h.id=s.halaqoh_id LEFT JOIN teachers t ON t.id=a.teacher_id LEFT JOIN users u ON u.id=s.guardian_user_id WHERE s.id=?';
    $historyParams = array($studentId);
    if (user()['role'] === 'ustadzah') {
        $historySql .= ' AND a.teacher_id=?';
        $historyParams[] = (int) (scalar('SELECT id FROM teachers WHERE user_id=?', array(user()['id'])) ?: 0);
    } elseif (user()['role'] === 'wali') {
        $historySql .= ' AND s.guardian_user_id=?';
        $historyParams[] = (int) user()['id'];
    }
    $historySql .= ' ORDER BY a.date DESC, a.id DESC';
    $historyRows = rows($historySql, $historyParams);
    foreach ($historyRows as $historyIndex => $historyRow) {
        $historyRows[$historyIndex]['formatted_date'] = format_date($historyRow['date']);
        $historyRows[$historyIndex]['final_score'] = report_score($historyRow);
        $historyRows[$historyIndex]['print_url'] = 'index.php?page=print-report&id=' . (int) $historyRow['id'];
        $historyRows[$historyIndex]['whatsapp_url'] = whatsapp_report_url($historyRow);
    }
    echo json_encode(array('success' => true, 'student' => $historyRows ? $historyRows[0]['student'] : '', 'halaqoh' => $historyRows ? $historyRows[0]['halaqoh'] : '', 'can_manage' => user()['role'] !== 'wali', 'csrf' => csrf(), 'history' => $historyRows), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($page === 'api-assessment-detail') {
    header('Content-Type: application/json; charset=UTF-8');
    $assessmentId = (int) ($_GET['id'] ?? 0);
    $assessment = row('SELECT * FROM assessments WHERE id = ?', array($assessmentId));
    if (!$assessment || user()['role'] === 'wali') {
        http_response_code(404); echo json_encode(array('success' => false, 'message' => 'Penilaian tidak ditemukan.')); exit;
    }
    if (user()['role'] === 'ustadzah') {
        $teacherId = (int) (scalar('SELECT id FROM teachers WHERE user_id = ?', array(user()['id'])) ?: 0);
        if ((int) $assessment['teacher_id'] !== $teacherId) { http_response_code(403); echo json_encode(array('success' => false, 'message' => 'Anda tidak memiliki akses ke penilaian ini.')); exit; }
    }
    echo json_encode(array('success' => true, 'assessment' => $assessment, 'scores' => rows('SELECT section, indicator_id, score FROM assessment_scores WHERE assessment_id = ?', array($assessmentId)), 'characters' => rows('SELECT aspect, grade FROM assessment_characters WHERE assessment_id = ?', array($assessmentId))), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($page === 'api-surah-verses') {
    header('Content-Type: application/json; charset=UTF-8');
    $surahNumber = (int) ($_GET['number'] ?? 0);
    if ($surahNumber < 1 || $surahNumber > 114) {
        http_response_code(422);
        echo json_encode(array('success' => false, 'message' => 'Nomor surat tidak valid.'));
        exit;
    }
    try {
        $response = fetch_json_url(QURAN_API_URL . '/' . $surahNumber);
        if (empty($response['data']['ayat'])) throw new RuntimeException('Ayat surat tidak ditemukan.');
        echo json_encode(array('success' => true, 'data' => $response['data']), JSON_UNESCAPED_UNICODE);
    } catch (Throwable $exception) {
        http_response_code(502);
        echo json_encode(array('success' => false, 'message' => $exception->getMessage()), JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($action === 'sync_surahs') {
        require_role('admin');
        try {
            $syncResult = synchronize_surahs_from_api();
            flash('success', $syncResult['added'] . ' surat ditambahkan dan ' . $syncResult['updated'] . ' surat diperbarui dari API.');
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('index.php?page=surahs');
    }
    if ($action === 'toggle_account') {
        require_role('admin');
        try {
            $status = toggle_role_account((int) ($_POST['user_id'] ?? 0));
            flash('success', $status ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.');
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('index.php?page=' . ($_POST['return'] ?? 'dashboard'));
    }
    if ($action === 'create_account') {
        require_role('admin');
        try {
            create_account_from_profile($_POST['entity'] ?? '', (int) ($_POST['record_id'] ?? 0));
            flash('success', 'Akun berhasil dibuat dan langsung diaktifkan dengan password default.');
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('index.php?page=' . ($_POST['return'] ?? 'dashboard'));
    }
    if ($action === 'update_profile') {
        try {
            update_own_profile($_POST);
            flash('success', 'Profil berhasil diperbarui.');
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('index.php?page=profile');
    }
    if ($action === 'change_password') {
        try {
            change_own_password($_POST['current_password'] ?? '', $_POST['new_password'] ?? '', $_POST['password_confirmation'] ?? '');
            flash('success', 'Kata sandi berhasil diubah.');
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('index.php?page=profile');
    }
    if ($action === 'send_report_email') {
        require_role('admin', 'ustadzah');
        $report = find_report((int) ($_POST['id'] ?? 0));

        if (!$report) {
            flash('error', 'Laporan tidak ditemukan.');
        } elseif (send_report_email($report)) {
            flash('success', 'Laporan berhasil diserahkan ke server email untuk dikirim kepada wali.');
        } else {
            flash('error', 'Laporan ditolak oleh server email. Periksa layanan mail pada hosting dan alamat email wali.');
        }
        $emailReturn = $_POST['return'] ?? 'reports';
        if ($emailReturn === 'history') redirect('index.php?page=history&type=report&student_id=' . (int) ($_POST['student_id'] ?? 0));
        redirect('index.php?page=reports');
    }
    if ($action === 'transfer_student') {
        require_role('admin');
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $toHalaqohId = (int) ($_POST['to_halaqoh_id'] ?? 0);
        $transferDate = trim($_POST['transfer_date'] ?? '');
        $student = row('SELECT id, halaqoh_id FROM students WHERE id = ?', array($studentId));
        if (!$student || !row('SELECT id FROM halaqoh WHERE id = ?', array($toHalaqohId))) {
            flash('error', 'Santri atau Halaqoh tujuan tidak ditemukan.');
        } elseif ((int) $student['halaqoh_id'] === $toHalaqohId) {
            flash('error', 'Halaqoh tujuan harus berbeda dari Halaqoh saat ini.');
        } elseif ($transferDate === '') {
            flash('error', 'Tanggal perpindahan wajib diisi.');
        } else {
            $db->beginTransaction();
            try {
                $db->prepare('INSERT INTO student_halaqoh_history (student_id, from_halaqoh_id, to_halaqoh_id, transfer_date, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute(array($studentId, $student['halaqoh_id'], $toHalaqohId, $transferDate, trim($_POST['notes'] ?? ''), user()['id'], date('Y-m-d H:i:s')));
                $db->prepare('UPDATE students SET halaqoh_id = ? WHERE id = ?')->execute(array($toHalaqohId, $studentId));
                $db->commit();
                flash('success', 'Santri berhasil dipindahkan dan riwayat Halaqoh telah disimpan.');
            } catch (Throwable $exception) {
                $db->rollBack();
                flash('error', $exception->getMessage());
            }
        }
        redirect('index.php?page=transfers');
    }
    if ($action === 'transfer_halaqoh_bulk') {
        require_role('admin');
        $fromHalaqohId = (int) ($_POST['from_halaqoh_id'] ?? 0);
        $toHalaqohId = (int) ($_POST['to_halaqoh_id'] ?? 0);
        $transferDate = trim($_POST['transfer_date'] ?? '');
        $sourceHalaqoh = row('SELECT id, name FROM halaqoh WHERE id = ?', array($fromHalaqohId));
        $destinationHalaqoh = row('SELECT id, name FROM halaqoh WHERE id = ?', array($toHalaqohId));
        $studentsToTransfer = $sourceHalaqoh ? rows('SELECT id FROM students WHERE halaqoh_id = ? ORDER BY id', array($fromHalaqohId)) : array();

        if (!$sourceHalaqoh || !$destinationHalaqoh) {
            flash('error', 'Halaqoh asal atau tujuan tidak ditemukan.');
        } elseif ($fromHalaqohId === $toHalaqohId) {
            flash('error', 'Halaqoh tujuan harus berbeda dari Halaqoh asal.');
        } elseif ($transferDate === '') {
            flash('error', 'Tanggal perpindahan wajib diisi.');
        } elseif (!$studentsToTransfer) {
            flash('error', 'Tidak ada santri pada Halaqoh asal yang dapat dipindahkan.');
        } else {
            $db->beginTransaction();
            try {
                $historyStatement = $db->prepare('INSERT INTO student_halaqoh_history (student_id, from_halaqoh_id, to_halaqoh_id, transfer_date, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $notes = trim($_POST['notes'] ?? '');
                foreach ($studentsToTransfer as $studentToTransfer) {
                    $historyStatement->execute(array((int) $studentToTransfer['id'], $fromHalaqohId, $toHalaqohId, $transferDate, $notes, user()['id'], date('Y-m-d H:i:s')));
                }
                $db->prepare('UPDATE students SET halaqoh_id = ? WHERE halaqoh_id = ?')->execute(array($toHalaqohId, $fromHalaqohId));
                $db->commit();
                flash('success', count($studentsToTransfer) . ' santri berhasil dipindahkan dari ' . $sourceHalaqoh['name'] . ' ke ' . $destinationHalaqoh['name'] . '.');
            } catch (Throwable $exception) {
                $db->rollBack();
                flash('error', $exception->getMessage());
            }
        }
        redirect('index.php?page=transfers');
    }
    if ($action === 'save') {
        require_role('admin','ustadzah'); $entity=$_POST['entity'] ?? '';
        try {
            if ($entity === 'assessment') {
                save_detailed_assessment($_POST);
                flash('success', 'Penilaian santri berhasil disimpan.');
                redirect('index.php?page=assessments');
            }
            if ($entity === 'halaqoh') {
                require_role('admin');
                save_halaqoh_with_surahs($_POST);
                flash('success', 'Halaqoh dan cakupan surat berhasil disimpan.');
                redirect('index.php?page=halaqoh');
            }
            if ($entity === 'teacher') {
                require_role('admin');
                save_teacher_with_account($_POST);
                flash('success', 'Data dan akun Ustadzah berhasil disimpan.');
                redirect('index.php?page=teachers');
            }
            if ($entity === 'student' && user()['role'] === 'admin') {
                $studentResult = save_student_with_guardian($_POST);
                $studentMessage = 'Data santri dan akun wali berhasil disimpan.';
                if (!empty($studentResult['guardian_reused'])) {
                    $studentMessage = 'Data santri berhasil disimpan dan dihubungkan ke akun wali yang sama. Akun tersebut kini memiliki ' . (int) $studentResult['child_count'] . ' anak.';
                }
                flash('success', $studentMessage);
                redirect('index.php?page=students');
            }
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
            redirect('index.php?page=' . ($_POST['return'] ?? 'dashboard'));
        }
        $maps=[
            'student'=>['students',['name','nickname','birth_date','email','gender','address','halaqoh_id','guardian_name','guardian_phone']],
            'category'=>['categories',['name']],
            'indicator'=>['indicators',['category_id','name','description']],
            'surah'=>['surahs',['name','verses','juz']],
            'assessment'=>['assessments',['student_id','teacher_id','date','surah','verse_range','memorization','murojaah','status','message']]
        ];
        if (isset($maps[$entity])) {
            if ($entity === 'assessment' && !row('SELECT id FROM surahs WHERE name = ?', array(trim($_POST['surah'] ?? '')))) {
                flash('error', 'Surah yang dipilih tidak terdaftar pada master Data Surah.');
                redirect('index.php?page=assessments');
            }
            [$table,$fields]=$maps[$entity]; $vals=array_map(function($f){ return trim((string)($_POST[$f]??'')); },$fields);
            if ($entity==='assessment' && user()['role']==='ustadzah') $vals[1]=1;
            $id=(int)($_POST['id']??0);
            if($id){$set=implode(',',array_map(function($f){ return "$f=?"; },$fields));$vals[]=$id;$db->prepare("UPDATE $table SET $set WHERE id=?")->execute($vals);}
            else {$q=implode(',',array_fill(0,count($fields),'?'));$db->prepare('INSERT INTO '.$table.'('.implode(',',$fields).") VALUES($q)")->execute($vals);}
            flash('success','Data berhasil disimpan.');
        }
        redirect('index.php?page='.($_POST['return']??'dashboard'));
    }
    if ($action === 'delete') {
        require_role('admin','ustadzah'); $allowed=['students','teachers','halaqoh','categories','indicators','surahs','assessments']; $table=$_POST['table']??'';
        if(in_array($table,$allowed,true)){$db->prepare("DELETE FROM $table WHERE id=?")->execute([(int)$_POST['id']]);flash('success','Data berhasil dihapus.');}
        redirect('index.php?page='.($_POST['return']??'dashboard'));
    }
}

if ($page === 'print-students') {
    $studentConditions = array();
    $studentParameters = array();
    if (user()['role'] === 'ustadzah') {
        $studentConditions[] = 'h.teacher_id = ?';
        $studentParameters[] = (int) (scalar('SELECT id FROM teachers WHERE user_id = ?', array(user()['id'])) ?: 0);
    } elseif (user()['role'] === 'wali') {
        $studentConditions[] = 's.guardian_user_id = ?';
        $studentParameters[] = user()['id'];
    }
    $printHalaqohId = (int) ($_GET['halaqoh_id'] ?? 0);
    if ($printHalaqohId > 0) {
        $studentConditions[] = 's.halaqoh_id = ?';
        $studentParameters[] = $printHalaqohId;
    }
    $studentPrintSql = 'SELECT s.*, h.name AS halaqoh, t.name AS teacher FROM students s LEFT JOIN halaqoh h ON h.id = s.halaqoh_id LEFT JOIN teachers t ON t.id = h.teacher_id';
    if ($studentConditions) $studentPrintSql .= ' WHERE ' . implode(' AND ', $studentConditions);
    $studentPrintSql .= ' ORDER BY s.name';
    $printStudents = rows($studentPrintSql, $studentParameters);
    include __DIR__ . '/views/reports/students.php';
    exit;
}

if ($page === 'print-report' || $page === 'print-reports' || $page === 'print-student-reports') {
    $conditions = array();
    $parameters = array();

    if ($page === 'print-report') {
        $conditions[] = 'a.id = ?';
        $parameters[] = (int) ($_GET['id'] ?? 0);
    }
    if ($page === 'print-student-reports') {
        $conditions[] = 's.id = ?';
        $parameters[] = (int) ($_GET['student_id'] ?? 0);
    }
    if (user()['role'] === 'ustadzah') {
        $teacherId = (int) (scalar('SELECT id FROM teachers WHERE user_id = ?', array(user()['id'])) ?: 0);
        $conditions[] = 'a.teacher_id = ?';
        $parameters[] = $teacherId;
    } elseif (user()['role'] === 'wali') {
        $conditions[] = 's.guardian_user_id = ?';
        $parameters[] = user()['id'];
    }

    $printSql = 'SELECT a.*, s.name AS student, s.gender, s.address, s.guardian_name, s.guardian_phone,
                        h.name AS halaqoh, t.name AS teacher
                 FROM assessments a
                 JOIN students s ON s.id = a.student_id
                 LEFT JOIN halaqoh h ON h.id = s.halaqoh_id
                 LEFT JOIN teachers t ON t.id = a.teacher_id';
    if ($conditions) $printSql .= ' WHERE ' . implode(' AND ', $conditions);
    $printSql .= ' ORDER BY s.name, a.date DESC';
    $printReports = rows($printSql, $parameters);
    foreach ($printReports as $reportIndex => $printReport) $printReports[$reportIndex] = complete_report($printReport);
    include __DIR__ . '/views/reports/print.php';
    exit;
}

if ($page === 'surah-detail') {
    require_role('admin');
    $surahRecord = row('SELECT * FROM surahs WHERE id = ?', array((int) ($_GET['id'] ?? 0)));
    $surahDetail = null;
    $surahError = '';
    if (!$surahRecord) {
        $surahError = 'Data surat tidak ditemukan.';
    } elseif (empty($surahRecord['surah_number'])) {
        $surahError = 'Nomor resmi surat belum tersedia. Sinkronkan Data Surat terlebih dahulu.';
    } else {
        try {
            $surahResponse = fetch_json_url(QURAN_API_URL . '/' . (int) $surahRecord['surah_number']);
            if (empty($surahResponse['data']['ayat'])) throw new RuntimeException('Daftar ayat tidak ditemukan.');
            $surahDetail = $surahResponse['data'];
        } catch (Throwable $exception) {
            $surahError = $exception->getMessage();
        }
    }
    $role = user()['role'];
    $pages = page_definitions($role);
    $pageMeta = array('title' => $surahRecord ? 'Surat ' . $surahRecord['name'] : 'Detail Surat', 'description' => 'Bacaan dan terjemahan surat.', 'template' => 'surah_detail');
    $flash = take_flash();
    include __DIR__ . '/views/layout.php';
    exit;
}

if ($page === 'history') {
    $historyType = ($_GET['type'] ?? '') === 'report' ? 'report' : 'assessment';
    $historyBackPage = $historyType === 'report' ? 'reports' : 'assessments';
    $historyStudentId = (int) ($_GET['student_id'] ?? 0);
    $historySql = 'SELECT a.*, s.name AS student, s.guardian_name, s.guardian_phone, COALESCE(u.email, s.email) AS guardian_email, h.name AS halaqoh, t.name AS teacher FROM assessments a JOIN students s ON s.id=a.student_id LEFT JOIN halaqoh h ON h.id=s.halaqoh_id LEFT JOIN teachers t ON t.id=a.teacher_id LEFT JOIN users u ON u.id=s.guardian_user_id WHERE s.id=?';
    $historyParams = array($historyStudentId);
    if (user()['role'] === 'ustadzah') {
        $historySql .= ' AND a.teacher_id=?';
        $historyParams[] = (int) (scalar('SELECT id FROM teachers WHERE user_id=?', array(user()['id'])) ?: 0);
    } elseif (user()['role'] === 'wali') {
        $historySql .= ' AND s.guardian_user_id=?';
        $historyParams[] = (int) user()['id'];
    }
    $historySql .= ' ORDER BY a.date DESC, a.id DESC';
    $historyRows = rows($historySql, $historyParams);
    $historyStudent = $historyRows ? $historyRows[0] : null;
    $historyTotal = count($historyRows);
    $historyScoreTotal = 0;
    $historyBestScore = 0;
    foreach ($historyRows as $historyIndex => $historyRow) {
        $score = report_score($historyRow);
        $historyRows[$historyIndex]['final_score'] = $score;
        $historyScoreTotal += $score;
        $historyBestScore = max($historyBestScore, $score);
    }
    $historyAverage = $historyTotal ? round($historyScoreTotal / $historyTotal, 1) : 0;
    $role = user()['role'];
    $pages = page_definitions($role);
    $pageMeta = array('title' => $historyType === 'report' ? 'Riwayat Laporan' : 'Riwayat Penilaian', 'description' => 'Riwayat perkembangan santri.', 'template' => 'history');
    $flash = take_flash();
    include __DIR__ . '/views/layout.php';
    exit;
}

$role = user()['role'];
$pages = page_definitions($role);
$allowedPages = allowed_pages($role);

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$pageMeta = $pages[$page];
$flash = take_flash();
$selectedHalaqohId = (int) ($_GET['halaqoh_id'] ?? 0);

if ($page === 'transfers') {
    require_role('admin');
    $transferStudents = rows('SELECT s.id, s.student_code, s.name, s.halaqoh_id, h.name AS halaqoh FROM students s LEFT JOIN halaqoh h ON h.id = s.halaqoh_id ORDER BY s.name');
    $transferHalaqohs = rows('SELECT id, name, level FROM halaqoh ORDER BY name');
    $transferHistory = rows('SELECT sh.*, s.student_code, s.name AS student, hf.name AS from_halaqoh, ht.name AS to_halaqoh, u.name AS operator FROM student_halaqoh_history sh JOIN students s ON s.id = sh.student_id LEFT JOIN halaqoh hf ON hf.id = sh.from_halaqoh_id JOIN halaqoh ht ON ht.id = sh.to_halaqoh_id LEFT JOIN users u ON u.id = sh.created_by ORDER BY sh.transfer_date DESC, sh.id DESC');
    include __DIR__ . '/views/layout.php';
    exit;
}

$query = trim(isset($_GET['q']) ? $_GET['q'] : '');
$data = array();
$dataConfig = null;

if ($page !== 'dashboard' && $page !== 'profile') {
    $dataConfig = data_page_config($page);
    $conditions = array();
    $parameters = array();

    if ($query !== '') {
        $conditions[] = $dataConfig['search'] . ' LIKE ?';
        $parameters[] = '%' . $query . '%';
    }

    if ($page === 'students' && $role === 'wali') {
        $conditions[] = 's.guardian_user_id = ?';
        $parameters[] = user()['id'];
    } elseif ($page === 'students' && $role === 'ustadzah') {
        $conditions[] = 'h.teacher_id = ?';
        $parameters[] = (int) (scalar('SELECT id FROM teachers WHERE user_id = ?', array(user()['id'])) ?: 0);
    }
    if ($page === 'students' && $selectedHalaqohId > 0) {
        $conditions[] = 's.halaqoh_id = ?';
        $parameters[] = $selectedHalaqohId;
    }

    if (in_array($page, array('assessments', 'reports'), true)) {
        if ($role === 'ustadzah') {
            $conditions[] = 'a.teacher_id = ?';
            $parameters[] = (int) (scalar('SELECT id FROM teachers WHERE user_id = ?', array(user()['id'])) ?: 0);
        } elseif ($role === 'wali') {
            $conditions[] = 's.guardian_user_id = ?';
            $parameters[] = user()['id'];
        }
    }

    $sql = 'SELECT ' . $dataConfig['select'] . ' FROM ' . $dataConfig['from'];
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    if (isset($dataConfig['group'])) {
        $sql .= ' GROUP BY ' . $dataConfig['group'];
    }
    $sql .= ' ORDER BY ' . (isset($dataConfig['order']) ? $dataConfig['order'] : '1 DESC');
    $data = rows($sql, $parameters);
    if (!empty($dataConfig['group_history'])) {
        $groupedData = array();
        foreach ($data as $record) {
            $studentKey = (int) $record['student_id'];
            if (!isset($groupedData[$studentKey])) {
                $groupedData[$studentKey] = $record;
                $groupedData[$studentKey]['history_count'] = 0;
                $groupedData[$studentKey]['score_total'] = 0;
            }
            $groupedData[$studentKey]['history_count']++;
            $groupedData[$studentKey]['score_total'] += (int) $record['memorization'] + (int) $record['murojaah'];
        }
        foreach ($groupedData as $studentKey => $record) {
            $groupedData[$studentKey]['average_score'] = round($record['score_total'] / max(1, $record['history_count']), 1);
        }
        $data = array_values($groupedData);
    }
}

include __DIR__ . '/views/layout.php';
