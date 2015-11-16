<?php
/*
Plugin Name: VV List Dirs
Plugin URI: http://wp.verdon.ca/
Description: Lets WordPress users display clickable lists of folders in their pages and posts.
Version: 1.0
Author: Verdon Vaillancourt
Author URI: http://verdon.ca/
License: GPLv2 or later
Text Domain: vv-list-dirs
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}


define( 'VVLF_VERSION', '0.0.1' );
define( 'VVLF__MINIMUM_WP_VERSION', '4.0' );
define( 'VVLF__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VVLF__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( VVLF__PLUGIN_DIR . 'vvlf-settings.php' );
require_once( VVLF__PLUGIN_DIR . 'vvlf-display.php' );
require_once( VVLF__PLUGIN_DIR . 'vvlf-functions.php' );

register_activation_hook( __FILE__, array( 'VVLF_Settings', 'vvlf_activate') );
register_deactivation_hook( __FILE__, array( 'VVLF_Settings', 'vvlf_deactivate') );
