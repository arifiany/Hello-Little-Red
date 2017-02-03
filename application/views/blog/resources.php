
<div id="theme" class="content-real">
	<?php if( $posts ): foreach($posts as $post): ?>
	<article class="mini-theme">
		<center><img src="<?php echo $post->resource_preview ?>" alt=""/></center>

		<h3><?php echo $post->resource_name ?></h3>
		<div class="theme-links">
			<a href="<?php echo $post->resource_download ?>">download</a>
		</div>

	</article>

<?php endforeach; else: ?>
	<h2>No resources yet!</h2>

<?php endif;?>
</div>
</div>