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
		add_shortcode( 'vvshowdirs', VVLFDisplayFolders );

		// init counter for distinguishing multiple lists
		$fileListCounter = 1;

		// display the folders
		&$this->vvlf_print_nubranding();

    }


    /*--------------------------------------------*
     * Functions
     *--------------------------------------------*/

	//	VVLFDisplayFolders()
	/*
		This function reads the shortcode from the blog post or page and displays the
		list of files/folders for the folder requested.  Several options are allowed, see these
		in the $values variable.  This function ultimately generates an HTML list to
		display the list of files.
	*/
	function VVLFDisplayFolders( $params )
	{
		// Store the various options values in an array.
		$values = shortcode_atts( array( 	'folder' => '',
											'link' => '',
											'sort' => '',
											'options' => ''
										), $params );

		// Get the folder and link options.
		// Read the folder as if it's constructed off the site's route.
		$folder = $values['folder'];
		$link = $values['link'];
		$sort = $values['sort'];
		$options = $values['options'];

		// "link" isn't currently exposed, so this is most likely just blank.  So, set
		// it to $folder.
		if ( $link == '' )
		{
			$link = $folder;
		}

		// The $filelist variable will hold a list of files,
		// lets go get it.
		$filelist = VVLFGenerateFileList( $folder, $link );

		// if there are no items, this folder is empty.
		if( !count( $filelist ) )
		{
			// Show the user that there are no files.
			return '<p><em>'. __( 'No files or folders found', 'vv-list-dirs' ) .'</em></p>';
		}
		else
		{
			// Using the list of files, generate an HTML representation of the folder.
			$output = VVLFListFiles( $filelist, $sort, $options );
			return $output;
		}
	}



	// VVLFGenerateFileList()
	/*
		@param $path - the folder to list, relative the the WordPress installation.
		@param $linkTarget - currently unused and requested to be the exact same
		value as $path.  With this relative path info, the function will loop
		through each file matching the criteria and add resulting files to a list
		which are returned.
	*/
	function VVLFGenerateFileList( $path, $linkTarget )
	{
		// init the array to build the list in
		$filelist = array();

		// Convert to the absolute path
		$path = ABSPATH . $path;

		// Attempt to open the folder
		if ( ( $p = @opendir( $path ) ) !== FALSE )
		{
			// Read the directory for items inside it.
			while ( ( $item = readDir( $p ) ) !== false )
			{
				$canView = true;
			
				// hide wp- content
				if ( substr( $item, 0, 3 ) == 'wp-' )
				{
					$canView = false;
				}
			
				// hide verdons stuff
				if ( substr( $item, 0, 5 ) == 'vtest' || substr( $item, 0, 4 ) == 'tjon' || substr( $item, 0, 10 ) == 'davidt_old' || substr( $item, 0, 10 ) == 'wendyp_old' || substr( $item, 0, 5 ) == 'danw2' || substr( $item, 0, 5 ) == 'danj2')
				{
					$canView = false;
				}
			
				// hide pass reset stuff
				if ( substr( $item, 0, 9 ) == 'passreset')
				{
					$canView = false;
				}
			
				// Exclude dotfiles, current, and parent and anything above
				if ( $item[0] != '.' && $canView )
				{
					/* Set up the relative path to the item.
					   START:
					   Code suggested by Peter Liu on 7/1/2011 for encoding UTF-8 files.  
					   I changed it slightly to use rawurlencode() instead of urlencode()
					   since this code fetches from the filesystem, not a URL.  See
					   php.net/rawurlencode for more on spaces and "+" symbols.
					*/
					if ( substr( $path, -1 ) == '/')
					{
						$newPath = $path . $item;
						$temparr = $linkTarget;
					} else {
						$newPath = $path . '/' . $item;
						$temparr = explode( '/' , $linkTarget );
					}
				
					// Have to assemble the path this way because we can't encode the "/" character!
					$assembledPath = "";
					for( $i = 0; $i<count($temparr); ++$i )
					{
						// Use rawurlencode() to properly encode spaces for the file system.
					   $assembledPath .= rawurlencode( $temparr[$i] ) . '/';
					}
					$newTarget = $assembledPath . rawurlencode( $item );
					if (substr($newTarget, 0, 1) == '/') {
						$newTarget = substr($newTarget, 1);
					} 
					// END:  Code suggested by Peter Liu.

					// If current item is a directory, do more stuff.  Otherwise, just skip it.
					if ( is_dir( $newPath ) )
					{
						// Special processing for links.  Read the path to the link and store it.
						if ( function_exists( 'is_link' ) && is_link( $newPath ) )
							$filelist[$item]['slTarget'] = readlink( $newPath );

						// Save the paths.
						$filelist[$item]['path'] = $newPath;
						$filelist[$item]['link'] = $newTarget;
						$filelist[$item]['size'] = filesize( $newPath );
						$filelist[$item]['date'] = filemtime( $newPath );
					}
				}
			}
			closeDir($p);
		}
		return $filelist;
	}


	// VVLFListFiles()
	/*
		This function takes a list of files and generates an HTML list to show them inside.
	*/
	function VVLFListFiles( $filelist, $sort, $options )
	{
		// Use this as a static variable
		global $fileListCounter;

		// Sort the items
		if ( 'reverse_alphabetic' == $sort )
		{
			// Reverse alphabetically sort
			krsort( $filelist );
		}
		elseif ( 'reverse_filesize' == $sort )
		{
			uasort( $filelist, 'VVReverseFileSizeSort' );
		}
		elseif ( 'filesize' == $sort )
		{
			uasort( $filelist, 'VVFileSizeSort' );
		}
		elseif ( 'reverse_date' == $sort )
		{
			uasort( $filelist, 'VVReverseDateSort' );
		}
		elseif ( 'date' == $sort )
		{
			uasort( $filelist, 'VVDateSort' );
		}
		else
		{
			// By default, alphabetically sort
			ksort( $filelist );
		}

		// Convert options into booleans

		$files = '';

		// Get the URL to the blog.  The path to the files will be added to this.
		$wpurl = get_bloginfo( "wpurl" );

		// Get the various options
		$isNewWindow = ( FALSE !== stripos( $options, 'new_window' ) );
		$isHideExtension = ( FALSE !== stripos( $options, 'hide_extension' ) );
		$isFilesize = ( FALSE !== stripos( $options, 'filesize' ) );
		$isDate = ( FALSE !== stripos( $options, 'date' ) );
		$isIcon = ( FALSE !== stripos( $options, 'icon' ) );

		// Start generating the HTML
		$retVal = "<div id='filelist$fileListCounter'>";

		foreach( $filelist as $itemName => $item )
		{
			// Get file variables
			$size = VVLFFormatFileSize( $item['size'] );
			//$date = date( "F j, Y", $item['date'] );
			$date = date( "n/j/Y g:i a", $item['date'] );
			$link = $wpurl.'/'.$item['link'];

			// Strip extension if necessary
			if ( $isHideExtension )
			{
				$ext = substr( strrchr( $itemName, '.' ), 0 );
				$itemName = str_replace( $ext, '', $itemName );
			}

			if ( $isNewWindow )
				$files .= '<li><a href="'.$link.'" target="_blank">'.$itemName.'</a>';
			else
				$files .= '<li><a href="'.$link.'">'.$itemName.'</a>';

			if ( $isFilesize )
				$files .= '<span class="size">' . __('Size: ', 'vv-list-dirs' ) . $size . '</span>' . PHP_EOL;

			if ( $isDate )
				$files .= '<span class="modified">' . __('Date: ', 'vv-list-dirs' ) . $date . '</span>' . PHP_EOL;

			$files .='</li>'.PHP_EOL;
		}

		// Encase the ouput in class and ID
		$fileListCounter++;
		$retVal .= '<ul id="listyofiles">'.PHP_EOL.$files.'</ul>'.PHP_EOL;

		// Close out the div
		$retVal .= '</div>'.PHP_EOL;

		// return the HTML
		return $retVal;
	}


	function VVLFFormatFileSize( $size )
	{
		if ( strlen($size) <= 9 && strlen($size) >= 7 )
		{
			$size = number_format( $size / 1048576, 1 );
			return "$size MB";
		}
		elseif ( strlen( $size ) >= 10 )
		{
			$size = number_format( $size / 1073741824, 1 );
			return "$size GB";
		}
		else
		{
			$size = number_format( $size / 1024, 1 );
			return "$size KB";
		}
	}


	function VVLFShowFilesCode( $userFolder, $folder )
	{
		return '[vvshowdirs folder="'.$userFolder.'/'.$folder.'" options="new_window,date,filesize"]';
	}

	function VVReverseFileSizeSort( $x, $y )
	{
		return ( $x['size'] > $y['size'] );
	}

	function VVFileSizeSort( $x, $y )
	{
		return ( $y['size'] > $x['size'] );
	}

	function VVReverseDateSort( $x, $y )
	{
		return ( $x['date'] > $y['date'] );
	}

	function VVDateSort( $x, $y )
	{
		return ( $y['date'] > $x['date'] );
	}




}

VVLF_Display::get_instance();

