<?php

/**
 * Filter functions should strip out anything except the most accurate info:
 *  - Registrar contact
 *  - Registration date
 *  - Nameservers
 **/

class FakeNewsFitness {

	static function filter_rdap( $data, $domain ) {
		$result = array(
			'status' => 'ok',
			'via' => 'rdap',
			'domain' => $domain
		);

		$registrant = RDAP_API::find_entity_by_role( $data, 'registrant' );

		if ( $registrant ) {

			// Object may be blank if private/redacted.
			$format_registrant = self::entity_to_object( $registrant );

			if ( $format_registrant ) {
				$result['registrant'] = $format_registrant;
			}
		}

		$reg_date = RDAP_API::find_event( $data, 'registration' );

		if ( $reg_date ) {
			$result['registration_date'] = self::format_reg_date( $reg_date['eventDate'] );
		}

		if ( !empty( $data['nameservers'] ) ) {
			$nameservers = array();
			foreach( $data['nameservers'] as $ns ) {
				$nameservers[] = $ns['ldhName'];
			}

			$result['nameservers'] = $nameservers;
		}

		return $result;
	}

	static function entity_to_object( $data ) {
		$contact = array();

		if ( isset( $data['vcardArray'][1] ) ) {
			foreach( $data['vcardArray'][1] as $c ) {
				switch( $c[0] ) {
					// Ignore version
					case 'version': break;
					case 'org':
						if ( false !== stripos( $c[3], 'privacy' ) ) {
							// Is private. Bail.
							return $contact = array();
						}

						/* falls through */
					case 'adr':
					default:
						$contact[$c[0]] = self::flatten_value( $c[3] );
						break;
				}
			}
		}

		// Notes privacy status, etc.
		if ( isset( $data['remarks'] ) ) {
			//$contact['remarks'] = $data['remarks'];
		}

		return $contact;
	}

	static function flatten_value( $val, $sep = ', ' ) {
		if ( is_array( $val ) ) {
			$arr = array_map( [ __CLASS__, 'flatten_value' ], $val );
			$val = implode( $sep, array_filter( $arr ) );
		}

		return $val;
	}

	static function format_reg_date( $datestring ) {
		return date( 'Y-m-d', strtotime( $datestring ) );
	}

	static function filter_whois( $data, $domain ) {
		// Result contains multiple entries. Just show the last.
		$record = is_array( $data ) ? end( $data ) : $data;

		$result = array(
			'status' => 'ok',
			'via' => 'whois',
			'domain' => $domain
		);


		$registrant = self::compile_contact_fields( $record, 'registrant' );
		if ( $registrant ) {
			$format_registrant = self::format_whois_contact( $registrant );

			if ( $format_registrant ) {
				$result['registrant'] = $format_registrant;
			}
		}

		$reg_date = isset( $record['creation_date'] ) ? $record['creation_date'] : false;

		if ( $reg_date ) {
			$result['registration_date'] = self::format_reg_date( $reg_date );
		}

		if ( !empty( $record['name_server'] ) ) {
			$result['nameservers'] = $record['name_server'];
		}

		return $result;
	}

	static function compile_contact_fields( $data, $type ) {
		$fields = array( 'name', 'organization', 'street', 'city', 'state_province', 'postal_code', 'country', 'phone', 'fax', 'email' );

		$contact = array();

		foreach( $fields as $f ) {
			$key = $type . '_' . $f;
			if ( isset( $data[$key] ) ) {
				$contact[$f] = $data[$key];
			}
		}

		return $contact;
	}

	static function format_whois_contact( $contact ) {
		// Bail if org includes "Privacy" - this is not foolproof!
		if ( isset( $contact['organization'] ) ) {
			if ( false !== stripos( $contact['organization'], 'privacy' ) ) {
				return array();
			}
		}

		return $contact;
	}

}
