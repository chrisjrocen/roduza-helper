<?php
/**
 * Template for displaying a collection item.
 */

?>
<article id="post-<?php the_ID(); ?> " onclick="getCurrentSlide()"
	class="entry-card card-content post-<?php the_ID(); ?> status-publish collections type-collections has-post-thumbnail hentry"
	data-reveal="yes:1"
	data-postid="<?php the_ID(); ?>">

	<div class="ct-media-container open-collection-modal" style="cursor:pointer;" data-postid="<?php the_ID(); ?>">
		<img src="<?php echo esc_url( get_the_post_thumbnail_url() ); ?>" class="attachment-medium_large size-medium_large wp-post-image" sizes="(max-width: 300px) 100vw, 300px" style="aspect-ratio: 4/3;">
	</div>

	<div class="card-content collection-info-section">
		<header class="entry-header">
			<ul class="entry-meta" data-type="simple:slash" data-id="meta_1">
				<li class="meta-categories" data-type="simple" id="collections-meta">
					<?php
					$terms = get_the_terms( get_the_ID(), 'artist' );
					if ( $terms && ! is_wp_error( $terms ) ) {
						$first_term = $terms[0];
						echo '<a href="' . esc_url( get_term_link( $first_term ) ) . '" rel="tag" class="ct-term-' . esc_attr( $first_term->term_id ) . '">' . esc_html( $first_term->name ) . '</a>';
					}
					?>
				</li>
			</ul>
			<h2 class="entry-title">
				<a href="#" class="open-collection-modal" data-postid="<?php the_ID(); ?>">
					<?php the_title(); ?>
					<?php
					if ( get_field( 'year' ) ) :
						?>
						,&nbsp;<?php the_field( 'year' ); ?><?php endif; ?>
				</a>
			</h2>
		</header>
	</div>
</article>

<!-- <div id="collection-modal" style="display:none;">
	<div class="modal-inner">
		<span class="close-modal" style="cursor:pointer;">&times;</span>
		<div id="modal-content-container">Loading...</div>
	</div>
</div> -->

<script>
	document.addEventListener('DOMContentLoaded', function () {
	let modal = null;
	let modalContainer = null;
	const modalCache = {};

	function createModalStructure() {
		// Only create it once
		if (!modal) {
			modal = document.createElement('div');
			modal.id = 'collection-modal';
			modal.style.display = 'none'; // hidden by default
			modal.style.position = 'fixed';
			modal.style.top = '0';
			modal.style.left = '0';
			modal.style.width = '100%';
			modal.style.height = '100%';
			modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
			modal.style.zIndex = '9999';
			modal.innerHTML = `
				<div id="modal-content-container" style="background:#fff; max-width:600px; margin:10% auto; padding:20px; position:relative;">
					<button class="close-modal" style="position:absolute;top:10px;right:10px;">×</button>
				</div>
			`;

			document.body.appendChild(modal);
			modalContainer = document.getElementById('modal-content-container');
		}
	}

	document.body.addEventListener('click', async function (e) {
		const openBtn = e.target.closest('.open-collection-modal');
		const closeBtn = e.target.closest('.close-modal');

		// Open Modal
		if (openBtn) {
			e.preventDefault();
			const postId = openBtn.dataset.postid;

			createModalStructure();

			// Use cached content if available
			if (modalCache[postId]) {
				modalContainer.innerHTML = modalCache[postId] + `<button class="close-modal" style="position:absolute;top:10px;right:10px;">×</button>`;
				modal.style.display = 'block';
				return;
			}

			try {
				const response = await fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'load_collection_modal',
						post_id: postId,
					}),
				});

				const data = await response.text();
				modalCache[postId] = data;
				modalContainer.innerHTML = data + `<button class="close-modal" style="position:absolute;top:10px;right:10px;">×</button>`;
				modal.style.display = 'block';
			} catch (error) {
				console.error('Failed to load modal content:', error);
			}
		}

		// Close Modal
		if (closeBtn) {
			e.preventDefault();
			if (modal) {
				modal.style.display = 'none';
			}
		}
	});
});

</script>
