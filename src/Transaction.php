<?php

namespace rdx\moneys;

use Exception;
use stdClass;

class Transaction extends Model {
	public const IGNORE_SPLIT = 1;
	private const IGNORE_ACCOUNT_BALANCE = 2; // Positive, on the Account's balance
	public const IGNORE_ACCOUNT_PAY_FOR_BALANCE = 3; // Negative, on the Main account

	/** @var array<int, string> */
	static public $_ignores = [
		self::IGNORE_SPLIT => 'split',
		self::IGNORE_ACCOUNT_BALANCE => 'balance (positive on Account)',
		self::IGNORE_ACCOUNT_PAY_FOR_BALANCE => 'pay for balance (negative on Main)',
	];

	static public $_table = 'transactions';

	/** @var array<int|string, ?scalar> */
	static public $_categories = array();

	// public $tags = array();

	/**
	 * @return array<string, string>
	 */
	static public function getTypes() : array {
		$importers = array_map('make_importer', MONEYS_IMPORTERS);

		$types = [];
		foreach ($importers as $importer) {
			$types = array_merge($types, $importer->getTypes());
		}
		return $types;
	}

	/**
	 * @return array<int|string, int|string>
	 */
	static public function allMonths() : array {
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

	static public function untag( int $transactionId, int $tagId ) : void {
		static::tag($transactionId, $tagId, true);
	}

	static public function tag( int $transactionId, int $tagId, bool $delete = false ) : void {
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

	static public function presave( array &$data ) : void {
		parent::presave($data);

		if ( isset($data['category_id']) && empty($data['category_id']) ) {
			$data['category_id'] = null;
		}

		if ( isset($data['account_id']) && empty($data['account_id']) ) {
			$data['account_id'] = null;
		}

		$data['hash'] = microtime() . ' ' . rand();
	}

	static public function insert( array $data ) {
		// Extract tags
		$tags = $data['tags'] ?? null;
		unset($data['tags']);

		parent::insert($data);
		$transaction = self::find(self::$_db->insert_id());

		// Save tags
		if ( $tags ) {
			$transaction->saveTags($tags, false);
		}

		return true;
	}

	public function similarityTo(self $other) : float|false {
		if ($other->date != $this->date) {
			return false;
		}

		if ((float) $other->amount != (float) $this->amount) {
			return false;
		}

		similar_text($this->safe_sumdesc, $other->safe_sumdesc, $similarity);
		return $similarity;
	}

	/**
	 * @param string|list<string> $tags
	 */
	public function saveTags( string|array $tags, bool $dbTransaction = true ) : void {
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

	protected function get_type_label() : ?string {
		$types = self::getTypes();
		return $types[$this->type] ?? null;
	}

	protected function get_type_label_full() : ?string {
		$label = $this->type_label;
		if ($this->type && $this->type != $label) {
			$label = "$label ($this->type)";
		}
		return $label;
	}

	protected function get_hide_category_dropdown() : bool {
		return $this->ignore && !$this->category_id;
	}

	protected function get_ignore_label() : string {
		return $this->ignore ? self::$_ignores[$this->ignore] : '';
	}

	protected function get_notes_summary() : string {
		if ( $this->notes ) {
			$notes = preg_split('#[\r\n]+#', trim($this->notes));
			return $notes[0]; // @phpstan-ignore offsetAccess.notFound
		}

		return '';
	}

	/**
	 * @return array<int, static>
	 */
	protected function get_child_transactions() : array {
		return self::all(['parent_transaction_id' => $this->id]);
	}

	/**
	 * @return array<int|string, ?scalar>
	 */
	protected function get_tags() : array {
		return self::$_db->fetch_fields('
			SELECT t.id, t.tag
			FROM tagged g
			JOIN tags t ON (t.id = g.tag_id)
			WHERE g.transaction_id = ?
			ORDER BY t.tag ASC
		', array($this->id));
	}

	protected function get_category() : string {
		$name = self::$_categories[ (int)$this->category_id ] ?? '';
		return (string) $name;
	}

	protected function get_amount2dec() : string {
		return number_format($this->amount, 2, '.', ',');
	}

	protected function get_tags_as_string() : string {
		return implode(' ', $this->tags);
	}

	protected function get_sumdesc() : string {
		return preg_replace('/ {2,}/', '   ', $this->summary . ' ' . $this->description);
	}

	protected function get_safe_sumdesc() : string {
		return str_replace(' ', '', mb_strtolower($this->sumdesc));
	}

	protected function get_simple_uniq() : string {
		return $this->date . ':' . $this->account . ':' . $this->amount;
	}

	protected function get_month() : string {
		return substr($this->date, 0, 7);
	}

	/**
	 * @return array<int|string, stdClass>
	 */
	protected function get_party_suggestions() : array {
		return array_intersect_key(cache_parties(), array_flip($this->party_id_suggestions));
	}

	/**
	 * @return list<int>
	 */
	protected function get_party_id_suggestions() : array {
		$parties = cache_parties();

		$suggestions = array();
		foreach ( $parties as $party ) {
			if ( $party->auto_sumdesc ) {
				$regex = '#' . $party->auto_sumdesc . '#i';
				if ( preg_match($regex, $this->description) || preg_match($regex, $this->summary) ) {
					$suggestions[] = $party->id;
				}
			}
		}

		return $suggestions;
	}

	/**
	 * @return list<stdClass>
	 */
	protected function get_category_suggestions() : array {
		if ( $this->category_id_suggestions ) {
			$categories = self::$_db->select('categories', 'id in (?)', array($this->category_id_suggestions));
			return $categories;
		}

		return array();
	}

	protected function get_category_id_suggestion() : ?int {
		if ( count($this->category_id_suggestions) == 1 ) {
			return reset($this->category_id_suggestions);
		}

		return null;
	}

	/**
	 * @return array<int|string, ?int>
	 */
	protected function get_category_id_suggestions() : array {
		if ( $this->party_id_suggestions ) {
			$parties = array_intersect_key(cache_parties(), array_flip($this->party_id_suggestions));
			$category_ids = array_unique(array_map(function(stdClass $party) {
				return $party->category_id;
			}, $parties));

			return $category_ids;
		}

		return array();
	}

	protected function get_party_category_once() : bool {
		if ( count($this->party_suggestions) == 1 ) {
			$party = reset($this->party_suggestions);
			return (bool) $party->once;
		}

		return false;
	}

	/**
	 * @return list<string>
	 */
	protected function get_tag_suggestions() : array {
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

	protected function get_selected_category_id() : ?int {
		return $this->category_id ?: $this->category_id_suggestion;
	}

	protected function get_formatted_amount() : string {
		$amount = (float)$this->amount;
		return html_money($amount, true);
	}

	/**
	 * @return list<string>
	 */
	protected function get_classes() : array {
		return array(
			$this->amount > 0 ? 'dir-in' : 'dir-out',
		);
	}

	protected function get_is_new() : bool {
		return !$this->category_id && !$this->tags;
	}

}
