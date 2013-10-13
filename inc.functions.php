<?php

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

function html_options( $options, $selected = null, $empty = '' ) {
	$html = '';
	$empty && $html .= '<option value>' . $empty;
	foreach ( $options AS $value => $label ) {
		$isSelected = $value == $selected ? ' selected' : '';
		$html .= '<option value="' . html($value) . '"' . $isSelected . '>' . html($label);
	}
	return $html;
}

function html($str) {
	return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
}

function csv_read_doc( $data, $withHeader = true, $keepCols = array() ) {
	$keepCols and $keepCols = array_flip($keepCols);

	$header = array();
	$csv = array_map(function($line) use (&$header, $withHeader, $keepCols) {
		$data = str_getcsv(trim($line), ',', '"', '"');
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