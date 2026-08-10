<?php
declare(strict_types=1);

session_start();

const ROOT = __DIR__ . '/..';
const DB_FILE = ROOT . '/storage/tahfidz.sqlite';
const DEFAULT_ACCOUNT_PASSWORD = 'Tahfidz123!';

$databaseConfig = require ROOT . '/config/database.php';

if (!is_dir(ROOT . '/storage')) {
    mkdir(ROOT . '/storage', 0777, true);
}

$drivers = PDO::getAvailableDrivers();
$configuredDriver = isset($databaseConfig['driver']) ? $databaseConfig['driver'] : 'auto';
$preferredDriver = strtolower((string)(getenv('TAHFIDZ_DB_DRIVER') ?: $configuredDriver));
try {
    if ($preferredDriver !== 'mysql' && in_array('sqlite', $drivers, true)) {
        $sqlitePath = getenv('TAHFIDZ_SQLITE_PATH') ?: $databaseConfig['sqlite']['path'];
        $db = new PDO('sqlite:' . $sqlitePath);
    } elseif (in_array('mysql', $drivers, true)) {
        $mysql = $databaseConfig['mysql'];
        $host = getenv('TAHFIDZ_DB_HOST') ?: $mysql['host'];
        $port = getenv('TAHFIDZ_DB_PORT') ?: $mysql['port'];
        $name = getenv('TAHFIDZ_DB_NAME') ?: $mysql['database'];
        $username = getenv('TAHFIDZ_DB_USER') ?: $mysql['username'];
        $password = getenv('TAHFIDZ_DB_PASS') !== false ? getenv('TAHFIDZ_DB_PASS') : $mysql['password'];
        $charset = isset($mysql['charset']) ? $mysql['charset'] : 'utf8mb4';
        $server = new PDO("mysql:host=$host;port=$port;charset=$charset", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $name) ?: 'rumahtahfidz';
        $server->exec("CREATE DATABASE IF NOT EXISTS `$safeName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db = new PDO("mysql:host=$host;port=$port;dbname=$safeName;charset=$charset", $username, $password);
    } else {
        throw new RuntimeException('PHP tidak memiliki driver SQLite maupun MySQL.');
    }
} catch (Throwable $exception) {
    http_response_code(500);
    exit('<h2>Koneksi database gagal</h2><p>Pastikan MySQL Laragon sedang berjalan atau aktifkan ekstensi <code>pdo_sqlite</code>.</p><pre>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>');
}
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') $db->exec('PRAGMA foreign_keys = ON');

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url) { header('Location: ' . $url); exit; }
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Sesi formulir kedaluwarsa. Silakan muat ulang halaman.');
    }
}
function flash(string $type, string $message): void { $_SESSION['flash'] = compact('type', 'message'); }
function take_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!user()) redirect('index.php?page=login'); }
function require_role(string ...$roles): void { require_login(); if (!in_array(user()['role'], $roles, true)) redirect('index.php'); }
function rows(string $sql, array $params = []): array { global $db; $s=$db->prepare($sql); $s->execute($params); return $s->fetchAll(); }
function row(string $sql, array $params = []): ?array { global $db; $s=$db->prepare($sql); $s->execute($params); return $s->fetch() ?: null; }
function scalar(string $sql, array $params = []) { return array_values(row($sql,$params) ?? [null])[0]; }

function initialize_database(PDO $db): void {
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150), email VARCHAR(190) UNIQUE, password VARCHAR(255), role VARCHAR(30), phone VARCHAR(30), is_active TINYINT(1) NOT NULL DEFAULT 1);
        CREATE TABLE IF NOT EXISTS teachers (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, name VARCHAR(150), address TEXT, email VARCHAR(190), phone VARCHAR(30));
        CREATE TABLE IF NOT EXISTS halaqoh (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150), level VARCHAR(100), coverage TEXT, surah_count INT, teacher_id INT NULL);
        CREATE TABLE IF NOT EXISTS halaqoh_surahs (halaqoh_id INT, surah_id INT, PRIMARY KEY (halaqoh_id, surah_id));
        CREATE TABLE IF NOT EXISTS students (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150), nickname VARCHAR(100), birth_date DATE NULL, email VARCHAR(190), gender VARCHAR(2), address TEXT, halaqoh_id INT NULL, guardian_name VARCHAR(150), guardian_phone VARCHAR(30), guardian_user_id INT NULL);
        CREATE TABLE IF NOT EXISTS categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100));
        CREATE TABLE IF NOT EXISTS indicators (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id INT NULL, name VARCHAR(150), description TEXT);
        CREATE TABLE IF NOT EXISTS surahs (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, surah_number INT NULL, name VARCHAR(150), verses INT, juz INT);
        CREATE TABLE IF NOT EXISTS surah_juz (surah_id INT, juz INT, PRIMARY KEY (surah_id, juz));
        CREATE TABLE IF NOT EXISTS assessments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, student_id INT, teacher_id INT, date DATE, surah VARCHAR(150), verse_range VARCHAR(50), murojaah_start VARCHAR(150), murojaah_end VARCHAR(150), murojaah_juz INT, memorization INT, murojaah INT, status VARCHAR(50), message TEXT);
        CREATE TABLE IF NOT EXISTS assessment_scores (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, assessment_id INT, section VARCHAR(30), indicator_id INT, score INT);
        CREATE TABLE IF NOT EXISTS assessment_characters (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, assessment_id INT, aspect VARCHAR(50), grade VARCHAR(10));
        CREATE TABLE IF NOT EXISTS activities (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, date DATE, actor VARCHAR(150), menu VARCHAR(150), activity TEXT)");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT, email TEXT UNIQUE, password TEXT, role TEXT, phone TEXT, is_active INTEGER NOT NULL DEFAULT 1);
    CREATE TABLE IF NOT EXISTS teachers (id INTEGER PRIMARY KEY, user_id INTEGER, name TEXT, address TEXT, email TEXT, phone TEXT);
    CREATE TABLE IF NOT EXISTS halaqoh (id INTEGER PRIMARY KEY, name TEXT, level TEXT, coverage TEXT, surah_count INTEGER, teacher_id INTEGER);
    CREATE TABLE IF NOT EXISTS halaqoh_surahs (halaqoh_id INTEGER, surah_id INTEGER, PRIMARY KEY (halaqoh_id, surah_id));
    CREATE TABLE IF NOT EXISTS students (id INTEGER PRIMARY KEY, name TEXT, nickname TEXT, birth_date TEXT, email TEXT, gender TEXT, address TEXT, halaqoh_id INTEGER, guardian_name TEXT, guardian_phone TEXT, guardian_user_id INTEGER);
    CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY, name TEXT);
    CREATE TABLE IF NOT EXISTS indicators (id INTEGER PRIMARY KEY, category_id INTEGER, name TEXT, description TEXT);
    CREATE TABLE IF NOT EXISTS surahs (id INTEGER PRIMARY KEY, surah_number INTEGER, name TEXT, verses INTEGER, juz INTEGER);
    CREATE TABLE IF NOT EXISTS surah_juz (surah_id INTEGER, juz INTEGER, PRIMARY KEY (surah_id, juz));
    CREATE TABLE IF NOT EXISTS assessments (id INTEGER PRIMARY KEY, student_id INTEGER, teacher_id INTEGER, date TEXT, surah TEXT, verse_range TEXT, murojaah_start TEXT, murojaah_end TEXT, murojaah_juz INTEGER, memorization INTEGER, murojaah INTEGER, status TEXT, message TEXT);
    CREATE TABLE IF NOT EXISTS assessment_scores (id INTEGER PRIMARY KEY, assessment_id INTEGER, section TEXT, indicator_id INTEGER, score INTEGER);
    CREATE TABLE IF NOT EXISTS assessment_characters (id INTEGER PRIMARY KEY, assessment_id INTEGER, aspect TEXT, grade TEXT);
    CREATE TABLE IF NOT EXISTS activities (id INTEGER PRIMARY KEY, date TEXT, actor TEXT, menu TEXT, activity TEXT);");
    }
    if ((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) return;
    $pass = password_hash('password', PASSWORD_DEFAULT);
    $u=$db->prepare('INSERT INTO users(name,email,password,role,phone) VALUES(?,?,?,?,?)');
    foreach ([['Bapak Zubaidi Rowy','admin@tahfidz.test','admin','081200000001'],['Ustadzah Unsiyah','ustadzah@tahfidz.test','ustadzah','081200000002'],["Ibu Wadi'ah",'wali@tahfidz.test','wali','082234567890']] as $x) $u->execute([$x[0],$x[1],$pass,$x[2],$x[3]]);
    $teachers=[['Unsiyah','Ganding Timur','unsiyah@rumahtahfidz.id','081234567890'],["Annafi'ah",'Ganding Timur','annafiah@rumahtahfidz.id','081345678901'],['Ruhana','Ganding Timur','ruhana@rumahtahfidz.id','081456789012'],['Mamtuhah','Lenteng Barat','mamtuhah@rumahtahfidz.id','081567890123'],['Lubna Annajud','Lenteng Barat','lubna@rumahtahfidz.id','081678901234'],['Masniyatur Royhanah','Lenteng Barat','masniyatur@rumahtahfidz.id','081789012345']];
    $s=$db->prepare('INSERT INTO teachers(user_id,name,address,email,phone) VALUES(?,?,?,?,?)'); foreach($teachers as $i=>$x)$s->execute(array_merge([$i===0?2:null],$x));
    $halaqoh=[['Ula 1','Tingkat Dasar','Juz 30 (An-Nas–Al-Lail)',23,1],['Ula 2','Tingkat Dasar',"Juz 30 (An-Nas–An-Naba')",37,2],['Wustha 1','Tingkat Menengah','Juz 30–29',48,3],['Wustha 2','Tingkat Menengah','Juz 30–29–1',55,4],['Ulya 1','Tingkat Atas','Juz 30–29–1–2',62,5],['Ulya 2','Tingkat Atas','Juz 30–29–1–2–3–4',74,6]];
    $s=$db->prepare('INSERT INTO halaqoh(name,level,coverage,surah_count,teacher_id) VALUES(?,?,?,?,?)'); foreach($halaqoh as $x)$s->execute($x);
    $students=[['Ahmad Waris','abdullah@rumahtahfidz.id','L','Ganding Timur',1,'Abdullah','081234567890',null],['Hanun','musfit@rumahtahfidz.id','P','Lenteng Barat',1,'Musfit','081345678901',null],['Elliyah','fatimoh@rumahtahfidz.id','P','Ganding Timur',6,'Fatimoh','081456789012',null],['Muhammad Fadli','hasan@rumahtahfidz.id','L','Lenteng Barat',1,'Hasan','081567890123',null],['Aisyah Zahra','yusuf@rumahtahfidz.id','P','Ganding Timur',1,'Yusuf','081678901234',null],['Siti Khadijah','hamzah@rumahtahfidz.id','P','Ganding Timur',1,'Hamzah','081890123456',null],['Nabila Putri','hasyim@rumahtahfidz.id','P','Ganding Timur',1,'Hasyim','082234567890',null],['Muhammad Arif','mahmud@rumahtahfidz.id','L','Lenteng Barat',1,'Mahmud','082345678901',null],["Gyta Surur Ghibtani",'wadiah@rumahtahfidz.id','P','Ganding Timur',6,"Wadi'ah",'082234567890',3]];
    $s=$db->prepare('INSERT INTO students(name,email,gender,address,halaqoh_id,guardian_name,guardian_phone,guardian_user_id) VALUES(?,?,?,?,?,?,?,?)'); foreach($students as $x)$s->execute($x);
    foreach(['Hafalan',"Muroja'ah"] as $x)$db->prepare('INSERT INTO categories(name) VALUES(?)')->execute([$x]);
    $inds=[[1,'Kelancaran','Hafalan dibaca lancar tanpa banyak kesalahan.'],[1,'Tajwid','Hukum tajwid diterapkan dengan benar.'],[1,'Makharijul Huruf','Huruf hijaiyah diucapkan sesuai makhraj.'],[1,'Tartil','Bacaan dilakukan dengan tempo yang baik dan benar.'],[1,"Waqaf & Ibtida'",'Berhenti dan memulai bacaan sesuai kaidah.'],[2,'Mutqin','Hafalan tetap kuat dan tidak mudah lupa.'],[2,'Tajwid',"Tajwid tetap benar saat muroja'ah."]];
    $s=$db->prepare('INSERT INTO indicators(category_id,name,description) VALUES(?,?,?)');foreach($inds as $x)$s->execute($x);
    $surahs=[['Al-Fatihah',7,1],['Al-Baqarah',286,2],["Ali 'Imran",200,3],["An-Nisa'",176,4],['Al-Mulk',30,29],['Al-Qalam',52,29],['Al-Haqqah',52,29],["An-Naba'",40,30],["An-Nazi'at",46,30],["'Abasa",42,30],['At-Takwir',29,30],['Al-Infitar',19,30]];
    $s=$db->prepare('INSERT INTO surahs(name,verses,juz) VALUES(?,?,?)');foreach($surahs as $x)$s->execute($x);
    $assessments=[[1,1,'2026-06-03','Al-Lail','8-20',43,42,'Lancar','Hafalan berkembang baik.'],[2,1,'2026-06-03','Asy-Syams','1-15',41,41,'Kurang Lancar','Tingkatkan kelancaran.'],[5,1,'2026-06-02','Adh-Dhuha','1-11',44,43,'Lancar',"Tingkatkan muroja'ah."],[6,1,'2026-06-02','Al-Insyirah','1-8',45,44,'Lancar','Hafalan sangat baik.'],[7,1,'2026-06-01','At-Tin','1-8',43,44,'Lancar','Pertahankan semangat.'],[9,1,'2026-06-03',"An-Nisa'",'1-5',45,45,'Lancar','Hafalan sangat baik, pertahankan.']];
    $s=$db->prepare('INSERT INTO assessments(student_id,teacher_id,date,surah,verse_range,memorization,murojaah,status,message) VALUES(?,?,?,?,?,?,?,?,?)');foreach($assessments as $x)$s->execute($x);
    $acts=[['2026-06-03','Unsiyah','Penilaian Hafalan','Menambahkan penilaian hafalan santri.'],['2026-06-03','Ruhana','Penilaian Hafalan','Menambahkan penilaian hafalan santri.'],['2026-06-02','Admin','Data Santri','Memperbarui data santri Muhammad Arif.']];
    $s=$db->prepare('INSERT INTO activities(date,actor,menu,activity) VALUES(?,?,?,?)');foreach($acts as $x)$s->execute($x);
}
initialize_database($db);

function apply_account_status_migration(PDO $db): void {
    try {
        $db->query('SELECT is_active FROM users LIMIT 1');
    } catch (Throwable $exception) {
        $db->exec('ALTER TABLE users ADD COLUMN is_active INTEGER NOT NULL DEFAULT 1');
    }
}

apply_account_status_migration($db);

function apply_student_profile_migration(PDO $db): void {
    foreach (array('nickname TEXT', 'birth_date DATE') as $column) {
        try {
            $name = explode(' ', $column)[0];
            $db->query('SELECT ' . $name . ' FROM students LIMIT 1');
        } catch (Throwable $exception) {
            $db->exec('ALTER TABLE students ADD COLUMN ' . $column);
        }
    }
}

apply_student_profile_migration($db);

function apply_student_code_migration(PDO $db): void {
    try {
        $db->query('SELECT student_code FROM students LIMIT 1');
    } catch (Throwable $exception) {
        $db->exec('ALTER TABLE students ADD COLUMN student_code VARCHAR(30)');
    }

    $students = $db->query("SELECT id FROM students WHERE student_code IS NULL OR student_code = '' ORDER BY id")->fetchAll();
    $update = $db->prepare('UPDATE students SET student_code = ? WHERE id = ?');
    foreach ($students as $student) {
        $id = (int) $student['id'];
        $update->execute(array('RTAS-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT), $id));
    }
}

apply_student_code_migration($db);

function apply_teacher_code_migration(PDO $db): void {
    try {
        $db->query('SELECT teacher_code FROM teachers LIMIT 1');
    } catch (Throwable $exception) {
        $db->exec('ALTER TABLE teachers ADD COLUMN teacher_code VARCHAR(30)');
    }

    $teachers = $db->query("SELECT id FROM teachers WHERE teacher_code IS NULL OR teacher_code = '' ORDER BY id")->fetchAll();
    $update = $db->prepare('UPDATE teachers SET teacher_code = ? WHERE id = ?');
    foreach ($teachers as $teacher) {
        $id = (int) $teacher['id'];
        $update->execute(array('UST-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT), $id));
    }
}

apply_teacher_code_migration($db);

function apply_halaqoh_transfer_migration(PDO $db): void {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $db->exec('CREATE TABLE IF NOT EXISTS student_halaqoh_history (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, from_halaqoh_id INT NULL, to_halaqoh_id INT NOT NULL, transfer_date DATE NOT NULL, notes TEXT, created_by INT NULL, created_at DATETIME NOT NULL)');
    } else {
        $db->exec('CREATE TABLE IF NOT EXISTS student_halaqoh_history (id INTEGER PRIMARY KEY, student_id INTEGER NOT NULL, from_halaqoh_id INTEGER, to_halaqoh_id INTEGER NOT NULL, transfer_date TEXT NOT NULL, notes TEXT, created_by INTEGER, created_at TEXT NOT NULL)');
    }
}

apply_halaqoh_transfer_migration($db);

function apply_assessment_detail_migration(PDO $db): void {
    foreach (array('murojaah_start TEXT', 'murojaah_end TEXT', 'murojaah_juz INTEGER') as $column) {
        try {
            $name = explode(' ', $column)[0];
            $db->query('SELECT ' . $name . ' FROM assessments LIMIT 1');
        } catch (Throwable $exception) {
            $db->exec('ALTER TABLE assessments ADD COLUMN ' . $column);
        }
    }
}

apply_assessment_detail_migration($db);

function apply_surah_number_migration(PDO $db): void {
    try {
        $db->query('SELECT surah_number FROM surahs LIMIT 1');
    } catch (Throwable $exception) {
        $db->exec('ALTER TABLE surahs ADD COLUMN surah_number INTEGER');
    }
}

apply_surah_number_migration($db);

function apply_surah_juz_migration(PDO $db): void {
    $db->exec('CREATE TABLE IF NOT EXISTS surah_juz (surah_id INTEGER, juz INTEGER, PRIMARY KEY (surah_id, juz))');
    $insert = $db->prepare('INSERT INTO surah_juz (surah_id, juz) VALUES (?, ?)');
    foreach ($db->query('SELECT id, juz FROM surahs WHERE juz BETWEEN 1 AND 30')->fetchAll() as $surah) {
        try { $insert->execute(array((int) $surah['id'], (int) $surah['juz'])); } catch (Throwable $exception) { /* Relasi sudah tersedia. */ }
    }
}

apply_surah_juz_migration($db);

function apply_halaqoh_surah_migration(PDO $db): void {
    $halaqohs = $db->query('SELECT id, surah_count FROM halaqoh')->fetchAll();
    $check = $db->prepare('SELECT COUNT(*) FROM halaqoh_surahs WHERE halaqoh_id = ?');
    $surahQuery = $db->prepare('SELECT id FROM surahs WHERE surah_number IS NOT NULL ORDER BY surah_number DESC LIMIT ?');
    $insert = $db->prepare('INSERT INTO halaqoh_surahs (halaqoh_id, surah_id) VALUES (?, ?)');
    foreach ($halaqohs as $halaqoh) {
        $check->execute(array($halaqoh['id']));
        if ((int)$check->fetchColumn() > 0 || (int)$halaqoh['surah_count'] < 1) continue;
        $surahQuery->bindValue(1, (int)$halaqoh['surah_count'], PDO::PARAM_INT);
        $surahQuery->execute();
        foreach ($surahQuery->fetchAll() as $surah) $insert->execute(array($halaqoh['id'], $surah['id']));
    }
}

apply_halaqoh_surah_migration($db);

function apply_default_password_migration(PDO $db): void {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $db->exec('CREATE TABLE IF NOT EXISTS app_settings (`key` VARCHAR(100) PRIMARY KEY, `value` TEXT)');
    } else {
        $db->exec('CREATE TABLE IF NOT EXISTS app_settings (key TEXT PRIMARY KEY, value TEXT)');
    }

    $statement = $db->prepare('SELECT value FROM app_settings WHERE `key` = ?');
    $statement->execute(array('default_password_v1'));
    if ($statement->fetchColumn() !== false) return;

    $db->prepare("UPDATE users SET password = ? WHERE role IN ('ustadzah', 'wali')")
        ->execute(array(password_hash(DEFAULT_ACCOUNT_PASSWORD, PASSWORD_DEFAULT)));
    $db->prepare('INSERT INTO app_settings (`key`, `value`) VALUES (?, ?)')
        ->execute(array('default_password_v1', date('c')));
}

apply_default_password_migration($db);
