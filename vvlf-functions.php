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


/*
	VVLFGenerateFileList()
	@param $path - the folder to list, relative the the WordPress installation.
	@param $admin - a flag to identify if the request is an admin screen or public
*/
function VVLFGenerateFileList( $path, $admin=0 ) {
	// init the array to build the list in
	$filelist = array();

	// get our settings
	$pluginoptions = (object)get_option( 'vvlf_settings' );

	// capture the path before processing it
	$rawpath = $path;
	
	// Convert path to the absolute path
	$path = ABSPATH . $path;

	// Attempt to open the folder
	if ( ( $p = @opendir( $path ) ) !== FALSE ) {
		// Read the directory for items inside it.
		while ( ( $item = readDir( $p ) ) !== false ) {
			$canView = true;
		
			// hide wp- content if slected in settings
			if ($admin || $pluginoptions->vvlf_hide_wpfolders) {
				if ( substr( $item, 0, 3 ) == 'wp-' ) {
					$canView = false;
				}
			}
		
			// hide folders from public view that are selected in settings
			if (!$admin) {
				if ($pluginoptions->vvlf_hide_specifiedfolders) {
					foreach( $pluginoptions->vvlf_hide_specifiedfolders as $folderName => $folder ) { 
						if ($item == $folderName) {
							$canView = false;
						}
					}
				}
			}
		
			// Exclude dotfiles, current, and parent and anything above
			if ( $item[0] != '.' && $canView ) {
				if ( substr( $path, -1 ) == '/') {
					$newPath = $path . $item;
					$temparr = $rawpath;
				} else {
					$newPath = $path . '/' . $item;
					$temparr = explode( '/' , $rawpath );
				}
				// Have to assemble the path this way because we can't encode the "/" character!
				$assembledPath = "";
				for( $i = 0; $i<count($temparr); ++$i ) {
				   $assembledPath .= rawurlencode( $temparr[$i] ) . '/';
				}
				$newTarget = $assembledPath . rawurlencode( $item );
				if (substr($newTarget, 0, 1) == '/') {
					$newTarget = substr($newTarget, 1);
				} 

				// If current item is a directory, store the info.  Otherwise, just skip it.
				if ( is_dir( $newPath ) ) {
					$filelist[$item]['path'] = $newPath;
					$filelist[$item]['link'] = $newTarget;
					$filelist[$item]['date'] = filemtime( $newPath );
				}
			}
		}
		closeDir($p);
	}

	return $filelist;
}


/*
	VVLFListFiles()
	This function takes a list of files and generates an HTML list to show them inside.
	@param $filelist - the list of files
	@param $admin - a flag to identify if the request is an admin screen or public
*/
function VVLFListFiles( $filelist, $admin=0 ) {
	// Use this as a static variable
	global $fileListCounter;
	$pluginoptions = (object)get_option( 'vvlf_settings' );

	// Sort the items
	if ( 'revname' == $pluginoptions->vvlf_sort_order ) {
		krsort( $filelist );
	} elseif ( 'revdate' == $pluginoptions->vvlf_sort_order ) {
		uasort( $filelist, 'VVReverseDateSort' );
	} elseif ( 'date' == $pluginoptions->vvlf_sort_order ) {
		uasort( $filelist, 'VVDateSort' );
	} else {
		ksort( $filelist );
	}

	// init the files var
	$filesMarkUp = '';

	// Get the URL to the blog.  The path to the files will be added to this.
	$wpurl = get_bloginfo( "wpurl" );

	// Start generating the HTML
	$finalMarkUp = "<div id='filelist$fileListCounter'>";

	foreach( $filelist as $itemName => $item ) {
		//$date = date( "F j, Y", $item['date'] );
		$date = date( "n/j/Y g:i a", $item['date'] );
		$link = $wpurl.'/'.$item['link'];
		$filesMarkUp .= '<li>';

		if ( $admin ) {
			if (isset($pluginoptions->vvlf_hide_specifiedfolders{$itemName})) {
				$pluginoptions->vvlf_hide_specifiedfolders{$itemName} = $pluginoptions->vvlf_hide_specifiedfolders{$itemName};
			} else {
				$pluginoptions->vvlf_hide_specifiedfolders{$itemName} = '';
			}
			$filesMarkUp .= '<input type="checkbox" name="vvlf_settings[vvlf_hide_specifiedfolders]['.$itemName.']" id="vvlf_settings[vvlf_hide_specifiedfolders]['.$itemName.']" ' . checked( $pluginoptions->vvlf_hide_specifiedfolders{$itemName}, 1, false ) . ' value="1" />';
		}

		if ( $pluginoptions->vvlf_link_target || $admin )
			$filesMarkUp .= '<a href="'.$link.'" target="_blank">'.$itemName.'</a>';
		else
			$filesMarkUp .= '<a href="'.$link.'">'.$itemName.'</a>';

		if ( $pluginoptions->vvlf_display_date )
			$filesMarkUp .= '<span class="modified"> (' . $date . ')</span>';

		$filesMarkUp .='</li>'.PHP_EOL;
	}

	// Encase the ouput in class and ID
	$fileListCounter++;
	$finalMarkUp .= '<ul id="vvlistfolders">'.PHP_EOL.$filesMarkUp.'</ul>'.PHP_EOL;

	// Close out the div
	$finalMarkUp .= '</div>'.PHP_EOL;

	// return the HTML
	return $finalMarkUp;
}


function VVReverseDateSort( $x, $y ) {
	return ( $x['date'] > $y['date'] );
}

function VVDateSort( $x, $y ) {
	return ( $y['date'] > $x['date'] );
}


