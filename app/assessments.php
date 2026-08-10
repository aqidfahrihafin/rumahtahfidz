<?php

function save_detailed_assessment($input)
{
    global $db;
    $assessmentId = (int) ($input['id'] ?? 0);
    $studentId = (int) ($input['student_id'] ?? 0);
    if (user()['role'] === 'ustadzah') {
        $teacherId = (int) (scalar('SELECT id FROM teachers WHERE user_id = ?', array(user()['id'])) ?: 0);
    } else {
        $teacherId = (int) ($input['teacher_id'] ?? 0);
    }
    $date = trim($input['date'] ?? '');
    $surah = trim($input['surah'] ?? '');
    $verseStart = max(1, (int) ($input['verse_start'] ?? 0));
    $verseEnd = max(1, (int) ($input['verse_end'] ?? 0));
    $hafalanScores = isset($input['hafalan_scores']) ? $input['hafalan_scores'] : array();
    $murojaahScores = isset($input['murojaah_scores']) ? $input['murojaah_scores'] : array();
    $murojaahStart = trim($input['murojaah_start'] ?? '');
    $murojaahEnd = trim($input['murojaah_end'] ?? '');
    $murojaahJuz = (int) ($input['murojaah_juz'] ?? 0);

    if (!row('SELECT id FROM students WHERE id = ?', array($studentId))) throw new InvalidArgumentException('Santri wajib dipilih.');
    $assignedTeacherId = (int) (scalar('SELECT h.teacher_id FROM students s JOIN halaqoh h ON h.id = s.halaqoh_id WHERE s.id = ?', array($studentId)) ?: 0);
    if ($assignedTeacherId < 1 || $assignedTeacherId !== $teacherId) throw new InvalidArgumentException('Ustadzah penilai harus sesuai dengan pembimbing Halaqoh santri.');
    $surahData = row('SELECT * FROM surahs WHERE name = ?', array($surah));
    if (!$surahData) throw new InvalidArgumentException('Surah hafalan tidak terdaftar pada Data Surah.');
    if ($verseEnd < $verseStart || $verseEnd > (int) $surahData['verses']) throw new InvalidArgumentException('Rentang ayat hafalan tidak sesuai jumlah ayat surah.');
    if ($murojaahJuz < 1 || $murojaahJuz > 30) throw new InvalidArgumentException('Juz murojaah wajib dipilih.');
    $murojaahStartData = row('SELECT id, juz, surah_number FROM surahs WHERE name = ?', array($murojaahStart));
    $murojaahEndData = row('SELECT id, juz, surah_number FROM surahs WHERE name = ?', array($murojaahEnd));
    if (!$murojaahStartData || !$murojaahEndData || !row('SELECT surah_id FROM surah_juz WHERE surah_id = ? AND juz = ?', array((int) $murojaahStartData['id'], $murojaahJuz)) || !row('SELECT surah_id FROM surah_juz WHERE surah_id = ? AND juz = ?', array((int) $murojaahEndData['id'], $murojaahJuz))) throw new InvalidArgumentException('Surah awal dan akhir harus sesuai dengan Juz murojaah yang dipilih.');
    if ((int) $murojaahEndData['surah_number'] < (int) $murojaahStartData['surah_number']) throw new InvalidArgumentException('Surah akhir tidak boleh berada sebelum Surah awal.');
    if ($teacherId < 1 || $date === '') throw new InvalidArgumentException('Ustadzah penilai dan tanggal wajib diisi.');
    if (!$hafalanScores || !$murojaahScores) throw new InvalidArgumentException('Nilai indikator hafalan dan murojaah wajib diisi.');
    if ($assessmentId > 0) {
        $existingAssessment = row('SELECT id, teacher_id FROM assessments WHERE id = ?', array($assessmentId));
        if (!$existingAssessment) throw new InvalidArgumentException('Data penilaian yang akan diedit tidak ditemukan.');
        if (user()['role'] === 'ustadzah' && (int) $existingAssessment['teacher_id'] !== $teacherId) throw new InvalidArgumentException('Anda tidak memiliki akses untuk mengedit penilaian ini.');
    }

    $normalize = function ($scores) {
        return array_map(function ($score) { return min(100, max(0, (int) $score)); }, $scores);
    };
    $hafalanScores = $normalize($hafalanScores);
    $murojaahScores = $normalize($murojaahScores);
    $hafalanAverage = array_sum($hafalanScores) / count($hafalanScores);
    $murojaahAverage = array_sum($murojaahScores) / count($murojaahScores);
    $finalAverage = ($hafalanAverage + $murojaahAverage) / 2;
    $status = $finalAverage >= 80 ? 'Lancar' : ($finalAverage >= 65 ? 'Kurang Lancar' : 'Ulangi');

    $db->beginTransaction();
    try {
        $assessmentValues = array($studentId, $teacherId, $date, $surah, $verseStart . '-' . $verseEnd, $murojaahStart, $murojaahEnd, $murojaahJuz, round($hafalanAverage / 2), round($murojaahAverage / 2), $status, trim($input['message'] ?? ''));
        if ($assessmentId > 0) {
            $statement = $db->prepare('UPDATE assessments SET student_id=?, teacher_id=?, date=?, surah=?, verse_range=?, murojaah_start=?, murojaah_end=?, murojaah_juz=?, memorization=?, murojaah=?, status=?, message=? WHERE id=?');
            $assessmentValues[] = $assessmentId;
            $statement->execute($assessmentValues);
            $db->prepare('DELETE FROM assessment_scores WHERE assessment_id = ?')->execute(array($assessmentId));
            $db->prepare('DELETE FROM assessment_characters WHERE assessment_id = ?')->execute(array($assessmentId));
        } else {
            $statement = $db->prepare('INSERT INTO assessments (student_id, teacher_id, date, surah, verse_range, murojaah_start, murojaah_end, murojaah_juz, memorization, murojaah, status, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->execute($assessmentValues);
            $assessmentId = (int) $db->lastInsertId();
        }
        $scoreStatement = $db->prepare('INSERT INTO assessment_scores (assessment_id, section, indicator_id, score) VALUES (?, ?, ?, ?)');
        foreach ($hafalanScores as $indicatorId => $score) $scoreStatement->execute(array($assessmentId, 'hafalan', (int) $indicatorId, $score));
        foreach ($murojaahScores as $indicatorId => $score) $scoreStatement->execute(array($assessmentId, 'murojaah', (int) $indicatorId, $score));
        foreach (($input['category_scores'] ?? array()) as $categoryId => $scores) {
            foreach ($normalize($scores) as $indicatorId => $score) {
                $validIndicator = row('SELECT id FROM indicators WHERE id = ? AND category_id = ?', array((int) $indicatorId, (int) $categoryId));
                if ($validIndicator) $scoreStatement->execute(array($assessmentId, 'kategori:' . (int) $categoryId, (int) $indicatorId, $score));
            }
        }
        $characterStatement = $db->prepare('INSERT INTO assessment_characters (assessment_id, aspect, grade) VALUES (?, ?, ?)');
        foreach (($input['characters'] ?? array()) as $aspect => $grade) {
            if (in_array($grade, array('SB', 'B', 'KB'), true)) $characterStatement->execute(array($assessmentId, $aspect, $grade));
        }
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}
