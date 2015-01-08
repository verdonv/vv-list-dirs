<?php

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

?>