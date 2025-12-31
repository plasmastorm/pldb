<?php
if (!defined('ABSPATH')) exit;

require_once 'admin-parser-mysql.php';

function pldb_reinitialize_database($db) {
    $schema_file = plugin_dir_path(__FILE__) . 'schema.sql';
    
    if (!file_exists($schema_file)) {
        throw new Exception('Schema file not found');
    }
    
    $schema = file_get_contents($schema_file);
    if ($schema === false) {
        throw new Exception('Failed to read schema file');
    }
    
    // Drop tables in correct order (respecting foreign key constraints)
    $db->query('DROP TABLE IF EXISTS plays');
    $db->query('DROP TABLE IF EXISTS tracks');
    $db->query('DROP TABLE IF EXISTS suggesters');
    $db->query('DROP TABLE IF EXISTS shows');
    $db->query('DROP TABLE IF EXISTS artists');
    
    // Split and execute CREATE statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $result = $db->query($stmt);
            if ($result === false) {
                throw new Exception('Failed to create tables: ' . $db->last_error);
            }
        }
    }
}

function pldb_parse_csv_upload($files, $post_data) {
    global $pldb_instance;
    $db = $pldb_instance->get_external_db();
    
    if (!$db) {
        return ['message' => 'Database connection failed', 'type' => 'error'];
    }

    // Validate file upload
    if (!isset($files['pldb_csv_upload']) || $files['pldb_csv_upload']['error'] !== UPLOAD_ERR_OK) {
        return ['message' => 'File upload failed', 'type' => 'error'];
    }

    $tmp_file = $files['pldb_csv_upload']['tmp_name'];
    
    if (!file_exists($tmp_file) || !is_readable($tmp_file)) {
        return ['message' => 'Cannot read uploaded file', 'type' => 'error'];
    }

    // Check if database should be reinitialized
    if (!empty($post_data['pldb_reinit_db'])) {
        try {
            pldb_reinitialize_database($db);
        } catch (Exception $e) {
            return ['message' => 'Database reinitialization failed: ' . $e->getMessage(), 'type' => 'error'];
        }
    }

    $csv_columns = 11;
    $file = fopen($tmp_file, "r");
    
    if (!$file) {
        return ['message' => 'Failed to open CSV file', 'type' => 'error'];
    }

    $db->query('START TRANSACTION');
    
    try {
        $shows_created = 0;
        $tracks_created = 0;
        $plays_created = 0;
        $line_num = 0;
        $errors = [];

        while ($arr = fgetcsv($file, escape: "")) {
            $line_num++;

            if (count($arr) != $csv_columns) {
                $errors[] = "Line $line_num: Wrong number of columns (".count($arr)." instead of $csv_columns)";
                continue;
            }

            if ($arr[0] === "" || $arr[0] === "Episode No.") {
                continue;
            }

            // Trim all fields once
            $arr = array_map('trim', $arr);

            // Validate required fields
            if (empty($arr[2]) || empty($arr[3]) || empty($arr[4])) {
                $errors[] = "Line $line_num: Missing show title, artist, or track";
                continue;
            }

            $show_id = get_show_id($db, $arr[2]);
            if (!isset($show_id)){
                $show_id = $arr[0];
                insert_new_show($db, $arr[0], $arr[2], $arr[1], $arr[10]);
                $shows_created++;
            }

            $artist_id = get_artist_id($db, $arr[3]);
            if (!isset($artist_id)){
                $artist_id = insert_new_artist($db, $arr[3]);
            }

            $track_id = get_track_id($db, $artist_id, $arr[4]);
            if (!isset($track_id)){
                $year = !empty($arr[5]) ? intval($arr[5]) : null;
                $track_id = insert_new_track($db, $artist_id, $arr[4], $year);
                $tracks_created++;
            }

            $play_id = get_play_id($db, $show_id, $track_id);
            if (!isset($play_id)){
                $suggesters = strtolower(implode(',', array_filter(array_slice($arr, 6, 3))));
                insert_new_play($db, $show_id, $track_id, $suggesters, $arr[9]);
                $plays_created++;
            }
        }

        $db->query('COMMIT');
        fclose($file);

        $msg = "Import successful! Created: $shows_created shows, $tracks_created tracks, $plays_created plays";
        if (!empty($errors)) {
            $msg .= "\n\nSkipped lines:\n" . implode("\n", $errors);
        }
        return ['message' => $msg, 'type' => 'success'];

    } catch (Exception $e) {
        $db->query('ROLLBACK');
        fclose($file);
        error_log('CSV import failed: '.$e->getMessage());
        return ['message' => 'Import failed: '.$e->getMessage(), 'type' => 'error'];
    }
}
