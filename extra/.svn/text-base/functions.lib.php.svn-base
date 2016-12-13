<?
function array_remove ( $string, $array ) {
	while (($key = array_search($string, $array)) !== false)
		unset($array[$key]);
	return $array;
}

function create_password ( $length = 8, $mixCase = 0 ) {
	for ($i=1; $i<=$length; $i++) {
		$n = rand(0,36);
		$rv .= $n > 9 ? chr($n + 55) : $n;
	}
	return $rv;
}
?>
