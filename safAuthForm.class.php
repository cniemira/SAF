<?php

/*
 * SAF - Siege Application Framework
 * 
 * HTTP Form-based Digest Authentication Class
 *
 * by: CJ Niemira <siege (at) siege (dot) org>
 * (c) 2006
 * http://siege.org/projects/saf
 *
 * This code is licensed under the GNU General Public License
 * http://www.gnu.org/licenses/gpl.html
 */


class safAuthForm {
	
	var $Auth_Field;
	var $Auth_Origin;
	var $Auth_Handle;
	var $Auth_User;
	var $Realm;

	function safAuthForm ($datasource) {
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


	function formDisplay () {
		printf('%s<form id="safAuthForm" method="POST"><div><label for="safAuthFormName">Username:</label>%s</div><div><label for="safAuthFormPassword">Password:</label>%s %s</div></form>',
			$this->formScript(),
			$this->FormFieldName(),
			$this->FormFieldPassword(),
			$this->FormFieldSubmit()
		);
	}


	function formFieldName () {
		global $app;
		$user = $app->_user;

		return sprintf('<input type="text" id="safAuthFormName" name="safAuthFormName" value="%s" />', $user->requested('safAuthFormName') ? $user->request('safAuthFormName') : '');
	}


	function formFieldPassword () {
		return sprintf('<input type="password" id="safAuthFormPassword" name="safAuthFormPassword" value="" /><input type="hidden" id="safAuthFormNc" name="safAuthFormNc" value="" /><input type="hidden" id="safAuthFormQop" name="safAuthFormQop" value="" /><input type="hidden" id="safAuthFormCnonce" name="safAuthFormCnonce" value="" /><input type="hidden" id="safAuthFormNonce" name="safAuthFormNonce" value="" /><input type="hidden" id="safAuthFormNoncek" name="safAuthFormNoncek" value="" /><input type="hidden" id="safAuthFormOpaque" name="safAuthFormOpaque" value="" /><input type="hidden" id="safAuthFormURI" name="safAuthFormURI" value="%s" />', $_SERVER["SCRIPT_NAME"]);
	}


	function formFieldSubmit () {
		global $app;

		$noncek = md5(date('Y-m-d H:i', time()) . ':' . $_SERVER['HTTP_USER_AGENT'] . ':safMagicKey');

		return sprintf('<button id="safAuthFormButton" onClick="safAuthFormSubmit(\'%s\',\'%s\',\'%s\');">Login</button>', $this->Realm, $noncek, md5($app->Version));
	}


	function formScript () {
		$saflib = file_get_contents(dirname(__FILE__) . '/extra/safauthform.js');
		return sprintf("<script language='JavaScript'>%s</script>", $saflib);
	}


	function isValid () {
		if (isset($_SESSION['safAuthForm']['valid']))
			return $_SESSION['safAuthForm']['valid'];
		
		return false;
	}
	

	function make_password ($user, $pass) {
		return md5(join(array($user, $this->Realm, $pass), ':'));
	}


	function perform () {
		global $app;

		$this->terminate();
		$time = time();
		$noncek = array(md5(date('Y-m-d H:i', $time) . ':' . $_SERVER['HTTP_USER_AGENT'] . ':safMagicKey'),md5(date('Y-m-d H:i', $time - 60) . ':' . $_SERVER['HTTP_USER_AGENT'] . ':safMagicKey'));
		$opaque = md5($app->Version);

		if(isset($_POST['safAuthFormName']) && isset($_POST['safAuthFormPassword']) && isset($_POST['safAuthFormNc']) && isset($_POST['safAuthFormQop']) && isset($_POST['safAuthFormCnonce']) && isset($_POST['safAuthFormNonce']) && isset($_POST['safAuthFormNoncek']) && isset($_POST['safAuthFormOpaque']) && isset($_POST['safAuthFormURI'])) {

			$requestURI = stripslashes($_SERVER['REQUEST_URI']);
			if ($_POST['safAuthFormURI'] != $requestURI) {
				$app->debug('authform url mismatch', 1);
				return false;
			}

			if (!in_array($_POST['safAuthFormNoncek'], $noncek)) {
				$app->debug('authform noncek mismatch', 1);
				return false;
			}

			if ($_POST['safAuthFormOpaque'] != $opaque) {
				$app->debug('authform opaque mismatch', 1);
				return false;
			}

			$pwd = $this->Auth_Handle->selectOne(array($this->Auth_Field), $this->Auth_Origin, array($this->Auth_User => $_POST['safAuthFormName']));

			$a2 = md5($_SERVER['REQUEST_METHOD'] . ':' . $requestURI);

			$valid = md5(join(':', array($pwd, $_POST['safAuthFormNonce'], $_POST['safAuthFormNc'], $_POST['safAuthFormCnonce'], $_POST['safAuthFormQop'], $a2)));

			if ($_POST['safAuthFormPassword'] == $valid) {
				$this->validate($_POST['safAuthFormName']);
				$app->debug('User logged in: ' . $_POST['safAuthFormName'], 2);
				return true;
			}

			$app->debug('Login failed', 3);
		}

		return false;
	}
	

	function required () {
		global $app;

		if (! $this->check()) {
			$this->formDisplay();

			$app->_page->Title = 'Login';
			$app->_page->applyTemplate();
			exit;
		}
	}
	

	function terminate () {
		$_SESSION['safAuthForm'] = array();
	}


	function user () {
		return $_SESSION['safAuthForm']['username'];
	}
	

	function validate ($user) {
		$_SESSION['safAuthForm'] = array('username' => $user, 'valid' => true);
	}
}

?>
