<?php
/*
Plugin Name: #ThePlaylist Database Query
Description: Connect to ThePlaylist database and query track/artist play statistics
Version: 1.0.2
Author: #ThePlaylist
Requires PHP: 8.0
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__).'includes/functions.php';
require_once plugin_dir_path(__FILE__).'includes/shortcodes/artists.php';
require_once plugin_dir_path(__FILE__).'includes/shortcodes/suggesters.php';
require_once plugin_dir_path(__FILE__).'includes/shortcodes/show.php';
require_once plugin_dir_path(__FILE__).'includes/shortcodes/search.php';

class PLDB {
    private $external_db;

    const VERSION = '1.0.0';
    const ITEMS_PER_PAGE = 20;
    const PAGINATION_RANGE = 2;

    private $shortcodes = [
        'pldb_artists' => ['callback' => 'pldb_display_artists_shortcode', 'defaults' => ['title' => 'Artists']],
        'pldb_suggesters' => ['callback' => 'pldb_display_suggesters_shortcode', 'defaults' => ['title' => 'Suggesters']],
        'pldb_show' => ['callback' => 'pldb_display_show_shortcode', 'defaults' => ['show_id' => '']],
        'pldb_search' => ['callback' => 'pldb_display_search_shortcode', 'defaults' => ['title' => 'Search #ThePlaylist']]
    ];

    public function __construct() {
        foreach ($this->shortcodes as $tag => $cfg) {
            add_shortcode($tag, [$this, 'render_shortcode_handler']);
        }
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles() {
        wp_enqueue_style('dashicons');
        wp_enqueue_style('pldb-styles', plugins_url('assets/css/pldb-styles.css', __FILE__), ['dashicons'], self::VERSION);
    }

    public function get_external_db() {
        if ($this->external_db) return $this->external_db;

        $host = get_option('pldb_host');
        $name = get_option('pldb_name');
        $user = get_option('pldb_user');
        $pass = get_option('pldb_password');
        
        if (!$host || !$name || !$user) {
            error_log('PLDB config missing');
            return false;
        }

        $this->external_db = new wpdb($user, $pass, $name, $host);

        if ($this->external_db->last_error) {
            error_log('PLDB connection failed: '.$this->external_db->last_error);
            return false;
        }

        $this->external_db->set_charset($this->external_db->dbh, 'utf8mb4');
        return $this->external_db;
    }

    private function handle_error($msg, $context = 'General') {
        error_log("PLDB {$context} Error: {$msg}");
        return 'I\'m sorry Dave, I\'m afraid I can\'t do that!';
    }

    private function render_shortcode($callback, $atts, $defaults) {
        $atts = shortcode_atts($defaults, $atts);
        $db = $this->get_external_db();
        if (!$db) return $this->handle_error('Database connection failed', 'Database');

        try {
            return $callback($atts, $db);
        } catch (Exception $e) {
            return $this->handle_error($e->getMessage(), 'Shortcode');
        }
    }

    public function render_shortcode_handler($atts, $content, $tag) {
        $cfg = $this->shortcodes[$tag];
        return $this->render_shortcode($cfg['callback'], $atts, $cfg['defaults']);
    }
}

$pldb_instance = new PLDB();

if (is_admin()) {
    require_once plugin_dir_path(__FILE__).'includes/admin/admin-init.php';
}