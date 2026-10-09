<?php

namespace rdx\moneys;

interface Importer {
	public function getTitle() : string;
	public function getDescription() : string;

	/**
	 * @return list<AssocArray>
	 */
	public function extractTransactions( string $filepath ) : array;

	/**
	 * @return array<string, string>
	 */
	public function getTypes() : array;
}
