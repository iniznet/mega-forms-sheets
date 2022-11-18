<?php

namespace MegaFormsSheets\Options;

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

	public $filters = [
		'wp_custom_fields_sanitized_value' => ['sanitizeJson', 10, 3],
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
			'title' => __('Mega Forms Sheets', 'mega-forms-sheets'),
			'capability' => 'manage_options',
			'menu_title' => __('MF Sheets', 'mega-forms-sheets'),
			'menu_icon' => 'dashicons-media-spreadsheet',
			'menu_position' => 99,
			'location' => 'menu',
			'sections' => [
				[
					'id' => 'general',
					'title' => __('General', 'mega-forms-sheets'),
					'fields' => [
						[
							'id' => 'google_service_account',
							'title' => __('Google Service Account', 'mega-forms-sheets'),
							'description' => __('Google Service Account digunakan untuk mengakses Google Sheets API. Masukkan data JSON Service Account yang didapat dari Google Cloud Platform.', 'mega-forms-sheets'),
							'type' => 'textarea',
							'rows' => 10
						],
						// [
						// 	'id' => 'sync_interval',
						// 	'title' => __('Sync Interval', 'mega-forms-sheets'),
						// 	'description' => __('Interval waktu untuk sinkronisasi data dari database ke Google (dalam menit).', 'mega-forms-sheets'),
						// 	'type' => 'input',
						// 	'subtype' => 'number',
						// ]
					],
				],
				[
					'id' => 'form',
					'title' => __('Forms', 'mega-forms-sheets'),
					'fields' => [
						[
							'id' => 'forms',
							'description' => __('Masukkan formulir yang akan digunakan untuk mengisi data Google Sheets.', 'mega-forms-sheets'),
							'type' => 'repeatable',
							'fields' => [
								[
									'id' => 'post_id',
									'title' => __('Post ID', 'mega-forms-sheets'),
									'description' => __('Masukkan ID Post yang akan digunakan', 'mega-forms-sheets'),
									'type' => 'input',
									'subtype' => 'number',
								],
								[
									'id' => 'hook',
									'title' => __('Form Hook', 'mega-forms-sheets'),
									'description' => __('Masukkan nama hook yang akan digunakan', 'mega-forms-sheets'),
									'type' => 'input',
								],
								[
									'id' => 'spreadsheet_id',
									'title' => __('Spreadsheet ID', 'mega-forms-sheets'),
									'description' => __('Masukkan ID Spreadsheet yang akan digunakan', 'mega-forms-sheets'),
									'type' => 'input',
								],
								[
									'id' => 'sheet_name',
									'title' => __('Sheet Name', 'mega-forms-sheets'),
									'description' => __('Masukkan nama Sheet yang akan digunakan, bila kosong maka akan menggunakan Sheet pertama', 'mega-forms-sheets'),
									'type' => 'input',
								],
								[
									'id' => 'exclude',
									'title' => __('Exclude Fields', 'mega-forms-sheets'),
									'description' => __('Masukkan id field yang akan diabaikan', 'mega-forms-sheets'),
									'type' => 'input',
								],
								[
									# Which row to start inserting data, can be %first% or %last% and possible to do %first% + 1 or %last% - 1
									'id' => 'insert',
									'title' => __('Insert Row', 'mega-forms-sheets'),
									'description' => __('Masukkan baris dimana data akan dimasukkan, bisa menggunakan %first% atau %last%, bisa juga %first% + 1 atau %last% - 1', 'mega-forms-sheets'),
									'type' => 'input',
								],
								[
									# Which rows & columns that will be saved back to the post
									'id' => 'saves',
									'title' => __('Save to Post', 'mega-forms-sheets'),
									'description' => __('Masukkan angka barisxkolom:nama_field/kolom untuk data-data akan disimpan kembali ke post. Bisa menggunakan %first% atau %last%, atau juga %first% + 1 atau %last% - 1', 'mega-forms-sheets'),
									'placeholder' => '%last%-1x4:total, %last%x4:0',
									'type' => 'input',
								],
								[
									'id' => 'timestamp',
									'title' => __('Timestamp', 'mega-forms-sheets'),
									'description' => __('Ikut sertakan timestamp saat menyimpan data ke Google Sheets', 'mega-forms-sheets'),
									'style' => 'switcher',
									'type' => 'checkbox',
									'options' => [
										'on' => [
											'label' => __('Yes', 'mega-forms-sheets'),
										]
									],
								],
								// [
								// 	'id' => 'sync',
								// 	'title' => __('Sync Regularly', 'mega-forms-sheets'),
								// 	'description' => __('Sinkronkan data secara berkala', 'mega-forms-sheets'),
								// 	'style' => 'switcher',
								// 	'type' => 'checkbox',
								// 	'options' => [
								// 		'on' => [
								// 			'label' => __('Yes', 'mega-forms-sheets'),
								// 		]
								// 	],
								// ]
							],
						],
					],
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
	 * Sanitize Google Service Account Field
	 * 
	 * @param string $value
	 * @param string $raw
	 * @param array $field
	 * 
	 * @return string
	 */
	public function sanitizeJson($value, $raw, $field)
	{
		if ($field['id'] !== 'google_service_account') {
			return $value;
		}

		$value = trim($value);
		# encode to one string
		$value = base64_encode($value);

		return $value;
	}
}