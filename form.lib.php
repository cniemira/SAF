<?
$form_error = array();

function form_input ( $name, $type = 'text', $val = null, $opt = array() ) {
	global $app;

	$opts = '';
	foreach ($opt as $k => $v) {
		$opts .= "$k = \"$v\" ";
	}

	$value = is_object($app->_user) && $app->_user->requested($name) ? $app->_user->request($name) : $val;

	echo "<input name =\"$name\" type=\"$type\" value=\"$value\" $opts />";
}


function form_dropdown ( $name, $val, $tag = array(), $sel = null, $opt = array() ) {
	global $app;
	
	$opts = '';
	foreach ($opt as $k => $v) {
		$opts .= "$k=\"$v\" ";
	}

	$sel = (is_object($app->_user) && $app->_user->requested($name)) ? $app->_user->request($name) : $sel;

	echo "<select name=\"$name\" $opts>";
	for ($c=0; $c < count($val); $c++) {
		$label = $tag[$c];
		$value = $val[$c];
		$selected = ($value == $sel) ? 'SELECTED' : null;
		echo "<option value=\"$value\" $selected>$label</option>";
	}
	echo "</select>";
}


function form_radio_set ( $name, $val, $tag = array(), $sel = null, $sep = null, $opt = array() ) {
	global $app;

	$opts = '';
	foreach ($opt as $k => $v) {
		$opts .= "$k=\"$v\" ";
	}

	$sel = (is_object($app->_user) && $app->_user->requested($name)) ? $app->_user->request($name) : $sel;

	for ($c=0; $c < count($val); $c++) {
		$label = $tag[$c];
		$value = $val[$c];
		$selected = ($value == $sel) ? 'CHECKED' : null;
		echo "<input name=\"$name\" type=\"radio\" value=\"$value\" $opts $selected />$label" . $sep;
	}

}


?>
