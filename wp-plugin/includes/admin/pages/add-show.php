<?php
if (!defined('ABSPATH')) exit;

$msg = '';
$msg_type = '';

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pldb_upload_csv'])) {
    try {
        $result = pldb_admin_handle_csv_upload($_FILES, $_POST);
        $msg = $result['message'];
        $msg_type = $result['type'];
    } catch (Exception $e) {
        $msg = 'CSV upload failed: ' . $e->getMessage();
        $msg_type = 'error';
    }
}

// Handle manual form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pldb_create_show'])) {
    try {
        $show_id = pldb_admin_transaction(function($db) {
            $show_id = pldb_admin_create_show($_POST);

            if (!$show_id) {
                throw new Exception('Failed to create show');
            }

            pldb_admin_create_tracks_and_plays_from_post(
                $show_id,
                isset($_POST['tracks']) ? $_POST['tracks'] : array(),
                isset($_POST['plays']) ? $_POST['plays'] : array()
            );

            return $show_id;
        });

        $msg = 'Show created successfully!';
        $msg_type = 'success';

        echo '<script>window.location.href = "' .
             esc_url(admin_url('admin.php?page=pldb-admin&pldb_show_id=' . $show_id)) .
             '";</script>';

    } catch (Exception $e) {
        $msg = 'Failed to create show: ' . $e->getMessage();
        $msg_type = 'error';
        error_log('Add show failed: ' . $e->getMessage());
    }
}
?>

<div class="wrap">
    <h1>Add New Show</h1>

    <?php pldb_render_admin_notice($msg, $msg_type); ?>

    <div style="margin-bottom: 30px; padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
        <form method="post" action="" enctype="multipart/form-data" style="display: flex; align-items: center; gap: 10px;">
            <label for="pldb_csv_upload" style="margin: 0;"><strong>Upload CSV:</strong></label>
            <input type="file"
                   name="pldb_csv_upload"
                   id="pldb_csv_upload"
                   accept=".csv"
                   required>
            <?php submit_button('Upload', 'secondary', 'pldb_upload_csv', false); ?>
        </form>
    </div>

    <hr style="margin: 30px 0;">

    <h2>Or Add Show Manually</h2>

    <form method="post" action="" id="pldb-add-show-form">
        <h3>Show Details</h3>
        <?php pldb_render_show_details_fields(null, true); ?>

        <hr>
        <h3>Playlist</h3>

        <?php pldb_render_playlist_table(array(), 'add'); ?>

        <hr>
        <?php submit_button('Create Show Manually', 'primary', 'pldb_create_show'); ?>
    </form>
</div>
