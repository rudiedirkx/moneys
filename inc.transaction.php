<?php

class Transaction extends db_generic_record {

	static public $_categories = array();

	// public $tags = array();

	static function insert( $data ) {
		global $db;

		// Collect tags
		$tags = @$data['tags'] ?: array();
		unset($data['tags']);
		if ( !is_array($tags) ) {
			$tags = array_filter(explode(' ', $tags));
		}

		// Create transaction record
		$db->insert('transactions', $data);
		$transaction = $db->select('transactions', array('id' => $db->insert_id()), null, 'Transaction')->first();

		// Save tags
		if ( $tags ) {
			$transaction->saveTags($tags, false);
		}

		return $transaction;
	}

	function saveTags( $tags, $dbTransaction = true ) {
		global $db;

		if ( $dbTransaction ) {
			$db->begin();
		}

		$db->delete('tagged', array('transaction_id' => $this->id));
		foreach ( array_unique($tags) as $tag ) {
			$tagId = $db->select_one('tags', 'id', compact('tag'));
			if ( !$tagId ) {
				$db->insert('tags', compact('tag'));
				$tagId = $db->insert_id();
			}

			$db->insert('tagged', array(
				'transaction_id' => $this->id,
				'tag_id' => $tagId,
			));
		}

		if ( $dbTransaction ) {
			$db->commit();
		}
	}

	function get_notes_summary() {
		if ( $this->notes ) {
			$notes = preg_split('#[\r\n]+#', trim($this->notes));
			return $notes[0];
		}

		return '';
	}

	function get_tags() {
		global $db;
		return $db->fetch_fields('
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

	function get_tags_as_string() {
		return implode(' ', $this->tags);
	}

	function get_sumdesc() {
		return preg_replace('/ {2,}/', '   ', $this->summary . ' ' . $this->description);
	}

	function get_simple_uniq() {
		return $this->date . ':' . $this->account . ':' . $this->amount;
	}

	function get_month() {
		return substr($this->date, 0, 7);
	}

	function get_party_id_suggestion() {
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

		$suggestions = array_unique($suggestions);

		if ( count($suggestions) == 1 ) {
			return reset($suggestions);
		}
	}

	function get_category_id_suggestion() {
		if ( $this->party_id_suggestion ) {
			$parties = cache_parties();
			return $parties[$this->party_id_suggestion]->category_id;
		}
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

}
