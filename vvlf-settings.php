<?php
/*
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

class VVLF_Settings {

    /*--------------------------------------------*
     * Attributes
     *--------------------------------------------*/

    /** Refers to a single instance of this class. */
    private static $instance = null;

    /* Saved options */
    public $options;

    private static $defaults = array(
		'vvlf_hide_wpfolders'			=> '1',
		'vvlf_hide_specifiedfolders'	=> '',
		'vvlf_link_target'				=> '0',
		'vvlf_display_date'				=> '0',
		'vvlf_sort_order'				=> 'name',
		'vvlf_reset'					=> '0',
		'vvlf_clearout'					=> '0'
	);


    /*--------------------------------------------*
     * Constructor
     *--------------------------------------------*/

    // CREATE OR RETURN AN INSTANCE OF THE CLASS
    public static function get_instance() {

        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    } // end get_instance;


	// INITIALIZE THE CLASS
    private function __construct() {

		// get our settings
		$this->options = (object) get_option( 'vvlf_settings', self::$defaults );

		// add page to admin menu
		add_action( 'admin_menu', array( $this, 'vvlf_add_admin_page' ) );

		// register page options
		add_action( 'admin_init', array( $this, 'vvlf_settings_init' ) );
    }



    /*--------------------------------------------*
     * Functions
     *--------------------------------------------*/

    // ADD PAGE UNDER SETTINGS MENU
	public function vvlf_add_admin_page() {
		$page = add_options_page(
			'List Directories', // Page title
			'List Directories', // Menu title
			'manage_options', // capability
			'vv_listdirs_options', // menu slug
			array( $this, 'vvlf_options_page' ) // Callback
		);
	// removed because I'm not using the colour picker
	// left as an example as to how to enqueue js to the settings page 
	// add_action( "load-{$page}", array( $this, 'vvlf_enqueue_admin_js') );
	}

    // RENDER THE ADMIN PAGE
	public function vvlf_options_page() {
		?>
        <div class="wrap">
			<h2>List Directories</h2>
			<form action='options.php' method='post'>
			<?php
				settings_fields('vvlf_settings_group');
				do_settings_sections('vv_listdirs_options');
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

    // REGISTER ADMIN PAGE OPTIONS
	public function vvlf_settings_init() {

		register_setting(
			'vvlf_settings_group', // option group
			'vvlf_settings', // option name
			array( $this, 'vvlf_validate_options' ) // sanitize
		);

		add_settings_section(
			'vvlf_options_section', // ID
			__( 'Choose List Directories Options', 'vv-list-dirs' ), // Title
			array( $this, 'vvlf_settings_section_callback' ), // Callback
			'vv_listdirs_options' // page
		);

		add_settings_section(
			'vvlf_admin_section', // ID
			__( 'Administrative Options', 'vv-list-dirs' ), // Title
			array( $this, 'vvlf_admin_section_callback' ), // Callback
			'vv_listdirs_options' // page
		);

		add_settings_field( // hide wp folders
			'vvlf_hide_wpfolders',
			__( 'Hide WP Directories', 'vv-list-dirs' ),
			array( $this, 'vvlf_hide_wpfolders_render' ),
			'vv_listdirs_options',
			'vvlf_options_section'
		);

		add_settings_field( // hide specified folders
			'vvlf_hide_specifiedfolders',
			__( 'Hide Specific Directories', 'vv-list-dirs' ),
			array( $this, 'vvlf_hide_specifiedfolders_render' ),
			'vv_listdirs_options',
			'vvlf_options_section'
		);

		add_settings_field( // link target
			'vvlf_link_target',
			__( 'Open linked folders in new window/tab', 'vv-list-dirs' ),
			array( $this, 'vvlf_link_target_render' ),
			'vv_listdirs_options',
			'vvlf_options_section'
		);

		add_settings_field( // display the date
			'vvlf_display_date',
			__( 'Display the date', 'vv-list-dirs' ),
			array( $this, 'vvlf_display_date_render' ),
			'vv_listdirs_options',
			'vvlf_options_section'
		);

		add_settings_field( // sort order
			'vvlf_sort_order',
			__( 'Sort order', 'vv-list-dirs' ),
			array( $this, 'vvlf_sort_order_render' ),
			'vv_listdirs_options',
			'vvlf_options_section'
		);

		add_settings_field( // reset defaults
			'vvlf_reset',
			__( 'RESET ALL TO DEFAULT', 'vv-list-dirs' ),
			array( $this, 'vvlf_reset_render' ),
			'vv_listdirs_options',
			'vvlf_admin_section'
		);

		add_settings_field( // delete settings on deactivate
			'vvlf_clearout',
			__( 'Clear stored settings from database when deactivating this plugin', 'vv-list-dirs' ),
			array( $this, 'vvlf_clearout_render' ),
			'vv_listdirs_options',
			'vvlf_admin_section'
		);

	}

	// RENDER THE WP FOLDERS DISPLAY OPTIONS
	public function vvlf_hide_wpfolders_render(  ) {
		?>
		<input type='checkbox' name='vvlf_settings[vvlf_hide_wpfolders]' id='vvlf_settings[vvlf_hide_wpfolders]' <?php checked( $this->options->vvlf_hide_wpfolders, 1 ); ?> value='1' />
		<?php
	}

	// RENDER THE SPECIFIC FOLDERS DISPLAY OPTIONS
	public function vvlf_hide_specifiedfolders_render(  ) {
		$folder = '';
		$admin = 1;
		$filelist = VVLFGenerateFileList( $folder, $admin );
		$output = VVLFListFiles( $filelist, $admin );
		print($output);
		?>
		<?php
	}

	// RENDER THE LINK TARGET OPTIONS
	public function vvlf_link_target_render(  ) {
		?>
		<input type='checkbox' name='vvlf_settings[vvlf_link_target]' id='vvlf_settings[vvlf_link_target]' <?php checked( $this->options->vvlf_link_target, 1 ); ?> value='1' />
		<?php
	}

	// RENDER THE DISPLAY DATE OPTIONS
	public function vvlf_display_date_render(  ) {
		?>
		<input type='checkbox' name='vvlf_settings[vvlf_display_date]' id='vvlf_settings[vvlf_display_date]' <?php checked( $this->options->vvlf_display_date, 1 ); ?> value='1' />
		<?php
	}

	// RENDER THE SORT ORDER OPTIONS
	public function vvlf_sort_order_render(  ) {
		?>
		<select name='vvlf_settings[vvlf_sort_order]' id='vvlf_settings[vvlf_sort_order]'>
			<option value='name' <?php selected( $this->options->vvlf_sort_order, 'name' ); ?>>By name</option>
			<option value='revname' <?php selected( $this->options->vvlf_sort_order, 'revname' ); ?>>Reverse name</option>
			<option value='date' <?php selected( $this->options->vvlf_sort_order, 'date' ); ?>>By date</option>
			<option value='revdate' <?php selected( $this->options->vvlf_sort_order, 'revdate' ); ?>>Reverse date</option>
		</select>
		<?php
	}

	// RENDER THE RESET TO DEFAULT DISPLAY OPTIONS
	public function vvlf_reset_render(  ) {
		?>
		<input type='checkbox' name='vvlf_settings[vvlf_reset]' id='vvlf_settings[vvlf_reset]' <?php checked( $this->options->vvlf_reset, 1 ); ?> value='1' />
		<?php
	}

	// RENDER THE RESET TO DEFAULT DISPLAY OPTIONS
	public function vvlf_clearout_render(  ) {
		?>
		<input type='checkbox' name='vvlf_settings[vvlf_clearout]' id='vvlf_settings[vvlf_clearout]' <?php checked( $this->options->vvlf_clearout, 1 ); ?> value='1' />
		<?php
	}

    // VALIDATE THE FIELDS
    public function vvlf_validate_options( $fields ) {
		$valid_fields = array();

		if ($fields['vvlf_reset'] == 1) {
			$valid_fields = self::$defaults;
			return $valid_fields;
		}

		// just passing these right through as they are fixed input values
		$valid_fields['vvlf_hide_wpfolders'] = $fields['vvlf_hide_wpfolders'];
		// consider what I might need for this
		$valid_fields['vvlf_hide_specifiedfolders'] = $fields['vvlf_hide_specifiedfolders'];
		$valid_fields['vvlf_link_target'] = $fields['vvlf_link_target'];
		$valid_fields['vvlf_display_date'] = $fields['vvlf_display_date'];
		$valid_fields['vvlf_sort_order'] = $fields['vvlf_sort_order'];
		// always zero this one back out
		$valid_fields['vvlf_reset'] = 0;
		$valid_fields['vvlf_clearout'] = $fields['vvlf_clearout'];

		return $valid_fields;
    }


    // CALLBACK FOR SETTINGS SECTION
	public function vvlf_settings_section_callback(  ) {
		echo __( 'You may customize the following options', 'vv-list-dirs' );
	}

    // CALLBACK FOR ADMIN SECTION
	public function vvlf_admin_section_callback(  ) {
		echo __( 'The following choices will reset options now, or tidy up vs. saving if deactivating this plugin.', 'vv-list-dirs' );
	}

	public static function vvlf_activate() {
		if (get_option( 'vvlf_settings' ) == FALSE) {
			update_option ('vvlf_settings', self::$defaults);
		}
	}

	public static function vvlf_deactivate() {
		$opts = (object) get_option( 'vvlf_settings' );
		$clear = $opts->vvlf_clearout;
		if ($clear == 1) {
			delete_option('vvlf_settings');
		}
	}



} // end class


VVLF_Settings::get_instance();

