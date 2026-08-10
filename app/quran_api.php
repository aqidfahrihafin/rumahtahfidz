<?php

const QURAN_API_URL = 'https://equran.id/api/v2/surat';
const QURAN_JUZ_API_URL = 'https://api.alquran.cloud/v1/quran/quran-uthmani';

function fetch_json_url($url)
{
    $body = false;
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 35, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_FOLLOWLOCATION => true, CURLOPT_ENCODING => '', CURLOPT_HTTPHEADER => array('Accept: application/json')));
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($body === false || $status !== 200) throw new RuntimeException('API Al-Qur’an tidak dapat dihubungi. ' . $error);
    } else {
        $context = stream_context_create(array('http' => array('timeout' => 20, 'header' => "Accept: application/json\r\n")));
        $body = @file_get_contents($url, false, $context);
        if ($body === false) throw new RuntimeException('Aktifkan ekstensi cURL atau allow_url_fopen untuk mengakses API Al-Qur’an.');
    }
    $response = json_decode($body, true);
    if (!is_array($response)) throw new RuntimeException('Format respons API Al-Qur’an tidak dikenali.');
    return $response;
}

function fetch_quran_api()
{
    $response = fetch_json_url(QURAN_API_URL);
    if (!isset($response['data']) || !is_array($response['data'])) throw new RuntimeException('Data surat dari EQuran.id tidak dikenali.');
    return $response['data'];
}

function fetch_juz_data_by_surah()
{
    $response = fetch_json_url(QURAN_JUZ_API_URL);
    $surahs = isset($response['data']['surahs']) ? $response['data']['surahs'] : array();
    if (count($surahs) < 100) throw new RuntimeException('Data Juz dari Al Quran Cloud tidak lengkap.');
    $starting = array();
    $memberships = array();
    foreach ($surahs as $surah) {
        $number = (int) ($surah['number'] ?? 0);
        $firstAyah = isset($surah['ayahs'][0]) ? $surah['ayahs'][0] : array();
        $starting[$number] = (int) ($firstAyah['juz'] ?? 0);
        $memberships[$number] = array();
        foreach (($surah['ayahs'] ?? array()) as $ayah) {
            $juz = (int) ($ayah['juz'] ?? 0);
            if ($juz > 0 && !in_array($juz, $memberships[$number], true)) $memberships[$number][] = $juz;
        }
    }
    return array('starting' => $starting, 'memberships' => $memberships);
}

function synchronize_surahs_from_api()
{
    global $db;
    $surahs = fetch_quran_api();
    $juzData = fetch_juz_data_by_surah();
    $juzMapping = $juzData['starting'];
    if (count($surahs) < 100) throw new RuntimeException('Data API tidak lengkap; sinkronisasi dibatalkan.');
    $added = 0; $updated = 0;
    foreach ($surahs as $surah) {
        $name = trim(isset($surah['namaLatin']) ? $surah['namaLatin'] : '');
        $verses = (int) (isset($surah['jumlahAyat']) ? $surah['jumlahAyat'] : 0);
        $number = (int) (isset($surah['nomor']) ? $surah['nomor'] : 0);
        $juz = isset($juzMapping[$number]) ? $juzMapping[$number] : 0;
        if ($name === '' || $verses < 1) continue;
        $existing = row('SELECT id, juz FROM surahs WHERE LOWER(name) = LOWER(?)', array($name));
        if ($existing) {
            $db->prepare('UPDATE surahs SET surah_number = ?, name = ?, verses = ?, juz = ? WHERE id = ?')->execute(array($number, $name, $verses, $juz, $existing['id']));
            $surahId = (int) $existing['id'];
            $updated++;
        } else {
            $db->prepare('INSERT INTO surahs (surah_number, name, verses, juz) VALUES (?, ?, ?, ?)')->execute(array($number, $name, $verses, $juz));
            $surahId = (int) $db->lastInsertId();
            $added++;
        }
        $db->prepare('DELETE FROM surah_juz WHERE surah_id = ?')->execute(array($surahId));
        $juzInsert = $db->prepare('INSERT INTO surah_juz (surah_id, juz) VALUES (?, ?)');
        foreach (($juzData['memberships'][$number] ?? array($juz)) as $memberJuz) $juzInsert->execute(array($surahId, (int) $memberJuz));
    }
    return array('added' => $added, 'updated' => $updated);
}
