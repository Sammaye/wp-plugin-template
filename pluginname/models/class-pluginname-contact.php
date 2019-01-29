<?php

class Pluginname_Contact {

	public function __construct() {
		register_post_type(
			apply_filters( 'pluginname_id', 'contact' ),
			[
				'labels'      => [
					'name'          => __( 'Contacts', 'pluginname' ),
					'singular_name' => __( 'Contact', 'pluginname' ),
					'add_new_item'  => __( 'Add Contact', 'pluginname' ),
				],
				'public'      => true,
				'has_archive' => false,
				'rewrite'     => [ 'slug' => 'contact' ],
				'supports'    => [ 'title', 'editor' ],
			]
		);

		add_action( 'cmb2_init', [ $this, 'metabox' ] );

		if ( is_admin() ) {
			add_filter(
				'manage_edit-' . apply_filters( 'pluginname_id', 'contact' )
				. '_columns',
				[ $this, 'admin_columns' ]
			);
			add_action(
				'manage_' . apply_filters( 'pluginname_id', 'contact' )
				. '_posts_custom_column',
				[ $this, 'admin_column_values' ],
				10,
				2
			);
			add_filter(
				'manage_edit-' . apply_filters( 'pluginname_id', 'contact' )
				. '_sortable_columns',
				[ $this, 'admin_sortable_columns' ]
			);
			add_action( 'load-edit.php', function () {
				add_filter( 'request', [ $this, 'admin_column_sort' ] );
			} );
		}

		add_action( 'wp_ajax_Contact', [ $this, 'save_contact' ] );
		add_action( 'wp_ajax_nopriv_Contact', [ $this, 'save_contact' ] );

		add_action( 'wp_ajax_Enquire', [ $this, 'save_enquiry' ] );
		add_action( 'wp_ajax_nopriv_Enquire', [ $this, 'save_enquiry' ] );

		add_action( 'pre_get_posts', function ( $query ) {
			if ( ! is_admin() ) {
				return;
			}

			$orderby = $query->get( 'orderby' );

			if ( 'name' === $orderby ) {
				$query->set( 'meta_key',
					apply_filters( 'pluginname_id', $orderby ) );
				$query->set( 'orderby', 'meta_value' );
			} elseif ( 'departure_date' === $orderby
			           || 'return_date' === $orderby
			) {
				$query->set( 'meta_key',
					apply_filters( 'pluginname_id', $orderby ) );
				$query->set( 'orderby', 'meta_value_num' );
			}
		} );
	}

	public function metabox() {
		$box = new_cmb2_box( [
			'id'           => apply_filters( 'pluginname_id', 'contact_metabox' ),
			'title'        => __( 'Details', 'pluginname' ),
			'object_types' => [ apply_filters( 'pluginname_id', 'contact' ) ],
			// Post type
			'context'      => 'normal',
			'priority'     => 'high',
			'option_key'   => 'pluginname_contact_options',
			'show_names'   => true,
			// Show field names on the left
		] );

		$title_options = [];
		foreach ( Pluginname::title_list() as $k => $v ) {
			$title_options[ $v ] = __( $v );
		}

		$box->add_field( [
			'name'             => __( 'Title', 'pluginname' ),
			'id'               => apply_filters( 'pluginname_id', 'title' ),
			'type'             => 'select',
			'show_option_none' => true,
			'default'          => 'custom',
			'options'          => $title_options,
		] );

		$box->add_field( [
			'name' => __( 'Name', 'pluginname' ),
			'id'   => apply_filters( 'pluginname_id', 'name' ),
			'type' => 'text',
		] );

		$box->add_field( [
			'name' => __( 'Company', 'pluginname' ),
			'id'   => apply_filters( 'pluginname_id', 'company' ),
			'type' => 'text',
		] );

		$box->add_field( [
			'name' => __( 'Phone Number', 'pluginname' ),
			'id'   => apply_filters( 'pluginname_id', 'phone' ),
			'type' => 'text',
		] );

		$box->add_field( [
			'name' => __( 'Email Address', 'pluginname' ),
			'id'   => apply_filters( 'pluginname_id', 'email' ),
			'type' => 'text_email',
		] );

		$country_options = [];
		foreach ( Pluginname::country_list() as $k => $v ) {
			$country_options[ $v['code'] ] = __( $v['name'] );
		}

		$box->add_field( [
			'name'             => __( 'Country' ),
			'id'               => apply_filters( 'pluginname_id', 'country' ),
			'type'             => 'select',
			'show_option_none' => true,
			'default'          => 'custom',
			'options'          => $country_options,
		] );

		$box->add_field( [
			'name' => __( 'Departure Airport' ),
			'id'   => apply_filters( 'pluginname_id', 'departure_airport' ),
			'type' => 'text',
		] );

		$group_field_id = $box->add_field( [
			'id'         => apply_filters( 'pluginname_id', 'arrival_airport' ),
			'name'       => __( 'Arrival Airport', 'pluginname' ),
			'type'       => 'group',
			'options'    => [
				'group_title'   => __( 'Arrival Airport/Leg Name {#}', 'pluginname' ),
				'add_button'    => __( 'Add Another Leg', 'pluginname' ),
				'remove_button' => __( 'Remove Leg', 'pluginname' ),
				'sortable'      => true,
			],
			'test_field' => true,
		] );
		$box->add_group_field( $group_field_id, [
			'name' => 'Airport Name',
			'id'   => apply_filters( 'pluginname_id', 'airport_name' ),
			'type' => 'text',
		] );

		$box->add_field( [
			'name'         => __( 'Departure Date' ),
			'id'           => apply_filters( 'pluginname_id', 'departure_date' ),
			'object_types' => [ apply_filters( 'pluginname_id', 'contact' ) ],
			'type'         => 'text_date_timestamp',
			'date_format'  => 'd/m/Y',
		] );

		$box->add_field( [
			'name'         => __( 'Return Date' ),
			'id'           => apply_filters( 'pluginname_id', 'return_date' ),
			'object_types' => [ apply_filters( 'pluginname_id', 'contact' ) ],
			'type'         => 'text_date_timestamp',
			'date_format'  => 'd/m/Y',
		] );

		$box->add_field( [
			'name'         => __( 'Nunber of Passengers' ),
			'id'           => apply_filters( 'pluginname_id', 'passenger_number' ),
			'object_types' => [ apply_filters( 'pluginname_id', 'contact' ) ],
			'type'         => 'text_small',
		] );

		$type_options = [];
		foreach ( Pluginname::aircraft_type_list() as $k => $v ) {
			$type_options[ $v ] = $v;
		}
		$box->add_field( [
			'name'             => __( 'Aircraft Type' ),
			'id'               => apply_filters( 'pluginname_id', 'aircraft_type' ),
			'object_types'     => [ apply_filters( 'pluginname_id', 'contact' ) ],
			'type'             => 'select',
			'show_option_none' => true,
			'default'          => 'custom',
			'options'          => $type_options,
		] );

		$trip_options = [];
		foreach ( Pluginname::trip_type_list() as $k => $v ) {
			$trip_options[ $v ] = __( $v );
		}
		$box->add_field( [
			'name'             => __( 'Trip Type' ),
			'id'               => apply_filters( 'pluginname_id', 'trip_type' ),
			'object_types'     => [ apply_filters( 'pluginname_id', 'contact' ) ],
			'type'             => 'select',
			'show_option_none' => true,
			'default'          => 'custom',
			'options'          => $trip_options,
		] );

	}

	public function admin_columns( $columns ) {

		$columns = [
			'cb'                => '<input type="checkbox" />',
			'id'                => __( 'ID' ),
			'name'              => __( 'Name' ),
			'company'           => __( 'Company' ),
			'email'             => __( 'Email Address' ),
			'departure_date'    => __( 'Departure Date' ),
			'return_date'       => __( 'Return Date' ),
			'departure_airport' => __( 'Departure Airport' ),
			'arrival_airport'   => __( 'Arrival Airport' ),
			'date'              => __( 'Date' ),
		];

		return $columns;
	}

	public function admin_column_values( $column, $post_id ) {
		global $post;

		if ( $column === 'id' ) {
			echo '<a href="' . get_edit_post_link( $post_id ) . '">'
			     . $post->post_title . ' #' . $post_id . '</a>';
		} else {
			$value = get_post_meta( $post_id,
				apply_filters( 'pluginname_id', $column ), true );
			if ( empty( $value ) ) {
				echo __( 'No Value' );
			} elseif ( is_array( $value ) && 'arrival_airport' === $column ) {
				$airports = [];
				foreach ( $value as $row ) {
					$airports[] = $row[ apply_filters( 'pluginname_id',
						'airport_name' ) ];
				}
				echo __( implode( ', ', $airports ) );
			} elseif ( 'departure_date' === $column
			           || 'return_date' === $column
			) {
				echo __( date( 'd/m/Y', $value ) );
			} else {
				echo __( $value );
			}
		}
	}

	public function admin_sortable_columns( $columns ) {
		$columns['id']             = 'post_id';
		$columns['name']           = 'name';
		$columns['departure_date'] = 'departure_date';
		$columns['return_date']    = 'return_date';

		return $columns;
	}

	function admin_column_sort( $vars ) {
		if ( isset( $vars['post_type'] )
		     && apply_filters( 'pluginname_id', 'contact' ) == $vars['post_type']
		) {
			if ( isset( $vars['orderby'] ) && 'id' == $vars['orderby'] ) {
				// Not need right now
			}
		}

		return $vars;
	}

	public function save_contact() {
		check_ajax_referer( 'ajax-contact-nonce', 'security' );

		$validate = $this->validate( 'contact_form' );

		if ( count( $validate[0] ) > 0 ) {
			echo json_encode( [
				'success' => false,
				'message' => __( 'Could not save your details', 'amdiraljet' ),
				'errors'  => $validate[0],
			] );
		} else {
			$fields = $validate[1];

			$post_id = wp_insert_post( [
				'post_type'      => apply_filters( 'pluginname_id', 'contact' ),
				'post_title'     => $fields['title'] . ' ' . $fields['name'],
				'post_content'   => $fields['message'],
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			] );

			if ( $post_id ) {
				// insert post meta
				foreach (
					[ 'title', 'name', 'email', 'phone', 'company', 'coutry' ] as
					$field_name
				) {
					add_post_meta( $post_id,
						apply_filters( 'pluginname_id', $field_name ),
						$fields[ $field_name ] );
				}

				mail(
					'xxx',
					'New Enquiry Added',
					'Name: ' . $fields['title'] . ' ' . $fields['name'] . "\r\n" .
					'Email: ' . $fields['email'] . "\r\n" .
					'Phone: ' . $fields['phone'] . "\r\n" .
					'Company: ' . $fields['company'] . "\r\n" .
					'Country: ' . $fields['country'] . "\r\n" .
					'From: xxx'
				);

				echo json_encode( [
					'success' => true,
					'message' =>
						__( 'Thank you for contacting Plugin Name. We will get back to you as soon as possible',
							'pluginname' ),
				] );
			} else {
				echo json_encode( [
					'success' => false,
					'message' => __( 'Could not save your details', 'pluginname' ),
					'errors'  => [
						__( 'An unkown error, the data could not be saved',
							'pluginname' ),
					],
				] );
			}
		}
		wp_die();
	}

	public function save_enquiry() {
		check_ajax_referer( 'ajax-enquire-nonce', 'security' );

		$validate = $this->validate( 'enquiry_form' );

		if ( count( $validate[0] ) > 0 ) {
			echo json_encode( [
				'success' => false,
				'message' => __( 'Could not save your details', 'pluginname' ),
				'errors'  => $validate[0],
			] );
		} else {
			$fields = $validate[1];

			$post_id = wp_insert_post( [
				'post_type'      => apply_filters( 'pluginname_id', 'contact' ),
				'post_title'     => $fields['title'] . ' ' . $fields['name'],
				'post_content'   => '',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			] );

			if ( $post_id ) {

				$arrival_airports = [];
				foreach ( $fields['arrival_airport'] as $k => $v ) {
					$arrival_airports[] = [
						apply_filters( 'pluginname_id', 'airport_name' ) => $v,
					];
				}

				foreach (
					[
						'title',
						'name',
						'email',
						'phone',
						'company',
						'country',
						'trip_type',
						'aircraft_type',
						'departure_date',
						'return_date',
						'departure_airport',
						'passengers',
					] as $field_name
				) {
					add_post_meta( $post_id,
						apply_filters( 'pluginname_id', $field_name ),
						$fields[ $field_name ] );
				}

				add_post_meta( $post_id,
					apply_filters( 'pluginname_id', 'arrival_airport' ),
					$arrival_airports );

				mail(
					'xxx',
					'New Enquiry Added',
					'Name: ' . $fields['title'] . ' ' . $fields['name'] . "\r\n" .
					'Email: ' . $fields['email'] . "\r\n" .
					'Phone: ' . $fields['phone'] . "\r\n" .
					'Company: ' . $fields['company'] . "\r\n" .
					'Trip Type: ' . $fields['trip_type'] . "\r\n" .
					'Aircraft Type: ' . $fields['aircraft_type'] . "\r\n" .
					'Departure Date: ' . date( 'd-m-Y', $fields['departure_date'] )
					. "\r\n" .
					'Return Date: ' . date( 'd-m-Y', $fields['return_date'] )
					. "\r\n" .
					'Departure Airport: ' . $fields['departure_airport'] . "\r\n"
					.
					'Arrival Airport: ' . implode( ', ',
						$fields['arrival_airports'] ) . "\r\n" .
					'Passengers: ' . $fields['passengers'],
					'From: xxx'
				);

				echo json_encode( [
					'success' => true,
					'message' =>
						__( 'Thank you for enquiring with Plugin Name. We will get back to you as soon as possible',
							'pluginname' ),
				] );
			} else {
				echo json_encode( [
					'success' => false,
					'message' => __( 'Could not save your details' ),
					'errors'  => [
						__( 'An unkown error, the data could not be saved',
							'pluginname' ),
					],
				] );
			}
		}
		wp_die();
	}

	public function validate( $scenario ) {
		$fields = apply_filters( 'filter_array', [
			'title',
			'name',
			'email',
			'phone',
			'company',
			'country',
			'message',
			'trip_type',
			'aircraft_type',
			'departure_date',
			'return_date',
			'departure_airport',
			'arrival_airport',
			'passengers',
		], $_POST );

		$errors = [];

		if (
			$scenario === 'contact_form'
			&& (
				! $fields['title'] || ! $fields['name'] || ! $fields['email']
				|| ! $fields['message']
			)
		) {
			$errors[] = __( 'Title, Name, Email, and Message are required',
				'pluginname' );
		}

		if (
			$scenario === 'enquiry_form'
			&& (
				! $fields['title'] || ! $fields['name'] || ! $fields['email']
				|| ! $fields['trip_type']
				|| ! $fields['aircraft_type']
				|| ! $fields['departure_date']
				|| ! $fields['departure_airport']
				|| ! $fields['passengers']
			)
		) {
			$errors[] = __( 'Title, Name, Email, Trip Type, Aircraft Type, Departure Date, 
            Departure Airport, and Number of Passengers are required',
				'pluginname' );
		}

		if ( $errors ) {
			// Do not proceed if we cannot even pass empty fields
			return [ $errors, $fields ];
		}

		$countries = [];
		foreach ( Pluginname::country_list() as $k => $v ) {
			$countries[] = $v['code'];
		}

		foreach ( $fields as $field => $value ) {

			if ( ! $value ) {
				// Required is done elsewhere
				continue;
			}

			if (
				$field === 'title'
				&& ! in_array( $value, Pluginname::title_list(), true )
			) {
				$errors[] = __( 'Title must be a value within the provided list',
					'pluginname' );
			}

			if (
				$field === 'email' && ! is_email( $value )
			) {
				$errors[] = __( 'You must enter a valid email to contact us',
					'pluginname' );
			}

			if (
				$field === 'country' && ! in_array( $value, $countries, true )
			) {
				$errors[]
					= __( 'Country must be a value within the provided list',
					'pluginname' );
			}

			if (
				$field === 'trip_type'
				&& ! in_array( $value, Pluginname::trip_type_list() )
			) {
				$errors[]
					= __( 'Trip Type must be a value within the provided list',
					'pluginname' );
			}

			if (
				$field === 'aircraft_type'
				&& ! in_array( $value, Pluginname::aircraft_type_list() )
			) {
				$errors[]
					= __( 'Aircraft Type must be a value within the provided list',
					'pluginname' );
			}

			if (
				$field === 'departure_date'
				&& (
					! preg_match( '#[0-9]{2}/[0-9]{2}/[0-9]{4}#', $value )
					|| DateTime::createFromFormat( 'd/m/Y', $value )->getTimestamp()
					   < time()
				)
			) {
				$errors[] = __( 'Departure Date must be valid and in the future',
					'pluginname' );
			}

			if (
				$field === 'return_date'
				&& (
					! preg_match( '#[0-9]{2}/[0-9]{2}/[0-9]{4}#', $value )
					|| DateTime::createFromFormat( 'd/m/Y', $value )->getTimestamp()
					   < time()
				)
			) {
				$errors[] = __( 'Return Date must be valid and in the future',
					'pluginname' );
			}

			if (
				$field === 'passengers'
				&& ! filter_var(
					$value,
					FILTER_VALIDATE_INT,
					[ 'min_range' => 0, 'max_range' => 100 ]
				)
			) {
				$errors[] = __( 'Enter a valid number of passengers',
					'pluginname' );
			}

			if ( $field === 'arrival_airport' ) {
				if ( ! is_array( $value ) || count( $value ) <= 0 ) {
					$errors[] = __( 'Arrival Airport is not a valid value',
						'pluginname' );
				} else {
					foreach ( $value as $k => $v ) {
						$value[ $k ] = sanitize_text_field( $v );
					}
				}
			}

			if ( $field === 'departure_date' || $field === 'return_date' ) {
				$fields[ $field ] = DateTime::createFromFormat( 'd/m/Y', $value )
				                            ->getTimestamp();
			} elseif ( is_array( $value ) ) {
				$fields[ $field ] = $value;
			} elseif ( $field !== 'message' ) {
				$fields[ $field ] = sanitize_text_field( $value );
			} else {
				$fields[ $field ] = sanitize_textarea_field( $value );
			}
		}

		return [ $errors, $fields ];
	}
}
