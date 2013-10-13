<?php

require 'inc.bootstrap.php';

if ( isset($_POST['parties']) ) {
	header('Content-type: text/plain');

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
				// $db->delete();
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
// print_r($parties);

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');
// print_r($categories);

require 'tpl.header.php';

$parties[] = (object)array('id' => '', 'name' => '');

?>
<style>
.auto input {
	border: 0;
	border-bottom: solid 1px #aaa;
	background-color: transparent;
}
</style>

<form method="post" action>
	<table>
		<thead>
			<tr>
				<th rowspan="2">Name</th>
				<th colspan="3" class="c">Auto-assign (regex)</th>
				<th rowspan="2">Category</th>
			</tr>
			<tr>
				<th>Summary</th>
				<th>Description</th>
				<th>Account no.</th>
			</tr>
		</thead>
		<tbody>
			<? foreach ($parties as $party): ?>
				<tr>
					<td><input name="parties[<?= $party->id ?: 0 ?>][name]" value="<?= html($party->name) ?>" placeholder="Party name" /></td>
					<? if ($party->id): ?>
						<td class="auto">
							#<input name="parties[<?= $party->id ?>][auto_summary]" value="<?= $party->auto_summary ?>" />#i
						</td>
						<td class="auto">
							#<input name="parties[<?= $party->id ?>][auto_description]" value="<?= $party->auto_description ?>" />#i
						</td>
						<td class="auto">
							#<input name="parties[<?= $party->id ?>][auto_account]" value="<?= $party->auto_account ?>" />#i
						</td>
						<td><select name="parties[<?= $party->id ?>][category_id]"><?= html_options($categories, $party->category_id, '--') ?></select></td>
					<? endif ?>
				</tr>
			<? endforeach ?>
		<tbody>
	</table>

	<p><button>Save</button></p>
</form>
<?php

require 'tpl.footer.php';