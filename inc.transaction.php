<?php

class Transaction extends db_generic_record {

	function get_party_id_suggestion() {
		$parties = cache_parties();

		$suggestions = array();
		foreach ( $parties as $party ) {
			foreach ( array('description', 'summary', 'account') as $field ) {
				if ( $party['auto_' . $field] ) {
					$regex = '#' . $party['auto_' . $field] . '#i';
					if ( preg_match($regex, $this->$field) ) {
						$suggestions[] = $party->id;
					}
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
		return array(
			$this->amount > 0 ? 'dir-in' : 'dir-out',
		);
	}

}
