<?php

function find_report($assessmentId)
{
    return row(
        'SELECT a.*, s.name AS student, COALESCE(u.email, s.email) AS guardian_email,
                s.guardian_name, s.guardian_phone, h.name AS halaqoh, t.name AS teacher
         FROM assessments a
         JOIN students s ON s.id = a.student_id
         LEFT JOIN halaqoh h ON h.id = s.halaqoh_id
         LEFT JOIN teachers t ON t.id = a.teacher_id
         LEFT JOIN users u ON u.id = s.guardian_user_id
         WHERE a.id = ?',
        array($assessmentId)
    );
}

function complete_report($report)
{
    if (!$report || empty($report['id'])) return $report;
    $report['score_groups'] = array();
    $scores = rows(
        'SELECT sc.section, sc.score, i.name AS indicator, c.name AS category
         FROM assessment_scores sc
         LEFT JOIN indicators i ON i.id = sc.indicator_id
         LEFT JOIN categories c ON c.id = i.category_id
         WHERE sc.assessment_id = ? ORDER BY c.id, i.id',
        array((int) $report['id'])
    );
    foreach ($scores as $score) {
        $group = $score['category'];
        if (!$group) {
            $group = $score['section'] === 'hafalan' ? 'Hafalan' : ($score['section'] === 'murojaah' ? 'Murojaah' : 'Penilaian Lainnya');
        }
        if (!isset($report['score_groups'][$group])) $report['score_groups'][$group] = array();
        $report['score_groups'][$group][] = $score;
    }
    $report['characters'] = rows('SELECT aspect, grade FROM assessment_characters WHERE assessment_id = ? ORDER BY id', array((int) $report['id']));
    return $report;
}

function report_score($report)
{
    return (int) $report['memorization'] + (int) $report['murojaah'];
}

function report_message($report)
{
    $report = complete_report($report);
    return "Assalamu'alaikum Bapak/Ibu " . $report['guardian_name'] . ",\n\n"
        . 'Berikut laporan perkembangan tahfidz ' . $report['student'] . ":\n"
        . 'Halaqoh: ' . $report['halaqoh'] . "\n"
        . 'Tanggal: ' . format_date($report['date']) . "\n"
        . 'Setoran: ' . $report['surah'] . ' ayat ' . $report['verse_range'] . "\n"
        . 'Murojaah: ' . ($report['murojaah_start'] ?: '-') . ' sampai ' . ($report['murojaah_end'] ?: '-') . ($report['murojaah_juz'] ? ' (Juz ' . $report['murojaah_juz'] . ')' : '') . "\n"
        . 'Ustadzah penilai: ' . ($report['teacher'] ?: '-') . "\n"
        . 'Nilai hafalan: ' . ((int) $report['memorization'] * 2) . "/100\n"
        . "Nilai murojaah: " . ((int) $report['murojaah'] * 2) . "/100\n"
        . 'Nilai akhir: ' . report_score($report) . "/100\n"
        . 'Status: ' . $report['status'] . "\n"
        . 'Catatan ustadzah: ' . $report['message'] . "\n\n"
        . 'Rumah Tahfidz As-Sakinah';
}

function whatsapp_report_url($report)
{
    $phone = preg_replace('/\D+/', '', $report['guardian_phone']);
    if (strpos($phone, '0') === 0) {
        $phone = '62' . substr($phone, 1);
    }
    return 'https://wa.me/' . $phone . '?text=' . rawurlencode(report_message($report));
}

function send_report_email($report)
{
    if (!filter_var($report['guardian_email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $subject = 'Laporan Tahfidz ' . $report['student'] . ' - ' . format_date($report['date']);
    $headers = array(
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: Rumah Tahfidz As-Sakinah <noreply@rumahtahfidz.local>',
    );

    return mail($report['guardian_email'], $subject, report_message($report), implode("\r\n", $headers));
}
