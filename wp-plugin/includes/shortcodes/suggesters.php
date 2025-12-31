<?php
if (!defined('ABSPATH')) exit;

function pldb_display_suggesters_shortcode($atts, $db) {
    $title = $atts['title'];

    $plays = $db->get_results("
        SELECT p.id, p.suggesters, p.track_id
        FROM plays p
        WHERE p.suggesters IS NOT NULL AND p.suggesters != ''
    ");

    $tracks = [];
    foreach ($plays as $pl) {
        $names = pldb_process_suggesters($pl->suggesters);
        foreach ($names as $n) {
            if (!isset($tracks[$n])) $tracks[$n] = [];
            $tracks[$n][$pl->track_id] = true;
        }
    }

    $stats = [];
    foreach ($tracks as $n => $ids) {
        $stats[$n] = count($ids);
    }

    $sort_p = 'pldb_suggesters_sort';
    $dir_p = 'pldb_suggesters_dir';
    $field = isset($_GET[$sort_p]) ? sanitize_key($_GET[$sort_p]) : 'tracks';
    $dir = isset($_GET[$dir_p]) && $_GET[$dir_p] === 'asc' ? 'asc' : 'desc';

    if ($field === 'suggester') {
        $dir === 'asc' ? ksort($stats) : krsort($stats);
    } else {
        $dir === 'asc' ? asort($stats) : arsort($stats);
    }

    $results = [];
    foreach ($stats as $n => $cnt) {
        $results[] = (object)['suggester' => $n, 'tracks' => $cnt];
    }

    list($per_page, $page, $offset) = pldb_get_pagination_params('pldb_suggesters_paged');

    $total = count($results);
    $paged = array_slice($results, $offset, $per_page);

    $cols = [
        'suggester' => ['label' => 'Suggester', 'sortable' => true, 'link_type' => 'suggester_search'],
        'tracks' => ['label' => 'Suggestions Played', 'sortable' => true]
    ];

    $html = pldb_build_html_table($cols, $paged, $title, $total, $page, $per_page, 'suggesters');
    $html .= pldb_build_pagination($total, $per_page, $page, pldb_get_base_url(['pldb_suggesters_paged']), 'pldb_suggesters_paged');
    return $html;
}
