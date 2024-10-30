<?php
/*
Plugin Name: Menu Description
Plugin URI: https://wordpress.org/plugins/menu-description
Version: 1.0.4
Description: Add the description from menu items when the item is display
Author: James Low
Author URI: http://jameslow.com
*/

class MenuDescription {
	static $settings = false;
	static $lastmenu = null;
	static $GROUP = 'menu_description';
	static $PREFIX = 'menu_description_';

	public static function add_hooks() {
		add_action('admin_init', array('MenuDescription', 'admin'), 10);
		add_filter('gettext_default', array('MenuDescription', 'enable'), 99, 3);
		add_filter('option_nav_menu_options', array('MenuDescription', 'settings'), 99, 2);
		add_action('wp_update_nav_menu', array('MenuDescription', 'update'), 20, 2);
		if (!is_admin()) {
			add_filter('walker_nav_menu_start_el', array('MenuDescription', 'output'), 10, 4);
			add_filter('wp_get_nav_menu_object', array('MenuDescription', 'last'), 10, 2);
		}
	}
	public static function admin() {
		self::register_option('menus');
	}
	public static function register_option($option) {
		//register_setting(self::$GROUP, self::$PREFIX.$option);
		register_setting('option', self::$PREFIX.$option);
	}
	public static function update_option($option, $value) {
		update_option(self::$PREFIX.$option, $value);
	}
	public static function get_option($option, $default = false) {
		return get_option(self::$PREFIX.$option, $default);
	}
	public static function menus() {
		return self::get_option('menus', array());
		//$value = self::get_option('menus', array());
		//return is_array($value) ? $value : array();
	}
	public static function menu($menu_id) {
		$menus = self::menus();
		return $menus[$menu_id];
	}
	public static function last($menu_obj, $menu) {
		self::$lastmenu = $menu_obj->term_id;
		return $menu_obj;
	}
	public static function output($item_output, $item, $depth, $args) {
		if (self::menu(self::$lastmenu)) {
			$item_output = str_replace( $args->link_after . '</a>', '<span class="menu-item-description">' . $item->description . '</span>' . $args->link_after . '</a>', $item_output );
		}
		return $item_output;
	}
	public static function update($menu_id, $menu_data = null) {
		$menus = MenuDescription::menus();
		$menus[$menu_id] = isset( $_POST['show-menu-description'] ) ? $_POST['show-menu-description'] == 1 : 0;
		self::update_option('menus', $menus);
		//$menus = self::menus();
	}
	public static function enable($translation, $text, $domain) {
		if ($text == 'Menu Settings') {
			self::$settings = true;
		}
		return $translation;
	}
	public static function menu_id() {
		// /wp-admin/nav-menus.php logic, if no menu selected, edit the first one
		$action = $_REQUEST['action']; //Raw action to determine new
		$nav_menu_selected_id = isset( $_REQUEST['menu'] ) ? (int) $_REQUEST['menu'] : 0;
		if (0 === $nav_menu_selected_id && $action != 'edit') {
			$nav_menus  = wp_get_nav_menus();
			$menu_count = count( $nav_menus );
			$recently_edited = absint( get_user_option( 'nav_menu_recently_edited' ) );
			if (!empty($recently_edited)) {
				$nav_menu_selected_id = $recently_edited;
			} elseif ($menu_count > 0) {
				$nav_menu_selected_id = $nav_menus[0]->term_id; 
			}
		}
		return $nav_menu_selected_id;
	}
	public static function settings($value, $option) {
		if (self::$settings) {
			//Remove filters first
			remove_filter('gettext_default', array('MenuDescription', 'enable'), 99);
			remove_filter('option_nav_menu_options', array('MenuDescription', 'settings'), 99);
			$nav_menu_selected_id = self::menu_id();
			$show_description = $nav_menu_selected_id === 0 ? false : self::menu($nav_menu_selected_id);
			?>
								<fieldset class="menu-settings-group menu-description">
									<legend class="menu-settings-group-name howto"><?php _e( 'Description' ); ?></legend>
									<div class="menu-settings-input checkbox-input">
										<input type="checkbox"<?php checked( $show_description ); ?> name="show-menu-description" id="show-menu-description" value="1" /> <label for="show-menu-description"><?php printf( __( 'Show description when displaying menu' ), 'menu-description' ); ?></label>
									</div>
								</fieldset>
			<?php
		}
		return $value;
	}
}
MenuDescription::add_hooks();