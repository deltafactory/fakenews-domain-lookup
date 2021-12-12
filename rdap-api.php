<?php

class RDAP_API {

	// Cache handler
	var $cache;
	var $use_cache = true;
	var $dns;

	function __construct( $cache ) {
		$this->cache = $cache;
		$this->bootstrap();
	}

	function bootstrap() {
		$cache = $this->cache;
		$bootstrap_domain = 'https://data.iana.org/rdap/dns.json';

		$dns = $cache->get_raw( $bootstrap_domain, 'rdap_bootstrap' );

		if ( !$dns ) {
			$dns = $this->http_get( $bootstrap_domain );
			$cache->set_raw( $bootstrap_domain, $dns, 'rdap_bootstrap' );
		}

		$this->dns = json_decode( $dns );
	}

	function find_rdap( $tld ) {
		$match = false;

		foreach( $this->dns->services as $service ) {
			if ( in_array( $tld, $service[0] ) ) {
				$match = $service[1][0];
				break;
			}
		}

		return $match;
	}

	function query( $domain ) {
		list( $name, $tld ) = explode( '.', $domain, 2 );

		$server = $this->find_rdap( $tld );

		if ( $server ) {
			$url = $server . "domain/$domain";
			$response = $this->query_url( $url );

			if ( $response ) {

				// Recursively request related link if found.
				// This needs refinement: there are issues with the URL/Response provided.
				$links = $this->find_link_by_rel( $response['links'], 'related' );
				if ( $links ) {
					$rel_link = reset( $links ); // First element;

					//echo $url . '<=>' . $rel_link['href'];
					
					// If the strings are different, case insensitive check.
					if ( 0 !== strcasecmp( $rel_link['href'], $url ) ) {
						$rel_response = $this->query_url( $rel_link['href'] );
						if ( $rel_response && empty( $rel_response['errorCode'] ) ) {
							$response = $rel_response;
						}
					}
				}
			}

			if ( $response ) {
				return $response;
			}

		}

		return false;
	}

	function query_url( $url ) {
		$response = $this->http_get( $url );
		return $response ? json_decode( $response, true ) : false;
	}

	function http_get( $url ) {
		$curl = curl_init( $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );

		// For SSL cert issues with CURL
		if ( defined( 'WHOIS_DEBUG' ) && WHOIS_DEBUG ) {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		}

		$response = curl_exec( $curl );

		if ( $error = curl_error( $curl ) ) {
			error_log( 'RDAP HTTP request error: ' . $error );
		}

		curl_close( $curl );
		return $response;
	}

	function find_link_by_rel( $links, $rel ) {
		$result = array();

		foreach( $links as $link ) {
			if ( $link['rel'] == $rel ) {
				$result[] = $link;
			}
		}

		return $result;
	}

	static function find_entity_by_role( $record, $role ) {
		$contact = false;

		if ( !empty( $record['entities'] ) ) {
			foreach( $record['entities'] as $ent ) {
				if ( is_array( $ent['roles'] ) && in_array( $role, $ent['roles'] ) ) {
					$contact = $ent;
					break;
				}
			}
		}

		return $contact;
	}

	static function find_event( $record, $action ) {
		$event = false;

		if ( !empty( $record['events'] ) ) {
			foreach( $record['events'] as $ev ) {
				if ( $ev['eventAction'] == $action ) {
					$event = $ev;
				}
			}
		}

		return $event;
	}


}