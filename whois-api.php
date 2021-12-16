<?php

use Iodev\Whois\Factory;

class WHOIS_API {

	var $WHOIS = 'timeout 15s /usr/bin/whois -H -- %s';
	var $response = array();
	var $lookup = 0;
//	var $ignore_keys = array( 'notice', 'terms_of_use' );

	var $use_cache = true;

	function __construct() {
		$this->reset();
	}

	function reset() {
		$this->response = array();
	}

	function query( $domain ) {
		$result = false;

		if ( $domain ) {
			$whois = Factory::get()->createWhois();
			$result = $whois->lookupDomain( $domain );
			$output = explode( "\n", $result->text );
			//exec( sprintf( $this->WHOIS, escapeshellcmd( $domain ) ), $output);
			$result = $this->parse_data( $output );
		}

		return $result;
	}

	function parse_data( $rows ) {
		$lookup = 0;

		foreach( $rows as $line ) {
			if ( false === strpos( $line, ': ' ) ) {
				continue;
			}

			$line = trim( $line );

			list( $key, $value ) = explode( ': ', $line, 2 );
			$key = $this->format_key( $key );
			$value = isset( $value ) ? trim( $value ) : '';

			if ( in_array( $key, $this->ignore_keys ) ) {
				continue;
			}

			if ( $key == 'domain_name' || $key == 'server_name' ) {
				if ( !empty( $this->response[$lookup] ) ) {
					$lookup++;
				}
			}

			if ( isset( $this->response[$lookup][$key] ) ) {
				if ( !is_array( $this->response[$lookup][$key] ) ) {
					$this->response[$lookup][$key] = (array) $this->response[$lookup][$key];
				}
				$this->response[$lookup][$key][] = $value;
			} else {
				$this->response[$lookup][$key] = $value;
			}
		}

		$this->lookup = $lookup;
		return $this->response;
	}

	function format_key( $key ) {
		$new_key = strtolower( $key );
		$new_key = preg_replace( '/([^a-z0-9])/', '_', $new_key );
		return trim( $new_key, '_' );
	}
}


