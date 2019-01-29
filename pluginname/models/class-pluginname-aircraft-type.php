<?php

class Pluginname_Aircraft_Type {
	public function __construct() {
		register_taxonomy(
			apply_filters( 'pluginname_id', 'aircraft_type' ),
			apply_filters( 'pluginname_id', 'aircraft' ),
			[
				'labels'       => [
					'name'          => __( 'Aircraft Types', 'pluginname' ),
					'singular_name' => __( 'Aircraft Type', 'pluginname' ),
					'menu_name'     => __( 'Categories', 'pluginname' ),
				],
				'hierarchical' => true,
				'rewrite'      => array( 'slug' => 'aircraft-categories' ),
			]
		);

		add_action( 'cmb2_init', [ $this, 'metabox' ] );
	}

	public function metabox() {
		$box = new_cmb2_box( [
			'id'           => apply_filters( 'pluginname_id', 'aircraft_type_metabox' ),
			'title'        => __( 'Extra Fields', 'pluginname' ),
			'object_types' => [ 'term' ],
			'taxonomies'   => [ apply_filters( 'pluginname_id', 'aircraft_type' ) ],
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left
		] );

		$box->add_field( [
			'name'    => __( 'Banner Image', 'pluginname' ),
			'id'      => apply_filters( 'pluginname_id', 'image' ),
			'type'    => 'file',
			'options' => [
				'url' => false,
			],
		] );
	}
}
