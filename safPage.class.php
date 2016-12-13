<?php

/*
 * SAF - Siege Application Framework
 * 
 * Page Class
 *
 * by: CJ Niemira <siege (at) siege (dot) org>
 * (c) 2005
 * http://siege.org/projects/saf
 *
 * This code is licensed under the GNU General Public License
 * http://www.gnu.org/licenses/gpl.html
 */
 
 
class safPage {
	
	var $_app = null;
	var $_componentArgs = array();
	var $_errors = array();
	var $Body;
	var $Charset;
	var $ComponentDebug = false;
	var $ComponentDebugSkip = array();
	var $ComponentPath;
	var $Title;
	var $Template;
	var $TemplatePath;
	var $Type;

	function safPage () {
		global $app;

		$this->_app = $app;

		ob_start(array(&$this, 'obWrapper'));

		$this->Charset = 'ISO-8859-1';
		$this->ComponentPath = $app->BasePath . '_components/';
		$this->TemplatePath = $app->BasePath . '_templates/';
		$this->Type = 'xhtml';
	}
    
    
	function applyTemplate ( $template = null ) {
		global $app;
		$this->Body = ob_get_clean();

		ob_start(array(&$this, 'obWrapper'));
		foreach ($app->_globals as $obj)
			eval(sprintf('$%s = $app->_%s;', $obj, $obj));

		if (is_null($template) && isset($this->Template)) {
			$template = $this->Template;

		} elseif (is_null($template)) {
			$app->Error('No template to apply.');
			return;
		}

		if (substr($template, -4) != '.php')
			$template .= '.php';

		$abs_path = $this->TemplatePath . $template;
		if (@include($abs_path)) {
			$app->debug("Applied template: $abs_path", 1);

		} else {
			$app->Error("Missing template $abs_path");
		}

		ob_end_flush();
		exit();
	}
   
   
	function component ( $component, $args = null ) {
		echo $this->subComponent($component, $args);
	}


	function dump ( $var ) {
		ob_start();
		var_dump($var);
		$message = ob_get_clean();

		print "<pre>$message</pre>";
		$app->debug($message);
	}
	
	
	function error ( $key = null, $message = null ) {
		if (is_null($key)) {
			return sizeof($this->_errors) > 0 ? true : false;
		}
		
		if (is_null($message)) {
			return array_key_exists($key, $this->_errors);
		} else {
			$this->_errors[$key] = $message;
		}
	}


	function fail ( $code = 500, $message = null ) {
		global $app;

		$status = constant("HTTP_STATUS_$code");
		$message = empty($message) ? $status : $message;

		while (ob_get_level()) {
			$app->debug('Purged an output buffer.', 1);
			ob_end_clean();
		}

		$app->debug($message, 5);
		header("HTTP/1.1 $code $status");
		echo "<html><title>Page Error</title><body>$message</body></html>";
		exit;
	}


	function go ( $location ) {
		@ob_end_clean();
		@ob_end_clean();
		
		header("Location: $location");
		echo "<html><body>Go to: <a href=\"$location\">$location</a></body></html>";
		exit;
	}


	function lipsum ( $length = null ) {
		if (is_null($length))
			$length = rand(1,5);

		$l = array();
		$l = array('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.','Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.','Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.','Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.','Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.','Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.','Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.','Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur?','Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?','At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga.','Et harum quidem rerum facilis est et expedita distinctio.','Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus.','Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae.','Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.');

		$r = '';
		for ($i=0; $i<$length; $i++) {
			$s = rand(4,9);
			$r .= '<p>';
			for ($j=0; $j<$s; $j++)
				$r .= $l[rand(1,sizeof($l))] . ' ';
			$r .= '</p>';
		}
		return $r;
	}


	function noCache () {
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Pragma: no-cache");
	}


	function obWrapper ( $buffer ) {
		# php 5.2.0 seems to not like globals in the ob callback
		if (version_compare(phpversion(), '5.2.0', 'lt')) {
			global $app;
		} else {
			$app = $this->_app;
		}
		
		$app->debug("obWrap type: " . $app->_page->Type, 1);
		
		switch ($app->_page->Type) {
			case 'blank':
				$output = null;
				break;

			case 'frame':
				header('Content-Type: text/html; charset=' . $app->_page->Charset);
		   		$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Frameset//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd\">\n\n$buffer";
				break;

			case 'html':
				header('Content-Type: text/html; charset=' . $app->_page->Charset);
		   		$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n\n$buffer";
				break;

			case 'pre':
				header('Content-Type: text/html; charset=' . $app->_page->Charset);
		   		$output = "<html>\n<body><pre>\n$buffer\n</pre></body>\n</html>";
				break;

			case 'raw':
				$output = $buffer;
				break;

			case 'simple':
				header('Content-Type: text/html; charset=' . $app->_page->Charset);
		   		$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\" \"http://www.w3.org/TR/REC-html40/loose.dtd\">\n\n<html>\n<head>\n<title>" . $app->_page->Title . "</title>\n" . $app->_page->Header . "\n</head>\n<body>\n$buffer\n</body>\n</html>";
				break;

			case 'strict':
				header('Content-Type: text/html; charset=' . $app->_page->Charset);
		   		$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n\n$buffer";
				break;

			case 'xhtml':
				header('Content-Type: text/html; charset=' . $app->_page->Charset);
		   		$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n\n$buffer";
				break;

			default:
				$app->debug('Sending page as ' . $app->_page->Type, 2);
				header('Content-Type: ' . $app->_page->Type);
				$output = $buffer;
		}

		header('Content-Length: ' . strlen($output) );
		return $output;
   }

	function subComponent ( $component, $arg = null ) {
		if (is_array($arg)) {
			foreach(array_keys($arg) as $key) {
				$$key = $arg[$key];
				$this->_componentArgs[$key] = $arg[$key];
			}

		} elseif (is_object($arg)) {
			foreach(get_object_vars($arg) as $key => $val) {
				$$key = $val;
				$this->_componentArgs[$key] = $val;
			}

		} elseif (is_string($arg)) {
			$id = $arg;

		} elseif (is_null($arg) && sizeof($this->_componentArgs)) {
			foreach($this->_componentArgs as $key => $val)
				$$key = $val;
		}

		global $app;
		
		ob_start();
		$safAuth = $app->_auth;
		$safData = $app->_data;
		$safPage = $app->_page;
		$safUser = $app->_user;

		$abs_path = $this->ComponentPath . $component;
		if (@include($abs_path)) {
			$app->debug("Added component: $abs_path", 1);

		} else {
			$app->Error("Missing component $abs_path");
		}

		$this->_componentArgs = array();

		if ($this->ComponentDebug && ! in_array($component, $this->ComponentDebugSkip)) {
			return "<fieldset><legend>" . $component . "</legend>" . ob_get_clean() . "</fieldset>";
		} else {
			return ob_get_clean();
		}
	}
    
}
?>
