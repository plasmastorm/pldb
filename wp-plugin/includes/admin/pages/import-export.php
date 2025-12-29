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

global $pldb_instance;
$db = $pldb_instance->get_external_db();
?>
<div class="wrap">
    <h1>Import/Export CSV</h1>

    <?php if ($msg): ?>
        <div class="notice notice-<?php echo esc_attr($msg_type); ?> is-dismissible">
            <p><?php echo nl2br(esc_html($msg)); ?></p>
        </div>
    <?php endif; ?>

    <h2>Import CSV</h2>
    <p>Upload a CSV file with 11 columns: Episode No., Air Date, Show Title, Artist, Track, Year, Suggester 1, Suggester 2, Suggester 3, Comment, Archive Link</p>
    
    <form method="post" enctype="multipart/form-data">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="pldb_csv_upload">CSV File</label></th>
                <td>
                    <?php
                    $upload_max = ini_get('upload_max_filesize');
                    $post_max = ini_get('post_max_size');
                    $upload_bytes = wp_convert_hr_to_bytes($upload_max);
                    $post_bytes = wp_convert_hr_to_bytes($post_max);
                    
                    if ($upload_bytes < $post_bytes) {
                        $limit = $upload_max . ' (upload_max_filesize)';
                    } else {
                        $limit = $post_max . ' (post_max_size)';
                    }
                    ?>
                    <input type="file" name="pldb_csv_upload" id="pldb_csv_upload" accept=".csv" required>
                    <p class="description">Maximum file size: <?php echo esc_html($limit); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">Options</th>
                <td>
                    <label>
                        <input type="checkbox" name="pldb_reinit_db" value="1">
                        Reinitialize database before import
                    </label>
                    <p class="description" style="color: #d63638;"><strong>WARNING:</strong> This will delete all existing data in the database before importing.</p>
                </td>
            </tr>
        </table>
        <?php submit_button('Upload CSV', 'primary', 'pldb_upload_csv'); ?>
    </form>

    <hr style="margin: 40px 0;">

    <h2>Export CSV</h2>
    <?php if ($db): 
        $total_shows = $db->get_var("SELECT COUNT(*) FROM shows");
        $total_tracks = $db->get_var("SELECT COUNT(*) FROM tracks");
        $total_plays = $db->get_var("SELECT COUNT(*) FROM plays");
    ?>
        <p>Download current database as CSV (<?php echo $total_shows; ?> shows, <?php echo $total_tracks; ?> tracks, <?php echo $total_plays; ?> plays)</p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pldb-import-export&action=export_csv')); ?>" class="button button-secondary">Download CSV</a>
    <?php else: ?>
        <p style="color: #d63638;">Database connection failed</p>
    <?php endif; ?>
</div>
