<?php

/*
 * SAF - Siege Application Framework
 * 
 * Digest Authentication Class
 *
 * by: CJ Niemira <siege (at) siege (dot) org>
 * (c) 2006
 * http://siege.org/projects/saf
 *
 * This code is licensed under the GNU General Public License
 * http://www.gnu.org/licenses/gpl.html
 */


class safAuthDigest {
	
	var $Auth_Field;
	var $Auth_Origin;
	var $Auth_Handle;
	var $Auth_User;
	var $Realm;

	function safAuthDigest ($datasource) {
		// 0 => SAF data handle
		// 1 => 'table space name'
		// 2 => "user" equality
		// 3 => "password" equality
		global $app;

		$this->Auth_Field	=  $datasource[3];
		$this->Auth_Origin	=  $datasource[1];
		$this->Auth_Handle	=& $datasource[0];
		$this->Auth_User	=  $datasource[2];

		$this->Realm = $app->BaseName;
	}

	function check () {
		return $this->isValid() ? true : $this->perform();
	}
	
	// Is authentication valid? Is the user logged in to this page?
	function isValid () {
		if (isset($_SESSION['safAuthDigest']['valid']))
			return $_SESSION['safAuthDigest']['valid'];
		
		return false;
	}
	
	function make_password ($user, $pass) {
		return md5(join(array($user, $this->Realm, $pass), ':'));
	}

	// Prompt for credentials
	function perform () {
		global $app;

		$this->terminate();
		$time		= time();

		$headers	= apache_request_headers();
		$noncek		= array(md5(date('Y-m-d H:i', $time) . ':' . $_SERVER['HTTP_USER_AGENT'] . ':safMagicKey'),
					  md5(date('Y-m-d H:i', $time - 60) . ':' . $_SERVER['HTTP_USER_AGENT'] . ':safMagicKey'));
		$opaque		= $app->Version;
		$stale		= false;

		if(isset($headers['Authorization']) && substr($headers['Authorization'],0,7) == 'Digest ') {
			$authtemp = explode(',', substr($headers['Authorization'],strpos($headers['Authorization'],' ')+1));
			$auth = array();

			foreach($authtemp as $key => $value) {
				$value = trim($value);
				if(strpos($value,'=') !== false) {
					$lhs = substr($value,0,strpos($value,'='));
					$rhs = substr($value,strpos($value,'=')+1);
					if(substr($rhs,0,1) == '"' && substr($rhs,-1,1) == '"') {
						$rhs = substr($rhs,1,-1);
					}
					$auth[$lhs] = $rhs;
				}
			}
	
			$requestURI = stripslashes($_SERVER['REQUEST_URI']);
			if(strpos($auth['uri'], '?') === false && strpos($requestURI, '?') !== false) {
				$requestURI = substr($requestURI, 0, strpos($requestURI, '?'));
			}
	
			if (in_array($auth['nonce'], $noncek) && $requestURI == $auth['uri'] && isset($auth['opaque']) && $auth['opaque'] == md5($opaque) && isset($auth['username']) && strlen($auth['username']) > 2) {
				$pwd = $this->Auth_Handle->selectOne(array($this->Auth_Field), $this->Auth_Origin, array($this->Auth_User => $auth['username']));

				$a2unhashed = $_SERVER['REQUEST_METHOD'] . ':' . $auth['uri'];
				$a2 = md5($a2unhashed);
	
				$combined = $pwd . ':' .
							$auth['nonce'] . ':'.
							$auth['nc'] . ':'.
							$auth['cnonce'] . ':'.
							$auth['qop'] . ':'.
							$a2;
				$valid = md5($combined);
		
				if($auth['response'] == $valid) {
					$this->validate($auth['username']);

					$app->debug("Successful authentication", 2);
					return true;
				}
		
			} elseif (! in_array($auth['nonce'], $noncek)) {
				$stale = true;
			}
		}

		header('HTTP/1.0 401 Unauthorized');
		header('WWW-Authenticate: Digest qop="auth", ' .
			   'algorithm=MD5, ' .
			   'domain="' . $_SERVER["HTTP_HOST"] . '", ' .
			   'realm="' . $this->Realm . '", ' .
			   'nonce="' . $noncek[0] . '", ' .
			   'opaque="' . md5($opaque) . '", ' .
			   'stale="' . $stale . '"');

		$app->debug("Not authenticated", 2);
		return false;
	}
	
	// This page is a locked resource, authentication is required to get in.
	function required () {
		if (! $this->check()) {
			exit;
		}
	}
	
	function terminate () {
		$_SESSION['safAuthDigest'] = array();
	}

	function user () {
		return $_SESSION['safAuthDigest']['username'];
	}
	
	function validate ($user) {
		$_SESSION['safAuthDigest'] = array('username' => $user, 'valid' => true);
	}
}

?>
