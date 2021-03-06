<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<style>
#info {
	margin: 9%;
}
</style>
<div class="content-real">
	<center>
		<h2><a href="http://gyuseu.tumblr.com">From My Tumblr</a></h2>
	</center>
	<div class="album">
	<?php
		foreach ($results as $result) {
			$caption = $result->caption;
			$url = $result->post_url;
			$img = $result->photos[0]->alt_sizes[0]->url;
	?>
		<a class="photo" href="<?= $url; ?>" target="_blank"><img src="<?= $img ?>"></a>
	<?php
		}
	?>
	</div>

	<h2>For Organizations</h2>

	<div class="album">
	<?php
		foreach ($seconds as $result) {
			$caption = $result->caption;
			$url = $result->post_url;
			$img = $result->photos[0]->alt_sizes[0]->url;
	?>
		<a class="photo" href="<?= $url; ?>" target="_blank"><img src="<?= $img ?>"></a>
	<?php
		}
	?>
</div>
</div>

<script src='https://cdnjs.cloudflare.com/ajax/libs/masonry/4.1.1/masonry.pkgd.js'></script>
<script src='https://npmcdn.com/imagesloaded@4.1/imagesloaded.pkgd.js'></script>
<script>
var $grid = $('.album').masonry({
	itemSelector: '.photo',
	percentPosition: true
});

$grid.imagesLoaded().progress(function () {
	$grid.masonry();
});
$('#bg, #container').show();
$('#loader').hide();
</script>

