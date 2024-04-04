<?php

namespace CodeSoup\WebArchive\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use voku\helper\HtmlDomParser;

// Exit if accessed directly
defined( 'WPINC' ) || die;

class Snapshot {

    use \CodeSoup\WebArchive\Traits\HelpersTrait;

    private $post;

    private $path;

    private $args;

    private $nonce_action = 'create_uri_snapshot';

    private $post_types = array();

    private $fs;

    private $path_base;

    private $path_assets;

    private $path_json;

    private $uri_base;

    private $uri_assets;

    private $path_wp;

    public function __construct( $args = array() )
    {
        $this->args       = $args;
        $this->path_wp    = wp_upload_dir();
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

        $time = time();

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
            'post_excerpt'   => json_encode( $_user ),
            'post_content'   => json_encode( $_post ),
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
                'guid' => $this->uri_base . '/index.html',
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
    public function save_html_snapshot( $snapshot_id ) {

        $this->fs = new \WP_Filesystem_Direct('');
        
        // Log Entry
        $snapshot = get_post( $snapshot_id );

        if ( empty($snapshot) )
        {
            error_log( sprintf('WP_Post not found, ID: %d', $snapshot_id) );
            return;
        }

        // Original WP_Post
        $wp_post = json_decode( $snapshot->post_content, true );

        // Generate Snapshot directory
        $this->generate_snapshot_directory( $snapshot, time() );

        // Directory where HTML snapshot is saved
        $this->update_snapshot_guid( $snapshot_id );

        // Save All meta data
        $this->save_snapshot_json( $snapshot );

        // Generate the permalink from the post ID
        $permalink = home_url( wp_make_link_relative( get_permalink( $wp_post['ID'] ) ) );

        // Fetch the WordPress post content using GuzzleHttp
        $page_content = $this->fetch_page_content($permalink);

        // Save all assets (images, CSS, scripts, fonts, videos)
        $this->save_assets($page_content);
    }


    
    /**
     * Fetch page content
     * 
     * @param  string $permalink
     * 
     * @return mixed            
     */
    private function fetch_page_content($permalink) {

        $client  = new Client();
        $headers = array(
            'User-Agent' => 'WPWebArchive/' . $this->get_plugin_version(),
            'X-WP-Nonce' => wp_create_nonce($this->nonce_action),
        );

        $response = $client->request('GET', $permalink, [
            'headers' => $headers
        ]);

        return $response->getBody()->getContents();
    }



    private function save_snapshot_json( $wp_post ) {

        $meta = json_encode( get_post_meta( $wp_post->ID ) );
        $post = json_encode( $wp_post );

        $this->fs->put_contents( $this->path_json . '/meta.json', $meta );
        $this->fs->put_contents( $this->path_json . '/post.json', $post );
    }


    
    private function save_assets($page_content) {

        $dom = HtmlDomParser::str_get_html($page_content);
        
        // Assets to save
        $assets = array(
            'css'     => 'link',
            'scripts' => 'script',
            'images'  => 'img',
            // 'meta'    => 'meta',
            'videos'  => 'video',
        );

        foreach ( $assets as $type => $tag )
        {
            $nodes = $dom->getElementsByTagName( $tag );
            
            foreach ( $nodes as $node )
            {
                $this->save_file($node, $type);
            }
        }

        $html = $dom->save();
        $this->fs->put_contents( $this->path_base . '/index.html', $html );
    }


    /**
     * Save file to disk
     * 
     * @param  DOMNode $node   
     * @param  string $type 
     * @return [type]       
     */
    private function save_file( $node, string $tag ) {

        $src    = '';
        $srcset = '';
        $uris   = array();

        switch ( $tag )
        {
            case 'link':
                $uris[] = $node->getAttribute('href');
            break;

            case 'meta':
                $uris[] = $node->getAttribute('content');
            break;

            default:
                $uris[] = $node->getAttribute('src');
                $srcset = $node->getAttribute('srcset');

                // Get from srcset
                foreach ( explode(' ', $srcset) as $item )
                {
                    if ( $this->isImageFile($item) ) {
                        $uris[] = $item;
                    }
                }
            break;
        }

        /**
         * Filter
         */
        $uris = array_filter( $uris );
        $uris = array_unique( $uris );
        

        // Nothing to work with
        if ( empty( $uris ) )
            return;


        /**
         * HTTP Client
         */
        $client = new Client([
            'http_errors' => false,
        ]);

        
        /**
         * Loop trough
         */
        foreach ( $uris as $src ) :

            $filename = basename($src);
            $abs_src  = strtok($src, '?');

            // Convert URI from relative to full URL
            if ( $this->is_relative( $src ) ) {
                $abs_src = home_url( strtok($src, '?') );
            }

            /**
             * Get absolute path and uri
             */
            $paths = $this->getPaths( $abs_src );

            /**
             * Skip if exists
             */
            if ( file_exists($paths['path']) )
            {
                continue;
            }

            /**
             * Get that file
             */
            $response = $client->get( $abs_src );

            try
            {
                // File does not exist
                if ( 200 !== $response->getStatusCode() )
                {
                    return;
                }

                // Create directory if it doesn't exist
                if ( ! is_dir( dirname($paths['path'])) )
                {
                    wp_mkdir_p( dirname($paths['path']) );
                }

                $content = $response->getBody()->getContents();

                switch ( $tag )
                {
                    case 'link':
                        $this->fs->put_contents( $paths['path'], $content );
                        $node->setAttribute('href', $paths['uri'] );
                    break;

                    case 'script':
                        $this->fs->put_contents( $paths['path'], $content );
                        $node->setAttribute('src', $paths['uri'] );
                    break;

                    default:
                        $this->fs->put_contents( $paths['path'], $content );
                        $node->setAttribute('src', $paths['uri'] );
                    break;
                }
            }
            catch (RequestException $e) {
                error_log( $e->getMessage() );
            }
        endforeach;
    }



    private function getPaths( string $abs_src ) {

        /**
         * Not from wp-content/uploads directory
         */
        if ( false === strpos($abs_src, $this->path_wp['baseurl']) )
        {   
            // Absolute path to file
            $path = sprintf(
                '%s/%s',
                $this->path_assets,
                basename($abs_src)
            );

            // Absolute URI to file
            $uri = sprintf(
                '%s/%s',
                $this->uri_assets,
                basename($abs_src)
            );
        }
        else
        {
            /**
             * YMD Path in case file is from uploads dir
             * Eg: 2023/12/thumb-300x175.png
             */
            $ydm_path = str_replace($this->path_wp['baseurl'], '', $abs_src );

            $path = sprintf(
                '%s%s',
                $this->get_constant('SNAPSHOTS_UPLOADS_DIR'),
                $ydm_path
            );

            $uri = sprintf(
                '%s%s',
                $this->get_constant('SNAPSHOTS_UPLOADS_URI'),
                $ydm_path
            );
        }

        return array(
            'path' => $path,
            'uri'  => $uri,
        );
    }


    /**
     * Check if passed url is actual image
     * @param  [type]  $string [description]
     * @return boolean         [description]
     */
    private function isImageFile($string) {
        // Define a list of image file extensions
        $imageExtensions = array('png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp');

        // Extract the extension from the string
        $extension = strtolower(pathinfo($string, PATHINFO_EXTENSION));

        // Check if the extension is in the list of image file extensions
        return in_array($extension, $imageExtensions);
    }



    /**
     * Generate directory where to save
     * @param  \WP_Post $post 
     * @return [type]         
     */
    private function generate_snapshot_directory( $post, $time ) {

        $now = date('Y/m/d');
        
        // Absolute path to where HTML copies of pages are saved
        // localhost/wp-c
        $this->path_base = sprintf(
            '%s/%s/%d/%d',
            $this->get_constant('SNAPSHOTS_BASE_DIR'),
            $now,
            $post->ID,
            $time
        );

        // URL
        $this->uri_base = sprintf(
            '%s/%s/%d/%d',
            content_url($this->get_constant('SNAPSHOTS_BASE_URI')),
            $now,
            $post->ID,
            $time
        );

        $this->path_assets = $this->path_base . '/assets';
        $this->path_json   = $this->path_base . '/json';
        $this->uri_assets  = $this->uri_base . '/assets';

        // Create base dir
        $created = wp_mkdir_p( $this->path_base );

        // Create directory where to save assets
        if ( $created ) {

            // Save assets
            wp_mkdir_p( $this->path_assets );

            // Where to save JSON copy of page
            wp_mkdir_p( $this->path_json );
        }
    }
}