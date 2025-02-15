<?php

/*
 * PHP-Cookie (https://github.com/delight-im/PHP-Cookie)
 * Copyright (c) delight.im (https://www.delight.im/)
 * Licensed under the MIT License (https://opensource.org/licenses/MIT)
 */

namespace Delight\Cookie;

/** HTTP response headers sent by the server */
final class ResponseHeader {

	private function __construct() { }

	/**
	 * Returns the header with the specified name (and optional value prefix)
	 *
	 * @param string $name the name of the header
	 * @param string $valuePrefix the optional string to match at the beginning of the header's value
	 * @return string|null the header (if found) or `null`
	 */
	public static function get($name, $valuePrefix = '') {
		if (empty($name)) {
			return null;
		}

		$nameLength = \strlen($name);
		$headers = \headers_list();

		foreach ($headers as $header) {
			if (\strcasecmp(\substr($header, 0, $nameLength + 1), ($name . ':')) === 0) {
				$headerValue = \trim(\substr($header, $nameLength + 1), "\t ");

				if (empty($valuePrefix) || \substr($headerValue, 0, \strlen($valuePrefix)) === $valuePrefix) {
					return $header;
				}
			}
		}

		return null;
	}

	/**
	 * Returns the value of the header with the specified name (and optional value prefix)
	 *
	 * @param string $name the name of the header
	 * @param string $valuePrefix the optional string to match at the beginning of the header's value
	 * @return string|null the value of the header (if found) or `null`
	 */
	public static function getValue($name, $valuePrefix = '') {
		$header = static::get($name, $valuePrefix);

		if (!empty($header)) {
			$nameLength = \strlen($name);
			$headerValue = \substr($header, $nameLength + 1);
			$headerValue = \trim($headerValue, "\t ");

			return $headerValue;
		}
		else {
			return null;
		}
	}

	/**
	 * Sets the header with the specified name and value
	 *
	 * If another header with the same name has already been set previously, that header will be overwritten
	 *
	 * @param string $name the name of the header
	 * @param string $value the corresponding value for the header
	 */
	public static function set($name, $value) {
		\header($name . ': ' . $value, true);
	}

	/**
	 * Adds the header with the specified name and value
	 *
	 * If another header with the same name has already been set previously, both headers (or header values) will be sent
	 *
	 * @param string $name the name of the header
	 * @param string $value the corresponding value for the header
	 */
	public static function add($name, $value) {
		\header($name . ': ' . $value, false);
	}

	/**
	 * Removes the header with the specified name (and optional value prefix)
	 *
	 * @param string $name the name of the header
	 * @param string $valuePrefix the optional string to match at the beginning of the header's value
	 * @return bool whether a header, as specified, has been found and removed
	 */
	public static function remove($name, $valuePrefix = '') {
		return static::take($name, $valuePrefix) !== null;
	}

	/**
	 * Returns and removes the header with the specified name (and optional value prefix)
	 *
	 * @param string $name the name of the header
	 * @param string $valuePrefix the optional string to match at the beginning of the header's value
	 * @return string|null the header (if found) or `null`
	 */
	public static function take($name, $valuePrefix = '') {
		if (empty($name)) {
			return null;
		}

		$nameLength = \strlen($name);
		$headers = \headers_list();

		$first = null;
		$homonyms = [];

		foreach ($headers as $header) {
			if (\strcasecmp(\substr($header, 0, $nameLength + 1), ($name . ':')) === 0) {
				$headerValue = \trim(\substr($header, $nameLength + 1), "\t ");

				if ((empty($valuePrefix) || \substr($headerValue, 0, \strlen($valuePrefix)) === $valuePrefix) && $first === null) {
					$first = $header;
				}
				else {
					$homonyms[] = $header;
				}
			}
		}

		if ($first !== null) {
			\header_remove($name);

			foreach ($homonyms as $homonym) {
				\header($homonym, false);
			}
		}

		return $first;
	}

	/**
	 * Returns the value of and removes the header with the specified name (and optional value prefix)
	 *
	 * @param string $name the name of the header
	 * @param string $valuePrefix the optional string to match at the beginning of the header's value
	 * @return string|null the value of the header (if found) or `null`
	 */
	public static function takeValue($name, $valuePrefix = '') {
		$header = static::take($name, $valuePrefix);

		if (!empty($header)) {
			$nameLength = \strlen($name);
			$headerValue = \substr($header, $nameLength + 1);
			$headerValue = \trim($headerValue, "\t ");

			return $headerValue;
		}
		else {
			return null;
		}
	}

}

/**
 * Session management with improved cookie handling
 *
 * You can start a session using the static method `Session::start(...)` which is compatible to PHP's built-in `session_start()` function
 *
 * Note that sessions must always be started before the HTTP headers are sent to the client, i.e. before the actual output starts
 */
final class Session {

	private function __construct() { }

	/**
	 * Starts or resumes a session in a way compatible to PHP's built-in `session_start()` function
	 *
	 * @param string|null $sameSiteRestriction indicates that the cookie should not be sent along with cross-site requests (either `null`, `None`, `Lax` or `Strict`)
	 */
	public static function start($sameSiteRestriction = Cookie::SAME_SITE_RESTRICTION_LAX) {
		// run PHP's built-in equivalent
		\session_start();

		// intercept the cookie header (if any) and rewrite it
		self::rewriteCookieHeader($sameSiteRestriction);
	}

	/**
	 * Returns or sets the ID of the current session
	 *
	 * In order to change the current session ID, pass the new ID as the only argument to this method
	 *
	 * Please note that there is rarely a need for the version of this method that *updates* the ID
	 *
	 * For most purposes, you may find the method `regenerate` from this same class more helpful
	 *
	 * @param string|null $newId (optional) a new session ID to replace the current session ID
	 * @return string the (old) session ID or an empty string
	 */
	public static function id($newId = null) {
		if ($newId === null) {
			return \session_id();
		}
		else {
			return \session_id($newId);
		}
	}

	/**
	 * Re-generates the session ID in a way compatible to PHP's built-in `session_regenerate_id()` function
	 *
	 * @param bool $deleteOldSession whether to delete the old session or not
	 * @param string|null $sameSiteRestriction indicates that the cookie should not be sent along with cross-site requests (either `null`, `None`, `Lax` or `Strict`)
	 */
	public static function regenerate($deleteOldSession = false, $sameSiteRestriction = Cookie::SAME_SITE_RESTRICTION_LAX) {
		// run PHP's built-in equivalent
		\session_regenerate_id($deleteOldSession);

		// intercept the cookie header (if any) and rewrite it
		self::rewriteCookieHeader($sameSiteRestriction);
	}

	/**
	 * Checks whether a value for the specified key exists in the session
	 *
	 * @param string $key the key to check
	 * @return bool whether there is a value for the specified key or not
	 */
	public static function has($key) {
		return isset($_SESSION[$key]);
	}

	/**
	 * Returns the requested value from the session or, if not found, the specified default value
	 *
	 * @param string $key the key to retrieve the value for
	 * @param mixed $defaultValue the default value to return if the requested value cannot be found
	 * @return mixed the requested value or the default value
	 */
	public static function get($key, $defaultValue = null) {
		if (isset($_SESSION[$key])) {
			return $_SESSION[$key];
		}
		else {
			return $defaultValue;
		}
	}

	/**
	 * Returns the requested value and removes it from the session
	 *
	 * This is identical to calling `get` first and then `remove` for the same key
	 *
	 * @param string $key the key to retrieve and remove the value for
	 * @param mixed $defaultValue the default value to return if the requested value cannot be found
	 * @return mixed the requested value or the default value
	 */
	public static function take($key, $defaultValue = null) {
		if (isset($_SESSION[$key])) {
			$value = $_SESSION[$key];

			unset($_SESSION[$key]);

			return $value;
		}
		else {
			return $defaultValue;
		}
	}

	/**
	 * Sets the value for the specified key to the given value
	 *
	 * Any data that already exists for the specified key will be overwritten
	 *
	 * @param string $key the key to set the value for
	 * @param mixed $value the value to set
	 */
	public static function set($key, $value) {
		$_SESSION[$key] = $value;
	}

	/**
	 * Removes the value for the specified key from the session
	 *
	 * @param string $key the key to remove the value for
	 */
	public static function delete($key) {
		unset($_SESSION[$key]);
	}

	/**
	 * Intercepts and rewrites the session cookie header
	 *
	 * @param string|null $sameSiteRestriction indicates that the cookie should not be sent along with cross-site requests (either `null`, `None`, `Lax` or `Strict`)
	 */
	private static function rewriteCookieHeader($sameSiteRestriction = Cookie::SAME_SITE_RESTRICTION_LAX) {
		// get and remove the original cookie header set by PHP
		$originalCookieHeader = ResponseHeader::take('Set-Cookie', \session_name() . '=');

		// if a cookie header has been found
		if (isset($originalCookieHeader)) {
			// parse it into a cookie instance
			$parsedCookie = Cookie::parse($originalCookieHeader);

			// if the cookie has successfully been parsed
			if (isset($parsedCookie)) {
				// apply the supplied same-site restriction
				$parsedCookie->setSameSiteRestriction($sameSiteRestriction);

				if ($parsedCookie->getSameSiteRestriction() === Cookie::SAME_SITE_RESTRICTION_NONE && !$parsedCookie->isSecureOnly()) {
					\trigger_error('You may have to enable the \'session.cookie_secure\' directive in the configuration in \'php.ini\' or via the \'ini_set\' function', \E_USER_WARNING);
				}

				// save the cookie
				$parsedCookie->save();
			}
		}
	}

}
