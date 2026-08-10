<?php

function save_halaqoh_with_surahs($input)
{
    global $db;
    $id = (int) ($input['id'] ?? 0);
    $surahIds = array_values(array_unique(array_filter(array_map('intval', $input['surah_ids'] ?? array()))));
    $selected = array();
    if ($surahIds) {
        $placeholders = implode(',', array_fill(0, count($surahIds), '?'));
        $selected = rows("SELECT id, name, juz, surah_number FROM surahs WHERE id IN ($placeholders) ORDER BY surah_number", $surahIds);
    }
    if (count($selected) !== count($surahIds)) throw new InvalidArgumentException('Terdapat pilihan surat yang tidak valid.');
    $juz = array_values(array_unique(array_map(function($surah){ return (int)$surah['juz']; }, $selected)));
    sort($juz);
    $coverage = $juz ? 'Juz ' . implode(', ', $juz) : '';

    $db->beginTransaction();
    try {
        if ($id) {
            $db->prepare('UPDATE halaqoh SET name=?, level=?, coverage=?, surah_count=?, teacher_id=? WHERE id=?')->execute(array(trim($input['name']), trim($input['level']), $coverage, count($selected), (int)$input['teacher_id'], $id));
            $db->prepare('DELETE FROM halaqoh_surahs WHERE halaqoh_id=?')->execute(array($id));
        } else {
            $db->prepare('INSERT INTO halaqoh (name,level,coverage,surah_count,teacher_id) VALUES (?,?,?,?,?)')->execute(array(trim($input['name']), trim($input['level']), $coverage, count($selected), (int)$input['teacher_id']));
            $id = (int)$db->lastInsertId();
        }
        $insert = $db->prepare('INSERT INTO halaqoh_surahs (halaqoh_id,surah_id) VALUES (?,?)');
        foreach ($surahIds as $surahId) $insert->execute(array($id,$surahId));
        $db->commit();
    } catch (Throwable $exception) { $db->rollBack(); throw $exception; }
}

function format_halaqoh_coverage($juzList)
{
    $juz = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)$juzList)))));
    sort($juz);
    return $juz ? 'Juz ' . implode(', ', $juz) : 'Belum ditentukan';
}
