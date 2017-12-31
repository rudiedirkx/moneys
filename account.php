<?php

require 'inc.bootstrap.php';

$id = (int)$_GET['id'];
$account = Account::find($id);
if ( !$account ) {
	do_404();
	require 'tpl.header.php';
	exit('Account not found.');
}

// SAVE
if ( isset($_POST['term_usage'], $_POST['term_payments'], $_POST['info']) ) {
	$account->update(array(
		'term_usage' => trim($_POST['term_usage']),
		'term_payments' => trim($_POST['term_payments']),
		'info' => trim($_POST['info']),
	));

	return do_redirect('account', compact('id'));
}

require 'tpl.header.php';

?>
<table border="1">
	<tr>
		<th>Name</th>
		<td><?= html($account->name) ?></td>
	</tr>
	<tr>
		<th><?= html($account->term_usage) ?></th>
		<td>
			<?= html_money($account->usage_balance, true) ?>
			(<a href="index.php?account=<?= $account->id ?>"><?= $account->num_usage_transactions ?></a>)
		</td>
	</tr>
	<tr>
		<th><?= html($account->term_payments) ?></th>
		<td>
			<?= html_money($account->payments_balance, true) ?>
			(<a href="index.php?account=<?= -$account->id ?>"><?= $account->num_payments_transactions ?></a>)
		</td>
	</tr>
</table>

<p><a href="import-ing.php?account=<?= $account->id ?>">Import transactions</a></p>

<details>
	<summary>Terminology &amp; info</summary>
	<form method="post" action>
		<p>Term for <em>Usage</em> <input name="term_usage" value="<?= html($account->term_usage) ?>" /></p>
		<p>Term for <em>Payments</em> <input name="term_payments" value="<?= html($account->term_payments) ?>" /></p>
		<p>Account info <textarea name="info" rows="4" cols="60"><?= html($account->info) ?></textarea></p>
		<p><button>Save</button></p>
	</form>
</details>
<?php

require 'tpl.footer.php';
