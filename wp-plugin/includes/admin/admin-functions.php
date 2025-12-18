<?php
if (!defined('ABSPATH')) exit;

// Helper to stripslashes multiple fields on an object
function pldb_admin_stripslashes_fields($obj, $fields) {
    foreach ($fields as $field) {
        if (isset($obj->$field)) {
            $obj->$field = $obj->$field ? stripslashes($obj->$field) : '';
        }
    }
}

// Helper to check if a record exists (with table name whitelist)
function pldb_admin_record_exists($table, $field, $value, $exclude_id = 0, $additional_where = array()) {
    $db = pldb_admin_get_db();
    if (!$db) return false;

    // Whitelist allowed tables for security
    $allowed_tables = array('artists', 'tracks', 'shows', 'plays');
    if (!in_array($table, $allowed_tables, true)) {
        error_log('PLDB: Invalid table name attempted: ' . $table);
        return false;
    }

    $value = pldb_sanitize_unslash($value);

    $where = array("$field = %s", "id != %d");
    $params = array($value, $exclude_id);

    foreach ($additional_where as $add_field => $add_value) {
        $where[] = "$add_field = %d";
        $params[] = $add_value;
    }

    $sql = "SELECT id FROM $table WHERE " . implode(' AND ', $where);

    // PHP 8.x: Use argument unpacking with spread operator
    $prepared = $db->prepare($sql, ...$params);
    $result = $db->get_row($prepared);

    return $result ? $result->id : false;
}

// Get database connection (reuses main plugin's connection method)
function pldb_admin_get_db() {
    global $pldb_instance;

    if (!$pldb_instance) {
        return false;
    }

    return $pldb_instance->get_external_db();
}

// Get all shows for dropdown
function pldb_admin_get_all_shows() {
    $db = pldb_admin_get_db();
    if (!$db) return array();

    $results = $db->get_results("
        SELECT id, theme, airdate
        FROM shows
        ORDER BY airdate DESC
    ");

    foreach ($results as $result) {
        pldb_admin_stripslashes_fields($result, ['theme']);
    }

    return $results;
}

// Get single show by ID
function pldb_admin_get_show($show_id) {
    $db = pldb_admin_get_db();
    if (!$db) return false;

    $result = $db->get_row($db->prepare("
        SELECT id, theme, airdate, archivelink, applemusiclink, spotifylink
        FROM shows
        WHERE id = %d
    ", $show_id));

    if ($result) {
        pldb_admin_stripslashes_fields($result, ['theme', 'archivelink', 'applemusiclink', 'spotifylink']);
    }

    return $result;
}

// Update show
function pldb_admin_update_show($show_id, $data) {
    $db = pldb_admin_get_db();
    if (!$db) return false;

    $result = $db->update(
        'shows',
        array(
            'theme' => pldb_sanitize_unslash($data['theme']),
            'airdate' => sanitize_text_field($data['airdate']),
            'archivelink' => !empty($data['archivelink']) ? esc_url_raw($data['archivelink']) : null,
            'applemusiclink' => !empty($data['applemusiclink']) ? esc_url_raw($data['applemusiclink']) : null,
            'spotifylink' => !empty($data['spotifylink']) ? esc_url_raw($data['spotifylink']) : null
        ),
        array('id' => $show_id),
        array('%s', '%s', '%s', '%s', '%s'),
        array('%d')
    );

    return $result !== false;
}

// Get next available show ID
function pldb_admin_get_next_show_id() {
    $db = pldb_admin_get_db();
    if (!$db) return 1;

    $max_id = $db->get_var("SELECT MAX(id) FROM shows");
    return $max_id ? ($max_id + 1) : 1;
}

// Create new show
function pldb_admin_create_show($data) {
    $db = pldb_admin_get_db();
    if (!$db) return false;

    $insert_data = array(
        'id' => intval($data['id']),
        'theme' => pldb_sanitize_unslash($data['theme']),
        'airdate' => sanitize_text_field($data['airdate']),
        'archivelink' => !empty($data['archivelink']) ? esc_url_raw($data['archivelink']) : null,
        'applemusiclink' => !empty($data['applemusiclink']) ? esc_url_raw($data['applemusiclink']) : null,
        'spotifylink' => !empty($data['spotifylink']) ? esc_url_raw($data['spotifylink']) : null
    );

    $result = $db->insert(
        'shows',
        $insert_data,
        array('%d', '%s', '%s', '%s', '%s', '%s')
    );

    if ($result === false) {
        error_log('PLDB: Failed to insert show. Error: ' . $db->last_error);
        return false;
    }

    return intval($data['id']);
}

// Get plays for a show
function pldb_admin_get_show_plays($show_id) {
    $db = pldb_admin_get_db();
    if (!$db) return array();

    // Include the artist's total tracks so we can warn if an artist would be removed on edit
    $results = $db->get_results($db->prepare("
        SELECT
            p.id,
            p.track_id,
            p.suggesters,
            p.comment,
            t.title as track_title,
            a.name as artist_name,
            a.id as artist_id,
            (SELECT COUNT(*) FROM tracks WHERE artist_id = a.id) as artist_total_tracks
        FROM plays p
        JOIN tracks t ON p.track_id = t.id
        JOIN artists a ON t.artist_id = a.id
        WHERE p.show_id = %d
        ORDER BY p.id ASC
    ", $show_id));

    foreach ($results as $result) {
        pldb_admin_stripslashes_fields($result, ['suggesters', 'comment', 'track_title', 'artist_name']);
    }

    return $results;
}

// Create new play (track in a show)
function pldb_admin_create_play($show_id, $track_id, $suggesters = '', $comment = '') {
    $db = pldb_admin_get_db();
    if (!$db) return false;

    $result = $db->insert(
        'plays',
        array(
            'show_id' => $show_id,
            'track_id' => $track_id,
            'suggesters' => pldb_sanitize_unslash($suggesters),
            'comment' => wp_unslash(sanitize_textarea_field($comment))
        ),
        array('%d', '%d', '%s', '%s')
    );

    return $result ? $db->insert_id : false;
}

// Update multiple plays
function pldb_admin_update_plays($plays_data, $delete_plays = array()) {
    $db = pldb_admin_get_db();
    if (!$db) return false;

    $success = true;

    // Delete marked plays
    if (!empty($delete_plays)) {
        foreach ($delete_plays as $play_id) {
            $result = $db->delete('plays', array('id' => intval($play_id)), array('%d'));
            if ($result === false) $success = false;
        }
    }

    // Update remaining plays
    foreach ($plays_data as $play_id => $play_data) {
        // Skip if marked for deletion
        if (in_array($play_id, $delete_plays)) continue;

        $result = $db->update(
            'plays',
            array(
                'suggesters' => pldb_sanitize_unslash($play_data['suggesters']),
                'comment' => wp_unslash(sanitize_textarea_field($play_data['comment']))
            ),
            array('id' => intval($play_id)),
            array('%s', '%s'),
            array('%d')
        );

        if ($result === false) $success = false;
    }

    return $success;
}

// Update track artists - split into smaller functions
function pldb_admin_update_track_artists($track_artists) {
    $db = pldb_admin_get_db();
    if (!$db) return false;

    $success = true;
    $old_artist_ids = array();

    foreach ($track_artists as $track_id => $new_artist_name) {
        $result = pldb_admin_update_single_track_artist($db, $track_id, $new_artist_name, $old_artist_ids);
        if (!$result) {
            $success = false;
            error_log('PLDB: Failed to update track artist: track_id=' . $track_id . ' new_artist=' . $new_artist_name);
        }
    }

    // Cleanup orphaned artists
    pldb_admin_cleanup_orphaned_artists($db, $old_artist_ids);

    return $success;
}

// Helper: Update a single track's artist
function pldb_admin_update_single_track_artist($db, $track_id, $new_artist_name, &$old_artist_ids) {
    $track_id = intval($track_id);
    $new_artist_name = pldb_sanitize_unslash($new_artist_name);

    $track = pldb_admin_get_track($track_id);
    if (!$track) {
        error_log('PLDB: Track not found: ' . $track_id);
        return true;
    }

    if ($track->artist_name === $new_artist_name) {
        return true;
    }

    $old_artist_ids[] = $track->artist_id;

    $new_artist_id = pldb_admin_artist_exists($new_artist_name);
    if (!$new_artist_id) {
        $result = $db->insert('artists', array('name' => $new_artist_name), array('%s'));
        if (!$result) {
            error_log('PLDB: Failed to create artist: ' . $new_artist_name . ' | DB Error: ' . $db->last_error);
            return false;
        }
        $new_artist_id = $db->insert_id;
        if (!$new_artist_id) {
            error_log('PLDB: Failed to get insert_id for new artist: ' . $new_artist_name);
            return false;
        }
    }

    $existing_track = pldb_admin_track_exists($track->title, $new_artist_id, $track_id);

    if ($existing_track) {
        $result = pldb_admin_merge_tracks($db, $track_id, $existing_track);
        if (!$result) {
            error_log('PLDB: Merge tracks failed for track_id=' . $track_id . ' into existing_track=' . $existing_track);
        }
        return $result;
    } else {
        $result = pldb_admin_move_track_to_artist($db, $track_id, $new_artist_id);
        if (!$result) {
            error_log('PLDB: Move track to artist failed for track_id=' . $track_id . ' to artist_id=' . $new_artist_id);
        }
        return $result;
    }
}

// Helper: Cleanup artists with no tracks
function pldb_admin_cleanup_orphaned_artists($db, $artist_ids) {
    $artist_ids = array_unique($artist_ids);
    foreach ($artist_ids as $artist_id) {
        $track_count = $db->get_var($db->prepare("SELECT COUNT(*) FROM tracks WHERE artist_id = %d", $artist_id));
        if ($track_count == 0) {
            $db->delete('artists', array('id' => $artist_id), array('%d'));
        }
    }
}

// Get all artists for dropdown
function pldb_admin_get_all_artists() {
    $db = pldb_admin_get_db();
    if (!$db) return array();

    $results = $db->get_results("
        SELECT id, name
        FROM artists
        ORDER BY name ASC
    ");

    foreach ($results as $result) {
        pldb_admin_stripslashes_fields($result, ['name']);
    }

    return $results;
}

// Get single track by ID
function pldb_admin_get_track($track_id) {
    $db = pldb_admin_get_db();
    if (!$db) return false;

    $result = $db->get_row($db->prepare("
        SELECT t.id, t.title, t.artist_id, a.name as artist_name
        FROM tracks t
        JOIN artists a ON t.artist_id = a.id
        WHERE t.id = %d
    ", $track_id));

    if ($result) {
        pldb_admin_stripslashes_fields($result, ['title', 'artist_name']);
    }

    return $result;
}

// Update track
function pldb_admin_update_track($track_id, $title) {
    $db = pldb_admin_get_db();
    if (!$db) return false;

    $result = $db->update(
        'tracks',
        array('title' => sanitize_text_field($title)),
        array('id' => $track_id),
        array('%s'),
        array('%d')
    );

    return $result !== false;
}

// Check if track already exists for artist
function pldb_admin_track_exists($title, $artist_id, $exclude_id = 0) {
    return pldb_admin_record_exists('tracks', 'title', $title, $exclude_id, array('artist_id' => $artist_id));
}

// Check if artist name already exists
function pldb_admin_artist_exists($name, $exclude_id = 0) {
    return pldb_admin_record_exists('artists', 'name', $name, $exclude_id);
}

// Get or create track (with error handling)
function pldb_admin_get_or_create_track($title, $artist_name) {
    $db = pldb_admin_get_db();
    if (!$db) {
        error_log('PLDB: Database connection failed in get_or_create_track');
        return false;
    }

    $title = pldb_sanitize_unslash($title);
    $artist_name = pldb_sanitize_unslash($artist_name);

    // Get or create artist
    $artist_id = pldb_admin_artist_exists($artist_name);
    if (!$artist_id) {
        $result = $db->insert('artists', array('name' => $artist_name), array('%s'));
        if (!$result) {
            error_log('PLDB: Failed to create artist: ' . $artist_name . ' | ' . $db->last_error);
            return false;
        }
        $artist_id = $db->insert_id;
    }

    // Check if track exists for this artist
    $track_id = pldb_admin_track_exists($title, $artist_id);
    if (!$track_id) {
        $result = $db->insert('tracks', array('title' => $title, 'artist_id' => $artist_id), array('%s', '%d'));
        if (!$result) {
            error_log('PLDB: Failed to create track: ' . $title . ' | ' . $db->last_error);
            return false;
        }
        $track_id = $db->insert_id;
    }

    return $track_id;
}

// Update or merge tracks - split into smaller functions
function pldb_admin_update_or_merge_tracks($tracks_data, $play_ids_to_move = array()) {
    $db = pldb_admin_get_db();
    if (!$db) return false;

    $success = true;

    foreach ($tracks_data as $track_id => $track_data) {
        $result = pldb_admin_update_single_track($db, $track_id, $track_data, $play_ids_to_move);
        if (!$result) {
            $success = false;
            error_log('PLDB: Failed to update track: track_id=' . $track_id);
        }
    }

    return $success;
}

// Helper: Update a single track
function pldb_admin_update_single_track($db, $track_id, $track_data, $play_ids_to_move) {
    $new_title = pldb_sanitize_unslash($track_data['title']);

    $track = pldb_admin_get_track($track_id);
    if (!$track) {
        error_log('PLDB: Track not found for update: ' . $track_id);
        return false;
    }

    if ($track->title === $new_title) return true;

    $existing_track_id = pldb_admin_track_exists($new_title, $track->artist_id, $track_id);

    if ($existing_track_id) {
        return pldb_admin_merge_track_plays($db, $track_id, $existing_track_id, $play_ids_to_move);
    } else {
        return pldb_admin_create_or_update_track($db, $track_id, $new_title, $track, $play_ids_to_move);
    }
}

// Helper: Merge plays when track already exists
function pldb_admin_merge_track_plays($db, $old_track_id, $new_track_id, $play_ids_to_move) {
    $plays_for_track = isset($play_ids_to_move[$old_track_id]) ? $play_ids_to_move[$old_track_id] : array();

    if (!empty($plays_for_track)) {
        foreach ($plays_for_track as $play_id) {
            $result = $db->update('plays', array('track_id' => $new_track_id), array('id' => $play_id));
            if ($result === false) {
                error_log('PLDB: Failed to merge play: ' . $play_id . ' | ' . $db->last_error);
                return false;
            }
        }
    }

    $remaining_plays = $db->get_var($db->prepare("SELECT COUNT(*) FROM plays WHERE track_id = %d", $old_track_id));
    if ($remaining_plays == 0) {
        $db->delete('tracks', array('id' => $old_track_id));
    }

    return true;
}

// Helper: Create new track or update existing
function pldb_admin_create_or_update_track($db, $track_id, $new_title, $track, $play_ids_to_move) {
    $plays_for_track = isset($play_ids_to_move[$track_id]) ? $play_ids_to_move[$track_id] : array();

    if (!empty($plays_for_track)) {
        $result = $db->insert('tracks', array('title' => $new_title, 'artist_id' => $track->artist_id), array('%s', '%d'));
        if (!$result) {
            error_log('PLDB: Failed to create new track: ' . $new_title . ' | ' . $db->last_error);
            return false;
        }

        $new_track_id = $db->insert_id;

        foreach ($plays_for_track as $play_id) {
            $result = $db->update('plays', array('track_id' => $new_track_id), array('id' => $play_id));
            if ($result === false) {
                error_log('PLDB: Failed to update play: ' . $play_id . ' | ' . $db->last_error);
                return false;
            }
        }

        $remaining_plays = $db->get_var($db->prepare("SELECT COUNT(*) FROM plays WHERE track_id = %d", $track_id));
        if ($remaining_plays == 0) {
            $db->delete('tracks', array('id' => $track_id));
        }

        return true;
    } else {
        $result = pldb_admin_update_track($track_id, $new_title);
        if ($result === false) {
            error_log('PLDB: Failed to update track title: ' . $track_id . ' | ' . $db->last_error);
        }
        return $result;
    }
}

// Helper: Merge duplicate tracks
function pldb_admin_merge_tracks($db, $old_track_id, $new_track_id) {
    $result = $db->update('plays', array('track_id' => $new_track_id), array('track_id' => $old_track_id));
    if ($result === false) {
        error_log('PLDB: Failed to merge tracks: ' . $old_track_id . ' -> ' . $new_track_id . ' | ' . $db->last_error);
        return false;
    }

    $remaining = $db->get_var($db->prepare("SELECT COUNT(*) FROM plays WHERE track_id = %d", $old_track_id));
    if ($remaining == 0) {
        $db->delete('tracks', array('id' => $old_track_id));
    }

    return true;
}

// Helper: Move track to different artist
function pldb_admin_move_track_to_artist($db, $track_id, $new_artist_id) {
    $result = $db->update('tracks', array('artist_id' => $new_artist_id), array('id' => $track_id), array('%d'), array('%d'));
    if ($result === false) {
        error_log('PLDB: Failed to move track to artist: ' . $track_id . ' -> ' . $new_artist_id . ' | ' . $db->last_error);
        return false;
    }
    return true;
}

// Create tracks and plays from POST data
function pldb_admin_create_tracks_and_plays_from_post($show_id, $tracks_data, $plays_data) {
    if (!isset($tracks_data) || !is_array($tracks_data)) {
        return true;
    }

    foreach ($tracks_data as $index => $track_data) {
        if (empty($track_data['title']) || empty($track_data['artist'])) {
            continue;
        }

        $track_id = pldb_admin_get_or_create_track($track_data['title'], $track_data['artist']);

        if (!$track_id) {
            throw new Exception('Failed to create track: ' . $track_data['title']);
        }

        $suggesters = isset($plays_data[$index]['suggesters']) ? $plays_data[$index]['suggesters'] : '';
        $comment = isset($plays_data[$index]['comment']) ? $plays_data[$index]['comment'] : '';

        $play_id = pldb_admin_create_play($show_id, $track_id, $suggesters, $comment);
        if (!$play_id) {
            throw new Exception('Failed to create play for track: ' . $track_data['title']);
        }
    }

    return true;
}
