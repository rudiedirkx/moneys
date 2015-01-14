<?php

class Transaction extends db_generic_record {

	static public $_categories = array();

	public $tags = array();

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
