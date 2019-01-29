<?php

/*
Plugin Name: Plugin Name
Plug URI: https://flyadmiral.com/
Description: The core site functionality
Version: 1.0
Author: sammaye.com
Author URI: https://sammaye.com
Text Domain: pluginname
Domain Path: /languages
*/

class Pluginname {

	public $prefix = 'pugn_';

	private $version = '1.0.0';
	private $notices = [];

	public static function activate() {

	}

	public static function deactivate() {

	}

	public static function uninstall() {

	}

	public function id( $name = null ) {
		if ( ! $name ) {
			return $this->prefix;
		} elseif ( 0 === strpos( $name, $this->prefix ) ) {
			return $name;
		}

		return $this->prefix . $name;
	}

	public static function has_box_metadata( $post_id, $box_id, $exclude = [], $all = false ) {

		$box_id = apply_filters( 'pluginname_id', $box_id );
		array_walk( $exclude, function ( &$item ) {
			$item = apply_filters( 'pluginname_id', $item );
		} );

		$box = cmb2_get_metabox( $box_id );

		if ( ! $box ) {
			return false;
		}

		$fields = $box->meta_box['fields'];

		$found           = false;
		$not_found_count = 0;

		foreach ( $fields as $id => $options ) {
			if (
				(
					(
						! isset( $options['list_attribute'] ) ||
						(
							isset( $options['list_attribute'] ) &&
							true === $options['list_attribute']
						)
					) &&
					! in_array( $id, $exclude, true )
				) &&
				get_post_meta( $post_id, $id, true )
			) {
				$found = true;
			} else {
				$not_found_count ++;
			}
		}

		if ( ( $all && $not_found_count ) || ! $found ) {
			return false;
		}

		return true;
	}

	public function __construct() {
		if ( ! class_exists( 'CMB2', false ) ) {
			// If we don't manage the CMB2 plugin via wordpress admin then do it here
			require_once plugin_dir_path( __FILE__ ) . 'includes/cmb2/init.php';
		}

		add_filter( 'pluginname_id', [ $this, 'id' ], 10, 1 );
		add_filter( 'filter_array', function ( $fields, $array ) {
			$a = [];
			foreach ( $fields as $field ) {
				$var = $array[ $field ];
				if ( isset( $array[ $field ] ) ) {
					if ( is_array( $var ) ) {
						$a_var = [];
						foreach ( $var as $k => $v ) {
							$v_var = trim( normalize_whitespace( $v ) );
							if ( $v_var || strlen( $v_var ) > 0 ) {
								$a_var[] = $v_var;
							}
						}

						if ( count( $a_var ) > 0 ) {
							$a[ $field ] = $a_var;
							continue;
						}
					} else {
						$var = trim( normalize_whitespace( $var ) );
						if ( $var || strlen( $var ) > 0 ) {
							$a[ $field ] = $var;
							continue;
						}
					}
				}
				$a[ $field ] = null;
			}

			return $a;
		}, 10, 2 );

		add_action( 'init', [ $this, 'run' ] );
	}

	public function run() {

		// Let's setup our data model

		require_once plugin_dir_path( __FILE__ ) . 'models/class-pluginname-aircraft.php';
		require_once plugin_dir_path( __FILE__ ) . 'models/class-pluginname-aircraft-type.php';
		require_once plugin_dir_path( __FILE__ ) . 'models/class-pluginname-contact.php';
		require_once plugin_dir_path( __FILE__ ) . 'models/class-pluginname-empty-leg.php';

		new Pluginname_Aircraft();
		new Pluginname_Aircraft_Type();
		new Pluginname_Contact();
		new Pluginname_Empty_Leg();

		flush_rewrite_rules();

		// Now we continue

		add_filter(
			'cmb2_override_meta_save',
			function ( $check, $args, $field_args, $field ) {
				$id    = $args['field_id'];
				$value = $args['value'];

				$name = $field_args['name'];
				if ( $field_args['type'] === 'group' ) {
					// TODO make group fields work!!!
					$name = $field_args['name'];
				}

				if ( ! $value ) {
					// Don't save if it is empty
					return true;
				}

				$validator = isset( $field_args['filter'] ) ? $field_args['filter'] : '';
				if ( 'integer' === $validator && ! preg_match( '#^[0-9]+$#', $value ) ) {
					do_action(
						'error',
						'<p>' . sprintf( __( '%s was not saved because it is not a valid number' ), $name ) . '</p>'
					);

					return true;
				} elseif ( 'float' === $validator && ! preg_match( '#^[0-9.]+$#', $value ) ) {
					do_action(
						'error',
						'<p>' . sprintf( __( '%s was not saved because it is not a valid float' ), $name ) . '</p>'
					);

					return true;
				}

				return null;
			}, 10, 4
		);

		if ( is_admin() ) {
			add_action( 'admin_init', [ $this, 'add_privacy_policy_content' ] );

			add_action( 'admin_init', [ $this, 'restore_notices' ] );
			add_action( 'admin_notices', [ $this, 'render_notices' ] );
			add_action( 'shutdown', [ $this, 'store_notices' ] );

			add_action( 'success', function ( $content, $dismissable = false, $key = '' ) {
				$this->add_notice( 'success', $content, $dismissable, $key );
			}, 10, 3 );
			add_action( 'error', function ( $content, $dismissable = false, $key = '' ) {
				$this->add_notice( 'error', $content, $dismissable, $key );
			}, 10, 3 );
			add_action( 'warning', function ( $content, $dismissable = false, $key = '' ) {
				$this->add_notice( 'warning', $content, $dismissable, $key );
			}, 10, 3 );
			add_action( 'info', function ( $content, $dismissable = false, $key = '' ) {
				$this->add_notice( 'info', $content, $dismissable, $key );
			}, 10, 3 );
			add_action( 'add_notice', [ $this, 'add_notice' ], 10, 4 );

			add_action( 'wp_ajax_pluginname_dismiss_notice', [ $this, 'dismiss_notice' ] );
			wp_enqueue_script(
				'pluginname-notice-handler',
				plugins_url( '/admin/js/notice-handler.js', __FILE__ ),
				[ 'jquery' ],
				$this->version,
				true
			);
		}
	}

	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			__( 'Add some privacy stuff here.

			<a href="%s" target="_blank">details click</a>', 'pluginname' ),
			'https://example.com/privacy-policy'
		);

		wp_add_privacy_policy_content(
			'Plugin Name',
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	public function add_notice( $type, $content, $dismissable = false, $key = '' ) {
		if ( ! in_array( $type, [
			'info',
			'success',
			'error',
			'warning',
		], true ) ) {
			throw new \Exception( 'That is not a valid type' );
		}

		$this->notices[] = [
			'key'         => $key ?? null,
			'type'        => $type,
			'content'     => $content,
			'dismissable' => $dismissable,
		];
	}

	public function render_notices() {
		$notice_html = '<div class="pluginname-notice notice notice-%s %s" %s>%s</div>';

		foreach ( $this->notices as $notice ) {
			echo sprintf(
				$notice_html,
				$notice['type'],
				$notice['dismissable'] ? 'is-dismissible' : '',
				isset( $notice['key'] ) && $notice['key'] ? 'data-key="' . $notice['key'] . '"' : '',
				$notice['content']
			);
		}
	}

	public function restore_notices() {
		$value = get_transient( apply_filters( 'pluginname_id', 'notices' ) );
		if ( $value ) {
			$this->notices = $value;
		}
	}

	public function store_notices() {
		$notices = [];
		foreach ( $this->notices as $notice ) {
			if ( $notice['key'] ) {
				$notices[] = $notice;
			}
		}
		set_transient( apply_filters( 'pluginname_id', 'notices' ), $notices, 60 * 10 );
	}

	public function dismiss_notice() {
		if ( isset( $_POST['key'] ) ) {
			foreach ( $this->notices as $k => $notice ) {
				if ( isset( $notice['key'] ) && $notice['key'] === $_POST['key'] ) {
					unset( $this->notices[ $k ] );
				}
			}
			$this->store_notices();
		}

		echo json_encode( [
			'success' => true,
		] );
		wp_die();
	}

	/**
	 * Fields
	 */

	public static function trip_type_list() {
		return [
			'One Way',
			'Return',
			'Multi-leg'
		];
	}

	public static function aircraft_type_list() {
		return [
			'Fixed Wing',
			'Helicopter'
		];
	}

	public static function title_list() {
		return [
			'Ms',
			'Mr',
			'Miss',
			'Mrs'
		];
	}

	public static function country_list() {
		return [
			[ 'code' => 'AF', 'name' => 'Afghanistan' ],
			[ 'code' => 'AX', 'name' => 'Åland Islands' ],
			[ 'code' => 'AL', 'name' => 'Albania' ],
			[ 'code' => 'DZ', 'name' => 'Algeria' ],
			[ 'code' => 'AS', 'name' => 'American Samoa' ],
			[ 'code' => 'AD', 'name' => 'Andorra' ],
			[ 'code' => 'AO', 'name' => 'Angola' ],
			[ 'code' => 'AI', 'name' => 'Anguilla' ],
			[ 'code' => 'AQ', 'name' => 'Antarctica' ],
			[ 'code' => 'AG', 'name' => 'Antigua and Barbuda' ],
			[ 'code' => 'AR', 'name' => 'Argentina' ],
			[ 'code' => 'AM', 'name' => 'Armenia' ],
			[ 'code' => 'AW', 'name' => 'Aruba' ],
			[ 'code' => 'AC', 'name' => 'Ascension Island' ],
			[ 'code' => 'AU', 'name' => 'Australia' ],
			[ 'code' => 'AT', 'name' => 'Austria' ],
			[ 'code' => 'AZ', 'name' => 'Azerbaijan' ],
			[ 'code' => 'BS', 'name' => 'Bahamas' ],
			[ 'code' => 'BH', 'name' => 'Bahrain' ],
			[ 'code' => 'BD', 'name' => 'Bangladesh' ],
			[ 'code' => 'BB', 'name' => 'Barbados' ],
			[ 'code' => 'BY', 'name' => 'Belarus' ],
			[ 'code' => 'BE', 'name' => 'Belgium' ],
			[ 'code' => 'BZ', 'name' => 'Belize' ],
			[ 'code' => 'BJ', 'name' => 'Benin' ],
			[ 'code' => 'BM', 'name' => 'Bermuda' ],
			[ 'code' => 'BT', 'name' => 'Bhutan' ],
			[ 'code' => 'BO', 'name' => 'Bolivia' ],
			[ 'code' => 'BA', 'name' => 'Bosnia and Herzegovina' ],
			[ 'code' => 'BW', 'name' => 'Botswana' ],
			[ 'code' => 'BR', 'name' => 'Brazil' ],
			[ 'code' => 'IO', 'name' => 'British Indian Ocean Territory' ],
			[ 'code' => 'VG', 'name' => 'British Virgin Islands' ],
			[ 'code' => 'BN', 'name' => 'Brunei' ],
			[ 'code' => 'BG', 'name' => 'Bulgaria' ],
			[ 'code' => 'BF', 'name' => 'Burkina Faso' ],
			[ 'code' => 'BI', 'name' => 'Burundi' ],
			[ 'code' => 'KH', 'name' => 'Cambodia' ],
			[ 'code' => 'CM', 'name' => 'Cameroon' ],
			[ 'code' => 'CA', 'name' => 'Canada' ],
			[ 'code' => 'IC', 'name' => 'Canary Islands' ],
			[ 'code' => 'CV', 'name' => 'Cape Verde' ],
			[ 'code' => 'BQ', 'name' => 'Caribbean Netherlands' ],
			[ 'code' => 'KY', 'name' => 'Cayman Islands' ],
			[ 'code' => 'CF', 'name' => 'Central African Republic' ],
			[ 'code' => 'EA', 'name' => 'Ceuta and Melilla' ],
			[ 'code' => 'TD', 'name' => 'Chad' ],
			[ 'code' => 'CL', 'name' => 'Chile' ],
			[ 'code' => 'CN', 'name' => 'China' ],
			[ 'code' => 'CX', 'name' => 'Christmas Island' ],
			[ 'code' => 'CC', 'name' => 'Cocos (Keeling) Islands' ],
			[ 'code' => 'CO', 'name' => 'Colombia' ],
			[ 'code' => 'KM', 'name' => 'Comoros' ],
			[ 'code' => 'CG', 'name' => 'Congo - Brazzaville' ],
			[ 'code' => 'CD', 'name' => 'Congo - Kinshasa' ],
			[ 'code' => 'CK', 'name' => 'Cook Islands' ],
			[ 'code' => 'CR', 'name' => 'Costa Rica' ],
			[ 'code' => 'CI', 'name' => 'Côte d’Ivoire' ],
			[ 'code' => 'HR', 'name' => 'Croatia' ],
			[ 'code' => 'CU', 'name' => 'Cuba' ],
			[ 'code' => 'CW', 'name' => 'Curaçao' ],
			[ 'code' => 'CY', 'name' => 'Cyprus' ],
			[ 'code' => 'CZ', 'name' => 'Czech Republic' ],
			[ 'code' => 'DK', 'name' => 'Denmark' ],
			[ 'code' => 'DG', 'name' => 'Diego Garcia' ],
			[ 'code' => 'DJ', 'name' => 'Djibouti' ],
			[ 'code' => 'DM', 'name' => 'Dominica' ],
			[ 'code' => 'DO', 'name' => 'Dominican Republic' ],
			[ 'code' => 'EC', 'name' => 'Ecuador' ],
			[ 'code' => 'EG', 'name' => 'Egypt' ],
			[ 'code' => 'SV', 'name' => 'El Salvador' ],
			[ 'code' => 'GQ', 'name' => 'Equatorial Guinea' ],
			[ 'code' => 'ER', 'name' => 'Eritrea' ],
			[ 'code' => 'EE', 'name' => 'Estonia' ],
			[ 'code' => 'ET', 'name' => 'Ethiopia' ],
			[ 'code' => 'FK', 'name' => 'Falkland Islands' ],
			[ 'code' => 'FO', 'name' => 'Faroe Islands' ],
			[ 'code' => 'FJ', 'name' => 'Fiji' ],
			[ 'code' => 'FI', 'name' => 'Finland' ],
			[ 'code' => 'FR', 'name' => 'France' ],
			[ 'code' => 'GF', 'name' => 'French Guiana' ],
			[ 'code' => 'PF', 'name' => 'French Polynesia' ],
			[ 'code' => 'TF', 'name' => 'French Southern Territories' ],
			[ 'code' => 'GA', 'name' => 'Gabon' ],
			[ 'code' => 'GM', 'name' => 'Gambia' ],
			[ 'code' => 'GE', 'name' => 'Georgia' ],
			[ 'code' => 'DE', 'name' => 'Germany' ],
			[ 'code' => 'GH', 'name' => 'Ghana' ],
			[ 'code' => 'GI', 'name' => 'Gibraltar' ],
			[ 'code' => 'GR', 'name' => 'Greece' ],
			[ 'code' => 'GL', 'name' => 'Greenland' ],
			[ 'code' => 'GD', 'name' => 'Grenada' ],
			[ 'code' => 'GP', 'name' => 'Guadeloupe' ],
			[ 'code' => 'GU', 'name' => 'Guam' ],
			[ 'code' => 'GT', 'name' => 'Guatemala' ],
			[ 'code' => 'GG', 'name' => 'Guernsey' ],
			[ 'code' => 'GN', 'name' => 'Guinea' ],
			[ 'code' => 'GW', 'name' => 'Guinea-Bissau' ],
			[ 'code' => 'GY', 'name' => 'Guyana' ],
			[ 'code' => 'HT', 'name' => 'Haiti' ],
			[ 'code' => 'HN', 'name' => 'Honduras' ],
			[ 'code' => 'HK', 'name' => 'Hong Kong SAR China' ],
			[ 'code' => 'HU', 'name' => 'Hungary' ],
			[ 'code' => 'IS', 'name' => 'Iceland' ],
			[ 'code' => 'IN', 'name' => 'India' ],
			[ 'code' => 'ID', 'name' => 'Indonesia' ],
			[ 'code' => 'IR', 'name' => 'Iran' ],
			[ 'code' => 'IQ', 'name' => 'Iraq' ],
			[ 'code' => 'IE', 'name' => 'Ireland' ],
			[ 'code' => 'IM', 'name' => 'Isle of Man' ],
			[ 'code' => 'IL', 'name' => 'Israel' ],
			[ 'code' => 'IT', 'name' => 'Italy' ],
			[ 'code' => 'JM', 'name' => 'Jamaica' ],
			[ 'code' => 'JP', 'name' => 'Japan' ],
			[ 'code' => 'JE', 'name' => 'Jersey' ],
			[ 'code' => 'JO', 'name' => 'Jordan' ],
			[ 'code' => 'KZ', 'name' => 'Kazakhstan' ],
			[ 'code' => 'KE', 'name' => 'Kenya' ],
			[ 'code' => 'KI', 'name' => 'Kiribati' ],
			[ 'code' => 'XK', 'name' => 'Kosovo' ],
			[ 'code' => 'KW', 'name' => 'Kuwait' ],
			[ 'code' => 'KG', 'name' => 'Kyrgyzstan' ],
			[ 'code' => 'LA', 'name' => 'Laos' ],
			[ 'code' => 'LV', 'name' => 'Latvia' ],
			[ 'code' => 'LB', 'name' => 'Lebanon' ],
			[ 'code' => 'LS', 'name' => 'Lesotho' ],
			[ 'code' => 'LR', 'name' => 'Liberia' ],
			[ 'code' => 'LY', 'name' => 'Libya' ],
			[ 'code' => 'LI', 'name' => 'Liechtenstein' ],
			[ 'code' => 'LT', 'name' => 'Lithuania' ],
			[ 'code' => 'LU', 'name' => 'Luxembourg' ],
			[ 'code' => 'MO', 'name' => 'Macau SAR China' ],
			[ 'code' => 'MK', 'name' => 'Macedonia' ],
			[ 'code' => 'MG', 'name' => 'Madagascar' ],
			[ 'code' => 'MW', 'name' => 'Malawi' ],
			[ 'code' => 'MY', 'name' => 'Malaysia' ],
			[ 'code' => 'MV', 'name' => 'Maldives' ],
			[ 'code' => 'ML', 'name' => 'Mali' ],
			[ 'code' => 'MT', 'name' => 'Malta' ],
			[ 'code' => 'MH', 'name' => 'Marshall Islands' ],
			[ 'code' => 'MQ', 'name' => 'Martinique' ],
			[ 'code' => 'MR', 'name' => 'Mauritania' ],
			[ 'code' => 'MU', 'name' => 'Mauritius' ],
			[ 'code' => 'YT', 'name' => 'Mayotte' ],
			[ 'code' => 'MX', 'name' => 'Mexico' ],
			[ 'code' => 'FM', 'name' => 'Micronesia' ],
			[ 'code' => 'MD', 'name' => 'Moldova' ],
			[ 'code' => 'MC', 'name' => 'Monaco' ],
			[ 'code' => 'MN', 'name' => 'Mongolia' ],
			[ 'code' => 'ME', 'name' => 'Montenegro' ],
			[ 'code' => 'MS', 'name' => 'Montserrat' ],
			[ 'code' => 'MA', 'name' => 'Morocco' ],
			[ 'code' => 'MZ', 'name' => 'Mozambique' ],
			[ 'code' => 'MM', 'name' => 'Myanmar (Burma)' ],
			[ 'code' => 'NA', 'name' => 'Namibia' ],
			[ 'code' => 'NR', 'name' => 'Nauru' ],
			[ 'code' => 'NP', 'name' => 'Nepal' ],
			[ 'code' => 'NL', 'name' => 'Netherlands' ],
			[ 'code' => 'NC', 'name' => 'New Caledonia' ],
			[ 'code' => 'NZ', 'name' => 'New Zealand' ],
			[ 'code' => 'NI', 'name' => 'Nicaragua' ],
			[ 'code' => 'NE', 'name' => 'Niger' ],
			[ 'code' => 'NG', 'name' => 'Nigeria' ],
			[ 'code' => 'NU', 'name' => 'Niue' ],
			[ 'code' => 'NF', 'name' => 'Norfolk Island' ],
			[ 'code' => 'KP', 'name' => 'North Korea' ],
			[ 'code' => 'MP', 'name' => 'Northern Mariana Islands' ],
			[ 'code' => 'NO', 'name' => 'Norway' ],
			[ 'code' => 'OM', 'name' => 'Oman' ],
			[ 'code' => 'PK', 'name' => 'Pakistan' ],
			[ 'code' => 'PW', 'name' => 'Palau' ],
			[ 'code' => 'PS', 'name' => 'Palestinian Territories' ],
			[ 'code' => 'PA', 'name' => 'Panama' ],
			[ 'code' => 'PG', 'name' => 'Papua New Guinea' ],
			[ 'code' => 'PY', 'name' => 'Paraguay' ],
			[ 'code' => 'PE', 'name' => 'Peru' ],
			[ 'code' => 'PH', 'name' => 'Philippines' ],
			[ 'code' => 'PN', 'name' => 'Pitcairn Islands' ],
			[ 'code' => 'PL', 'name' => 'Poland' ],
			[ 'code' => 'PT', 'name' => 'Portugal' ],
			[ 'code' => 'PR', 'name' => 'Puerto Rico' ],
			[ 'code' => 'QA', 'name' => 'Qatar' ],
			[ 'code' => 'RE', 'name' => 'Réunion' ],
			[ 'code' => 'RO', 'name' => 'Romania' ],
			[ 'code' => 'RU', 'name' => 'Russia' ],
			[ 'code' => 'RW', 'name' => 'Rwanda' ],
			[ 'code' => 'BL', 'name' => 'Saint Barthélemy' ],
			[ 'code' => 'SH', 'name' => 'Saint Helena' ],
			[ 'code' => 'KN', 'name' => 'Saint Kitts and Nevis' ],
			[ 'code' => 'LC', 'name' => 'Saint Lucia' ],
			[ 'code' => 'MF', 'name' => 'Saint Martin' ],
			[ 'code' => 'PM', 'name' => 'Saint Pierre and Miquelon' ],
			[ 'code' => 'WS', 'name' => 'Samoa' ],
			[ 'code' => 'SM', 'name' => 'San Marino' ],
			[ 'code' => 'ST', 'name' => 'São Tomé and Príncipe' ],
			[ 'code' => 'SA', 'name' => 'Saudi Arabia' ],
			[ 'code' => 'SN', 'name' => 'Senegal' ],
			[ 'code' => 'RS', 'name' => 'Serbia' ],
			[ 'code' => 'SC', 'name' => 'Seychelles' ],
			[ 'code' => 'SL', 'name' => 'Sierra Leone' ],
			[ 'code' => 'SG', 'name' => 'Singapore' ],
			[ 'code' => 'SX', 'name' => 'Sint Maarten' ],
			[ 'code' => 'SK', 'name' => 'Slovakia' ],
			[ 'code' => 'SI', 'name' => 'Slovenia' ],
			[ 'code' => 'SB', 'name' => 'Solomon Islands' ],
			[ 'code' => 'SO', 'name' => 'Somalia' ],
			[ 'code' => 'ZA', 'name' => 'South Africa' ],
			[ 'code' => 'GS', 'name' => 'South Georgia & South Sandwich Islands' ],
			[ 'code' => 'KR', 'name' => 'South Korea' ],
			[ 'code' => 'SS', 'name' => 'South Sudan' ],
			[ 'code' => 'ES', 'name' => 'Spain' ],
			[ 'code' => 'LK', 'name' => 'Sri Lanka' ],
			[ 'code' => 'VC', 'name' => 'St. Vincent & Grenadines' ],
			[ 'code' => 'SD', 'name' => 'Sudan' ],
			[ 'code' => 'SR', 'name' => 'Suriname' ],
			[ 'code' => 'SJ', 'name' => 'Svalbard and Jan Mayen' ],
			[ 'code' => 'SZ', 'name' => 'Swaziland' ],
			[ 'code' => 'SE', 'name' => 'Sweden' ],
			[ 'code' => 'CH', 'name' => 'Switzerland' ],
			[ 'code' => 'SY', 'name' => 'Syria' ],
			[ 'code' => 'TW', 'name' => 'Taiwan' ],
			[ 'code' => 'TJ', 'name' => 'Tajikistan' ],
			[ 'code' => 'TZ', 'name' => 'Tanzania' ],
			[ 'code' => 'TH', 'name' => 'Thailand' ],
			[ 'code' => 'TL', 'name' => 'Timor-Leste' ],
			[ 'code' => 'TG', 'name' => 'Togo' ],
			[ 'code' => 'TK', 'name' => 'Tokelau' ],
			[ 'code' => 'TO', 'name' => 'Tonga' ],
			[ 'code' => 'TT', 'name' => 'Trinidad and Tobago' ],
			[ 'code' => 'TA', 'name' => 'Tristan da Cunha' ],
			[ 'code' => 'TN', 'name' => 'Tunisia' ],
			[ 'code' => 'TR', 'name' => 'Turkey' ],
			[ 'code' => 'TM', 'name' => 'Turkmenistan' ],
			[ 'code' => 'TC', 'name' => 'Turks and Caicos Islands' ],
			[ 'code' => 'TV', 'name' => 'Tuvalu' ],
			[ 'code' => 'UM', 'name' => 'U.S. Outlying Islands' ],
			[ 'code' => 'VI', 'name' => 'U.S. Virgin Islands' ],
			[ 'code' => 'UG', 'name' => 'Uganda' ],
			[ 'code' => 'UA', 'name' => 'Ukraine' ],
			[ 'code' => 'AE', 'name' => 'United Arab Emirates' ],
			[ 'code' => 'GB', 'name' => 'United Kingdom' ],
			[ 'code' => 'US', 'name' => 'United States' ],
			[ 'code' => 'UY', 'name' => 'Uruguay' ],
			[ 'code' => 'UZ', 'name' => 'Uzbekistan' ],
			[ 'code' => 'VU', 'name' => 'Vanuatu' ],
			[ 'code' => 'VA', 'name' => 'Vatican City' ],
			[ 'code' => 'VE', 'name' => 'Venezuela' ],
			[ 'code' => 'VN', 'name' => 'Vietnam' ],
			[ 'code' => 'WF', 'name' => 'Wallis and Futuna' ],
			[ 'code' => 'EH', 'name' => 'Western Sahara' ],
			[ 'code' => 'YE', 'name' => 'Yemen' ],
			[ 'code' => 'ZM', 'name' => 'Zambia' ],
			[ 'code' => 'ZW', 'name' => 'Zimbabwe' ],
		];
	}
}

register_activation_hook( __FILE__, [ 'Pluginname', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Pluginname', 'deactivate' ] );
register_uninstall_hook( __FILE__, [ 'Pluginname', 'uninstall' ] );

$pluginname = new Pluginname();
