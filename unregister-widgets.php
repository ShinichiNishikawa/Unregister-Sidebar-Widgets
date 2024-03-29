<?php
/*
Plugin Name: Unregister Sidebar Widgets
Plugin URI: https://github.com/ShinichiNishikawa/Unregister-Sidebar-Widgets
Description: You can choose and unregister/disable/hide widgets which you don't need, both defaults and added by plugins.
Author: Shinichi Nishikawa
Version: 0.1
Author URI: http://nskw-style.com

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html
  Copyright 2013 Shinichi Nishikawa (email : shinichi.nishikawa@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class unregister_sidebar_widgets {

public $classes = array();
public $classes_re = array();
public $classes_un = array();

function __construct() {
	add_action( 'admin_menu',   array( $this, 'menu' )       );
	add_action( 'admin_init',   array( $this, 'save' )       );
	add_action( 'widgets_init', array( $this, 'unregister' ) );
	
}

// add menu
public function menu() {
	add_theme_page(
		'Unregister Widgets',
		'Unregister Widgets',
		'activate_plugins',
		'unregister_widget',
		array( $this, 'form' )
	);
}

// get array of UNregistered widgets
// from the DB.
public function get_unregistered() {
	
	$from_db = get_option( 'unregid_classes' );
	if ( $from_db ) {
		$this->classes_un = $from_db;
	} else {
		$this->classes_un = array();
	}
	
	
}

// make associative array of registered widgets
// from $wp_registered_widgets global variable.
public function get_registered() {
	
	global $wp_registered_widgets;
	
	foreach ( $wp_registered_widgets as $rw ) {

		$obj   = $rw['callback'][0];
		$class = get_class( $obj );

		$this->classes_re[$class] = $this->class_to_namedesc( $class );

	}
	
}

// return name & desc array by given class name.
// it's possible only for registered widgets.
public function class_to_namedesc( $class ) {

	global $wp_widget_factory;
	$obj  = $wp_widget_factory->widgets[$class];

	return array(
		'name' => $obj->name,
		'desc' => $obj->widget_options['description']
	);

}


// save the key[class]=>[name=>name, desc=>desc] array
public function save() {

	if ( isset( $_POST['uw-submit'] ) && $_POST['uw-submit'] && check_admin_referer( 'uw-display-form', 'unregister_widget' ) ) {
	
		$posted = $_POST;
		$dont_save = array( 'unregister_widget', '_wp_http_referer', 'uw-submit' );
		
		foreach ( $dont_save as $dn ) {
			if ( isset( $posted[$dn] ) ) {
				unset($posted[$dn]);
			}
		}
		
		$unregid_classes = array();
		foreach ( $posted as $p ) {
			$unregid_classes[$p] = $this->class_to_namedesc($p);
		}
		
		$updated = update_option( 'unregid_classes', $unregid_classes );
	
		if ( $updated ) {
			add_action( 'admin_notices', array( $this, 'notice' ) );
		}
	
	}

}

// admin notice
public function notice() {
	?>
	<div class="updated">
		<ul>
			<li>Saved! The widgets you chose has been hided :) <a href="<?php echo admin_url( 'widgets.php' ); ?>">Widgets Page.</a></li>
		</ul>
	</div>
	<?php
}

// unregister actually
public function unregister() {
	
	
	$this->get_unregistered();
	
	$unregid = array_keys( $this->classes_un );	
	
	foreach ( $unregid as $un ) {
		unregister_widget($un);
	}
	
}

// display the form
public function form() {
	?>
	<div class="wrap">
	<h2>Unregister Widgets</h2>
	<form id="display-form" method="post" action="">
	
	<h3>Choose widgets to unregister</h3>
	
	<table class="form-table">
	<tr valign="top">
	<th scope="row">Check the Widgets You don't need.</th>
	<td>
	<?php
	$this->get_registered();
	$this->get_unregistered();
		
	$all_wids = array_merge( $this->classes_re, $this->classes_un );
	
	$already_unregistered = array_keys( $this->classes_un );
	
	$num = 0;
	foreach ( $all_wids as $key => $val ) {		
	?>
	<label for="<?php echo $val['name']; ?>" style="padding-bottom:30px;">
		<input 
			type="checkbox" 
			name="uw_widgets_<?php echo $num; ?>" 
			id="<?php echo $key; ?>" 
			value="<?php echo $key; ?>"
			<?php if ( in_array( $key, $already_unregistered ) ) { ?>
				checked="checked"
			<?php } ?>
			/> 
		<?php echo $val['name']; ?>(<?php echo $val['desc']; ?>) <br />
	</label>
	<?php
	$num++;
	} // end foreach.
	?>
	</td>
	</tr>
	</table>
	<?php wp_nonce_field( 'uw-display-form', 'unregister_widget' ); ?>
	<p class="submit"><input id="submit" class="button button-primary" type="submit" value="Save" name="uw-submit"></p>
	</form>
	</div>
	<?php
}


}

new unregister_sidebar_widgets();