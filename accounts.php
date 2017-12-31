<?php

require 'inc.bootstrap.php';

if ( isset($_POST['new_account']) ) {
	Account::insert(['name' => trim($_POST['new_account'])]);

	return do_redirect('accounts');
}

$accounts = Account::all('1 ORDER BY name ASC');

require 'tpl.header.php';

?>
<table class="accounts">
	<thead>
		<tr>
			<th>Name</th>
			<th>Usage</th>
			<th>Payments</th>
		</tr>
	</thead>
	<tbody>
		<? foreach ($accounts as $account): ?>
			<tr>
				<td>
					<a href="account.php?id=<?= $account->id ?>"><?= html($account->name) ?></a>
				</td>
				<td>
					<?= html_money($account->usage_balance, true) ?>
					(<a href="index.php?account=<?= $account->id ?>"><?= $account->num_usage_transactions ?></a>)
				</td>
				<td>
					<?= html_money($account->payments_balance, true) ?>
					(<a href="index.php?account=<?= -$account->id ?>"><?= $account->num_payments_transactions ?></a>)
				</td>
			</tr>
		<? endforeach ?>
	<tbody>
</table>

<form method="post" action style="margin: 1em 0; border: solid 1px #aaa; padding: 0 10px">
	<p>New account: <input name="new_account" /></p>
	<p><button>Save</button></p>
</form>
<?php

require 'tpl.footer.php';
