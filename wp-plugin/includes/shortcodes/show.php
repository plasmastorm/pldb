<?php
if (!defined('ABSPATH')) exit;

function pldb_display_show_shortcode($atts, $db) {
    $show_id = intval($atts['show_id']);
    if (!$show_id) return '<p>Please provide a show ID.</p>';

    $show = $db->get_row($db->prepare("
        SELECT id, theme, airdate, archivelink
        FROM shows
        WHERE id = %d
    ", $show_id));

    if (!$show) return '<p>Show not found.</p>';

    $plays = $db->get_results($db->prepare("
        SELECT
            t.title as track,
            a.name as artist,
            p.suggesters,
            p.comment
        FROM plays p
        JOIN tracks t ON p.track_id = t.id
        JOIN artists a ON t.artist_id = a.id
        WHERE p.show_id = %d
        ORDER BY p.id ASC
    ", $show_id));

    pldb_normalize_suggester_display($plays, 'suggesters');

    $cols = [
        'track' => ['label' => 'Track', 'link_type' => 'track_search'],
        'artist' => ['label' => 'Artist', 'link_type' => 'artist_search'],
        'suggesters' => ['label' => 'Suggested By', 'link_type' => 'suggester_list'],
        'comment' => 'Comment'
    ];

    return pldb_build_html_table($cols, $plays, $show->theme.' ('.$show->airdate.')');
}
