<?php

require 'inc.bootstrap.php';

if ( isset($_POST['parties']) ) {
	foreach ( $_POST['parties'] as $id => $party ) {
		$name = trim($party['name']);

		// Existing
		if ( $id ) {
			// Update
			if ( $name ) {
				isset($party['category_id']) and empty($party['category_id']) and $party['category_id'] = null;

				$db->update('parties', $party, compact('id'));
			}
			// Delete
			else {
				$db->delete('parties', compact('id'));
			}
		}
		// New
		else if ( $name ) {
			$db->insert('parties', $party);
		}
	}

	return do_redirect('parties');
}

$parties = $db->select('parties', '1 ORDER BY name ASC')->all();

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');

require 'tpl.header.php';

$parties[] = (object) array('id' => '0', 'name' => '');

$tags = $db->select_fields('tags', 'id, tag', '1 ORDER BY tag ASC');

?>
<style>
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
			</tr>
		</thead>
		<tbody>
			<? foreach ($parties as $party): ?>
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
				</tr>
			<? endforeach ?>
		<tbody>
	</table>

	<datalist id="data-tags"><?= html_options($tags, '', '', true) ?></datalist>

	<p><button>Save</button></p>
</form>
<?php

require 'tpl.footer.php';
