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
	 * REST namespace for the collection modal endpoint.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'roduza-helper/v1';

	/**
	 * Register function is called by default to get the class running.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'create_mosaic_gallery_block' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register block function called by init hook.
	 *
	 * Styles and the front-end view script are declared in block.json, so they
	 * only load on pages that contain the block.
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
	 * Register the REST route that returns a single collection's modal HTML.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/collection/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_collection_modal' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Render the Mosaic Gallery block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block content.
	 */
	public function render_mosaic_gallery_block( $attributes ) {
		$the_category  = $attributes['categoryToDisplay'] ?? '';
		$no_to_show    = absint( $attributes['numberOfItems'] ?? 4 );
		$heading_color = $attributes['headingColor'] ?? '';

		$args = array(
			'post_type'      => 'collections',
			'post_status'    => 'publish',
			// 0 means "show all items".
			'posts_per_page' => 0 === $no_to_show ? -1 : $no_to_show,
		);

		// An empty category means "all collections"; an unknown slug shows the empty state.
		if ( '' !== $the_category ) {
			$term = get_term_by( 'slug', $the_category, 'collection-category' );

			if ( $term ) {
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'collection-category',
						'field'    => 'term_id',
						'terms'    => array( $term->term_id ),
					),
				);
			} else {
				$args['post__in'] = array( 0 );
			}
		}

		$collection_query = new \WP_Query( $args );

		if ( $collection_query->have_posts() ) {
			// The markup below mimics Blocksy's archive cards, so load Blocksy's
			// entries styles (grid layout), which it only enqueues on archives.
			wp_enqueue_style( 'ct-entries-styles' );

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

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'data-endpoint' => esc_url( rest_url( self::REST_NAMESPACE . '/collection/' ) ),
			)
		);

		return sprintf( '<div %s>%s</div>', $wrapper_attributes, $gallery_html );
	}

	/**
	 * REST callback returning the modal HTML for one published collection.
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_collection_modal( \WP_REST_Request $request ) {
		$post = get_post( $request['id'] );

		if (
			! $post
			|| 'collections' !== $post->post_type
			|| 'publish' !== $post->post_status
			|| post_password_required( $post )
		) {
			return new \WP_Error(
				'roduza_collection_not_found',
				__( 'Collection not found.', 'roduza-helper' ),
				array( 'status' => 404 )
			);
		}

		// Set up the global post so the_content filters behave as on a singular view.
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		$html = $this->build_modal_html( $post );

		wp_reset_postdata();

		return rest_ensure_response( array( 'html' => $html ) );
	}

	/**
	 * Build the modal markup for a collection.
	 *
	 * @param \WP_Post $post The collection post.
	 * @return string
	 */
	private function build_modal_html( \WP_Post $post ) {
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
			apply_filters( 'the_content', get_the_content( null, false, $post ) )
		);

		$modal_html = sprintf(
			'<div class="modal-content-single">%s%s%s%s</div>',
			$image_html,
			$title_html,
			$material_html,
			$content_html
		);

		return wp_kses_post( $modal_html );
	}
}
