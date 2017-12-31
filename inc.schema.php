<?php

return array(
	'version' => 2,
	'tables' => array(
		'categories' => array(
			'columns' => array(
				'id' => array('pk' => true),
				'name',
			),
		),
		'tags' => array(
			'columns' => array(
				'id' => array('pk' => true),
				'tag',
			),
		),

		'transactions' => array(
			'columns' => array(
				'id' => array('pk' => true),
				'hash' => array('unique' => true),
				'date' => array('null' => false, 'default' => ''),
				'summary' => array('null' => false, 'default' => ''),
				'description' => array('null' => false, 'default' => ''),
				'type' => array('null' => true),
				'account' => array('null' => true),
				'amount' => array('null' => false, 'type' => 'real'),
				'category_id' => array('unsigned' => true, 'null' => true, 'references' => array('categories', 'id')),
				'other_party_id' => array('unsigned' => true, 'null' => true, 'references' => array('parties', 'id')),
				'parent_transaction_id' => array('unsigned' => true, 'null' => true, 'references' => array('transactions', 'id')),
				'ignore' => array('unsigned' => true, 'default' => 0),
				'notes' => array('null' => false, 'default' => ''),
			),
			'indexes' => array(
				'hash' => 'hash',
				'account' => 'account',
				'type' => 'type',
				'category_id' => 'category_id',
				'other_party_id' => 'other_party_id',
				'parent_transaction_id' => 'parent_transaction_id',
			),
		),
		'parties' => array(
			'columns' => array(
				'id' => array('pk' => true),
				'name' => array('null' => false),
				'auto_sumdesc' => array('null' => false, 'default' => ''),
				'auto_account' => array('null' => false, 'default' => ''),
				'category_id' => array('unsigned' => true, 'null' => true, 'references' => array('categories', 'id')),
				'tags' => array('null' => false, 'default' => ''),
			),
		),
		'tagged' => array(
			'columns' => array(
				'transaction_id' => array('unsigned' => true, 'references' => array('transactions', 'id', 'cascade')),
				'tag_id' => array('unsigned' => true, 'references' => array('tags', 'id', 'cascade')),
			),
			'indexes' => array(
				'tagged_pk' => array('columns' => array('transaction_id', 'tag_id'), 'unique' => true),
			),
		),
	),
);
