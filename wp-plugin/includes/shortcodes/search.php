<?php
if (!defined('ABSPATH')) exit;

function pldb_display_search_shortcode($atts, $db) {
    $title = $atts['title'];

    $html = '<div class="pldb-search-form"><form method="get" action="" role="search">';

    if (isset($_GET['p'])) {
        $html .= '<input type="hidden" name="p" value="'.esc_attr($_GET['p']).'">';
    }
    if (isset($_GET['page_id'])) {
        $html .= '<input type="hidden" name="page_id" value="'.esc_attr($_GET['page_id']).'">';
    }

    $html .= '<h2>'.esc_html($title).'</h2>';
    $html .= '<div class="pldb-search-container">';
    $html .= '<label for="pldb-search-input" class="screen-reader-text">Search artists, tracks, or suggesters</label>';
    $html .= '<input type="text" id="pldb-search-input" name="pldb_search" class="pldb-search-input" placeholder="Search artists, tracks, or suggesters..." value="'.(isset($_GET['pldb_search']) ? esc_attr($_GET['pldb_search']) : '').'">';
    $html .= '<button type="submit" class="pldb-search-button"><span class="dashicons dashicons-search" aria-hidden="true"></span><span class="screen-reader-text">Search</span></button>';
    $html .= '</div></form></div>';

    if (!empty($_GET['pldb_search'])) {
        $search = sanitize_text_field($_GET['pldb_search']);
        $html .= '<div class="pldb-search-results">';
        $html .= pldb_search_artists($db, $search);
        $html .= pldb_search_tracks($db, $search);
        $html .= pldb_search_suggesters($db, $search);
        $html .= pldb_search_shows($db, $search);
        $html .= '</div>';
    }

    return $html;
}

function pldb_search_artists($db, $search) {
    $like = '%'.$db->esc_like($search).'%';

    $results = $db->get_results($db->prepare("
        SELECT
            a.name as artist,
            COUNT(DISTINCT t.id) as tracks,
            COUNT(p.id) as plays
        FROM artists a
        LEFT JOIN tracks t ON a.id = t.artist_id
        LEFT JOIN plays p ON t.id = p.track_id
        WHERE a.name LIKE %s
        GROUP BY a.id, a.name
        ORDER BY plays DESC, a.name ASC
        LIMIT 20
    ", $like));

    if (!$results) return '';

    $html = '';
    $cols = [
        'artist' => ['label' => 'Artist', 'link_type' => 'artist_search'],
        'tracks' => 'Tracks',
        'plays' => 'Plays'
    ];

    $html .= pldb_build_html_table($cols, $results, 'Artists', null, 1, 20, 'search_artists');

    foreach ($results as $r) {
        $html .= pldb_get_artist_tracks($db, $r->artist);
    }

    return $html;
}

function pldb_search_tracks($db, $search) {
    $like = '%'.$db->esc_like($search).'%';

    $results = $db->get_results($db->prepare("
        SELECT
            t.title as track,
            a.name as artist,
            COUNT(p.id) as plays
        FROM tracks t
        JOIN artists a ON t.artist_id = a.id
        LEFT JOIN plays p ON t.id = p.track_id
        WHERE t.title LIKE %s
        GROUP BY t.id, t.title, a.name
        ORDER BY plays DESC, t.title ASC
        LIMIT 20
    ", $like));

    if (!$results) return '';

    $cols = [
        'track' => ['label' => 'Track', 'link_type' => 'track_search'],
        'artist' => ['label' => 'Artist', 'link_type' => 'artist_search'],
        'plays' => 'Plays'
    ];

    return pldb_build_html_table($cols, $results, 'Tracks', null, 1, 20, 'search_tracks');
}

function pldb_search_suggesters($db, $search) {
    $like = '%'.$db->esc_like($search).'%';

    $plays = $db->get_results($db->prepare("
        SELECT id, suggesters, track_id
        FROM plays
        WHERE suggesters IS NOT NULL
        AND suggesters != ''
        AND suggesters LIKE %s
    ", $like));

    $tracks = [];
    if ($plays) {
        foreach ($plays as $p) {
            foreach (pldb_process_suggesters($p->suggesters) as $n) {
                if (stripos($n, $search) !== false) {
                    if (!isset($tracks[$n])) $tracks[$n] = [];
                    $tracks[$n][$p->track_id] = true;
                }
            }
        }
    }

    if (!$tracks) return '';

    $html = '';
    $results = [];
    foreach ($tracks as $n => $ids) {
        $results[] = (object)['suggester' => $n, 'tracks' => count($ids)];
    }

    $cols = [
        'suggester' => ['label' => 'Suggester', 'link_type' => 'suggester_search'],
        'tracks' => 'Tracks Suggested'
    ];

    $html .= pldb_build_html_table($cols, $results, 'Suggesters', null, 1, 20, 'search_suggesters');

    foreach ($tracks as $n => $ids) {
        $html .= pldb_get_suggester_tracks($db, $n);
    }

    return $html;
}

function pldb_get_suggester_tracks($db, $name) {
    $like = '%'.$db->esc_like($name).'%';

    $plays = $db->get_results($db->prepare("
        SELECT DISTINCT
            t.title as track,
            a.name as artist,
            s.theme as `show`,
            p.suggesters
        FROM plays p
        JOIN tracks t ON p.track_id = t.id
        JOIN artists a ON t.artist_id = a.id
        LEFT JOIN shows s ON p.show_id = s.id
        WHERE p.suggesters LIKE %s
        ORDER BY t.title ASC
    ", $like));

    $filtered = [];
    foreach ($plays as $pl) {
        $names = pldb_process_suggesters($pl->suggesters);
        if (in_array($name, $names, true)) {
            $filtered[] = (object)[
                'track' => $pl->track,
                'artist' => $pl->artist,
                'show' => $pl->show
            ];
        }
    }

    if (!$filtered) return '';

    $cols = [
        'track' => ['label' => 'Track', 'link_type' => 'track_search'],
        'artist' => ['label' => 'Artist', 'link_type' => 'artist_search'],
        'show' => 'Show'
    ];

    return pldb_build_html_table($cols, $filtered, 'Tracks suggested by '.$name, null, 1, 100);
}

function pldb_get_artist_tracks($db, $name) {
    $tracks = $db->get_results($db->prepare("
        SELECT 
            t.title as track,
            GROUP_CONCAT(DISTINCT s.theme ORDER BY s.id SEPARATOR ', ') as `show`,
            GROUP_CONCAT(DISTINCT p.suggesters ORDER BY p.id SEPARATOR ', ') as suggesters,
            COUNT(p.id) as plays
        FROM tracks t
        JOIN artists a ON t.artist_id = a.id
        LEFT JOIN plays p ON t.id = p.track_id
        LEFT JOIN shows s ON p.show_id = s.id
        WHERE a.name = %s
        GROUP BY t.id, t.title
        ORDER BY plays DESC, t.title ASC
    ", $name));

    if (!$tracks) return '';

    pldb_normalize_suggester_display($tracks, 'suggesters');

    $cols = [
        'track' => ['label' => 'Track', 'link_type' => 'track_search'],
        'show' => 'Show',
        'suggesters' => ['label' => 'Suggesters', 'link_type' => 'suggester_list'],
        'plays' => 'Plays'
    ];

    return pldb_build_html_table($cols, $tracks, 'Tracks by '.$name, null, 1, 100);
}

function pldb_search_shows($db, $search) {
    $like = '%'.$db->esc_like($search).'%';

    $results = $db->get_results($db->prepare("
        SELECT
            s.theme,
            s.airdate,
            s.archivelink,
            COUNT(p.id) as tracks
        FROM shows s
        LEFT JOIN plays p ON s.id = p.show_id
        WHERE s.theme LIKE %s
        GROUP BY s.id, s.theme, s.airdate, s.archivelink
        ORDER BY s.airdate DESC
        LIMIT 20
    ", $like));

    if (!$results) return '';

    $cols = [
        'theme' => ['label' => 'Show Theme', 'link_type' => 'show_archive'],
        'airdate' => 'Air Date',
        'tracks' => 'Tracks'
    ];

    return pldb_build_html_table($cols, $results, 'Shows', null, 1, 20, 'search_shows');
}

