<?php

class Model extends db_generic_model {

}

class Tag extends Model {
	static public $_table = 'tags';

	static function decorateTransactions( array $transactions, array $tags ) {
		foreach ( $transactions as $tr ) {
			$tr->tags = array();
		}

		$tagged = self::$_db->select('tagged', array('transaction_id' => array_keys($transactions)))->all();
		foreach ( $tagged as $record ) {
			$transactions[ $record->transaction_id ]->tags[] = $tags[ $record->tag_id ];
		}
	}

	static function split( $tags ) {
		if ( !is_array($tags) ) {
			$tags = preg_split('#\s+#', trim($tags));
		}
		return array_values(array_unique(array_filter($tags)));
	}

	static function ensure( $tag ) {
		$tag = trim($tag, '- ');
		if ( $object = self::get($tag) ) {
			return $object->id;
		}

		return self::insert(compact('tag'));
	}

	static function get( $tag ) {
		return self::first(compact('tag'));
	}
}

class Category extends Model {
	static public $_table = 'categories';
}

class Party extends Model {
	static public $_table = 'parties';

	static function presave( array &$data ) {
		parent::presave($data);

		isset($data['category_id']) and empty($data['category_id']) and $data['category_id'] = null;
	}
}

class Account extends Model {
	static public $_table = 'accounts';

	function get_usage_query() {
		return self::$_db->replaceholders('account_id = ? AND ignore <> ?', [$this->id, Transaction::IGNORE_ACCOUNT_PAY_FOR_BALANCE]);
	}

	function get_num_usage_transactions() {
		return self::$_db->count('transactions', $this->usage_query);
	}

	function get_usage_balance() {
		return round(self::$_db->select_one('transactions', 'sum(amount)', $this->usage_query), 2);
	}

	function get_payments_query() {
		return self::$_db->replaceholders('account_id = ? AND ignore = ?', [$this->id, Transaction::IGNORE_ACCOUNT_PAY_FOR_BALANCE]);
	}

	function get_num_payments_transactions() {
		return self::$_db->count('transactions', $this->payments_query);
	}

	function get_payments_balance() {
		return round(self::$_db->select_one('transactions', 'sum(amount)', $this->payments_query), 2);
	}

	function __toString() {
		return $this->name;
	}
}

class Transaction extends Model {
	const IGNORE_SPLIT = 1;
	const IGNORE_ACCOUNT_BALANCE = 2; // Positive, on the Account's balance
	const IGNORE_ACCOUNT_PAY_FOR_BALANCE = 3; // Negative, on the Main account

	static public $_ignores = [
		self::IGNORE_SPLIT => 'split',
		self::IGNORE_ACCOUNT_BALANCE => 'balance (positive on Account)',
		self::IGNORE_ACCOUNT_PAY_FOR_BALANCE => 'pay for balance (negative on Main)',
	];

	static public $_table = 'transactions';

	static public $_categories = array();

	// public $tags = array();

	static function allMonths() {
		$months = self::$_db->select_fields(self::$_table, "strftime('%Y-%m-01', date) d", '1 group by d order by d desc');
		$options = [];
		$lastYear = 0;
		$lastQ = '';
		foreach ( $months as $date ) {
			$year = (int) $date;
			$month = (int) substr($date, 5);

			if ( $year != $lastYear ) {
				$options[$year] = $year;
				$lastYear = $year;
			}

			$q = "$year-q" . ceil($month/3);
			if ( $q != $lastQ ) {
				$options[$q] = "$year - Q" . ceil($month/3);
				$lastQ = $q;
			}

			$options[substr($date, 0, 7)] = date('Y - M', strtotime($date));
		}
		return $options;
	}

	static function untag( $transactionId, $tagId ) {
		return static::tag($transactionId, $tagId, true);
	}

	static function tag( $transactionId, $tagId, $delete = false ) {
		try {
			$method = $delete ? 'delete' : 'insert';
			call_user_func([self::$_db, $method], 'tagged', array(
				'tag_id' => $tagId,
				'transaction_id' => $transactionId,
			));
		}
		catch (Exception $ex) {
			// Assume this is a duplicity error, and ignore it.
		}
	}

	static function presave( array &$data ) {
		parent::presave($data);

		isset($data['category_id']) and empty($data['category_id']) and $data['category_id'] = null;

		isset($data['account_id']) and empty($data['account_id']) and $data['account_id'] = null;

		$data['hash'] = microtime() . ' ' . rand();
	}

	static function insert( array $data ) {
		// Extract tags
		$tags = @$data['tags'];
		unset($data['tags']);

		$id = parent::insert($data);
		$transaction = self::find($id);

		// Save tags
		if ( $tags ) {
			$transaction->saveTags($tags, false);
		}

		return $id;
	}

	function similarityTo(self $other) {
		if ($other->date != $this->date) {
			return false;
		}

		if ((float) $other->amount != (float) $this->amount) {
			return false;
		}

		similar_text($this->safe_sumdesc, $other->safe_sumdesc, $similarity);
		return $similarity;
	}

	function saveTags( $tags, $dbTransaction = true ) {
		$tags = Tag::split($tags);

		if ( $dbTransaction ) {
			self::$_db->begin();
		}

		self::$_db->delete('tagged', array('transaction_id' => $this->id));
		foreach ( $tags as $tag ) {
			$tagId = Tag::ensure($tag);

			self::$_db->insert('tagged', array(
				'transaction_id' => $this->id,
				'tag_id' => $tagId,
			));
		}

		if ( $dbTransaction ) {
			self::$_db->commit();
		}
	}

	function get_hide_category_dropdown() {
		return $this->ignore && !$this->category_id;
	}

	function get_ignore_label() {
		return $this->ignore ? self::$_ignores[$this->ignore] : '';
	}

	function get_notes_summary() {
		if ( $this->notes ) {
			$notes = preg_split('#[\r\n]+#', trim($this->notes));
			return $notes[0];
		}

		return '';
	}

	function get_child_transactions() {
		return self::all(['parent_transaction_id' => $this->id]);
	}

	function get_tags() {
		return self::$_db->fetch_fields('
			SELECT t.id, t.tag
			FROM tagged g
			JOIN tags t ON (t.id = g.tag_id)
			WHERE g.transaction_id = ?
			ORDER BY t.tag ASC
		', array($this->id));
	}

	function get_category() {
		return @self::$_categories[ (int)$this->category_id ] ?: '';
	}

	function get_amount2dec() {
		return number_format($this->amount, 2, '.', ',');
	}

	function get_tags_as_string() {
		return implode(' ', $this->tags);
	}

	function get_sumdesc() {
		return preg_replace('/ {2,}/', '   ', $this->summary . ' ' . $this->description);
	}

	function get_safe_sumdesc() {
		return str_replace(' ', '', mb_strtolower($this->sumdesc));
	}

	function get_simple_uniq() {
		return $this->date . ':' . $this->account . ':' . $this->amount;
	}

	function get_month() {
		return substr($this->date, 0, 7);
	}

	function get_party_suggestions() {
		return array_intersect_key(cache_parties(), array_flip($this->party_id_suggestions));
	}

	function get_party_id_suggestions() {
		$parties = cache_parties();

		$suggestions = array();
		foreach ( $parties as $party ) {
			if ( $party['auto_sumdesc'] ) {
				$regex = '#' . $party['auto_sumdesc'] . '#i';
				if ( preg_match($regex, $this->description) || preg_match($regex, $this->summary) ) {
					$suggestions[] = $party->id;
				}
			}
		}

		return $suggestions;
	}

	function get_category_suggestions() {
		if ( $this->category_id_suggestions ) {
			$categories = self::$_db->select('categories', 'id in (?)', array($this->category_id_suggestions))->all();
			return $categories;
		}

		return array();
	}

	function get_category_id_suggestion() {
		if ( count($this->category_id_suggestions) == 1 ) {
			return reset($this->category_id_suggestions);
		}
	}

	function get_category_id_suggestions() {
		if ( $this->party_id_suggestions ) {
			$parties = array_intersect_key(cache_parties(), array_flip($this->party_id_suggestions));
			$category_ids = array_unique(array_map(function($party) {
				return $party->category_id;
			}, $parties));

			return $category_ids;
		}

		return array();
	}

	function get_party_category_once() {
		if ( count($this->party_suggestions) == 1 ) {
			$party = reset($this->party_suggestions);
			return (bool) $party->once;
		}

		return false;
	}

	function get_tag_suggestions() {
		$tags = array();

		if ( $this->party_suggestions ) {
			foreach ($this->party_suggestions as $party) {
				foreach (Tag::split($party->tags) as $tag) {
					$tags[] = $tag;
				}
			}
		}

		return $tags;
	}

	function get_selected_category_id() {
		return $this->category_id ?: $this->category_id_suggestion;
	}

	function get_formatted_amount() {
		$amount = (float)$this->amount;
		return html_money($amount, 2, true);
	}

	function get_classes() {
		$classes = array(
			$this->amount > 0 ? 'dir-in' : 'dir-out',
		);
		$this->new_group and $classes[] = 'new-group';
		return $classes;
	}

	function get_is_new() {
		return !$this->category_id && !$this->tags;
	}

}
