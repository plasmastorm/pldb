<?php
if (!defined('ABSPATH')) exit;

$msg = '';
$msg_type = '';

// Handle manual form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pldb_save_show'])) {
    try {
        pldb_admin_transaction(function($db) {
            $show_id = intval($_POST['show_id']);

            if (!pldb_admin_update_show($show_id, $_POST)) {
                throw new Exception('Failed to update show details');
            }

            if (isset($_POST['track_artists'])) {
                if (!pldb_admin_update_track_artists($_POST['track_artists'])) {
                    throw new Exception('Failed to update artists');
                }
            }

            if (isset($_POST['tracks'])) {
                $move = isset($_POST['move_tracks']) ? $_POST['move_tracks'] : [];
                if (!pldb_admin_update_or_merge_tracks($_POST['tracks'], $move)) {
                    throw new Exception('Failed to update tracks');
                }
            }

            if (isset($_POST['plays'])) {
                $delete = isset($_POST['delete_plays']) ? $_POST['delete_plays'] : [];
                if (!pldb_admin_update_plays($_POST['plays'], $delete)) {
                    throw new Exception('Failed to update plays');
                }
            }
        });

        $msg = 'Show updated successfully.';
        $msg_type = 'success';

    } catch (Exception $e) {
        $msg = 'Failed to save changes: '.$e->getMessage();
        $msg_type = 'error';
        error_log('Edit show failed: '.$e->getMessage());
    }
}

// Get selected show from query string (with validation)
$show_id = isset($_GET['pldb_show_id']) ? intval($_GET['pldb_show_id']) : 0;

// Validate show ID exists
if ($show_id > 0) {
    $show = pldb_admin_get_show($show_id);
    if (!$show) {
        $show_id = 0; // Invalid show ID
        $show = null;
    }
} else {
    $show = null;
}

$plays = $show_id ? pldb_admin_get_show_plays($show_id) : [];
// Get all shows for dropdown
$shows = pldb_admin_get_all_shows();
?>

<div class="wrap">
    <h1>Edit Show</h1>

    <?php pldb_render_admin_notice($msg, $msg_type); ?>

    <!-- Show Selection Form -->
    <form method="get" action="">
        <input type="hidden" name="page" value="pldb-admin">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pldb_show_id">Select Show</label>
                </th>
                <td>
                    <select name="pldb_show_id" id="pldb_show_id" class="regular-text" onchange="this.form.submit()">
                        <option value="">-- Choose a show --</option>
                        <?php foreach ($shows as $s): ?>
                            <option value="<?php echo esc_attr($s->id); ?>"
                                    <?php selected($show_id, $s->id); ?>>
                                <?php echo esc_html('#'.$s->id.' - '.$s->theme.' ('.$s->airdate.')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
    </form>

    <?php if ($show): ?>
        <hr>

        <!-- Edit Form -->
        <form method="post" action="" id="pldb-edit-show-form">
            <?php wp_nonce_field('pldb_edit_show', 'pldb_edit_show_nonce'); ?>
            <input type="hidden" name="show_id" value="<?php echo esc_attr($show->id); ?>">

            <h2>Show Details</h2>
            <?php pldb_render_show_details_fields($show); ?>

            <hr>
            <h2>Playlist (<?php echo count($plays); ?> tracks)</h2>

            <?php if ($plays): ?>
                <?php pldb_render_playlist_table($plays, 'edit'); ?>
            <?php else: ?>
                <p>No tracks found for this show.</p>
            <?php endif; ?>

            <hr>
            <?php submit_button('Save All Changes', 'primary', 'pldb_save_show'); ?>
        </form>
    <?php endif; ?>
</div>
