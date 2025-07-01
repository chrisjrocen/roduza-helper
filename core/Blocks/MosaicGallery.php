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
			array()
		);

		wp_enqueue_script(
			'roduza-helper-mosaic-gallery-script',
			$this->plugin_url . 'assets/js/scripts.js',
			array(),
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
				get_template_part( 'template-parts/content', 'collection' );
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
				esc_html__( 'No collections found.', 'blocksy' )
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
		$post_id  = intval( $_POST['post_id'] );
		$taxonomy = 'collection-category';

		if ( $post_id && taxonomy_exists( $taxonomy ) ) {
			$terms = wp_get_post_terms( $post_id, $taxonomy );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$first_term = $terms[0];
				echo esc_html( $first_term->name );
			} else {
				echo 'No terms found or an error occurred.';
			}
		} else {
			echo 'Invalid post ID or taxonomy.';
		}

		$collection_ids = array();
		if ( isset( $first_term ) && isset( $first_term->term_id ) ) {
			$collection_ids = get_objects_in_term( $first_term->term_id, $taxonomy );
			if ( ! empty( $collection_ids ) ) {
				$collection_ids        = array_map( 'intval', $collection_ids );
				$cache_key             = 'collection_ids_sorted_' . md5( implode( ',', $collection_ids ) );
				$sorted_collection_ids = wp_cache_get( $cache_key, 'custom' );

				if ( false === $sorted_collection_ids ) {
					$query                 = new \WP_Query(
						array(
							'post_type'      => 'collections',
							'post__in'       => $collection_ids,
							'orderby'        => 'date',
							'order'          => 'DESC',
							'posts_per_page' => -1,
							'fields'         => 'ids',
						)
					);
					$sorted_collection_ids = $query->posts;
					wp_cache_set( $cache_key, $sorted_collection_ids, 'custom', 300 );
				}

				$collection_ids = $sorted_collection_ids;
			}
		}

		if ( empty( $collection_ids ) ) {
			echo 'No collections found.';
			wp_die();
		}

		$collections = array();
		foreach ( $collection_ids as $cid ) {
			$collections[] = get_post( $cid );
		}

		$slides_html = '';
		foreach ( $collections as $post ) {
			setup_postdata( $post );
			$active_class = ( $post->ID === $post_id ) ? 'active' : '';

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

			$slides_html .= sprintf(
				'<div class="carousel-slide %s" data-post-id="%d" style="display: %s;">%s%s%s%s</div>',
				esc_attr( $active_class ),
				$post->ID,
				$active_class ? 'block' : 'none',
				$image_html,
				$title_html,
				$material_html,
				$content_html
			);
		}

		$carousel_html = sprintf(
			'<div class="carousel-modal-container">
				<div class="carousel-slides">%s</div>
				<div id="slider-buttons">
					<button class="carousel-nav prev" onclick="showPrevSlide()">&#10094;</button>
					<button class="carousel-nav next" onclick="showNextSlide()">&#10095;</button>
				</div>
			</div>',
			$slides_html
		);

		echo $carousel_html;

		wp_reset_postdata();
		wp_die();
	}
}
