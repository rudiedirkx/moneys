<?php

use rdx\moneys\Account;
use rdx\moneys\Importer;
use rdx\moneys\Transaction;

require 'inc.bootstrap.php';

$account = Account::find($_GET['account'] ?? null);

$importers = array_map('make_importer', MONEYS_IMPORTERS);

if ( isset($_POST['importer']) && (isset($_FILES['file']) || isset($_POST['filepath'])) ) {
	$importer = array_reduce($importers, function(?Importer $match, Importer $importer) {
		return $importer->getTitle() === $_POST['importer'] ? $importer : $match;
	}, null);
	if ( !$importer ) {
		do_400();
		exit('Need importer.');
	}

	$batch = time();

	$transactions = $importer->extractTransactions($_POST['filepath'] ?? $_FILES['file']['tmp_name']);

	usort($transactions, function(array $a, array $b) {
		$d1 = strcmp($b['date'], $a['date']);
		if ( $d1 != 0 ) return $d1;

		$d2 = abs($b['amount']) <=> abs($a['amount']);
		if ( $d2 != 0 ) return $d2;

		return 0;
	});

	$records = array_map(function(array $tr) use ($batch, $account) {
		return $tr + [
			'account_id' => $account ? $account->id : null,
			'batch' => $batch,
		];
	}, $transactions);

	$transactions = array_map(function(array $record) {
		return new Transaction($record);
	}, $records);

	$dates = array_unique(array_column($transactions, 'date'));
	$potentialDoubleTransactions = Transaction::all(['date' => $dates]);

	$potentialDoubles = [];
	foreach ($transactions as $i => $trans1) {
		$potentialDoubles[$i] = array_filter($potentialDoubleTransactions, function(Transaction $trans2) use ($trans1) {
			return $trans1->similarityTo($trans2) > 80;
		});
	}

	$withPotentialDoubles = count(array_filter($potentialDoubles));

	if ( !empty($_POST['confirm']) ) {
		@unlink($_POST['filepath']);

		db()->begin();

		$selected = $_POST['selected'] ?? [];
		if ( count($selected) == 0 ) {
			exit('No selected..?');
		}

		foreach ( $records as $i => $record ) {
			if ( in_array($i, $selected) ) {
				Transaction::insert($record);
			}
		}

		db()->commit();

		return do_redirect('index');
	}

	$filepath = tempnam(sys_get_temp_dir(), 'moneys');
	file_put_contents($filepath, file_get_contents($_FILES['file']['tmp_name']));

	?>
	<style>
	td[rowspan] {
		width: 1px;
		padding: 3px;
	}
	td[rowspan="1"]:first-child {
		background-color: green;
	}
	td[rowspan="2"]:first-child {
		background-color: red;
	}
	td[rowspan="3"]:first-child,
	td[rowspan="4"]:first-child {
		background-color: blue;
	}
	.nowrap {
		white-space: nowrap;
	}
	</style>

	<p>
		<?= count($transactions) ?> uploaded. <?= $withPotentialDoubles ?> with potential doubles.
	</p>

	<form action method="post">
		<input type="hidden" name="confirm" value="1" />
		<input type="hidden" name="importer" value="<?= html($_POST['importer']) ?>" />
		<input type="hidden" name="filepath" value="<?= html($filepath) ?>" />

		<table border="1" cellspacing="0" cellpadding="6">
			<? foreach ($transactions as $i => $tr):
				$exists = count($potentialDoubles[$i]);
				?>
				<tr>
					<td rowspan="<?= ($exists + 1) ?>">
						<input type="checkbox" name="selected[]" value="<?= $i ?>" <?= $exists ? '' : 'checked' ?> />
					</td>
					<td rowspan="<?= ($exists + 1) ?>" class="nowrap"><?= html($tr->date) ?></td>
					<td rowspan="<?= ($exists + 1) ?>" class="nowrap" align="right" style="background-color: <?= $tr->amount < 0 ? '#fdd' : '#dfd' ?>">
						<?= number_format($tr->amount, 2) ?>
					</td>
					<td><?= html($tr->summary) ?> <?= html($tr->description) ?></td>
					<td></td>
				</tr>
				<? foreach ($potentialDoubles[$i] as $tr2): ?>
					<tr>
						<td><?= html($tr2->sumdesc) ?></td>
						<td nowrap><a href="transaction.php?id=<?= $tr2->id ?>"><?= $tr2->id ?></a></td>
					</tr>
				<? endforeach ?>
			<? endforeach ?>
		</table>

		<p><button style="font-weight: bold; padding: 9px 14px">
			IMPORT SELECTED
		</button></p>
	</form>
	<?php

	exit;
}

require 'tpl.header.php';

?>
<h1>
	Import transactions
	<? if ($account): ?>
		into <em><?= html($account) ?></em>
	<? endif ?>
</h1>

<style>
label {
	display: block;
	margin-bottom: .5em;
}
.gutter {
	display: inline-block;
	width: 30px;
}
</style>

<form method="post" action enctype="multipart/form-data">
	<p>
		<? foreach ($importers as $importer):
			$title = $importer->getTitle();
			?>
			<label>
				<span class="gutter"><input type="radio" name="importer" value="<?= html($title) ?>" /></span>
				<?= html($title) ?><br>
				<span class="gutter"></span>
				<?= html($importer->getDescription()) ?>
			</label>
		<? endforeach ?>
	</p>
	<p>Upload file: <input type="file" name="file" /></p>

	<p><button>NEXT</button></p>

</form>
<?php

require 'tpl.footer.php';
