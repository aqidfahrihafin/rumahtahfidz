<?php

function deletion_relation_message($table, $id)
{
    $id = (int) $id;
    if ($id < 1) return 'Data yang akan dihapus tidak valid.';

    $checks = array(
        'students' => array(
            array('SELECT COUNT(*) FROM assessments WHERE student_id = ?', 'penilaian dan laporan'),
            array('SELECT COUNT(*) FROM student_halaqoh_history WHERE student_id = ?', 'riwayat perpindahan Halaqoh'),
        ),
        'teachers' => array(
            array('SELECT COUNT(*) FROM halaqoh WHERE teacher_id = ?', 'data Halaqoh'),
            array('SELECT COUNT(*) FROM assessments WHERE teacher_id = ?', 'penilaian dan laporan'),
        ),
        'halaqoh' => array(
            array('SELECT COUNT(*) FROM students WHERE halaqoh_id = ?', 'data santri'),
            array('SELECT COUNT(*) FROM halaqoh_surahs WHERE halaqoh_id = ?', 'cakupan surat'),
            array('SELECT COUNT(*) FROM student_halaqoh_history WHERE from_halaqoh_id = ? OR to_halaqoh_id = ?', 'riwayat perpindahan Halaqoh', true),
        ),
        'categories' => array(
            array('SELECT COUNT(*) FROM indicators WHERE category_id = ?', 'indikator penilaian'),
        ),
        'indicators' => array(
            array('SELECT COUNT(*) FROM assessment_scores WHERE indicator_id = ?', 'riwayat nilai indikator'),
        ),
        'assessments' => array(
            array('SELECT COUNT(*) FROM assessment_scores WHERE assessment_id = ?', 'rincian nilai indikator'),
            array('SELECT COUNT(*) FROM assessment_characters WHERE assessment_id = ?', 'penilaian karakter'),
        ),
    );

    if ($table === 'surahs') {
        $surah = row('SELECT id, name FROM surahs WHERE id = ?', array($id));
        if (!$surah) return 'Data surat tidak ditemukan.';
        if ((int) scalar('SELECT COUNT(*) FROM halaqoh_surahs WHERE surah_id = ?', array($id)) > 0) {
            return 'Data tidak dapat dihapus karena masih digunakan pada cakupan Halaqoh.';
        }
        if ((int) scalar('SELECT COUNT(*) FROM assessments WHERE surah = ? OR murojaah_start = ? OR murojaah_end = ?', array($surah['name'], $surah['name'], $surah['name'])) > 0) {
            return 'Data tidak dapat dihapus karena masih digunakan pada penilaian dan laporan.';
        }
        return '';
    }

    if (!isset($checks[$table])) return '';
    foreach ($checks[$table] as $check) {
        $parameters = !empty($check[2]) ? array($id, $id) : array($id);
        if ((int) scalar($check[0], $parameters) > 0) {
            return 'Data tidak dapat dihapus karena masih digunakan pada ' . $check[1] . '.';
        }
    }
    return '';
}
