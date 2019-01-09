<?php

namespace rdx\moneys;

class IngMainAccountImporter extends CsvImporter {

	protected $directions = array(
		'Af' => -1,
		'Bij' => 1,
	);
	protected $types = array(
		'BA' => 'manual',
		'GM' => 'atm',
		'IC' => 'auto',
		'VZ' => 'auto',
		'GT' => 'manual',
		'OV' => 'manual',
	);

	public function getMandatoryColumns() {
		return ['Af Bij', 'Naam / Omschrijving', 'Tegenrekening', 'Bedrag (EUR)'];
	}

	public function getTitle() {
		return 'ING main account offical export';
	}

	public function extractTransactions( $filepath ) {
		$data = $this->readCsv($filepath);

		$records = array_map(function($tr) {
			$dir = $this->directions[ trim($tr['Af Bij']) ];
			$type = trim(@$tr['Code']);

			$record = array(
				'date' => get_date_from_ymd($tr['Datum']),
				'summary' => trim($tr['Naam / Omschrijving']),
				'description' => trim(@$tr['Mededelingen']),
				'type' => @$this->types[$type] ?: $type,
				'account' => preg_replace('#\s+#', '', trim(@$tr['Tegenrekening'])) ?: null,
				'amount' => $dir * get_amount_from_eu($tr['Bedrag (EUR)']),
			);

			return $record;
		}, $data);

		return $records;
	}

}
