<?php

require 'inc.bootstrap.php';

$parties = Party::all('1 ORDER BY once DESC, name ASC');

if ( isset($_POST['parties']) ) {
	foreach ( $_POST['parties'] as $id => $data ) {
		$name = trim($data['name']);
		$party = Party::find($id);

		$data['once'] = !empty($data['once']);

		// Existing
		if ( $party ) {
			// Update
			if ( $name ) {
				$party->update($data);
			}
			// Delete
			else {
				$party->delete();
			}
		}
		// New
		elseif ( $name ) {
			Party::insert($data);
		}
	}

	return do_redirect('parties');
}

require 'tpl.header.php';

$parties[] = new Party(array('id' => 0, 'name' => ''));

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');

$tags = $db->select_fields('tags', 'id, tag', '1 ORDER BY tag ASC');

?>
<style>
tr.hr td {
	padding: 4px;
	background-color: #000;
}
.auto {
	white-space: nowrap;
}
.auto input {
	border: 0;
	border-bottom: solid 1px #aaa;
	background-color: transparent;
	width: 400px;
	padding-left: 1px;
	padding-right: 1px;
}
</style>

<form method="post" action>
	<table>
		<thead>
			<tr>
				<th>Name</th>
				<th class="c">Match (summary &amp; description)</th>
				<th>Category</th>
				<th>Tags</th>
				<th align="center">Once</th>
			</tr>
		</thead>
		<tbody>
			<? $prev = null ?>
			<? foreach ($parties as $i => $party): ?>
				<? if ($prev && $prev->once && !$party->once): ?>
					<tr class="hr">
						<td colspan="5"></td>
					</tr>
				<? endif ?>
				<tr>
					<td>
						<input name="parties[<?= $party->id ?>][name]" value="<?= html($party->name) ?>" placeholder="<?= $party->id ? 'Delete this party' : 'New party name' ?>" />
					</td>
					<td class="auto">
						#<input name="parties[<?= $party->id ?>][auto_sumdesc]" value="<?= @$party->auto_sumdesc ?>" />#i
					</td>
					<td>
						<select name="parties[<?= $party->id ?>][category_id]"><?= html_options($categories, @$party->category_id, '--') ?></select>
					</td>
					<td>
						<input name="parties[<?= $party->id ?>][tags]" value="<?= html(@$party->tags) ?>" list="data-tags" autocomplete="off" />
					</td>
					<td align="center">
						<input name="parties[<?= $party->id ?>][once]" type="checkbox" <?= $party->once ? 'checked' : '' ?> />
					</td>
				</tr>
				<? $prev = $party ?>
			<? endforeach ?>
		<tbody>
	</table>

	<datalist id="data-tags"><?= html_options($tags, '', '', true) ?></datalist>

	<p><button>Save</button></p>
</form>
<?php

require 'tpl.footer.php';
