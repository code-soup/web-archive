<?php

namespace CodeSoup\WebArchive\Core;

// Exit if accessed directly
defined( 'WPINC' ) || die;

/**
 * Use built in WordPress classes
 */
require_once ( ABSPATH . '/wp-admin/includes/file.php' );

class SnapshotController {

    use \CodeSoup\WebArchive\Traits\HelpersTrait;

    public function __construct() {
        // Do something if required
    }

    /**
     * Register route for file uploads from remote repository
     */
    public function register_routes() {

        register_rest_route(
            'web-archive/v1',
            '/snapshots',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                ),
            )
        );
    }




    public function get_items( \WP_REST_Request $request ) {
        
        $params    = $request->get_params();
        $response  = array();
        $data      = array(
            'posts' => array(),
            'pages' => 1,
            'found' => 0,
        );

        // Query args
        $args = array(
            'paged'          => empty($params['page']) ? 1 : $params['page'],
            'posts_per_page' => empty($params['per_page']) ? 15 : $params['per_page'],
            'post_type'      => 'snapshot',
            'post_status'    => empty($params['status']) ? 'pending' : $params['page'],
        );

        // Search string
        if ( ! empty($params['search']) )
        {
            $args['s']           = $params['search'];
            $args['post_status'] = 'any';
        }

        $snapshots = new \WP_Query($args);

        if ( $snapshots->have_posts() )
        {
            foreach( $snapshots->posts as $wp_post ) {
                
                $_post = array(
                    'id'   => $wp_post->ID,
                    'time' => $wp_post->post_title,
                    'url'  => $wp_post->guid,
                    'post' => json_decode( $wp_post->post_content, true ),
                    'user' => json_decode( $wp_post->post_excerpt, true ),
                );

                
                $data['posts'][] = $_post;
            }

            $data['pages'] = $snapshots->max_num_pages;
            $data['found'] = $snapshots->found_posts;
        }

        return rest_ensure_response( $data );
    }


    /**
     * Validate user permissions when trying to deploy from docker
     */
    public function get_items_permissions_check( \WP_REST_Request $request ) {

        $wp_user = wp_get_current_user();

        if ( ! empty($wp_user) )
        {
            return $wp_user->has_cap('manage_snapshots');
        }

        return false;
    }


    /**
     * Get the query params for collections
     *
     * @return array
     */
    public function get_collection_params() {
        return array(
            'page'     => array(
                'description'       => 'Current page of the collection.',
                'type'              => 'integer',
                'min'               => 1,
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => 'Maximum number of items to be returned in result set.',
                'type'              => 'integer',
                'min'               => '1',
                'default'           => 20,
                'sanitize_callback' => 'absint',
            ),
            'search'   => array(
                'description'       => 'Limit results to those matching a string.',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'status'   => array(
                'description'       => 'Snapshot status.',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }
}