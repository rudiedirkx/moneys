<?php

namespace rdx\moneys;

use db_generic_relationship_aggregate;

class Tag extends Model {
	static public $_table = 'tags';

	protected function relate_num_transactions() : db_generic_relationship_aggregate {
		return $this->to_count('tagged', 'tag_id');
	}

	/**
	 * @param array<int, Transaction> $transactions
	 * @param array<int|string, ?scalar> $tags
	 */
	static public function decorateTransactions( array $transactions, array $tags ) : void {
		foreach ( $transactions as $tr ) {
			$tr->tags = array();
		}

		$tagged = self::$_db->select('tagged', array('transaction_id' => array_keys($transactions)));
		foreach ( $tagged as $record ) {
			$transactions[ $record->transaction_id ]->tags[] = $tags[ $record->tag_id ];
		}
	}

	/**
	 * @param string|list<string> $tags
	 * @return list<string>
	 */
	static public function split( string|array $tags ) : array {
		if ( !is_array($tags) ) {
			$tags = preg_split('#\s+#', trim($tags));
		}
		return array_values(array_unique(array_filter($tags)));
	}

	static public function ensure( string $tag ) : int {
		$tag = trim($tag, '- ');
		if ( $object = self::get($tag) ) {
			return $object->id;
		}

		self::insert(compact('tag'));
		return self::$_db->insert_id();
	}

	static private function get( string $tag ) : ?static {
		return self::first(compact('tag'));
	}
}
