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


class VVLF_Display {

    /*--------------------------------------------*
     * Attributes
     *--------------------------------------------*/

    /** Refers to a single instance of this class. */
    private static $instance = null;

    /* Saved options */
    public $options;


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
		$this->options = (object)get_option( 'vvlf_settings' );

		// add the shortcode
		add_shortcode( 'vvlistfolders', array( &$this, 'VVLFDisplayFolders' ) );

		// init counter for distinguishing multiple lists
		$fileListCounter = 1;

    }


    /*--------------------------------------------*
     * Functions
     *--------------------------------------------*/


	/*
		VVLFDisplayFolders()
		This function reads the shortcode from the blog post or page and displays the
		list of files/folders for the folder requested.  Several options are allowed, see these
		in the $values variable.  This function ultimately generates an HTML list to
		display the list of files.
	*/
	function VVLFDisplayFolders( $params )
	{
		// Store the various options values in an array.
		$values = shortcode_atts( array( 	'folder' => ''
										), $params );

		// Get the folder and link options.
		// Read the folder as if it's constructed off the site's route.
		$folder = $values['folder'];
		// until I decide about folder in the short-code, default this to
		// root of WP install
		$folder = '';

		// The $filelist variable will hold a list of files,
		// lets go get it.
		$filelist = VVLFGenerateFileList( $folder );

		// if there are no items, this folder is empty.
		if( !count( $filelist ) )
		{
			// Show the user that there are no files.
			return '<p><em>'. __( 'No files or folders found', 'vv-list-dirs' ) .'</em></p>';
		}
		else
		{
			// Using the list of files, generate an HTML representation of the folder.
			$output = VVLFListFiles( $filelist );

			return $output;
		}
	}





}

VVLF_Display::get_instance();

