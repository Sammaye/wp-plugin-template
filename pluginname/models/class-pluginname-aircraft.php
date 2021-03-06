<?php

class Pluginname_Aircraft {
	public function __construct() {
		register_post_type(
			apply_filters( 'pluginname_id', 'aircraft' ),
			[
				'labels'      => [
					'name'          => esc_html__( 'Aircraft', 'pluginname' ),
					'singular_name' => esc_html__( 'Aircraft', 'pluginname' ),
					'add_new_item'  => esc_html__( 'Add Aircraft', 'pluginname' )
				],
				'public'      => true,
				'has_archive' => true,
				'rewrite'     => [ 'slug' => 'aircraft' ],
				'supports'    => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
			]
		);
		add_action( 'cmb2_init', [ $this, 'metabox' ] );

		if ( is_admin() ) {
			add_filter(
				'manage_edit-' . apply_filters( 'pluginname_id', 'aircraft' ) . '_columns',
				[ $this, 'admin_columns' ]
			);
			add_action(
				'manage_' . apply_filters( 'pluginname_id', 'aircraft' ) . '_posts_custom_column',
				[ $this, 'admin_column_values' ],
				10,
				2
			);
			add_filter(
				'manage_edit-' . apply_filters( 'pluginname_id', 'aircraft' ) . '_sortable_columns',
				[ $this, 'admin_sortable_columns' ]
			);
			add_action( 'load-edit.php', function () {
				add_filter( 'request', [ $this, 'admin_column_sort' ] );
			} );

			add_filter( 'posts_clauses', function ( $clauses, $wp_query ) {
				if ( ! is_admin() ) {
					return;
				}

				global $wpdb;

				if ( isset( $wp_query->query['orderby'] ) && 'cat' == $wp_query->query['orderby'] ) {

					$taxonomy_name = apply_filters( 'pluginname_id', 'aircraft_type' );

					$clauses['join'] .= <<<SQL
LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id
LEFT OUTER JOIN {$wpdb->term_taxonomy} USING (term_taxonomy_id)
LEFT OUTER JOIN {$wpdb->terms} USING (term_id)
SQL;

					$clauses['where']   .= " AND (taxonomy = '$taxonomy_name' OR taxonomy IS NULL)";
					$clauses['groupby'] = "object_id";
					$clauses['orderby'] = "GROUP_CONCAT({$wpdb->terms}.name ORDER BY name ASC) ";
					$clauses['orderby'] .= ( 'ASC' == strtoupper( $wp_query->get( 'order' ) ) ) ? 'ASC' : 'DESC';
				}

				return $clauses;
			}, 10, 2 );

			add_action( 'pre_get_posts', function ( $query ) {
				if ( ! is_admin() ) {
					return;
				}
			} );
		}
	}

	public function metabox() {
		$box = new_cmb2_box( [
			'id'           => apply_filters( 'pluginname_id', 'aircraft_metabox' ),
			'title'        => esc_html__( 'Specifications', 'pluginname' ),
			'object_types' => [ apply_filters( 'pluginname_id', 'aircraft' ) ],
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		] );

		$box->add_field( [
			'name'   => esc_html__( 'Seats', 'pluginname' ),
			'id'     => apply_filters( 'pluginname_id', 'seats' ),
			'filter' => 'integer',
			'type'   => 'text_small'
		] );
		$box->add_field( [
			'name'   => esc_html__( 'Speed', 'pluginname' ),
			'id'     => apply_filters( 'pluginname_id', 'speed' ),
			'filter' => 'integer',
			'type'   => 'text_small'
		] );
		$box->add_field( [
			'name'   => esc_html__( 'Range', 'pluginname' ),
			'id'     => apply_filters( 'pluginname_id', 'range' ),
			'filter' => 'integer',
			'type'   => 'text_small'
		] );
		$box->add_field( [
			'name'   => esc_html__( 'Cabin Height', 'pluginname' ),
			'id'     => apply_filters( 'pluginname_id', 'cabin_height' ),
			'filter' => 'float',
			'type'   => 'text_small'
		] );
		$box->add_field( [
			'name'   => esc_html__( 'Cabin Width', 'pluginname' ),
			'id'     => apply_filters( 'pluginname_id', 'cabin_width' ),
			'filter' => 'float',
			'type'   => 'text_small'
		] );
		$box->add_field( [
			'name'   => esc_html__( 'Luggage Space', 'pluginname' ),
			'id'     => apply_filters( 'pluginname_id', 'space' ),
			'filter' => 'float',
			'type'   => 'text_small'
		] );
		$box->add_field( [
			'name'           => esc_html__( 'Layout Image', 'pluginname' ),
			'desc'           => esc_html__( 'Upload an image or enter an URL.', 'pluginname' ),
			'id'             => apply_filters( 'pluginname_id', 'layout_image' ),
			'type'           => 'file',
			'options'        => [
				'url' => false,
			],
			'text'           => [
				'add_upload_file_text' => esc_html__( 'Add File', 'pluginname' )
			],
			'list_attribute' => false
		] );
		$box->add_field( [
			'name'           => esc_html__( 'Aircraft Gallery', 'pluginname' ),
			'id'             => apply_filters( 'pluginname_id', 'images' ),
			'type'           => 'file_list',
			'list_attribute' => false
		] );
		$group_field_id = $box->add_field( [
			'id'          => apply_filters( 'pluginname_id', 'market_points' ),
			'name'        => esc_html__( 'Marketing Points', 'pluginname' ),
			'type'        => 'group',
			'description' => esc_html__( 'Custom little marketing points', 'pluginname' ),
			'options'     => [
				'group_title'   => esc_html__( 'Point {#}', 'pluginname' ),
				'add_button'    => esc_html__( 'Add Another Point', 'pluginname' ),
				'remove_button' => esc_html__( 'Remove Point', 'pluginname' ),
				'sortable'      => true,
			],
		] );
		$box->add_group_field( $group_field_id, [
			'name' => esc_html__( 'Marketing Point', 'pluginname' ),
			'id'   => apply_filters( 'pluginname_id', 'market_point' ),
			'type' => 'text',
		] );
	}

	public function admin_columns( $columns ) {

		$columns = [
			'cb'   => '<input type="checkbox" />',
			'id'   => esc_html__( 'ID', 'pluginname' ),
			'cat'  => esc_html__( 'Category', 'pluginname' ),
			'date' => esc_html__( 'Date', 'pluginname' )
		];

		return $columns;
	}

	public function admin_column_values( $column, $post_id ) {
		global $post;

		if ( $column === 'id' ) {
			echo '<a href="' . get_edit_post_link( $post_id ) . '">' . $post->post_title . '</a>';
		} elseif ( $column == 'cat' ) {
			$value = get_the_terms( $post, apply_filters( 'pluginname_id', 'aircraft_type' ) );
			$terms = [];
			foreach ( $value as $term ) {
				$terms[] = $term->name;
			}

			if ( count( $terms ) <= 0 ) {
				echo esc_html__( 'No Value' );
			} else {
				echo esc_html__( implode( ', ', $terms ) );
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
		$columns['id']  = 'post_id';
		$columns['cat'] = 'cat';

		return $columns;
	}

	function admin_column_sort( $vars ) {
		if (
			isset( $vars['post_type'] ) &&
			apply_filters( 'pluginname_id', 'aircraft' ) == $vars['post_type']
		) {
			if ( isset( $vars['orderby'] ) ) {
				// nothing but could be needed
			}
		}

		return $vars;
	}
}
