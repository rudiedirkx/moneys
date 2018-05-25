<?php

namespace rdx\moneys;

interface Importer {
	public function getTitle();
	public function getDescription();

	public function extractTransactions( $filepath );
}
