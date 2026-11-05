<?php
/**
 * Test bootstrap: provides WordPress mock environment for CLI testing.
 *
 * @package AB_Form_Entries_Manager
 */

// phpcs:ignoreFile

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', dirname( dirname( __DIR__ ) ) );
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

// Global mock state.
global $mock_posts, $mock_postmeta, $mock_options, $mock_transients, $mock_actions, $mock_did_actions, $mock_filters, $current_user_caps, $wpdb;

$mock_posts         = array();
$mock_postmeta      = array();
$mock_options       = array();
$mock_transients    = array();
$mock_actions       = array();
$mock_did_actions   = array();
$mock_filters       = array();
$current_user_caps  = array( 'manage_options' => true );

class Mock_WPDB {
	public $prefix = 'wp_';
	public $posts = 'wp_posts';
	public $postmeta = 'wp_postmeta';

	public function prepare( $query, ...$args ) {
		if ( empty( $args ) ) {
			return $query;
		}
		if ( is_array( $args[0] ) && 1 === count( $args ) ) {
			$args = $args[0];
		}
		foreach ( $args as $arg ) {
			if ( is_numeric( $arg ) ) {
				$query = preg_replace( '/%[df]/', (string) $arg, $query, 1 );
			} else {
				$escaped = addslashes( (string) $arg );
				$query = preg_replace( '/%s/', "'" . $escaped . "'", $query, 1 );
			}
		}
		return $query;
	}

	public function esc_like( $text ) {
		return addcslashes( $text, '_%\\' );
	}

	public function get_var( $query ) {
		global $mock_posts, $mock_postmeta;

		// Mock duplicate hash check.
		if ( false !== strpos( $query, '_abfem_hash' ) && preg_match( "/_abfem_hash'\\s+AND\\s+pm\\.meta_value = '([^']+)'/", $query, $matches ) ) {
			$hash = $matches[1];
			// Check threshold timestamp.
			$threshold = '';
			if ( preg_match( "/p\\.post_date_gmt >= '([^']+)'/", $query, $t_matches ) ) {
				$threshold = $t_matches[1];
			}
			foreach ( $mock_postmeta as $post_id => $metas ) {
				if ( isset( $metas['_abfem_hash'] ) && $metas['_abfem_hash'] === $hash ) {
					$post = $mock_posts[ $post_id ] ?? null;
					if ( $post && ( empty( $threshold ) || $post->post_date_gmt >= $threshold ) ) {
						return $post_id;
					}
				}
			}
			return null;
		}

		// Mock total count.
		if ( false !== strpos( $query, 'SELECT COUNT(ID) FROM wp_posts' ) ) {
			$count = 0;
			foreach ( $mock_posts as $p ) {
				if ( 'abfem_entry' === $p->post_type && 'publish' === $p->post_status ) {
					$count++;
				}
			}
			return $count;
		}

		// Mock unread count.
		if ( false !== strpos( $query, "_abfem_is_read' AND pm.meta_value = '0'" ) ) {
			$count = 0;
			foreach ( $mock_posts as $id => $p ) {
				if ( 'abfem_entry' === $p->post_type && 'publish' === $p->post_status ) {
					$is_read = $mock_postmeta[ $id ]['_abfem_is_read'] ?? 0;
					if ( 0 == $is_read ) {
						$count++;
					}
				}
			}
			return $count;
		}

		// Mock count by source plugin.
		if ( false !== strpos( $query, "_abfem_source_plugin'" ) && preg_match( "/pm\\.meta_value = '([^']+)'/", $query, $p_matches ) ) {
			$plugin = $p_matches[1];
			$count = 0;
			foreach ( $mock_posts as $id => $p ) {
				if ( 'abfem_entry' === $p->post_type && 'publish' === $p->post_status ) {
					if ( isset( $mock_postmeta[ $id ]['_abfem_source_plugin'] ) && $mock_postmeta[ $id ]['_abfem_source_plugin'] === $plugin ) {
						$count++;
					}
				}
			}
			return $count;
		}

		return 0;
	}

	public function get_results( $query, $output = OBJECT ) {
		global $mock_posts, $mock_postmeta;

		// Mock forms summary query.
		if ( false !== strpos( $query, 'GROUP BY pm_form.meta_value' ) ) {
			$grouped = array();
			foreach ( $mock_posts as $id => $p ) {
				if ( 'abfem_entry' !== $p->post_type || 'publish' !== $p->post_status ) {
					continue;
				}
				$f_id   = $mock_postmeta[ $id ]['_abfem_form_id'] ?? '';
				$plugin = $mock_postmeta[ $id ]['_abfem_source_plugin'] ?? '';
				$name   = $mock_postmeta[ $id ]['_abfem_form_name'] ?? '';
				$date   = $p->post_date;

				$key = $plugin . '_' . $f_id;
				if ( ! isset( $grouped[ $key ] ) ) {
					$grouped[ $key ] = array(
						'form_id'            => $f_id,
						'source_plugin'      => $plugin,
						'form_name'          => $name,
						'submission_count'   => 0,
						'latest_submission'  => $date,
					);
				}
				$grouped[ $key ]['submission_count']++;
				if ( $date > $grouped[ $key ]['latest_submission'] ) {
					$grouped[ $key ]['latest_submission'] = $date;
				}
			}
			return array_values( $grouped );
		}

		return array();
	}

	public function get_col( $query ) {
		global $mock_posts, $mock_postmeta;
		$ids = array();

		// Check for email filter.
		if ( false !== strpos( $query, '_abfem_submitter_email' ) && preg_match( "/pm\\.meta_value = '([^']+)'/", $query, $m ) ) {
			$email = $m[1];
			foreach ( $mock_posts as $id => $p ) {
				if ( 'abfem_entry' === $p->post_type && 'publish' === $p->post_status ) {
					if ( isset( $mock_postmeta[ $id ]['_abfem_submitter_email'] ) && $mock_postmeta[ $id ]['_abfem_submitter_email'] === $email ) {
						$ids[] = $id;
					}
				}
			}
			return $ids;
		}

		foreach ( $mock_posts as $id => $p ) {
			if ( 'abfem_entry' === $p->post_type && 'publish' === $p->post_status ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}
}

$wpdb = new Mock_WPDB();

// Mock WP Core Functions.
if ( ! function_exists( 'wp_insert_post' ) ) {
	function wp_insert_post( $post_arr, $wp_error = false ) {
		global $mock_posts;
		static $auto_id = 100;
		$auto_id++;

		$post = (object) array(
			'ID'            => $auto_id,
			'post_title'    => $post_arr['post_title'] ?? '',
			'post_type'     => $post_arr['post_type'] ?? 'post',
			'post_status'   => $post_arr['post_status'] ?? 'draft',
			'post_date'     => gmdate( 'Y-m-d H:i:s' ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s' ),
		);

		$mock_posts[ $auto_id ] = $post;
		return $auto_id;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post_id ) {
		global $mock_posts;
		return $mock_posts[ $post_id ] ?? null;
	}
}

if ( ! function_exists( 'wp_delete_post' ) ) {
	function wp_delete_post( $post_id, $force = false ) {
		global $mock_posts, $mock_postmeta;
		if ( isset( $mock_posts[ $post_id ] ) ) {
			unset( $mock_posts[ $post_id ] );
			unset( $mock_postmeta[ $post_id ] );
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $meta_key, $meta_value ) {
		global $mock_postmeta;
		if ( ! isset( $mock_postmeta[ $post_id ] ) ) {
			$mock_postmeta[ $post_id ] = array();
		}
		$mock_postmeta[ $post_id ][ $meta_key ] = $meta_value;
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $meta_key = '', $single = false ) {
		global $mock_postmeta;
		if ( ! isset( $mock_postmeta[ $post_id ] ) ) {
			return $single ? '' : array();
		}
		if ( empty( $meta_key ) ) {
			return $mock_postmeta[ $post_id ];
		}
		if ( isset( $mock_postmeta[ $post_id ][ $meta_key ] ) ) {
			return $mock_postmeta[ $post_id ][ $meta_key ];
		}
		return $single ? '' : array();
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $mock_options;
		return $mock_options[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ) {
		global $mock_options;
		$mock_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		global $mock_options;
		unset( $mock_options[ $option ] );
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		global $mock_transients;
		return $mock_transients[ $transient ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		global $mock_transients;
		$mock_transients[ $transient ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		global $mock_transients;
		unset( $mock_transients[ $transient ] );
		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		global $mock_actions;
		$mock_actions[ $hook ][] = $callback;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		global $mock_actions, $mock_did_actions;
		$mock_did_actions[ $hook ] = ( $mock_did_actions[ $hook ] ?? 0 ) + 1;
		if ( ! empty( $mock_actions[ $hook ] ) ) {
			foreach ( $mock_actions[ $hook ] as $cb ) {
				call_user_func_array( $cb, $args );
			}
		}
	}
}

if ( ! function_exists( 'did_action' ) ) {
	function did_action( $hook ) {
		global $mock_did_actions;
		return $mock_did_actions[ $hook ] ?? 0;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		global $mock_filters;
		$mock_filters[ $hook ][] = $callback;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value, ...$args ) {
		global $mock_filters;
		if ( ! empty( $mock_filters[ $hook ] ) ) {
			foreach ( $mock_filters[ $hook ] as $cb ) {
				$value = call_user_func_array( $cb, array_merge( array( $value ), $args ) );
			}
		}
		return $value;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		global $current_user_caps;
		return ! empty( $current_user_caps[ $capability ] );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		if ( 'timestamp' === $type ) {
			return time();
		}
		if ( 'mysql' === $type ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		return gmdate( $type );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return is_a( $thing, 'WP_Error' );
	}
}

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) {
		$this->message = $message;
	}
	public function get_error_message() {
		return $this->message;
	}
}

if ( ! function_exists( 'register_post_type' ) ) {
	function register_post_type( $post_type, $args = array() ) {}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$r = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$r =& $args;
		} else {
			wp_parse_str( $args, $r );
		}
		return array_merge( $defaults, $r );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text, $remove_breaks = false ) {
		$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $text );
		$text = preg_replace( '@<[^>]*?>@si', '', $text );
		if ( $remove_breaks ) {
			$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
		}
		return trim( $text );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return wp_strip_all_tags( (string) $str );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return wp_strip_all_tags( (string) $str );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( trim( (string) $email ), FILTER_VALIDATE_EMAIL ) ? trim( (string) $email ) : '';
	}
}

if ( ! function_exists( 'is_email' ) ) {
	function is_email( $email ) {
		return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return $url;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ) {
		return abs( intval( $maybeint ) );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number, $decimals = 0 ) {
		return number_format( (float) $number, $decimals );
	}
}

if ( ! function_exists( 'human_time_diff' ) ) {
	function human_time_diff( $from, $to = 0 ) {
		return '1 hour';
	}
}

if ( ! function_exists( 'date_i18n' ) ) {
	function date_i18n( $format, $timestamp_with_offset = false, $gmt = false ) {
		return gmdate( 'Y-m-d H:i:s', $timestamp_with_offset ?: time() );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return stripslashes_deep( $value );
	}
}

if ( ! function_exists( 'stripslashes_deep' ) ) {
	function stripslashes_deep( $value ) {
		return is_array( $value ) ? array_map( 'stripslashes_deep', $value ) : stripslashes( (string) $value );
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $function ) {}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $file, $function ) {}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return true;
	}
}

if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		return 1 === (int) $number ? $single : $plural;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( ...$args ) {
		if ( is_array( $args[0] ) ) {
			$params = $args[0];
			$url    = $args[1] ?? '';
		} else {
			$params = array( $args[0] => $args[1] );
			$url    = $args[2] ?? '';
		}
		$sep = false !== strpos( $url, '?' ) ? '&' : '?';
		return $url . $sep . http_build_query( $params );
	}
}

if ( ! function_exists( 'wp_nonce_url' ) ) {
	function wp_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) {
		return add_query_arg( $name, 'mock_nonce_' . $action, $actionurl );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( $text );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo esc_attr( $text );
	}
}

if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $text ) {
		return addcslashes( (string) $text, "\\'\"&\n\r<>" );
	}
}

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
	function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
		return true;
	}
}

// Load plugin classes.
require_once dirname( __DIR__ ) . '/ab-form-entries-manager.php';

// Trigger lifecycle hooks for mock test environment.
do_action( 'plugins_loaded' );
do_action( 'init' );
