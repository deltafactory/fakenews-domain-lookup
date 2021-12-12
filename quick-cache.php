<?php

class Quick_Cache {

	var $storage_path;
	var $expiration = 3600;

	function __construct( $storage_path ) {
		$this->storage_path = $storage_path;
		$this->prep_storage();
	}

	function prep_storage() {
		if ( !is_dir( $this->storage_path ) ) {
			mkdir( $this->storage_path, 0700, true );
			touch( $this->storage_path . '/index.html' );
			file_put_contents( $this->storage_path . '/.htaccess', $this->deny_htaccess() );
		}

		return is_dir( $this->storage_path );
	}

	function deny_htaccess() {
		return implode( "\n", array(
			'order deny,allow',
			'deny from all'
		) );
	}

	function key( $name, $group = '' ) {
		$key = md5( $name );

		if ( $group ) {
			$key = $group . '_' . $key;
		}

		return $key;
	}

	function path( $key ) {
		return $this->storage_path . '/' . $key;
	}

	function get_raw( $name, $group = '' ) {
		$key = $this->key( $name, $group );
		$path = $this->path( $key );

		if ( $this->is_valid( $path ) ) {
			return file_get_contents( $path );
		}

		return null;
	}

	function set_raw( $name, $value, $group = '' ) {
		$key = $this->key( $name, $group );
		$path = $this->path( $key );

		file_put_contents( $path, $value );
	}

	function get( $name, $group = '' ) {
		return unserialize( $this->get_raw( $name, $group ) );
	}

	function set( $name, $value, $group = '' ) {
		return $this->set_raw( $name, serialize( $value ), $group );
	}

	function is_valid( $path, $expiration = false ) {
		if ( !$expiration ) {
			$expiration = $this->expiration;
		}

		return ( is_readable( $path ) && ( (int) filemtime( $path ) + (int) $expiration ) > time() );
	}
}
