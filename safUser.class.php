<?php

/*
 * SAF - Siege Application Framework
 * 
 * User Class
 *
 * by: CJ Niemira <siege (at) siege (dot) org>
 * (c) 2005
 * http://siege.org/projects/saf
 *
 * This code is licensed under the GNU General Public License
 * http://www.gnu.org/licenses/gpl.html
 */
 
 
class safUser {
	var $IP;
	var $_request;
	
	function safUser () {
		global $app;

		$this->IP = (getenv('HTTP_X_FORWARDED_FOR'))
			? getenv('HTTP_X_FORWARDED_FOR')
			: getenv('REMOTE_ADDR');
		
		#session_set_cookie_params(0, $app->BaseUrl);
		session_start();
	}


	function destroy () {
		session_destroy();
	}


	function req ( $attr, $def = null, $val = '\S+' ) {
		return $this->requested($attr, $val)
			? $this->request($attr)
			: $def;
	}


	function request ( $attr ) {
		if (! isset($this->_request[$attr])) {
			if (isset($_POST[$attr])) {
				$this->_request[$attr] = $this->_sanitize($_POST[$attr]);
			} elseif (isset($_GET[$attr])) {
				$this->_request[$attr] = $this->_sanitize($_GET[$attr]);
			} else {
				$this->_request[$attr] = null;
			}
		}
		
		return $this->_request[$attr];
	}
	
	
	function requested ( $attr, $validator = '\S+' ) {
		$value = $this->request($attr);

		if (is_array($value)) {
			// BREAK!!!
			return true;
		}

		if (is_array($validator))
			return in_array($value, $validator);

		$regex = in_array($validator{0}, array('/', '|')) ? $validator : "/$validator/";

		if ( (! isset($value) || is_null($value) || $value == '' || $value == "\0") &! preg_match($regex, null) )
			return false;
		
		return preg_match($regex, $value) ? true : false;
	}


	function requestsMatch ( $attr1, $attr2 ) {
		$value1 = $this->request($attr1);
		$value2 = $this->request($attr2);

		if (! isset($value1) || is_null($value1) || $value1 == '' || $value1 == "\0") return false;

		return $value1 === $value2;
	}


	function _sanitize ( $string ) {
		if (is_array($string)) {
			 $rv = get_magic_quotes_gpc()
				? array_map(array(&$this, '_sanitize'), $string)
				: array_map('trim', $string);
			 return $rv;
		}

		return get_magic_quotes_gpc()
			? trim(stripslashes($string))
			: trim($string);
	}
}

if ( version_compare(phpversion(), '5.1.0') ) {
	class safUser51 extends safUser {
		function __get( $key ) {
			return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
		}

		function __isset( $key ) {
			return isset($_SESSION[$key]);
		}

		function __set( $key, $val ) {
			$_SESSION[$key] = $val;
		}

		function __unset( $key ) {
			unset($_SESSION[$key]);
		}
	}
}

?>
