<?php

class Pluginname_Empty_Leg {

	public function __construct() {
		register_post_type(
			apply_filters( 'pluginname_id', 'empty_leg' ),
			[
				'labels'      => [
					'name'          => esc_html__( 'Empty Legs', 'pluginname' ),
					'singular_name' => esc_html__( 'Empty Leg', 'pluginname' ),
					'add_new_item'  => esc_html__( 'Add Empty Leg', 'pluginname' )
				],
				'public'      => true,
				'has_archive' => true,
				'rewrite'     => [ 'slug' => 'empty-leg' ], // my custom slug
				'supports'    => [ 'editor' ]
			]
		);
		add_action( 'cmb2_init', [ $this, 'metabox' ] );

		if ( is_admin() ) {
			add_filter(
				'manage_edit-' . apply_filters( 'pluginname_id', 'empty_leg' ) . '_columns',
				[ $this, 'admin_columns' ]
			);
			add_action(
				'manage_' . apply_filters( 'pluginname_id', 'empty_leg' ) . '_posts_custom_column',
				[ $this, 'admin_column_values' ],
				10,
				2
			);
			add_filter(
				'manage_edit-' . apply_filters( 'pluginname_id', 'empty_leg' ) . '_sortable_columns',
				[ $this, 'admin_sortable_columns' ]
			);
			add_action( 'load-edit.php', function () {
				add_filter( 'request', [ $this, 'admin_column_sort' ] );
			} );

			add_action( 'pre_get_posts', function ( $query ) {
				if ( ! is_admin() ) {
					return;
				}

				$orderby = $query->get( 'orderby' );

				if ( 'e_leg_aircraft' === $orderby ) {
					$query->set( 'meta_key', apply_filters( 'pluginname_id', $orderby ) );
					$query->set( 'orderby', 'meta_value' );
				} elseif ( 'from_date' === $orderby || 'to_date' === $orderby ) {
					$query->set( 'meta_key', apply_filters( 'pluginname_id', $orderby ) );
					$query->set( 'orderby', 'meta_value_num' );
				}
			} );
		}
	}

	public function metabox() {
		$box = new_cmb2_box( [
			'id'           => apply_filters( 'pluginname_id', 'empty_leg_metabox' ),
			'title'        => esc_html__( 'Details', 'pluginname' ),
			'object_types' => [ apply_filters( 'pluginname_id', 'empty_leg' ) ], // Post type
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left
		] );

		$box->add_field( [
			'name'        => esc_html__( 'From Date', 'pluginname' ),
			'id'          => apply_filters( 'pluginname_id', 'from_date' ),
			'type'        => 'text_date_timestamp',
			'date_format' => 'd/m/Y'
		] );
		$box->add_field( [
			'name'        => esc_html__( 'To Date', 'pluginname' ),
			'id'          => apply_filters( 'pluginname_id', 'to_date' ),
			'type'        => 'text_date_timestamp',
			'date_format' => 'd/m/Y'
		] );
		$box->add_field( [
			'name' => esc_html__( 'Departure Airport/City', 'pluginname' ),
			'id'   => apply_filters( 'pluginname_id', 'depart_airport' ),
			'type' => 'text_small'
		] );
		$box->add_field( [
			'name' => esc_html__( 'Arrival Airport/City', 'pluginname' ),
			'id'   => apply_filters( 'pluginname_id', 'arrive_airport' ),
			'type' => 'text_small'
		] );
		$box->add_field( [
			'name' => esc_html__( 'Aircraft', 'amdiraljet' ),
			'id'   => apply_filters( 'pluginname_id', 'e_leg_aircraft' ),
			'type' => 'text_small'
		] );
	}

	public function admin_columns( $columns ) {

		$columns = [
			'cb'             => '<input type="checkbox" />',
			'id'             => esc_html__( 'ID', 'pluginname' ),
			'from_date'      => esc_html__( 'From Date', 'pluginname' ),
			'to_date'        => esc_html__( 'To Date', 'pluginname' ),
			'depart_airport' => esc_html__( 'Departure Airport/City', 'pluginname' ),
			'arrive_airport' => esc_html__( 'Arrival Airport/City', 'pluginname' ),
			'e_leg_aircraft' => esc_html__( 'Aircraft', 'pluginname' ),
			'date'           => esc_html__( 'Date', 'pluginname' )
		];

		return $columns;
	}

	public function admin_column_values( $column, $post_id ) {
		global $post;

		if ( $column === 'id' ) {
			echo '<a href="' . get_edit_post_link( $post_id ) . '">' . $post->post_title . ' #' . $post_id . '</a>';
		} elseif ( $column == 'from_date' || $column == 'to_date' ) {
			$value = get_post_meta( $post_id, apply_filters( 'pluginname_id', $column ), true );
			if ( empty( $value ) ) {
				echo esc_html__( 'No Value' );
			} else {
				echo esc_html__( date( 'd/m/Y', $value ) );
			}
		} else {
			$value = get_post_meta( $post_id, apply_filters( 'pluginname_id', $column ), true );
			if ( empty( $value ) ) {
				echo esc_html__( 'No Value' );
			} else {
				echo esc_html__( $value );
			}
		}
	}

	public function admin_sortable_columns( $columns ) {
		$columns['id']             = 'post_id';
		$columns['from_date']      = 'from_date';
		$columns['to_date']        = 'to_date';
		$columns['e_leg_aircraft'] = 'e_leg_aircraft';

		return $columns;
	}

	function admin_column_sort( $vars ) {
		if (
			isset( $vars['post_type'] ) &&
			apply_filters( 'pluginname_id', 'empty_leg' ) == $vars['post_type']
		) {
			if ( isset( $vars['orderby'] ) ) {
				// nothing but could be needed
			}
		}

		return $vars;
	}
}
