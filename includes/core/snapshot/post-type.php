<?php

// Exit if accessed directly
defined( 'WPINC' ) || die;


$labels = array(
	'name'               => __( 'Snapshot', 'content-changelog' ),
	'singular_name'      => __( 'Snapshot', 'content-changelog' ),
	'add_new'            => _x( 'Add New Snapshot', 'Add new item', 'content-changelog' ),
	'add_new_item'       => __( 'Add New Snapshot', 'content-changelog' ),
	'edit_item'          => __( 'Edit Snapshot', 'content-changelog' ),
	'new_item'           => __( 'New Snapshot', 'content-changelog' ),
	'view_item'          => __( 'View Snapshot', 'content-changelog' ),
	'search_items'       => __( 'Search Snapshot', 'content-changelog' ),
	'not_found'          => __( 'No Snapshot found', 'content-changelog' ),
	'not_found_in_trash' => __( 'No Snapshot found in Trash', 'content-changelog' ),
	'parent_item_colon'  => __( 'Parent Snapshot:', 'content-changelog' ),
	'menu_name'          => __( 'Snapshot', 'content-changelog' ),
);

return array(
	'labels'              => $labels,
	'hierarchical'        => false,
	'description'         => _x( 'Page Snapshot', 'Post type description', 'content-changelog' ),
	'taxonomies'          => array(),
	'public'              => true,
	'show_ui'             => true,
	'show_in_menu'        => false,
	'show_in_admin_bar'   => false,
	'menu_position'       => null,
	'menu_icon'           => 'dashicons-welcome-view-site',
	'show_in_nav_menus'   => false,
	'publicly_queryable'  => false,
	'exclude_from_search' => false,
	'has_archive'         => false,
	'query_var'           => true,
	'show_in_rest'        => false,
	'can_export'          => false,
	'rewrite'             => false,
	'capability_type'     => 'snapshot',
);