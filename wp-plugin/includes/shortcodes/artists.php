<?php
if (!defined('ABSPATH')) exit;

function pldb_display_artists_shortcode($atts, $db) {
    $title = $atts['title'];

    $sort_p = 'pldb_artists_sort';
    $dir_p = 'pldb_artists_dir';
    $field = isset($_GET[$sort_p]) ? sanitize_key($_GET[$sort_p]) : 'plays';
    $dir = isset($_GET[$dir_p]) && $_GET[$dir_p] === 'asc' ? 'asc' : 'desc';

    $allowed = ['artist', 'tracks', 'plays'];
    if (!in_array($field, $allowed)) $field = 'plays';

    $order_map = ['artist' => 'a.name', 'tracks' => 'tracks', 'plays' => 'plays'];
    $order_by = $order_map[$field];

    $results = $db->get_results("
        SELECT
            a.name as artist,
            COUNT(DISTINCT t.id) as tracks,
            COUNT(DISTINCT p.id) as plays
        FROM artists a
        LEFT JOIN tracks t ON a.id = t.artist_id
        LEFT JOIN plays p ON t.id = p.track_id
        GROUP BY a.id
        ORDER BY {$order_by} {$dir}, a.name ASC
    ");

    list($per_page, $page, $offset) = pldb_get_pagination_params('pldb_artists_paged');

    $total = count($results);
    $paged = array_slice($results, $offset, $per_page);

    $cols = [
        'artist' => ['label' => 'Artist', 'sortable' => true, 'link_type' => 'artist_search'],
        'tracks' => ['label' => 'Tracks', 'sortable' => true],
        'plays' => ['label' => 'Plays', 'sortable' => true]
    ];

    $html = pldb_build_html_table($cols, $paged, $title, $total, $page, $per_page, 'artists');
    $html .= pldb_build_pagination($total, $per_page, $page, pldb_get_base_url(['pldb_artists_paged']), 'pldb_artists_paged');

    return $html;
}
