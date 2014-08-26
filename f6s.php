<?php
/*
Plugin Name: f6s Wordpress Plugin
Plugin URI: http://www.f6s.com
Description: Integrate f6s data into your WordPress Site
Version: 0.5.2
Author: f6s.com
License: GPL2
*/

$f6s_shortcodes = array(
	'deal' => array( 'program', 'area' ), 
	'mentor' => array( 'mentoring' ),
	'team' => array( 'program' )
);
$f6s_data = null;
$f6s_transient_pref = 'f6s_cache_';
$f6s_transient_time = 60 * 60; // 1 hour
$f6s_plugin_url = WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) );

function f6s_header_script() {
	global $f6s_plugin_url;
	echo '<script type="text/javascript">var f6s_plugin_dir = "' . $f6s_plugin_url . '";</script>';
}

function f6s_add_tinymce_js( $plugin_array ) {
	global $f6s_plugin_url;
	$plugin_array['f6s'] =  $f6s_plugin_url . '/editor_plugin.js';
	return $plugin_array;
}

function f6s_add_tinymce_css( $in ) {
	global $f6s_plugin_url;
	$in['content_css'] .= ',' . $f6s_plugin_url . '/editor-style.css';
	return $in;
}

function f6s_tinymce_plugin() {
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		return;

	// Add only in Rich Editor mode
	if ( 'true' == get_user_option('rich_editing') ) {
		add_action( 'admin_head', 'f6s_header_script' );
		add_filter( 'mce_external_plugins', 'f6s_add_tinymce_js' );
		add_filter( 'tiny_mce_before_init', 'f6s_add_tinymce_css' );
	}
}

function f6s_santize_api_key( $api_key ) {
	return preg_replace( '/[^a-zA-Z0-9_-]/', '', $api_key );
}

function f6s_reset_cache( $value ) {
	global $f6s_shortcodes, $f6s_transient_pref, $wpdb;

	if( 1 == (int) $value ) {
		foreach( array_keys( $f6s_shortcodes ) as $type ) {
			// Identify all used transients
			$transient_pref = '_transient_';
			$f6s_transient_form = $transient_pref . $f6s_transient_pref . $type . '_%';
			$query = $wpdb->prepare( "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s';", $f6s_transient_form );
			$f6s_transient_names = $wpdb->get_col( $query );
			foreach( $f6s_transient_names as $f6s_transient_name ) {
				$f6s_transient_name = substr( $f6s_transient_name, strlen( $transient_pref ) );
				delete_transient( $f6s_transient_name );
			}
		}
		add_settings_error( 'f6s_reset_cache', null, 'Success! The cache has been reset. Refresh your site to see the latest data.', 'updated' );
	}

	return false;
}

function f6s_register_options() {
	register_setting( 'f6s-settings-group', 'f6s_api_key',  'f6s_santize_api_key' );
	register_setting( 'f6s-settings-group', 'f6s_reset_cache',  'f6s_reset_cache' );
}

function f6s_add_options_menu() {
	add_options_page( 'f6s Plugin Options', 'f6s Plugin', 'manage_options', 'f6s-plugin-settings', 'f6s_options_menu' );
}

function f6s_options_menu() {
	global $f6s_transient_time;
	
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
?>
<div class="wrap">
<h2>f6s Plugin Settings</h2>
<script type="text/javascript">
function reset_cache() {
	var oform = document.forms['f6s-options'];
	if ( 'undefined' != typeof( oform ) ) {
		var elem = oform.f6s_reset_cache;
		if ( 'undefined' != typeof( elem ) ) {
			elem.value = 1;
			oform.submit();
		}
	}
}
</script>
<form name="f6s-options" method="post" action="options.php">
	<?php settings_fields( 'f6s-settings-group' ); ?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">API key</th>
			<td><input type="text" name="f6s_api_key" value="<?php echo esc_attr( get_option( 'f6s_api_key' ) ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Cache refresh interval</th>
			<td><?php echo $f6s_transient_time / ( 60 * 60 ); ?> hour default<br /><a href="#" onclick="reset_cache()">Pull All Latest</a></td>
			<input type="hidden" name="f6s_reset_cache" value="0" />
		</tr>
	</table>
	<?php submit_button( null, 'primary', 'btnsubmit' ); ?>
</form>
</div>
<?php
}

function shortcode_f6s_data( $atts, $content = null ) {
	if ( is_null( $content ) ) return '';
	return do_shortcode( $content );
}

function f6s_conv_currency( $currency ) {
	return str_replace(
		array( 'USD', 'EUR', 'GBP' ), 
		array( '$', '€', '£' ), 
		$currency
	);
}

function f6s_get_data( $type, $param, $args = array() ) {
	global $f6s_shortcodes, $f6s_transient_pref, $f6s_transient_time;
	
	if ( ! in_array( $type, array_keys( $f6s_shortcodes ) ) ) return '';
	
	$f6s_transient_name = $f6s_transient_pref . $type . '_' . $param;
	$raw_data = get_transient( $f6s_transient_name );
	if( false == $raw_data ) {
		// No cache
		$api_key = get_option( 'f6s_api_key' );
		if( false === $api_key || '' == trim( $api_key ) ) return false;

		switch( $type ) {
			case 'deal':
				$api_url = 'http://www.f6s.com/f6sapi/deals/browse?api_key=' . $api_key . '&program=' . $param;
				break;
			case 'mentor':
				$api_url = 'http://www.f6s.com/f6sapi/' . $param . '/mentors/browse?api_key=' . $api_key;
				break;
			case 'team':
				$api_url = 'http://www.f6s.com/f6sapi/' . $param . '/teams/browse?api_key=' . $api_key;
				break;
		}
        if ($args) {
            $api_url .= '&'.http_build_query($args);
        }
		$raw_data = file_get_contents( $api_url );
	}

	if ( '' != trim( $raw_data ) ) {
		if ( b"\xEF\xBB\xBF" == substr( $raw_data, 0, 3 ) ) $raw_data = substr( $raw_data, 3 );
		$data = json_decode( $raw_data );
		if ( ! is_null( $data ) ) {
			set_transient( $f6s_transient_name, $raw_data, $f6s_transient_time );

			if ( is_object( $data ) ) {
				if ( property_exists( $data, 'error' ) && property_exists( $data->error, 'error_code' ) ) {
					// Check for errors
					if( 0 != $data->error->error_code )
						return '';
				}

				// QUICKFIX: Deal value
				if ( property_exists( $data, 'deals' ) && is_array( $data->deals ) ) {
					foreach ( $data->deals as $index => $deal ) {
						if( property_exists( $deal, 'value' ) &&  property_exists( $deal, 'currency' ) ) {
							$data->deals[ $index ]->value = f6s_conv_currency( $deal->currency ) . ( $deal->value + 0 );
						}
					}
				}
				// QUICKFIX: Mentors mentor_of
				if ( property_exists( $data, 'mentors' ) && is_array( $data->mentors ) ) {
					foreach ( $data->mentors as $index => $mentors ) {
						if ( property_exists( $mentors, 'mentor_of' ) ) {
							$data->mentors[$index]->mentored_orgs = $mentors->mentor_of;
							unset( $data->mentors[$index]->mentor_of );
						}
					}
				}
				
			}

			return $data;
		}
	}
	return false;
}

function get_display_property( $data = null, $display = null ) {
	if ( is_null( $data ) || is_null( $display ) ) return '';

	if ( false !== strpos( $display, '.' ) ) {
		// Composed nodes
		$display = explode( '.', $display );
		foreach( $display as $property ) {
			if( property_exists( $data, $property ) )
				$data = $data->$property;
		}
		return $data;
	}
	else {
		// Single property
		if( property_exists( $data, $display ) )
			return $data->$display;
	}

	return '';
}

function shortcode_item_list( $atts, $content = null, $tag, $data = null ) {
	global $f6s_data, $f6s_shortcodes;

	if ( is_null( $content ) ) return '';
	
	$tag = str_replace( '-list', '', $tag );
	if ( ! in_array( $tag, array_keys( $f6s_shortcodes ) ) ) return '';

	if ( is_null( $data ) ) {
		extract( shortcode_atts( array(
			'program' => null,
			'area' => null,
			'mentoring' => null
		), $atts ) );
	
		$param = $f6s_shortcodes[ $tag ][0];
		if( ! isset( $$param ) || ! is_numeric( $$param ) ) return '';
        
        $args = array();
        foreach ($f6s_shortcodes[ $tag ] as $key => $shortcode) {
            if ($key == 0) continue;
            
            if (isset($$shortcode) && !empty($$shortcode)) 
                $args[$shortcode] = $$shortcode;
        }

		$data = f6s_get_data( $tag, $$param, $args );
	}

	$ret = '';
	$objects = $tag . 's';
	if ( is_object( $data ) && property_exists( $data, $objects ) ) {
		if( is_array( $data->$objects ) ) {
			$index = 1;
			foreach ( $data->$objects as $object ) {
				$f6s_data[ 'current_' . $tag ] = $object;
				$f6s_data[ 'index_' . $tag ] = $index;
				$f6s_data[ 'total_' . $tag ] = count( $data->$objects );
				if ( ! isset( $f6s_data['level'] ) || end( $f6s_data['level'] ) != $tag ) {
					$f6s_data['level'][] = $tag;
				}
				$ret .= do_shortcode( $content );
				$index++;
			}
		}
		else
			return '';
	}

	foreach ( array( 'current', 'index', 'total' ) as $key ) {
		unset( $f6s_data[ $key . '_' . $tag ] );
	}
	if( isset( $f6s_data['level'] ) ) array_pop( $f6s_data['level'] );
	if ( empty( $f6s_data['level'] ) ) unset( $f6s_data['level'] );
	unset( $data );
	
	return $ret;
}

function shortcode_render_sublist( $item, $list, $content ) {
	global $f6s_shortcodes;
	
	if ( property_exists( $item, $list ) && is_array( $item->$list ) ) {
		// Property is array
		$singular = substr( $list, 0, -1 );
		$f6s_shortcodes[ $singular ] = array();
		add_shortcode( $singular, 'shortcode_item' );
		$ret = shortcode_item_list( array(), $content, $singular, $item );
		remove_shortcode( $singular );
		unset( $f6s_shortcodes[ $singular ] );
		return $ret;
	}
	else {
		return '';
	}
}

function shortcode_item( $atts, $content = null, $tag ) {
	global $f6s_data, $f6s_shortcodes;

	if ( ! in_array( $tag, array_keys( $f6s_shortcodes ) ) ) return '';

	extract( shortcode_atts( array(
		'program' => null,
		'mentoring' => null,
		'id' => null,
		'display' => null,
		'list' => null
	), $atts ) );
	
	if ( '' != trim( $content ) && ! isset( $list ) ) return '';
	if ( '' != trim( $content ) && isset( $display ) ) return '';
	
	if( isset( $f6s_data[ 'current_' . $tag ] ) ) {
		// List mode
		$item = $f6s_data['current_' . $tag ];

		if ( isset( $display ) && ! is_null( $display ) ) {
			return get_display_property( $item, $display );
		}
		elseif ( isset( $list )  && ! is_null( $list ) ) {
			return shortcode_render_sublist( $item, $list, $content );
		}
		else {
			return '';
		}
	}
	elseif ( !is_null( $id ) ) {
		// Single mode
		if ( 'query_' == substr( $id, 0, 6 ) ) {
			// From query var
			$q_var_name = substr( $id, 6 );
			if( isset( $_GET[ $q_var_name ] ) )
				$id = $_GET[ $q_var_name ];
		}

		if ( ! is_numeric( $id ) ) return '';
		$id = (int) $id;
		
		$param = $f6s_shortcodes[ $tag ][0];
		if( ! isset( $$param ) || ! is_numeric( $$param ) ) return '';
        
        $args = array();
        foreach ($f6s_shortcodes[ $tag ] as $key => $shortcode) {
            if ($key == 0) continue;
            
            if (isset($$shortcode) && !empty($$shortcode)) 
                $args[$shortcode] = $$shortcode;
        }

		$data = f6s_get_data( $tag, $$param, $args );
		$objects = $tag . 's';
		if ( is_object( $data ) && property_exists( $data, $objects ) ) {
			foreach ( $data->$objects as $item ) {
				if ( property_exists( $item, 'id' ) && $item->id == $id ) {

					if ( isset( $display ) && ! is_null( $display ) ) {
						return get_display_property( $item, $display );
					}
					elseif ( isset( $list )  && ! is_null( $list ) ) {
						return shortcode_render_sublist( $item, $list, $content );
					}
					else {
						return '';
					}
					
				}
			}
		}
		unset( $data );
	}

	return '';
}

function shortcode_list_index( $atts, $content = null, $tag ) {
	global $f6s_data;
	
	if ( is_null( $content ) ) return '';

	extract( shortcode_atts( array(
		'multiple' => null,
		'notmultiple' => null
	), $atts ) );
	
	// Validation
	if ( ! is_array( $atts ) ) return '';

	if ( ! isset( $f6s_data['level'] ) || ! is_array( $f6s_data['level'] ) ) return '';
	else $level = end( $f6s_data['level'] );

	if ( ! isset( $f6s_data[ 'index_' . $level ] ) || ! is_numeric( $f6s_data[ 'index_' . $level ] ) ) return '';
	$index = (int) $f6s_data['index_' . $level ];
	
	if ( ! isset( $f6s_data[ 'total_' . $level ] ) || ! is_numeric( $f6s_data[ 'total_' . $level ] ) ) return '';

	// Options
	if ( in_array( 'first', array_values( $atts ) ) ) {
		if ( 1 == $index ) return $content;
	}
	elseif ( in_array( 'notfirst', array_values( $atts ) ) ) {
		if ( 1 != $index ) return $content;
	}
	elseif ( in_array( 'last', array_values( $atts ) ) ) {
		if ( $index ==  (int) $f6s_data[ 'total_' . $level ] ) return $content;
	}
	elseif ( in_array( 'notlast', array_values( $atts ) ) ) {
		if ( $index !=  (int) $f6s_data[ 'total_' . $level ] ) return $content;
	}
	elseif ( in_array( 'odd', array_values( $atts ) ) ) {
		if ( 0 == $index % 2 ) return $content;
	}
	elseif ( in_array( 'even', array_values( $atts ) ) ) {
		if ( 0 != $index % 2 ) return $content;
	}
	elseif ( ! is_null( $multiple ) && is_numeric( $multiple ) ) {
		$multiple = (int) $multiple;
		if ( 0 == $index % $multiple ) return $content;
	}
	elseif ( ! is_null( $notmultiple ) && is_numeric( $notmultiple ) ) {
		$notmultiple = (int) $notmultiple;
		if ( 0 != $index % $notmultiple ) return $content;
	}
	
	return '';
}

function f6s_pre_process_shortcodes( $content ) {
	global $shortcode_tags, $f6s_shortcodes;

	$orig_shortcode_tags = $shortcode_tags;
	$shortcode_tags = array();
	
	// Shorcode filters
	foreach( array_keys( $f6s_shortcodes ) as $tag ) {
		add_shortcode( $tag . '-list', 'shortcode_item_list' );
		add_shortcode( $tag, 'shortcode_item' );
	}
	add_shortcode( 'list-index', 'shortcode_list_index' );
	add_shortcode( 'f6s-data', 'shortcode_f6s_data' );
	
	$content = do_shortcode( $content );
	
	$shortcode_tags = $orig_shortcode_tags;
	
	return $content;
}

function f6s_dummy_shortcode( $atts, $content = '' ) {
    return $content;
}

function f6s_add_dummy_shortcodes( $content = '' ) {
	global $f6s_shortcodes;

	foreach( array_keys( $f6s_shortcodes ) as $tag ) {
		add_shortcode( $tag . '-list', 'f6s_dummy_shortcode' );
		add_shortcode( $tag, 'f6s_dummy_shortcode' );
	}
	add_shortcode( 'list-index', 'f6s_dummy_shortcode' );
	add_shortcode( 'f6s-data', 'f6s_dummy_shortcode' );

    return $content;
}

// TinyMCE filters
add_action( 'init', 'f6s_tinymce_plugin' );
add_filter( 'the_content', 'f6s_pre_process_shortcodes', 7 );
add_filter( 'the_content', 'f6s_add_dummy_shortcodes', 12 );

// Admin
if ( is_admin() ) {
	add_action( 'admin_init', 'f6s_register_options' );
	add_action( 'admin_menu', 'f6s_add_options_menu' );
}
?>