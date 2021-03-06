<?php

function get_param( $key, $default = '' ) {
	return isset( $_GET[$key] ) ? $_GET[$key] : $default;
}

function json_response( $response, $http_status = 200 ) {

	// CORS header
	header( 'Access-Control-Allow-Origin: *' );

	// No-cache header.
	header( 'Cache-Control: no-cache' );

	http_response_code( $http_status );
	header( 'Content-type: application/json' );
	echo json_encode( $response );
}


function parse_domain( $query ) {
	$query = trim( $query );
	if ( $query == '' ) {
		return false;
	}

	if ( 0 === strrpos( $query, 'http' ) ) {
		$url = parse_url( $query );
		$domain = $url['host'];
	} else {
		$domain = $query;
	}

	$match_domain = "/([-\w]+\.\w+)$/";

	if ( preg_match( $match_domain, $domain, $matches) ) {
		$domain = $matches[1];
	} else {
		$domain = false;
	}

	return $domain;
}

