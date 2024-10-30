<?php

class Cartboss_Singleton {
	private static $instances = array();

	final public static function instance() {
		$class = get_called_class();
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static();
		}

		return self::$instances[ $class ];
	}

	public function __wakeup() {
		throw new Exception( "Cannot unserialize singleton" );
	}

	private function __clone() {
	}
}