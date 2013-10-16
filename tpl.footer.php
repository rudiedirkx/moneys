
<p id="_loadtime">load time here</p>

<script src="rjs-custom.js"></script>
<script>
$(function() {
	var time = (Date.now() - performance.timing.requestStart) / 1000,
		$p = $('_loadtime');
	console.log('Page load ', time);
	$p && $p.setHTML(time).css('visibility', 'visible');
});
</script>

</body>

</html>
