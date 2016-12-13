<?
/*
 * This library contains 'forward compatability' functions for PHP < 5.0.0
 * to that version.
 */

if (! function_exists('array_combine')) {
function array_combine($keys, $values) {
	$array = array();
	foreach($keys as $key)
		$array[$key] = array_shift($values);
	return $array;
} }


// Idea borrowed from "egingell at sisna dot com" c/o php.net
if (! function_exists('file_put_contents')) {
define('FILE_APPEND', 1);
function file_put_contents($string, $data, $flag = false) {
	$mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND')
		? 'a'
		: 'w';

	$fh = @fopen($string, $mode);

	if ($fh === false) {
		return 0;

	} else {
		$len = fwrite($fh, is_array($data) ? implode($data) : $data);
		fclose($fh);

		return $len;
	}
} }

?>
