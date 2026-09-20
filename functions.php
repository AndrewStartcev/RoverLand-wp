<?php

defined( 'ABSPATH' ) || exit;

$roverland_function_files = glob( get_template_directory() . '/functions/*.php' );

if ( is_array( $roverland_function_files ) ) {
	foreach ( $roverland_function_files as $file ) {
		require_once $file;
	}
}
