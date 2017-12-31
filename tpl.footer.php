<hr />

<details>
	<summary>Queries (<?= count($db->queries) ?>)</summary>
	<ul>
		<? foreach ($db->queries as $query): ?>
			<li><?= html($query) ?></li>
		<? endforeach ?>
	</ul>
</details>

<p id="_loadtime">load time here</p>

<script src="rjs-custom.js"></script>
<script>
$(function() {
	var time = (Date.now() - performance.timing.navigationStart) / 1000,
		$p = $('_loadtime');
	console.log('Page load ', time);
	$p && $p.setHTML(time).css('visibility', 'visible');
});
</script>

</body>

</html>
