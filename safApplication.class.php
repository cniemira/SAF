<?php

/*
 * SAF - Siege Application Framework
 * 
 * Application Class
 *
 * by: CJ Niemira <siege (at) siege (dot) org>
 * (c) 2005
 * http://siege.org/projects/saf
 *
 * This code is licensed under the GNU General Public License
 * http://www.gnu.org/licenses/gpl.html
 */


class safApplication {
	var $BaseName;
	var $BasePath;
	var $BaseUrl;
	var $DebugLog;
	var $Version = '3.0a';
	
	var $_breaks = 0;
	var $_errors = array();
	var $_globals = array();
	var $_initialized = false;

	var $_auth;
	var $_data;
	var $_page;
	var $_user;
	
	
	function safApplication () {
	}


	function _initialize () {
		if ($this->_initialized === true) {
			return;
		}

		if (! isset($this->BaseName))
			$this->BaseName = 'safApplication';
		
		if (! isset($this->BasePath))
			$this->BasePath = './';

		if (! isset($this->BaseUrl))
			$this->BaseUrl = sprintf('%s://%s%s',
				(array_key_exists('HTTPS', $_SERVER) && $_SERVER["HTTPS"] == 'on')
					? 'https' : 'http',
				$_SERVER['SERVER_NAME'],
				preg_replace('/[^\/]*$/', '', $_SERVER["REQUEST_URI"])
			);

		if (! isset($this->DebugLog))
			$this->DebugLog = '/tmp/' . $this->BaseName . '.debug.log';
		
		$this->_initialized = true;
		$this->debug("Date: " . date('Ymd H:i:s'), 2);
		$this->debug("URI: " . $_SERVER['REQUEST_URI'], 2);
	}
	
	
	function _break ( $message ) {
		$bt = debug_backtrace();
		$this->_breaks++;
		
		$msg = join(': ', array($this->_breaks, $bt[1]['class'], $bt[1]['function'], $message));
		
		error_log("$msg\n", 3, $this->DebugLog);
	}
	

	function alert ( $message ) {
		$this->debug($message, 4);
	}


	function debug ( $message, $level = 6 ) {
		$this->_initialize();

		if (! isset($this->DebugLevel))
			return;

		if ($level >= $this->DebugLevel) {
			if (! is_string($message)) {
				ob_start();
				var_dump($message);
				$message = ob_get_clean();
			}
			error_log("$level: $message\n", 3, $this->DebugLog);
		}
	}


	function error ( $message ) {
		$this->debug($message, 5);
		array_push($this->_errors, $message);
	}


	function errored () {
		return (sizeof($this->_errors) > 0) ? true : false;
	}


	function errors () {
		return $this->_errors;
	}


	function fail ( $message ) {
		global $app;

		while (ob_get_level()) {
			$app->debug('Purged an output buffer.', 1);
			ob_end_clean();
		}
		
		$app->debug($message, 5);
		header("HTTP/1.1 500 Internal Server Error");
		echo "<html><title>Application Error</title><body>$message</body></html>";
		exit;
	}
	
	
	function info ( $message ) {
		$this->debug($message, 2);
	}


	function loadLib ( $name ) {
		require_once( $name . '.lib.php' );
	}
	
	
	function &makeAuth ( $type, $args ) {
		$this->_initialize();
		
		$this->debug("Making auth.", 1);
		require_once('safAuth' . ucfirst($type) . '.class.php');
			
		eval( "\$auth = new saf" . 'Auth' . ucfirst($type) . "(\$args);" );

		return $auth;
	}
	
	
	function &makeData ( $type, $args ) {
		$this->_initialize();
		
		$this->debug("Making data.", 1);
		require_once('saf' . ucfirst($type) . '.class.php');
			
		eval( "\$data = new saf" . ucfirst($type) . "(\$args);" );

		return $data;
	}

	
	function notice ( $message ) {
		$this->debug($message, 1);
	}


	function setupAuth ( $type, $args, $name = 'auth' ) {
		if (! isset($this->_auth)) {
			$this->_initialize();
			
			$this->debug("Setting up auth.", 1);

			global $$name;
			$$name = $this->makeAuth($type, $args);

			array_push($this->_globals, 'auth');
			$this->_auth =& $$name;
		}
	}

	
	function setupData ( $type, $args, $name = 'data' ) {
		if (! isset($this->_data)) {
			$this->_initialize();
			
			$this->debug("Setting up data.", 1);

			global $$name;
			$$name = $this->makeData($type, $args);

			array_push($this->_globals, 'data');
			$this->_data =& $$name;
		}
	}

	
	function setupPage ( $name = 'page' ) {
		if (! isset($this->_page)) {
			$this->_initialize();
			
			$this->debug("Setting up page: $$name", 1);
			require_once('safPage.class.php');
			
			global $$name;
			$$name = new safPage();

			array_push($this->_globals, 'page');
			$this->_page =& $$name;
		}
	}

	
	function setupUser ( $name = 'user' ) {
		if (! isset($this->_user)) {
			$this->_initialize();
			
			$this->debug("Setting up user.", 1);
			require_once('safUser.class.php');

			global $$name;
			
			if ( class_exists('safUser51') ) {
				$$name = new safUser51();

			} else {
				$$name = new safUser();
			}

			array_push($this->_globals, 'user');
			$this->_user =& $$name;
		}
	}
	

	function signature () {
		return "Siege Application Framework (SAF)/" . $this->Version;
	}


	function warn ( $message ) {
		$this->debug($message, 3);
	}
}

?>
