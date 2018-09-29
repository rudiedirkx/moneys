<?php

namespace rdx\moneys;

abstract class CsvImporter implements Importer {

	abstract public function getMandatoryColumns();

	public function getDescription() {
		return "CSV file with mandatory columns '" . implode("', '", $this->getMandatoryColumns()) . "'.";
	}

	protected function readCsv( $filepath ) {
		if ( !file_exists($filepath) || !is_readable($filepath) ) {
			throw new ImportException("Can't read file");
		}

		$data = csv_read_doc(file_get_contents($filepath), true);
		$this->requireColumns($data, $this->getMandatoryColumns());
		return $data;
	}

	protected function requireColumns( array $data, array $columns ) {
		$row = $data[0];

		$missing = array_diff($columns, array_keys($row));
		if ( $missing ) {
			throw new ImportException('Missing columns: ' . implode(', ', $missing));
		}
	}

}
