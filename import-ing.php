<?php

require 'inc.bootstrap.php';

if ( isset($_FILES['csv']) ) {
	header('Content-type: text/plain');

	$data = csv_read_doc(file_get_contents($_FILES['csv']['tmp_name']), true);
// var_export($data);
// exit;

	if ( headers_sent() ) {
		exit('Error # ' . __LINE__);
	}

	// $directions = array('Af' => 'out', 'Bij' => 'in');
	$directions = array('Af' => -1, 'Bij' => 1);
	$types = array('BA' => 'manual', 'GM' => 'atm', 'IC' => 'auto', 'VZ' => 'auto', 'GT' => 'manual', 'OV' => 'manual');

	$records = array_map(function($tr) use ($directions, $types) {
		$dir = $directions[ trim($tr['Af Bij']) ];
		$record = array(
			'date' => substr($tr['Datum'], 0, 4) . '-' . substr($tr['Datum'], 4, 2) . '-' . substr($tr['Datum'], 6, 2),
			'summary' => trim($tr['Naam / Omschrijving']),
			'description' => trim($tr['Mededelingen']),
			// 'direction' => $dir,
			'type' => @$types[ trim($tr['Code']) ],
			'account' => preg_replace('#\s+#', '', trim($tr['Tegenrekening'])) ?: null,
			'amount' => $dir * (float)strtr($tr['Bedrag (EUR)'], array('.' => '', ',' => '.')),
		);
		ksort($record);
		$record['hash'] = md5(serialize($record));
		return $record;
	}, $data);
// print_r($records);
// exit;

	if ( headers_sent() ) {
		exit('Error # ' . __LINE__);
	}

	$existingHashes = $db->select_fields('transactions', 'hash, hash', '1');
// print_r($existingHashes);
// exit;

	$db->begin();
	$inserts = 0;
	foreach ( $records as $record ) {
		if ( !isset($existingHashes[ $record['hash'] ]) ) {
			$db->insert('transactions', $record);
			$inserts++;
		}
	}
	$db->commit();

	var_dump($inserts);

	exit;
}

require 'tpl.header.php';

?>
<form method="post" enctype="multipart/form-data">

	<p>Upload CSV: <input type="file" name="csv" /></p>

	<p><input type="submit" /></p>

</form>
<?php

require 'tpl.footer.php';
