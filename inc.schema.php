<?php

return array(
	'version' => 2,
	'tables' => array(
		'transactions' => array(
			'columns' => array(
				'id' => array('pk' => true),
				'hash' => array('unique' => true),
				'date' => array('null' => false, 'default' => ''),
				'summary' => array('null' => false, 'default' => ''),
				'description' => array('null' => false, 'default' => ''),
				// 'direction' => array('null' => false, 'default' => ''),
				'type' => array('null' => true),
				'account' => array('null' => true),
				'amount' => array('null' => false, 'type' => 'real'),
				'category_id' => array('unsigned' => true, 'null' => true),
				'other_party_id' => array('unsigned' => true, 'null' => true),
				'parent_transaction_id' => array('unsigned' => true, 'null' => true),
				'ignore' => array('unsigned' => true, 'default' => 0),
				'notes' => array('null' => false, 'default' => ''),
			),
			'indexes' => array(
				'hash' => 'hash',
				'account' => 'account',
				'type' => 'type',
				// 'direction' => 'direction',
				'category_id' => 'category_id',
				'other_party_id' => 'other_party_id',
				'parent_transaction_id' => 'parent_transaction_id',
			),
		),
		'parties' => array(
			'columns' => array(
				'id' => array('pk' => true),
				'name' => array('null' => false),
				// 'auto_summary' => array('null' => false, 'default' => ''),
				// 'auto_description' => array('null' => false, 'default' => ''),
				'auto_sumdesc' => array('null' => false, 'default' => ''),
				'auto_account' => array('null' => false, 'default' => ''),
				'category_id' => array('unsigned' => true, 'null' => true),
				'tags' => array('null' => false, 'default' => ''),
			),
		),
		'categories' => array(
			'columns' => array(
				'id' => array('pk' => true),
				'name',
			),
		),
		'tagged' => array(
			'columns' => array(
				'transaction_id' => array('unsigned' => true),
				'tag_id' => array('unsigned' => true),
			),
			'indexes' => array(
				'tagged_pk' => array('columns' => array('transaction_id', 'tag_id'), 'unique' => true),
			),
		),
		'tags' => array(
			'columns' => array(
				'id' => array('pk' => true),
				'tag',
			),
		),
		'variables' => array(
			'columns' => array(
				'name' => array('type' => 'text', 'unique' => true),
				'value' => array('type' => 'text'),
			),
		),
	),
);
