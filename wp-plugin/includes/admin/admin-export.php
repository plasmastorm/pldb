<?php
if (!defined('ABSPATH')) exit;

function pldb_admin_export_csv() {
    global $pldb_instance;
    $db = $pldb_instance->get_external_db();
    
    if (!$db) {
        wp_die('Database connection failed');
    }

    // Query all plays with related data
    $results = $db->get_results("
        SELECT 
            s.id as episode_no,
            s.airdate,
            s.theme,
            t.title as track,
            a.name as artist,
            t.year,
            p.suggesters,
            p.comment,
            s.archivelink
        FROM plays p
        JOIN shows s ON p.show_id = s.id
        JOIN tracks t ON p.track_id = t.id
        JOIN artists a ON t.artist_id = a.id
        ORDER BY s.id ASC, p.id
    ");

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pldb-export-'.date('Y-m-d-His').'.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Write header row
    fputcsv($output, [
        'Episode No.',
        'Air Date',
        'Show Title',
        'Artist',
        'Track',
        'Year',
        'Suggester 1',
        'Suggester 2',
        'Suggester 3',
        'Comment',
        'Archive Link'
    ], escape: '');

    // Write data rows
    $last_show_id = null;
    foreach ($results as $row) {
        // Split suggesters into 3 columns
        $suggesters = array_filter(array_map('trim', explode(',', $row->suggesters)));
        $sug1 = isset($suggesters[0]) ? $suggesters[0] : '';
        $sug2 = isset($suggesters[1]) ? $suggesters[1] : '';
        $sug3 = isset($suggesters[2]) ? $suggesters[2] : '';

        // Only include archive link on first row of each show
        $archive_link = ($row->episode_no !== $last_show_id) ? $row->archivelink : '';
        $last_show_id = $row->episode_no;

        fputcsv($output, [
            $row->episode_no,
            $row->airdate,
            $row->theme,
            $row->artist,
            $row->track,
            $row->year,
            $sug1,
            $sug2,
            $sug3,
            $row->comment,
            $archive_link
        ], escape: '');
    }

    fclose($output);
    exit;
}
