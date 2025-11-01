<?php
if (!defined('ABSPATH')) exit;

// Load admin functions
require_once plugin_dir_path(__FILE__) . 'admin-functions.php';
require_once plugin_dir_path(__FILE__) . 'admin-components.php';

// Register admin menu
function pldb_admin_menu() {
    add_menu_page(
        '#ThePlaylist DB',
        '#ThePlaylist DB',
        'manage_options',
        'pldb-admin',
        'pldb_admin_edit_show_page',
        'dashicons-playlist-audio',
        30
    );

    // Add submenu pages
    add_submenu_page('pldb-admin', 'Edit Show', 'Edit Show', 'manage_options', 'pldb-admin', 'pldb_admin_edit_show_page');
    add_submenu_page('pldb-admin', 'Add Show', 'Add Show', 'manage_options', 'pldb-add-show', 'pldb_admin_add_show_page');
}
add_action('admin_menu', 'pldb_admin_menu');

// Load and display the edit show page
function pldb_admin_edit_show_page() {
    require_once plugin_dir_path(__FILE__) . 'pages/edit-show.php';
}

// Load and display the add show page
function pldb_admin_add_show_page() {
    require_once plugin_dir_path(__FILE__) . 'pages/add-show.php';
}
