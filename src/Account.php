<?php

namespace rdx\moneys;

class Account extends Model {
	static public $_table = 'accounts';

	protected function get_usage_query() : string {
		return self::$_db->replaceholders('account_id = ? AND ignore <> ?', [$this->id, Transaction::IGNORE_ACCOUNT_PAY_FOR_BALANCE]);
	}

	protected function get_num_usage_transactions() : int {
		return self::$_db->count('transactions', $this->usage_query);
	}

	protected function get_usage_balance() : float {
		return round(self::$_db->select_one('transactions', 'sum(amount)', $this->usage_query), 2);
	}

	protected function get_payments_query() : string {
		return self::$_db->replaceholders('account_id = ? AND ignore = ?', [$this->id, Transaction::IGNORE_ACCOUNT_PAY_FOR_BALANCE]);
	}

	protected function get_num_payments_transactions() : int {
		return self::$_db->count('transactions', $this->payments_query);
	}

	protected function get_payments_balance() : float {
		return round(self::$_db->select_one('transactions', 'sum(amount)', $this->payments_query), 2);
	}

	public function __toString() {
		return $this->name;
	}
}
