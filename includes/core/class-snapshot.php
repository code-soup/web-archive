<?php

namespace CodeSoup\ContentChangeLog\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;

// Exit if accessed directly
defined( 'WPINC' ) || die;

class Snapshot {

    use \CodeSoup\ContentChangeLog\Traits\HelpersTrait;

    private $post_id;

    private $args;

    private $nonce_action = 'create_uri_snapshot';

    public function __construct($post_id, $args = array())
    {
		$this->post_id = $post_id;
		$this->args    = $args;


        /**
         * Use built in WordPress classes
         */
        if ( ! defined('FS_CHMOD_DIR') ) {
            define( 'FS_CHMOD_DIR', ( 0755 & ~ umask() ) );    
        }
        
        if ( ! defined('FS_CHMOD_FILE') ) {
            define( 'FS_CHMOD_FILE', ( 0644 & ~ umask() ) );
        }

        include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
    }

    public function create_snapshot() {

        // Generate the permalink from the post ID
        $permalink = get_permalink($this->post_id);        

        // Create the directory structure for saving the snapshot
        $this->generate_snapshot_directory();

        // Fetch the WordPress post content using GuzzleHttp
        $page_content = $this->fetch_page_content($permalink);

        // // Save all assets (images, CSS, scripts, fonts, videos)
        $this->save_assets($page_content);
    }


    /**
     * Fetch page content
     * 
     * @param  [type] $permalink [description]
     * @return [type]            [description]
     */
    private function fetch_page_content($permalink) {

        $client  = new Client();
        $headers = array(
            'User-Agent' => 'WPContentChangeLog/' . $this->get_plugin_version(),
            'X-WP-Nonce' => wp_create_nonce($this->nonce_action),
        );

        $response = $client->request('GET', $permalink, [
            'headers' => $headers
        ]);

        return $response->getBody()->getContents();
    }


    private function save_assets($page_content) {

        $dom = new \DOMDocument();
        @$dom->loadHTML($page_content);

        $this->fs = new \WP_Filesystem_Direct('');
        
        
        // Assets to save
        $assets = array(
            'css'     => 'link',
            'scripts' => 'script',
            'images'  => 'img',
            // 'videos'  => 'video',
        );

        foreach ( $assets as $type => $tag )
        {
            $nodes = $dom->getElementsByTagName( $tag );
            
            foreach ( $nodes as $node )
            {
                $this->save_file($node, $type);
            }
        }

        $html = $dom->saveHTML();
        $this->fs->put_contents( $this->dir . '/index.html', $html );
    }


    /**
     * Save file to disk
     * @param  [type] $el   [description]
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    private function save_file( $node, $type ) {

        switch ( $type )
        {
            case 'css':
                $src = $node->getAttribute('href');
            break;

            default:
                $src = $node->getAttribute('src');
            break;
        }

        /**
         * Possible 
         * - Inline <script> tag
         */
        if ( empty($src) ) {
            return;
        }

        // Convert URI from relative to full URL
        if ( $this->is_relative( $src ) ) {
            $src = home_url( $src );
        }

        $client = new Client([
            'http_errors' => false,
        ]);

        try
        {
            $response = $client->get($src);

            // File does not exist
            if ( 200 !== $response->getStatusCode() )
            {
                return;
            }

            $content  = $response->getBody()->getContents();
            $filename = basename(parse_url($src, PHP_URL_PATH));
            $savepath = sprintf(
                '%s/%s',
                $this->dir_assets,
                $filename
            );

            $this->fs->put_contents( $savepath, $content );


            switch ( $type )
            {
                case 'css':
                    $node->setAttribute('href', 'assets/' . $filename );
                break;

                default:
                    $node->setAttribute('src', 'assets/' . $filename );
                break;
            }
        }
        catch (RequestException $e) {
            error_log( $e->getMessage() );
        }
    }


    private function generate_snapshot_directory() {
        
        // Base dir
        $this->dir = sprintf(
            '%s/%s/%d/%d',
            $this->get_constant('SNAPSHOTS_BASE_DIR'),
            date('Y/m/d'),
            $this->post_id,
            123
        );

        // Assets dir
        $this->dir_assets = $this->dir . '/assets';

        // Create base dir
        $created = wp_mkdir_p( $this->dir );

        // Create directory where to save assets
        if ( $created ) {
            wp_mkdir_p( $this->dir_assets );
        }
    }
}