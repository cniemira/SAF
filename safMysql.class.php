<?php

/*
 * SAF - Siege Application Framework
 * 
 * Mysql Class
 *
 * by: CJ Niemira <siege (at) siege (dot) org>
 * (c) 2005
 * http://siege.org/projects/saf
 *
 * This code is licensed under the GNU General Public License
 * http://www.gnu.org/licenses/gpl.html
 */


class safMysql {
	
	var $_dbh;
	
	var $_host;
	var $_name;
	var $_user;
	var $_pass;
	
	var $_initialized;

	function safMysql ($args) {
		global $app;

		$this->_pass = sizeof($args) > 0 ? $args[0] : $app->BaseName;
		$this->_user = sizeof($args) > 1 ? $args[1] : $app->BaseName;
		$this->_name = sizeof($args) > 2 ? $args[2] : $app->BaseName;
		$this->_host = sizeof($args) > 3 ? $args[3] : 'localhost';
	}


	function _initialize () {
		global $app;
		
		if (isset($this->_initialized)) {
			return;
		}
		
		$app->debug("Initializing MySQL connection.", 1);
		
		if (! function_exists('mysql_connect')) {
			@dl('mysql.so');
		}

		$this->_dbh = @mysql_connect($this->_host, $this->_user, $this->_pass);
		
		if (! $this->_dbh) {
			$app->debug(mysql_error(), 4);
			$app->fail("Database connection failure.");
		}
		
		$sel = @mysql_select_db($this->_name, $this->_dbh);
		
		if (! $sel) {
			$app->debug(mysql_error(), 4);
			$app->fail("Database selection failure.");
		}
		
		$app->debug("MySQL connection initialized.", 1);
		$this->_initialized = true;
	}


	function delete ( $sql, $table = null ) {
		$this->_initialize();

		if (is_array($sql) && ! is_null($table)) {
			$sql = "DELETE FROM `$table` WHERE " . $this->make_clause($sql);

		} elseif (is_array($sql)) {
			return -2;
		}

		return $this->query($sql) ? mysql_affected_rows() : -1;
	}
	

	function file ( $file ) {
		global $app;

		$sql = file_get_contents($file);
		if (strlen($sql) < 1)
			return -2;

		$rows = 0;
		foreach (explode(";\n", $sql) as $statement) {
			$this->query($statement);
			$rows += mysql_affected_rows();
		}

		return $rows;
	}


	function insert ( $sql, $table = null ) {
		$this->_initialize();

		if (is_array($sql) && ! is_null($table)) {
			$set = array_map(array(&$this, 'prep_quote'), $sql);
			$keys = array_map(array(&$this, 'prep_backquote'), array_keys($sql));
			$sql = "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', array_values($set)) . ")";
		} elseif (is_array($sql)) {
			return -2;
		}

		return $this->query($sql) ? mysql_insert_id() : -1;
	}

	function make_clause ( $where ) {
		$clause = array();
		foreach ($where as $k => $v) {
			$kq = false;
			if (strpos($k, '.') === false) {
				$k = sprintf('`%s`', $k);
				$kq = true;
			}

			if ($v === 'IS NOT NULL' || in_array(substr($v, 0, 1), array('<', '>', '=')) || substr($v, 0, 4) == 'LIKE') {
				$clause[] = "$k $v";
			} else {
				if ((strpos($v, '.') === false &! is_numeric($v)) || $kq === true)
					$v = sprintf("'%s'", $v);

				$clause[] = "$k = $v";
			}
		}
		return implode(' AND ', $clause);
	}

	function make_col ( $cols ) {
		$clause = array();

		if (! is_array($cols))
			return strpos($cols, '.') === false ? sprintf("`%s`", $cols) : $cols;

		foreach ($cols as $col)
			$clause[] = strpos($col, '.') === false ? sprintf("`%s`", $col) : $col;

		return implode(', ', $clause);
	}

	function make_limit ( $pos ) {
		if (! is_array($pos) || count($pos) != 2)
			return false;

		if (! is_numeric($pos[0]) || ! is_numeric($pos[1]))
			return false;

		return " LIMIT " . $pos[0] . ", " . $pos[1];
	}

	function make_order ( $keys ) {
		if (! is_array($keys))
			$keys = array($keys);

		$order = 'ASC';
		if (preg_match('/^[A-Z]{3,5}$/', $keys[0]))
			$order = array_shift($keys);

		$clause = array();
		foreach ($keys as $key) {
			if (preg_match('/^[\w\.]+$/', $key))
				$clause[] = $key;
		}

		return sizeof($clause) ? " ORDER BY " . implode(', ', $clause) . " $order" : null;
	}

	function prep ( $val ) {
		$this->_initialize();
		
		return mysql_real_escape_string(addslashes($val));
	}


	function prep_backquote ( $val ) {
		$this->_initialize();
		return sprintf('`%s`', $val);
	}


	function prep_quote ( $val ) {
		$this->_initialize();
		return (is_numeric($val)) ? $val : "'" . mysql_real_escape_string(addslashes($val)) . "'";
	}


	function scrub ( $attr ) {
		global $app;
		$this->_initialize();
		
		return $this->prep($app->_user->request($attr));
	}
	
	
	// This function is used internally ONLY
	function select ( $sql, $table, $where, $order = null, $limit = null ) {
		global $app;

		if (is_array($sql)) {
			foreach ($sql as $v) $sel[] = ($v == '*') ? $v : "`$v`";
			if (is_array($table))
				$table = implode($table, '`,`');

			$sql = "SELECT " . implode(', ', $sel) . " FROM `" . $table . "` ";

			if (is_array($where)) {
				$sql .= " WHERE " . $this->make_clause($where);
			} elseif (is_numeric($where)) {
				$sql .= " WHERE `id` = $where";
			}

			if (!is_null($order))
				$sql .= $this->make_order($order);

			if (!is_null($limit))
				$sql .= $this->make_limit($limit);
		}
	
		return $this->query($sql);
	}


	function selectArray ( $sql, $table = null, $where = null, $order = 'id', $limit = null ) {
		$this->_initialize();

		$array = array();
		
		if ($result = $this->select($sql, $table, $where, $order, $limit)) {
			while ($row = mysql_fetch_assoc($result)) {
				$array[] = array_map('stripslashes', $row);
			}
		}
		
		return $array;
	}


	function selectCount ( $col, $table, $where ) {
		if (is_array($table))
			$table = implode($table, '`,`');

		$sql = "SELECT COUNT(`" . $col . "`) FROM `" . $table . "` ";

		if (is_array($where)) {
			$sql .= " WHERE " . $this->make_clause($where);
		} elseif (is_numeric($where)) {
			$sql .= " WHERE `id` = $where";
		}

		return ($result = $this->query($sql)) ? array_shift(mysql_fetch_row($result)) : 0;
	}


	function selectList ( $col, $table, $where, $order = null, $limit = null ) {
		$this->_initialize();

		if (is_array($table))
			$table = implode($table, '`,`');

		$sql = "SELECT " . $this->make_col($col) . " FROM `" . $table . "`";

		if (is_array($where)) {
			$sql .= " WHERE " . $this->make_clause($where);
		} elseif (is_numeric($where)) {
			$sql .= " WHERE `id` = $where";
		}

		if (!is_null($order))
			$sql .= $this->make_order($order);

		if (!is_null($limit))
			$sql .= $this->make_limit($limit);

		$array = array();
		
		if ($result = $this->query($sql)) {
			while ($row = mysql_fetch_assoc($result))
				$array[] = array_shift($row);
		}
		
		return $array;
	}


	function selectOne ( $sql, $table = null, $where = null ) {
		$this->_initialize();

		$array = ($result = $this->select($sql, $table, $where)) ? mysql_fetch_array($result, MYSQL_NUM) : array();

		return (count($array) > 0) ? stripslashes(array_shift($array)) : null;
	}
	
	
	function selectOneRow ( $sql, $table = null, $where = null ) {
		$this->_initialize();

		$array = ($result = $this->select($sql, $table, $where)) ? mysql_fetch_array($result, MYSQL_ASSOC) : array();
		array_map('stripslashes', $array);

		return $array;
	}
	
	
	function query ($sql) {
		global $app;
		$this->_initialize();

		$app->debug("SQL query: $sql", 2);
		$result = mysql_query($sql);

		if (! $result) {
			$app->debug(mysql_error(), 4);
			return false;
		}

		if (preg_match('/^SELECT/i', $sql) && mysql_num_rows($result) == 0) {
			$app->debug("Empty query result.", 4);
			return false;

		} elseif (preg_match('/^DELETE|INSERT|UPDATE/i', $sql) && mysql_affected_rows() == 0) {
			$app->debug("No rows affected.", 3);
			return false;
		}

		return $result;
	}


	function update ( $sql, $table = null, $where = null ) {
		$this->_initialize();
		
		if (is_array($sql) && ! is_null($table)) {
			foreach (array_map(array(&$this, 'prep_quote'), $sql) as $k => $v) $set[] = " `$k` = $v";
			$sql = "UPDATE `$table` SET " . implode(', ', $set);

			if (is_array($where)) {
				$sql .= " WHERE " . $this->make_clause($where);
			} elseif (is_numeric($where)) {
				$sql .= " WHERE id = $where";
			} else {
				return -2;
			}

		} elseif (is_array($sql)) {
			return -2;
		}
	
		return $this->query($sql) ? mysql_affected_rows() : -1;
	}
}
?>
