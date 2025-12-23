<?php
/**
 * Mosaic Gallery Block
 *
 * @package roduza_helper
 */

namespace roduza_helper\Blocks;

use roduza_helper\Base\BaseController;

/**
 * Class to handle the Mosaic Gallery Block.
 */
class MosaicGallery extends BaseController {
	/**
	 * Register function is called by default to get the class running.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'create_mosaic_gallery_block' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_mosaic_gallery_script' ) );
		add_action( 'wp_ajax_load_collection_modal', array( $this, 'load_collection_modal_callback' ) );
		add_action( 'wp_ajax_nopriv_load_collection_modal', array( $this, 'load_collection_modal_callback' ) );
	}

	/**
	 * Register block function called by init hook.
	 *
	 * @return void
	 */
	public function create_mosaic_gallery_block() {
		register_block_type_from_metadata(
			$this->plugin_path . 'build/mosaic-gallery/',
			array(
				'render_callback' => array( $this, 'render_mosaic_gallery_block' ),
			)
		);
	}

	/**
	 * Enqueue script for frontend open/closed status.
	 *
	 * @return void
	 */
	public function enqueue_frontend_mosaic_gallery_script() {
		wp_enqueue_style(
			'roduza-helper-mosaic-gallery-styles',
			$this->plugin_url . 'build/mosaic-gallery/style-index.css',
			array(),
			$this->plugin_version
		);

		wp_enqueue_script(
			'roduza-helper-mosaic-gallery-script',
			$this->plugin_url . 'assets/js/scripts.js',
			array(),
			$this->plugin_version,
			true
		);
	}

	/**
	 * Render the Mosaic Gallery block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block content.
	 */
	public function render_mosaic_gallery_block( $attributes ) {

		do_action( 'qm/debug', $attributes );

		$the_category  = $attributes['categoryToDisplay'] ?? '';
		$no_to_show    = $attributes['numberOfItems'] ?? 0;
		$heading_color = $attributes['headingColor'] ?? '#000';

		$term    = get_term_by( 'slug', $the_category, 'collection-category' );
		$term_id = $term ? $term->term_id : 0;

		$args = array(
			'post_type'      => 'collections',
			'posts_per_page' => $no_to_show,
		);

		if ( $term_id ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'collection-category',
					'field'    => 'term_id',
					'terms'    => array( $term_id ),
				),
			);
		} else {
			$args['post__in'] = array( 0 );
		}

		do_action( 'qm/debug', $args );

		$collection_query = new \WP_Query( $args );
		$gallery_html     = '';

		if ( $collection_query->have_posts() ) {
			$entries_html = '';
			while ( $collection_query->have_posts() ) {
				$collection_query->the_post();
				ob_start();
				get_template_part( 'template-parts/content', 'collection', array( 'heading_color' => $heading_color ) );
				$entries_html .= ob_get_clean();
			}

			$gallery_html = sprintf(
				'<div class="ct-posts-shortcode" data-prefix="collections_archive">
					<div class="entries" data-archive="default" data-layout="grid" data-cards="simple">
						%s
					</div>
				</div>',
				$entries_html
			);
		} else {
			$gallery_html = sprintf(
				'<div class="no-collections"><p>%s</p></div>',
				esc_html__( 'No collections found.', 'roduza-helper' )
			);
		}

		wp_reset_postdata();

		return sprintf(
			'<div class="roduza-helper-mosaic-gallery-block">%s</div>',
			$gallery_html
		);
	}

	/**
	 * Callback function to load the collection modal.
	 *
	 * @return void
	 */
	public function load_collection_modal_callback() {
		if ( ! isset( $_POST['post_id'] ) ) {
			echo 'Post ID not set.';
			wp_die();
		}
		$post_id = intval( $_POST['post_id'] );

		$post = get_post( $post_id );
		if ( ! $post || 'collections' !== $post->post_type ) {
			echo 'Invalid post.';
			wp_die();
		}

		setup_postdata( $post );

		$taxonomy = 'collection-category';
		if ( taxonomy_exists( $taxonomy ) ) {
			$terms = wp_get_post_terms( $post_id, $taxonomy );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$first_term = $terms[0];
				echo esc_html( $first_term->name );
			}
		}

		$image_html = '';
		if ( has_post_thumbnail( $post ) ) {
			$image_html = sprintf(
				'<div class="modal-image-container">%s</div>',
				get_the_post_thumbnail( $post, '', array( 'style' => 'max-height:80vh; width:auto;' ) )
			);
		}

		$the_title    = get_the_title( $post );
		$the_year     = get_field( 'year', $post->ID );
		$the_material = get_field( 'material', $post->ID );
		$artists      = get_the_terms( $post, 'artist' );
		$the_artist   = ( ! empty( $artists ) && ! is_wp_error( $artists ) ) ? esc_html( $artists[0]->name ) : '';

		$title_html = sprintf(
			'<h2 id="modal-title"><span id="collection-name">%s</span>%s%s</h2>',
			esc_html( $the_title ),
			$the_year ? ' <span id="collection-year">(' . esc_html( $the_year ) . ')</span>' : '',
			$the_artist ? ', <span id="collection-artist">' . $the_artist . '</span>' : ''
		);

		$material_html = $the_material
			? sprintf( '<h3 id="collection-material">(%s)</h3>', esc_html( $the_material ) )
			: '';

		$content_html = sprintf(
			'<div class="content-collection">%s</div>',
			wp_kses_post( apply_filters( 'the_content', get_the_content( null, false, $post ) ) )
		);

		$modal_html = sprintf(
			'<div class="modal-content-single">%s%s%s%s</div>',
			$image_html,
			$title_html,
			$material_html,
			$content_html
		);

		echo wp_kses_post( $modal_html );

		wp_reset_postdata();
		wp_die();
	}
}
