<?php

namespace rdx\moneys;

class IngCreditcardImporter extends CsvImporter {

	public function getMandatoryColumns() {
		return ["Date", "Description", "Amount"];
	}

	public function getTitle() {
		return 'ING custom creditcard export';
	}

	public function extractTransactions( $filepath ) {
		$data = $this->readCsv($filepath);

		$records = array_map(function($tr) {
			$record = array(
				'date' => get_date_from_ymd($tr['Date']),
				'summary' => trim($tr['Description']),
				'description' => '',
				'type' => 'cc',
				'account' => null,
				'amount' => $tr['Amount'],
				// 'account_id' => $account ? $account->id : null,
				// 'batch' => $batch,
			);

			// $record['hash'] = get_transaction_hash($record);

			return $record;
		}, $data);

		return $records;
	}

}
