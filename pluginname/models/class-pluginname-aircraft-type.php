<?php

class Pluginname_Aircraft_Type {
	public function __construct() {
		register_taxonomy(
			apply_filters( 'pluginname_id', 'aircraft_type' ),
			apply_filters( 'pluginname_id', 'aircraft' ),
			[
				'labels'       => [
					'name'          => esc_html__( 'Aircraft Types', 'pluginname' ),
					'singular_name' => esc_html__( 'Aircraft Type', 'pluginname' ),
					'menu_name'     => esc_html__( 'Categories', 'pluginname' ),
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
			'title'        => esc_html__( 'Extra Fields', 'pluginname' ),
			'object_types' => [ 'term' ],
			'taxonomies'   => [ apply_filters( 'pluginname_id', 'aircraft_type' ) ],
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left
		] );

		$box->add_field( [
			'name'    => esc_html__( 'Banner Image', 'pluginname' ),
			'id'      => apply_filters( 'pluginname_id', 'image' ),
			'type'    => 'file',
			'options' => [
				'url' => false,
			],
		] );
	}
}
