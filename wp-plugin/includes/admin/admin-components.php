<?php
if (!defined('ABSPATH')) exit;

// Render show details form fields
function pldb_render_show_details_fields($show_data = null, $show_id_editable = false) {
    ?>
    <table class="form-table">
        <?php if ($show_id_editable): ?>
        <tr>
            <th><label for="id">Show ID *</label></th>
            <td>
                <input type="number" name="id" id="id" class="regular-text" value="<?php echo $show_data ? esc_attr($show_data->id) : esc_attr(pldb_admin_get_next_show_id()); ?>" min="1" required>
                <p class="description">Show number</p>
            </td>
        </tr>
        <?php endif; ?>

        <tr>
            <th><label for="theme">Theme *</label></th>
            <td>
                <input type="text" name="theme" id="theme" class="regular-text" value="<?php echo $show_data ? esc_attr($show_data->theme) : ''; ?>" required>
            </td>
        </tr>

        <tr>
            <th><label for="airdate">Air Date *</label></th>
            <td>
                <input type="date" name="airdate" id="airdate" class="regular-text" value="<?php echo $show_data ? esc_attr($show_data->airdate) : ''; ?>" required>
            </td>
        </tr>

        <tr>
            <th><label for="archivelink">Archive Link</label></th>
            <td>
                <input type="url" name="archivelink" id="archivelink" class="regular-text" value="<?php echo $show_data ? esc_attr($show_data->archivelink) : ''; ?>">
            </td>
        </tr>

        <tr>
            <th><label for="applemusiclink">Apple Music Link</label></th>
            <td>
                <input type="url" name="applemusiclink" id="applemusiclink" class="regular-text" value="<?php echo $show_data ? esc_attr($show_data->applemusiclink) : ''; ?>">
            </td>
        </tr>

        <tr>
            <th><label for="spotifylink">Spotify Link</label></th>
            <td>
                <input type="url" name="spotifylink" id="spotifylink" class="regular-text" value="<?php echo $show_data ? esc_attr($show_data->spotifylink) : ''; ?>">
            </td>
        </tr>
    </table>
    <?php
}

// Render admin notice
function pldb_render_admin_notice($msg, $type = 'success') {
    if (!$msg) return;
    echo '<p style="padding: 10px; background: '.($type === 'error' ? '#f44' : '#4f4').'; color: white;">'.esc_html($msg).'</p>';
}

// Database transaction wrapper - simplified
function pldb_admin_transaction($callback) {
    $db = pldb_admin_get_db();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $db->query('START TRANSACTION');
    
    try {
        $result = $callback($db);
        $db->query('COMMIT');
        return $result;
    } catch (Exception $e) {
        $db->query('ROLLBACK');
        throw $e;
    }
}

// Render playlist table for add/edit show pages
function pldb_render_playlist_table($plays_data = [], $mode = 'edit') {
    $is_edit = ($mode === 'edit');
    $empty = empty($plays_data);

    // For add mode with no data, create one empty row
    if (!$is_edit && $empty) {
        $plays_data = [(object)[
            'id' => 0,
            'track_id' => '',
            'track_title' => '',
            'artist_name' => '',
            'artist_id' => '',
            'artist_total_tracks' => 0,
            'suggesters' => '',
            'comment' => ''
        ]];
    }
    ?>
    <table class="wp-list-table widefat fixed striped" id="pldb-tracks-table">
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 22%;">Track<?php echo $is_edit ? '' : ' *'; ?></th>
                <th style="width: 15%;">Artist<?php echo $is_edit ? '' : ' *'; ?></th>
                <th style="width: 18%;">Suggesters</th>
                <th style="width: 30%;">Comment</th>
                <th style="width: 3%;"><?php echo $is_edit ? 'Delete' : 'Remove'; ?></th>
            </tr>
        </thead>
        <tbody id="tracks-tbody">
            <?php $counter = 1; ?>
            <?php foreach ($plays_data as $idx => $pl): ?>
                <tr class="track-row">
                    <td><?php echo $counter++; ?></td>
                    <td>
                        <?php if ($is_edit): ?>
                            <input type="text"
                                   name="tracks[<?php echo $pl->track_id; ?>][title]"
                                   value="<?php echo esc_attr($pl->track_title); ?>"
                                   class="widefat track-title-input"
                                   data-original="<?php echo esc_attr($pl->track_title); ?>"
                                   placeholder="Track title">
                        <?php else: ?>
                            <input type="text"
                                   name="tracks[<?php echo $idx; ?>][title]"
                                   class="widefat track-title-input"
                                   placeholder="Track title">
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_edit): ?>
                            <input type="text"
                                   name="track_artists[<?php echo $pl->track_id; ?>]"
                                   value="<?php echo esc_attr($pl->artist_name); ?>"
                                   class="widefat artist-input"
                                   data-original="<?php echo esc_attr($pl->artist_name); ?>"
                                   data-original-id="<?php echo esc_attr($pl->artist_id); ?>"
                                   data-total-tracks="<?php echo esc_attr($pl->artist_total_tracks); ?>"
                                   placeholder="Artist name">
                        <?php else: ?>
                            <input type="text"
                                   name="tracks[<?php echo $idx; ?>][artist]"
                                   class="widefat artist-input"
                                   placeholder="Artist name">
                        <?php endif; ?>
                    </td>
                    <td>
                        <input type="text"
                               name="plays[<?php echo $is_edit ? $pl->id : $idx; ?>][suggesters]"
                               value="<?php echo esc_attr($pl->suggesters); ?>"
                               class="widefat suggester-input"
                               data-original="<?php echo esc_attr($pl->suggesters); ?>"
                               placeholder="comma, separated">
                    </td>
                    <td>
                        <textarea name="plays[<?php echo $is_edit ? $pl->id : $idx; ?>][comment]"
                                  rows="2"
                                  class="widefat comment-textarea"
                                  data-original="<?php echo esc_attr($pl->comment); ?>"><?php echo esc_textarea($pl->comment); ?></textarea>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($is_edit): ?>
                            <input type="checkbox"
                                   name="delete_plays[]"
                                   value="<?php echo esc_attr($pl->id); ?>">
                        <?php else: ?>
                            <button type="button" class="button remove-track" <?php echo $idx === 0 ? 'disabled' : ''; ?>>×</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($is_edit): ?>
        <p>Edit track titles, artists, suggesters and comments. Check "Delete" to remove a track from this show.</p>
    <?php else: ?>
        <p>
            <button type="button" class="button" id="add-track-row">+ Add Track</button>
        </p>

        <script>
        jQuery(document).ready(function($) {
            let rowCount = <?php echo count($plays_data); ?>;

            $('#add-track-row').on('click', function() {
                const newRow = `
                    <tr class="track-row">
                        <td>${rowCount + 1}</td>
                        <td>
                            <input type="text"
                                   name="tracks[${rowCount}][title]"
                                   class="widefat track-title-input"
                                   placeholder="Track title">
                        </td>
                        <td>
                            <input type="text"
                                   name="tracks[${rowCount}][artist]"
                                   class="widefat artist-input"
                                   placeholder="Artist name">
                        </td>
                        <td>
                            <input type="text"
                                   name="plays[${rowCount}][suggesters]"
                                   class="widefat suggester-input"
                                   placeholder="comma, separated">
                        </td>
                        <td>
                            <textarea name="plays[${rowCount}][comment]"
                                      rows="2"
                                      class="widefat comment-textarea"></textarea>
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="button remove-track">×</button>
                        </td>
                    </tr>
                `;
                $('#tracks-tbody').append(newRow);
                rowCount++;
                updateRemoveButtons();
                renumberRows();
            });

            $(document).on('click', '.remove-track', function() {
                $(this).closest('tr').remove();
                updateRemoveButtons();
                renumberRows();
            });

            function updateRemoveButtons() {
                $('.remove-track').prop('disabled', $('.track-row').length === 1);
            }

            function renumberRows() {
                $('.track-row').each(function(idx) {
                    $(this).find('td:first').text(idx + 1);
                });
            }
        });
        </script>
    <?php endif; ?>
    <?php
}

// Handle CSV upload processing
function pldb_admin_handle_csv_upload($files, $post_data) {
    $parser = plugin_dir_path(__FILE__).'admin-parser.php';
    if (file_exists($parser)) {
        require_once $parser;
        if (function_exists('pldb_parse_csv_upload')) {
            return pldb_parse_csv_upload($files, $post_data);
        } else {
            return ['message' => 'CSV parser function not found.', 'type' => 'error'];
        }
    } else {
        return ['message' => 'CSV parser script not yet implemented.', 'type' => 'warning'];
    }
}

