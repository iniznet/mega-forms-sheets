<?php

namespace WpFormSheets\Options;

use MakeitWorkPress\WP_Custom_Fields\Framework;
use WPTrait\Model;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin Settings in Admin Screen
 */
class Settings extends Model
{
	public $actions = [
		'wcf_after_field' => ['fieldStyles', 10],
	];

	/**
	 * @param object|Model $plugin
	 * 
	 * @return void
	 */
	public function __construct($plugin)
	{
		parent::__construct($plugin);
		$this->register();
	}

	public function register(): void
	{
		/** @var Framework */
		$fields = Framework::instance();

		$fields->add('options', [
			'class' => 'tabs-left',
			'id' => $this->plugin->prefix . '_options',
			'title' => __('WP Form Sheets', 'wp-form-sheets'),
			'capability' => 'manage_options',
			'menu_title' => __('WP Form Sheets', 'wp-form-sheets'),
			'menu_icon' => 'dashicons-media-spreadsheet',
			'menu_position' => 99,
			'location' => 'menu',
			'sections' => [
				[
					'id' => 'general',
					'title' => __('General', 'wp-form-sheets'),
					'fields' => [
						[
							'id' => 'google_service_account',
							'title' => __('Google Service Account', 'wp-form-sheets'),
							'description' => __('Google Service Account digunakan untuk mengakses Google Sheets API. Masukkan data JSON Service Account yang didapat dari Google Cloud Platform.', 'wp-form-sheets'),
							'type' => 'textarea',
							'rows' => 10
						],
						[
							'id' => 'post_types',
							'title' => __('Post Types', 'wp-form-sheets'),
							'description' => __('Pilih post type yang akan digunakan oleh plugin ini.', 'wp-form-sheets'),
							'type' => 'select',
							'multiple' => true,
							'options' => $this->getPostTypes(),
						]
					]
				]
			]
		]);
	}

	/**
	 * Field Styles
	 * 
	 * @param array $field
	 * 
	 * @return string
	 */
	public function fieldStyles($field)
	{
		if ($field['type'] !== 'select' && ($field['multiple'] ?? 0) !== 1) {
			return;
		}

		echo '<style>
			.select2-container {
				width: 100% !important;
			}

			.select2-container .select2-search--inline .select2-search__field {
				margin-top: 0 !important;
			}
		</style>';
	}

	/**
	 * Get all post types
	 * 
	 * @return array
	 */
	private function getPostTypes()
	{
		$postTypes = get_post_types(['public' => true], 'objects');
		$options = [];

		foreach ($postTypes as $postType) {
			$options[$postType->name] = $postType->label;
		}

		return $options;
	}
}