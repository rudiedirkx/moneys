<?php

namespace rdx\moneys;

use DateTime;
use RuntimeException;

class AsnCsvImporter implements Importer {

	public function getTitle() : string {
		return 'ASN CSV export';
	}

	public function getDescription() : string {
		return 'CSV 2004, without column names';
	}

	public function extractTransactions( string $filepath ) : array {
		$data = csv_read_doc(file_get_contents($filepath), false);

		return array_map(function($tr) {
			$description = trim($tr['17']);
			$description = trim(preg_replace('#^' . preg_quote($tr[2], '#') . '-#', '', $description));
			$description = trim(preg_replace('#^' . preg_quote($tr[3], '#') . '-#i', '', $description));
			return [
				'date' => get_date_from_ymd($tr[0]),
				'summary' => trim($tr[3]),
				'description' => $description,
				'type' => 'asn:' . $tr[14],
				'account' => $tr[2],
				'amount' => (float) $tr[10],
			];
		}, $data);
	}

	public function getTypes() : array {
		return [
			'asn:DIV' => '?asn', // Diversen
			'asn:IOB' => 'manual', // Interne Overboeking
			'asn:OVB' => 'manual', // Overboeking
		];
	}

}
