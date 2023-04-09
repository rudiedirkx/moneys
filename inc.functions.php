<?php

function get_doubles() {
	$undouble = Tag::ensure('undouble');
	return Transaction::query("
		SELECT t.*
		FROM (
			SELECT date, account, amount, COUNT(1) AS num
			FROM transactions
			WHERE ignore = 0
			GROUP BY date, account, amount
			HAVING num > 1
		) x
		JOIN transactions t
			ON (x.date = t.date AND COALESCE(x.account, '') = COALESCE(t.account, '') AND x.amount = t.amount)
		WHERE t.ignore = 0 AND NOT EXISTS (SELECT * FROM tagged WHERE transaction_id = t.id AND tag_id = ?)
		ORDER BY date DESC, account, amount
	", [$undouble]);
}

function make_importer( $config ) {
	if ( is_string($config) ) {
		return new $config();
	}

	if ( is_array($config) && isset($config[0]) ) {
		$class = array_shift($config);
		return new $class(...$config);
	}

	throw new Exception("Invalid importer config: " . var_export($config, true));
}

function do_auth() {
	$ips = MONEYS_LOCAL_IPS;
	$regex = '#^(' . str_replace('.', '\\.', implode('|', $ips)) . ')#';
	if ( preg_match($regex, $_SERVER['REMOTE_ADDR']) ) {
		return true;
	}

	session_start();

	$session_user = trim(@$_SESSION['moneys']['user']);
	$session_pass = trim(@$_SESSION['moneys']['pass']);
	if ( $session_user && $session_pass ) {
		if ( check_auth($session_user, $session_pass) ) {
			return true;
		}
	}

	$login_user = trim(@$_POST['user']);
	$login_pass = trim(@$_POST['pass']);
	if ( $login_user && $login_pass ) {
		$_SESSION['moneys']['user'] = $login_user;
		if (check_auth($login_user, $login_pass)) {
			$_SESSION['moneys']['pass'] = $login_pass;
		}

		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit;
	}

	echo '<p>Only if you know the secret handshake...</p>' . "\n";
	if ( $session_user ) {
		echo '<p style="color: red">Wrong secret handshake!</p>' . "\n";
	}
	echo '<form method="post" action novalidate>' . "\n";
	echo '<p>User: <input type="email" name="user" /></p>' . "\n";
	echo '<p>Pass: <input type="password" name="pass" /></p>' . "\n";
	echo '<p><button>Log in</button></p>' . "\n";
	echo '</form>' . "\n";
	exit;
}

function check_auth($username, &$password) {
	$auths = MONEYS_LOCAL_AUTHS;
	foreach ($auths as $auth) {
		list($check_username, $check_password) = explode(':', $auth);
		if ($check_username == $username) {
			if ($password[0] == '$') {
				if ($check_password == $password) {
					$password = $check_password;
					return true;
				}
			}
			else {
				if (password_verify($password, $check_password)) {
					$password = $check_password;
					return true;
				}
			}
		}
	}
	return false;
}

function do_400() {
	header('HTTP/1.1 400 Error');
}

function do_403() {
	header('HTTP/1.1 403 Access denied');
}

function do_404() {
	header('HTTP/1.1 404 Not found');
}

function get_date_from_ymd( $date ) {
	if ( preg_match('#^(\d\d\d\d)\-?(\d\d)\-?(\d\d)$#', $date, $match) ) {
		return "$match[1]-$match[2]-$match[3]";
	}
	elseif ( preg_match('#^(\d\d)\-(\d\d)\-(\d\d\d\d)$#', $date, $match) ) {
		return "$match[3]-$match[2]-$match[1]";
	}

	throw new InvalidArgumentException("Unknown date format: '$date'");
}

function get_date_from_d_m_y( $date ) {
	return substr($date, 6, 4) . '-' . substr($date, 3, 2) . '-' . substr($date, 0, 2);
}

function get_amount_from_eu( $amount ) {
	return (float) strtr($amount, array('.' => '', ',' => '.'));
}

function get_safe_accountno($account) {
	$account = trim($account);
	if ( !$account ) {
		return '';
	}

	// IBAN, use only the number
	if ( preg_match('#^[a-z]{2}\d{2}[a-z]{4}(\d{10,14})#i', $account, $match) ) {
		return ltrim($match[1], '0');
	}

	// Not IBAN, use all of it, except leading 0s.
	return ltrim($account, '0');
}

function sort_opposite( $column, $current ) {
	// Reverse direction
	if ( $column == ltrim($current, '-') ) {
		return $current[0] == '-' ? $column : '-' . $column;
	}

	// New column, default direction
	return '-' . $column;
}

function html_query( $add ) {
	$q = $add + $_GET;
	return http_build_query($q);
}

function html_money( $amount, $sign = false ) {
	if ( $amount !== null ) {
		$sign = $sign && $amount > 0 ? '+' : '';
		return $sign . number_format((float)$amount, 2);
	}

	return '';
}

function cache_months() {
	static $months;
	if ( !$months ) {
		for ( $i=1; $i<=12; $i++ ) {
			$months[ $i ] = date('M', strtotime('2001-' . substr('0' . $i, -2) . '-01'));
		}
	}
	return $months;
}

function cache_parties() {
	static $parties = false;
	if ( !is_array($parties) ) {
		global $db;
		$parties = $db->select_by_field('parties', 'id', '1')->all();
	}
	return $parties;
}

function do_redirect( $path = false, $query = null ) {
	if ( !$path ) {
		$location = $_SERVER['HTTP_REFERER'];
	}
	else {
		$fragment = '';
		if ( is_int($p = strpos($path, '#')) ) {
			$fragment = substr($path, $p);
			$path = substr($path, 0, $p);
		}

		$query = $query ? '?' . http_build_query($query) : '';
		$location = $path . '.php' . $query . $fragment;
	}

	header('Location: ' . $location);
	exit;
}

function html_options( $options, $selected = null, $empty = '', $datalist = false ) {
	$html = '';
	$empty && $html .= '<option value="">' . $empty . '</option>';
	foreach ( $options AS $value => $label ) {
		$isSelected = $value == $selected ? ' selected' : '';
		$value = $datalist ? html($label) : html($value);
		$label = $datalist ? '' : html($label);
		$html .= '<option value="' . $value . '"' . $isSelected . '>' . $label . '</option>';
	}
	return $html;
}

function html($str) {
	return htmlspecialchars($str ?? '', ENT_COMPAT, 'UTF-8');
}

function csv_read_doc( $data, $withHeader = true, $keepCols = array() ) {
	$keepCols and $keepCols = array_flip($keepCols);

	if ( substr($data, 0, 3) === chr(0xEF) . chr(0xBB) . chr(0xBF) ) {
		$data = substr($data, 3);
	}

	$header = array();
	$delim = ',';
	$csv = array_map(function($line) use (&$delim, &$header, $withHeader, $keepCols) {
		$data = str_getcsv(trim($line), $delim, '"', '"');
		if (count($data) == 1 && strpos($data[0], ';') !== false) {
			$delim = ';';
			$data = str_getcsv(trim($line), $delim, '"', '"');
		}

		if ( $withHeader ) {
			if ( $header ) {
				$data = array_combine($header, $data);
				$keepCols and $data = array_intersect_key($data, $keepCols);
			}
			else {
				$header = $data;
			}
		}
		return $data;
	}, explode("\n", trim($data)));
	$withHeader and $csv = array_slice($csv, 1);
	return $csv;
}

function csv_escape( $val ) {
	return str_replace('"', '""', $val);
}

function csv_row( $data ) {
	return '"' . implode('","', array_map('csv_escape', $data)) . '"' . "\r\n";
}

function csv_cols( $data ) {
	$cols = array();
	foreach ( $data as $i => $name ) {
		$cols[] = !is_int($i) && is_callable($name) ? $i : $name;
	}
	return $cols;
}

function csv_rows( $data ) {
	return implode(array_map('csv_row', $data));
}

function csv_header( $filename = '' ) {
	header('Content-Type: text/plain; charset=utf-8');

	if ( $filename ) {
		header('Content-Disposition: attachment; filename="' . $filename . '"');
	}
}

function csv_file( $data, $cols, $filename = '' ) {
	csv_header($filename);

	echo csv_row(csv_cols($cols));
	foreach ( $data AS $row ) {
		$data = array();
		foreach ( $cols as $i => $name ) {
			$data[] = !is_int($i) && is_callable($name) ? $name($row) : $row->$name;
		}
		echo csv_row($data);
	}

	if ( $filename ) {
		exit;
	}
}
