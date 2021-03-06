<?php

/**
 * Domain lookup API for Fake News Fitness
 **/


// Config
define( 'USE_CACHE', true );
define( 'WHOIS_DEBUG', true );
$cache_storage = __DIR__ . '/_cache';

require( __DIR__ . '/util.php' );
require( __DIR__ . '/whois-api.php' );
require( __DIR__ . '/rdap-api.php' );
require( __DIR__ . '/quick-cache.php' );
require( __DIR__ . '/fnf.php' );
require( __DIR__ . '/vendor/autoload.php' );

// URL Parameters:
//  - q: <domain to query> (required)
//  - use: rdap, whois, or both (default) to force lookup method.
//  - nocache: any value will disable use of result cache.

$domain = parse_domain( get_param( 'q', '' ) );
$use = get_param( 'use', 'both' );
$nocache = (bool) get_param( 'nocache', false );
$raw = WHOIS_DEBUG ? get_param( 'raw', false ) : false;

// Determine by config constant and per-request option.
$use_cache = USE_CACHE && !$nocache;

if ( !$domain ) {
	$result = array(
		'status' => 'error',
		'error' => 'No query'
	);

	json_response( $result, 404 );
	exit();
}

$result = false;
$output = array();
$cache = new Quick_Cache( $cache_storage );

if ( $use == 'rdap' || $use == 'both' ) {
	$rdap = new RDAP_API( $cache );
	$rdap->use_cache = $use_cache;
	$result = $rdap->query( $domain );
	$output = FakeNewsFitness::filter_rdap( $result, $domain );
}

if ( ( $use == 'both' && !$result ) || $use == 'whois' ) {
	$whois = new WHOIS_API();
	$whois->use_cache = $use_cache;
	$result = $whois->query( $domain );
	$output = FakeNewsFitness::filter_whois( $result, $domain );
}

if ( !$output ) {
	$output = array(
		'status' => 'error',
		'error' => 'Not found.'
	);
}

if ( $raw ) {
	json_response( $result );
} else {
	json_response( $output );
}

