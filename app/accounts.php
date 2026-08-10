<?php

function find_user_by_email($email)
{
    return row('SELECT * FROM users WHERE email = ?', array($email));
}

function save_role_account($role, $name, $email, $phone, $password, $userId = 0)
{
    global $db;

    $existing = $userId ? row('SELECT * FROM users WHERE id = ?', array($userId)) : find_user_by_email($email);
    if ($existing && $existing['role'] !== $role) {
        throw new InvalidArgumentException('Email sudah dipakai oleh akun dengan peran lain.');
    }
    if (!$existing && $password === '') $password = DEFAULT_ACCOUNT_PASSWORD;

    if ($existing) {
        $sql = 'UPDATE users SET name = ?, email = ?, role = ?, phone = ?';
        $params = array($name, $email, $role, $phone);
        if ($password !== '') {
            $sql .= ', password = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = ?';
        $params[] = $existing['id'];
        $db->prepare($sql)->execute($params);
        return (int) $existing['id'];
    }

    $statement = $db->prepare('INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)');
    $statement->execute(array($name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $phone));
    return (int) $db->lastInsertId();
}

function save_teacher_with_account($input)
{
    global $db;
    $id = (int) ($input['id'] ?? 0);
    $current = $id ? row('SELECT * FROM teachers WHERE id = ?', array($id)) : null;
    $userId = save_role_account(
        'ustadzah', trim($input['name']), trim($input['email']), trim($input['phone']),
        (string) ($input['login_password'] ?? ''), $current ? (int) $current['user_id'] : 0
    );

    if ($current) {
        $db->prepare('UPDATE teachers SET user_id = ?, name = ?, address = ?, email = ?, phone = ? WHERE id = ?')
            ->execute(array($userId, trim($input['name']), trim($input['address']), trim($input['email']), trim($input['phone']), $id));
    } else {
        $db->prepare('INSERT INTO teachers (user_id, name, address, email, phone) VALUES (?, ?, ?, ?, ?)')
            ->execute(array($userId, trim($input['name']), trim($input['address']), trim($input['email']), trim($input['phone'])));
    }
}

function save_student_with_guardian($input)
{
    global $db;
    $id = (int) ($input['id'] ?? 0);
    $current = $id ? row('SELECT * FROM students WHERE id = ?', array($id)) : null;
    $existingGuardian = !$current ? find_user_by_email(trim($input['email'])) : null;
    $guardianName = $existingGuardian ? $existingGuardian['name'] : trim($input['guardian_name']);
    $guardianEmail = $existingGuardian ? $existingGuardian['email'] : trim($input['email']);
    $guardianPhone = $existingGuardian ? $existingGuardian['phone'] : trim($input['guardian_phone']);
    $guardianUserId = save_role_account(
        'wali', $guardianName, $guardianEmail, $guardianPhone,
        (string) ($input['login_password'] ?? ''), $current ? (int) $current['guardian_user_id'] : 0
    );
    $values = array(trim($input['name']), trim($input['nickname'] ?? ''), trim($input['birth_date'] ?? ''), $guardianEmail, trim($input['gender']), trim($input['address']), (int) $input['halaqoh_id'], $guardianName, $guardianPhone, $guardianUserId);

    if ($current) {
        $values[] = $id;
        $db->prepare('UPDATE students SET name = ?, nickname = ?, birth_date = ?, email = ?, gender = ?, address = ?, halaqoh_id = ?, guardian_name = ?, guardian_phone = ?, guardian_user_id = ? WHERE id = ?')->execute($values);
    } else {
        $db->prepare('INSERT INTO students (name, nickname, birth_date, email, gender, address, halaqoh_id, guardian_name, guardian_phone, guardian_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($values);
        $studentId = (int) $db->lastInsertId();
        $studentCode = 'RTAS-' . str_pad((string) $studentId, 5, '0', STR_PAD_LEFT);
        $db->prepare('UPDATE students SET student_code = ? WHERE id = ?')->execute(array($studentCode, $studentId));
    }

    $childCount = (int) scalar('SELECT COUNT(*) FROM students WHERE guardian_user_id = ?', array($guardianUserId));
    return array('guardian_reused' => (bool) $existingGuardian, 'child_count' => $childCount);
}

function toggle_role_account($userId)
{
    global $db;
    $account = row("SELECT id, role, is_active FROM users WHERE id = ? AND role IN ('ustadzah', 'wali')", array($userId));
    if (!$account) throw new InvalidArgumentException('Akun guru atau wali tidak ditemukan.');

    $newStatus = (int) $account['is_active'] === 1 ? 0 : 1;
    $db->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute(array($newStatus, $userId));
    return $newStatus;
}

function create_account_from_profile($entity, $recordId)
{
    global $db;
    if ($entity === 'teacher') {
        $profile = row('SELECT * FROM teachers WHERE id = ?', array($recordId));
        if (!$profile) throw new InvalidArgumentException('Data Ustadzah tidak ditemukan.');
        $userId = save_role_account('ustadzah', $profile['name'], $profile['email'], $profile['phone'], DEFAULT_ACCOUNT_PASSWORD);
        $db->prepare('UPDATE teachers SET user_id = ? WHERE id = ?')->execute(array($userId, $recordId));
        return;
    }
    if ($entity === 'student') {
        $profile = row('SELECT * FROM students WHERE id = ?', array($recordId));
        if (!$profile) throw new InvalidArgumentException('Data santri tidak ditemukan.');
        $userId = save_role_account('wali', $profile['guardian_name'], $profile['email'], $profile['guardian_phone'], DEFAULT_ACCOUNT_PASSWORD);
        $db->prepare('UPDATE students SET guardian_user_id = ? WHERE id = ?')->execute(array($userId, $recordId));
        return;
    }
    throw new InvalidArgumentException('Jenis akun tidak valid.');
}

function update_own_profile($input)
{
    global $db;
    $current = user();
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Nama dan email yang valid wajib diisi.');
    }
    $duplicate = row('SELECT id FROM users WHERE email = ? AND id <> ?', array($email, $current['id']));
    if ($duplicate) throw new InvalidArgumentException('Email sudah digunakan oleh akun lain.');

    $db->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?')->execute(array($name, $email, $phone, $current['id']));
    if ($current['role'] === 'ustadzah') {
        $db->prepare('UPDATE teachers SET name = ?, email = ?, phone = ? WHERE user_id = ?')->execute(array($name, $email, $phone, $current['id']));
    } elseif ($current['role'] === 'wali') {
        $db->prepare('UPDATE students SET guardian_name = ?, email = ?, guardian_phone = ? WHERE guardian_user_id = ?')->execute(array($name, $email, $phone, $current['id']));
    }

    $_SESSION['user'] = row('SELECT id, name, email, role, phone, is_active FROM users WHERE id = ?', array($current['id']));
}

function change_own_password($currentPassword, $newPassword, $confirmation)
{
    global $db;
    $account = row('SELECT * FROM users WHERE id = ?', array(user()['id']));
    if (!$account || !password_verify($currentPassword, $account['password'])) {
        throw new InvalidArgumentException('Kata sandi saat ini tidak sesuai.');
    }
    if (strlen($newPassword) < 8) {
        throw new InvalidArgumentException('Kata sandi baru minimal 8 karakter.');
    }
    if ($newPassword !== $confirmation) {
        throw new InvalidArgumentException('Konfirmasi kata sandi baru tidak sesuai.');
    }
    $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute(array(password_hash($newPassword, PASSWORD_DEFAULT), user()['id']));
}
