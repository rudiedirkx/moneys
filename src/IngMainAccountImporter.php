<?php

namespace rdx\moneys;

class IngMainAccountImporter extends CsvImporter {

	protected $directions = array(
		'Af' => -1,
		'Bij' => 1,
	);

	public function getMandatoryColumns() : array {
		return ['Af Bij', 'Naam / Omschrijving', 'Tegenrekening', 'Bedrag (EUR)'];
	}

	public function getTitle() : string {
		return 'ING main account offical export';
	}

	public function getTypes() : array {
		return array(
			'BA' => 'manual', // Betaalautomaat
			'DV' => '?ing', // Diversen
			'GM' => 'atm', // Geldautomaat
			'GT' => 'manual', // Internetbankieren
			'IC' => 'auto', // Incasso
			'ID' => 'manual', // IDEAL?
			'OV' => 'manual', // Overschrijving
			'PK' => 'manual', // Opname kantoor
			'ST' => 'manual', // Storting
			'VZ' => 'auto', // Verzamelbetaling
		);
	}

	public function extractTransactions( string $filepath ) : array {
		$data = $this->readCsv($filepath);

		$records = array_map(function($tr) {
			$dir = $this->directions[ trim($tr['Af Bij']) ];
			$type = trim(@$tr['Code']);

			$record = array(
				'date' => get_date_from_ymd($tr['Datum']),
				'summary' => trim($tr['Naam / Omschrijving']),
				'description' => trim(@$tr['Mededelingen']),
				'type' => $type,
				'account' => preg_replace('#\s+#', '', trim(@$tr['Tegenrekening'])) ?: null,
				'amount' => $dir * get_amount_from_eu($tr['Bedrag (EUR)']),
			);

			return $record;
		}, $data);

		return $records;
	}

}
