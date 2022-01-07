<?php

namespace rdx\moneys;

interface Importer {
	public function getTitle() : string;
	public function getDescription() : string;

	public function extractTransactions( string $filepath ) : array;

	public function getTypes() : array;
}
