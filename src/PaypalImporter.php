<?php

namespace rdx\moneys;

use DateTime;
use RuntimeException;

class PaypalImporter extends CsvImporter {

	protected $currency;

	public function __construct( $targetCurrency ) {
		$this->currency = $targetCurrency;
	}

	public function getMandatoryColumns() : array {
		return ["Date", "Time", "TimeZone", "Name", "Type", "Currency", "Gross", "From Email Address", "To Email Address", "Item Title", "Subject"];
	}

	public function getTitle() : string {
		return "Paypal 'Completed payments' export";
	}

	public function getTypes() : array {
		return [];
	}

	public function extractTransactions( string $filepath ) : array {
		$data = $this->readCsv($filepath);

		$data = $this->appendUtc($data);

		$rows = array_values($this->filterHoldsAndAuths($this->filterFromTo($data)));

		$records = array_map(function(array $row) use ($data) {
			$amount = $this->getAmount($row, $data);
			return [
				'date' => date('Y-m-d', $row['_utc']),
				'summary' => trim($row['Name']),
				'description' => implode("\n", array_filter(array_unique([$row['Item Title'], $row['Subject']]))),
				'type' => 'paypal',
				'account' => $amount > 0 ? $row['From Email Address'] : $row['To Email Address'],
				'amount' => $amount,
			];
		}, $rows);

		return $records;
	}

	protected function getAmount( array $row, array $rows ) {
		if ( $row['Currency'] === $this->currency ) {
			return (float) $row['Gross'];
		}

		$convRows = array_values(array_filter($rows, function(array $convRow) use ($row) {
			return $convRow['Type'] === 'General Currency Conversion' && abs($row['_utc'] - $convRow['_utc']) < 5;
		}));
		if ( count($convRows) == 2 ) {
			if ( $convRows[0]['Currency'] === $this->currency && $convRows[1]['Currency'] === $row['Currency'] ) {
				return (float) $convRows[0]['Gross'];
			}
			elseif ( $convRows[1]['Currency'] === $this->currency && $convRows[0]['Currency'] === $row['Currency'] ) {
				return (float) $convRows[1]['Gross'];
			}
		}

		throw new RuntimeException("Can't convert {$row['Currency']} to $this->currency for {$row['Name']} on {$row['Date']}");
	}

	protected function filterFromTo( array $rows ) {
		return array_filter($rows, function(array $row) {
			return $row['From Email Address'] && $row['To Email Address'];
		});
	}

	protected function filterHoldsAndAuths( array $rows ) {
		return array_filter($rows, function(array $row) {
			return !in_array($row['Type'], ['General Authorization', 'Account Hold for Open Authorization', 'Reversal of General Account Hold']);
		});
	}

	protected function appendUtc( array $rows ) {
		return array_map(function(array $row) {
			$date = $row['Date'] . ' ' . $row['Time'] . ' ' . $row['TimeZone'];
			$dt = DateTime::createFromFormat('d/m/Y H:i:s T', $date);
			if ( !$dt ) {
				throw new RuntimeException("Invalid date format: '$date' on line $line");
			}
			$utc = $dt->getTimestamp();
			$row['_utc'] = $utc;
			return $row;
		}, $rows);
	}

}
