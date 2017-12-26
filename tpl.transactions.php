
<style>
.cb-checked {
	background-color: green;
}
form.saving-tags button.save-cats,
form:not(.saving-tags) button.save-tags {
	display: none;
}
label {
	display: block;
	cursor: pointer;
}
.pager {
	text-align: center;
}
tr + tr.new-group td,
tr + tr.new-group th {
	border-top-width: 7px;
}
.summary {
	font-weight: bold;
}
.notes {
	font-style: italic;
	color: #005C00; /* darker green */
}
.no-tags {
	color: #ccc;
	display: block;
	text-align: center;
}
.hide-sumdesc .col-sumdesc,
body:not(.hide-sumdesc) .show-sumdesc {
	display: none;
}
</style>

<form method="post" action="index.php">
	<table>
		<thead>
			<? if ($show_pager): ?>
				<? ob_start() ?>
				<tr class="pager">
					<td colspan="8">
						<? if ($pager): ?>
							<a href="?<?= html_query(array('page' => $page - 1)) ?>">&lt;&lt;</a>
							|
						<? endif ?>
						<?= $offset + 1 ?> - <?= $offset + count($transactions) ?> / <?= $totalRecords ?>
						<? if ($pager): ?>
							|
							page <?= $page + 1 ?> / <?= $pages ?>
							|
							<a href="?<?= html_query(array('page' => $page + 1)) ?>">&gt;&gt;</a>
						<? endif ?>
						(<a href="?<?= $_SERVER['QUERY_STRING'] ?>&export">export</a>)
					</td>
				</tr>
				<? $pager_html = ob_get_contents() ?>
			<? endif ?>
			<tr>
				<th class="col-id"></th>
				<th class="col-cb">
					<input type="checkbox" onclick="$$('tbody .cb').prop('checked', this.checked); onCheck()" />
				</th>
				<th>
					<? if ($with_sorting): ?><a href="index.php?<?= html_query(array('sort' => sort_opposite('date', $sort))) ?>"><? endif ?>
						Date
					<? if ($with_sorting): ?></a><? endif ?>
				</th>
				<th>
					<? if ($with_sorting): ?><a href="index.php?<?= html_query(array('sort' => sort_opposite('amount', $sort))) ?>"><? endif ?>
						Amount
					<? if ($with_sorting): ?></a><? endif ?>
				</th>
				<th class="col-type">Type</th>
				<th class="col-sumdesc">
					<? if ($with_sorting): ?><a href="javascript:void(0)" onclick="document.body.toggleClass('hide-sumdesc')"><? endif ?>
						Summary &amp; Description
					<? if ($with_sorting): ?></a><? endif ?>
				</th>
				<th>Category</th>
				<th>Tags</th>
			</tr>
		</thead>
		<tbody>
			<? $totalMoney = 0.0 ?>
			<? foreach ($transactions as $tr):
				$totalMoney += $tr->amount;
				$tr->new_group = @$old_group != $tr->$grouper;
				$old_group = $tr->$grouper;
				?>
				<tr class="<?= implode(' ', $tr->classes) ?>">
					<th class="col-id">
						<a href="transaction.php?id=<?= $tr->id ?>"><?= $tr->id ?></a>
					</th>
					<td class="col-cb">
						<input type="checkbox" name="check[]" value="<?= $tr->id ?>" class="cb" id="tr-<?= $tr->id ?>" onclick="onCheck()" />
					</td>
					<td class="date" nowrap>
						<label for="tr-<?= $tr->id ?>"><?= $tr->date ?></label>
					</td>
					<td class="amount" nowrap>
						<label for="tr-<?= $tr->id ?>"><?= $tr->formatted_amount ?></label>
					</td>
					<td class="col-type" nowrap><?= $tr->type ?: '?' ?></td>
					<td class="col-sumdesc">
						<div class="summary"><?= html($tr->summary) ?> <? if ($tr->account): ?>(<?= html($tr->account) ?>)<? endif ?></div>
						<div class="notes"><?= html($tr->notes_summary) ?></div>
						<div class="description">
							<? if ($tr->parent_transaction_id): ?>
								<a title="Open parent transaction" href="transaction.php?id=<?= $tr->parent_transaction_id ?>">&lt;&lt;</a>
							<? endif ?>
							<?= html($tr->description) ?>
						</div>
					</td>
					<td class="category <? if (!$tr->category_id): ?>empty<? endif ?>">
						<select name="category[<?= $tr->id ?>]"><?= html_options($categories, $tr->selected_category_id, '--') ?></select>
					</td>
					<td>
						<? if ($tr->is_new): ?>
							<? foreach ($tr->tag_suggestions as $tag): ?>
								<label style="white-space: nowrap"><input checked type="checkbox" name="trtags[<?= $tr->id ?>][]" value="<?= html($tag) ?>" /><?= html($tag) ?></label>
							<? endforeach ?>
						<? else: ?>
							<label for="tr-<?= $tr->id ?>"><?= implode('<br>', $tr->tags) ?></label>
						<? endif ?>
					</td>
				</tr>
			<? endforeach ?>
		</tbody>
		<tfoot>
			<? if ($show_pager): ?>
				<?= $pager_html ?>
			<? endif ?>
			<tr>
				<td class="col-id"></td>
				<td class="col-cb"><?= count($transactions) ?></td>
				<td class="col-date"></td>
				<td class="amount"><?= html_money($totalMoney, true) ?></td>
				<td class="col-type"></td>
				<td colspan="3"></td>
			</tr>
		</tfoot>
	</table>

	<datalist id="data-tags"><?= html_options($tags, '', '', true) ?></datalist>

	<p>
		<button class="save-cats">Save</button>
		<button class="save-tags">Save tags</button>
		Add tag: <input name="add_tag" list="data-tags" autocomplete="off" />
	</p>
</form>

<script>
function onCheck() {
	var m = $$('tbody .cb:checked').length ? 'addClass' : 'removeClass';
	$$('form')[m]('saving-tags');

	$$('tbody .cb').each(function(cb) {
		var m = cb.checked ? 'addClass' : 'removeClass';
		cb.parentNode[m]('cb-checked');
	});
}
</script>
