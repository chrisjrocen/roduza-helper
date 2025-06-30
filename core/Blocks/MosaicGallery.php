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
	}

	/**
	 * Register block function called by init hook.
	 *
	 * @return void
	 */
	public function create_mosaic_gallery_block() {
		register_block_type_from_metadata(
			$this->plugin_path . 'build/mosaic-gallery/', // Path to block.json.
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
	}

	/**
	 * Render the Mosaic Gallery block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content Block content.
	 * @return string Rendered block content.
	 */
	public function render_mosaic_gallery_block( $attributes, $content ) {
		return sprintf(
			'<div class="roduza-helper-mosaic-gallery-block">%sGallery</div>',
			$content
		);
	}
}
