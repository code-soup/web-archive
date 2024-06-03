<?php

namespace CodeSoup\WebArchive\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use voku\helper\HtmlDomParser;
use CodeSoup\WebArchive\Utils\DOMNodeProcessor;

// Exit if accessed directly
defined( 'WPINC' ) || die;

class Snapshot {

    use \CodeSoup\WebArchive\Traits\HelpersTrait;

    private $post;

    private $dirs;

    private $args;

    private $post_types = array();

    public function __construct( $args = array() )
    {
        $this->args       = $args;
        $this->post_types = array(
            'document',
            'drops',
            'free',
            'learn',
            'news',
            'page',
            'partnership',
            'post',
            'talks',
            'tools',
            'town-hall',
            'updates',
        );


        /**
         * Use built in WordPress classes
         */
        if ( ! defined('FS_CHMOD_DIR') ) {
            define( 'FS_CHMOD_DIR', ( 0755 & ~ umask() ) );    
        }
        
        if ( ! defined('FS_CHMOD_FILE') ) {
            define( 'FS_CHMOD_FILE', ( 0644 & ~ umask() ) );
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
    }



    /**
     * Create log each time page is updated
     */
    public function create_log_entry( string $new_status, string $old_status, \WP_Post $post ) {

        // Not enabled for current post type
        if ( ! in_array($post->post_type, $this->post_types) )
            return;

        // Don't log in case post was never published
        if ( 'publish' !== $new_status && 'publish' !== $old_status )
            return;

        $time = gmdate('U');

        /**
         * Format required user data
         */
        $user = wp_get_current_user();
        $name = empty( $user->display_name )
            ? $user->user_login
            : $user->display_name;

        $_user = (array) $user->data;
        $_skip = array(
            'password',
            'user_url',
            'user_pass',
            'user_login',
            'user_email',
            'user_status',
            'user_nicename',
            'user_registered',
            'user_activation_key'
        );

        foreach( $_skip as $key ) {
            unset($_user[$key]);
        }


        /**
         * Format required $post data
         */
        $_post = (array) $post;
        $_skip = array(
            'comment_count',
            'comment_status',
            'filter',
            'ping_status',
            'pinged',
            'post_content_filtered',
            'post_mime_type',
            'post_password',
            'to_ping',
        );

        foreach( $_skip as $key ) {
            unset($_post[$key]);
        }

        $_post['old_status'] = $old_status;
        $_post['new_status'] = $new_status;
        $_post['permalink']  = home_url( wp_make_link_relative( get_permalink( $_post['ID'] ) ) );


        /**
         * Save Log entry
         */
        $snapshot_id = wp_insert_post( array(
            'post_title'     => $time,
            'post_name'      => $time,
            'post_type'      => 'snapshot',
            'post_status'    => 'pending',
            'post_excerpt'   => wp_json_encode( $_user ),
            'post_content'   => wp_json_encode( $_post ),
            'post_author'    => $user->ID,
            'post_parent'    => $post->ID,
            'post_mime_type' => 'application/json',
        ));


        if ( 'publish' === $new_status )
        {
            /**
             * Save HTML version of page in case page is public
             * Delay saving new version for 10 minutes
             */
            wp_schedule_single_event( $time + 600, 'webarchive_create_snapshot', array( $snapshot_id ) );
        }
    }


    private function update_snapshot_guid( $snapshot_id ) {

        global $wpdb;

        $wpdb->update(
            $wpdb->posts,
            array(
                'guid' => $this->dirs['snapshot_uri'] . '/index.html',
            ),
            array( 'ID' => $snapshot_id ),
            array(
                '%s',
            ),
            array( '%d' )
        );
    }


    /**
     * Save HTML version of page
     */
    public function do_snapshot( $snapshot_id ) {

        // Log Entry
        $snapshot = get_post( $snapshot_id );

        if ( empty($snapshot) )
        {
            // error_log( sprintf('WP_Post not found, ID: %d', $snapshot_id) );
            return;
        }

        $fs = new \WP_Filesystem_Direct('');

        // Original WP_Post
        $wp_post = json_decode( $snapshot->post_content, true );

        // Generate Snapshot directory
        $this->generate_snapshot_directory( $wp_post['ID'] );

        // Directory where HTML snapshot is saved
        $this->update_snapshot_guid( $snapshot_id );

        // Fetch the WordPress post content using GuzzleHttp
        $this->save_to_disk( $wp_post['ID'], $fs );

        /**
         * Save JSON WP_Post and postmeta data
         */
        $_meta = wp_json_encode( get_post_meta( $wp_post['ID'] ) );

        $fs->put_contents( "{$this->dirs['json_path']}/meta.json", $_meta );
        $fs->put_contents( "{$this->dirs['json_path']}/post.json", $snapshot->post_content );
    }


    
    /**
     * Fetch page content   
     */
    private function save_to_disk($post_id, $fs) {

        $permalink = home_url( wp_make_link_relative( get_permalink( $post_id ) ) );
        $client    = new Client([
            'http_errors' => false,
        ]);
        $headers   = array(
            'User-Agent' => "WPWebArchive/{$this->get_plugin_version()}",
            'X-WP-Nonce' => wp_create_nonce('create_snapshot'),
        );

        $content = $client->request('GET', $permalink, [
            'headers' => $headers
        ])->getBody()->getContents();
        
        /**
         * Snapshot DOM HTML
         */
        $dom   = HtmlDomParser::str_get_html($content);
        $nodes = $dom->find('link, script, img, source');

        foreach ($nodes as $node )
        {
            // Skip inline elements without src/href
            if ( ! $node->hasAttribute('src') && ! $node->hasAttribute('href')  )
                continue;

            $updater = new DOMNodeProcessor( $node, $this->dirs, $fs );
            $updater->processNode();
        }

        $fs->put_contents( "{$this->dirs['snapshot_path']}/index.html", $dom->save() );
    }


    /**
     * Generate directory where to save
     */
    private function generate_snapshot_directory( $post_id ) {

        $now        = gmdate('Y/m/d');
        $time       = gmdate('U');
        $baseDir    = $this->get_constant('SNAPSHOTS_BASE_DIR');
        $baseUri    = content_url($this->get_constant('SNAPSHOTS_BASE_URI'));

        // Consolidating directories and URIs into a single array
        $paths = [
            'snapshot_path' => "{$baseDir}/{$now}/{$post_id}/{$time}",
            'assets_path'   => "{$baseDir}/{$now}/{$post_id}/{$time}/assets",
            'json_path'     => "{$baseDir}/{$now}/{$post_id}/{$time}/json",
            'snapshot_uri'  => "{$baseUri}/{$now}/{$post_id}/{$time}",
            'assets_uri'    => "{$baseUri}/{$now}/{$post_id}/{$time}/assets",
            'uploads_path'  => $this->get_constant('SNAPSHOTS_UPLOADS_DIR'),
            'uploads_uri'   => $this->get_constant('SNAPSHOTS_UPLOADS_URI'),
            'home_url'      => untrailingslashit( home_url() ),
            'theme_url'     => untrailingslashit( get_stylesheet_directory_uri() )
        ];

        $this->dirs = array_merge( $paths, wp_upload_dir() );

        // Create directories
        if (wp_mkdir_p($this->dirs['snapshot_path']))
        {
            wp_mkdir_p($this->dirs['assets_path']);
            wp_mkdir_p($this->dirs['json_path']);
        }
    }
}