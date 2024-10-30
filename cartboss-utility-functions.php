<?php

function cartboss_get_path( $filename = '' ) {
	return CARTBOSS_PATH . ltrim( $filename, '/' );
}

function cartboss_include( $filename = '' ) {
	$file_path = cartboss_get_path( $filename );
	if ( file_exists( $file_path ) ) {
		include_once( $file_path );
	}
}
