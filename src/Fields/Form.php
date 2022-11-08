<?php

namespace WpFormSheets\Fields;

use MakeitWorkPress\WP_Custom_Fields\Framework;
use WPTrait\Model;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Form Class
 * 
 * Handles the form creation in the post editor
 */
class Form extends Model
{
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

	/**
	 * Register custom fields
	 * 
	 * @return void
	 */
	public function register(): void
	{
		/** @var Framework */
		$fields = Framework::instance();

		$fields->add('meta', [
			'id' => $this->plugin->prefix . 'form',
			'title' => __('WP Form Sheets', 'wp-form-sheets'),
			'screen' => [call_user_func(function() {
				$options = get_option($this->plugin->prefix . '_options', []);
				return isset($options['post_types']) ? $options['post_types'] : ['404'];
			})],
			'single' => false,
			'context' => 'normal',
			'priority' => 'high',
			'sections' => [
				[
					'id' => 'section_1',
					'title' => __('Fields', 'wp-custom-fields'),
					'fields' => [
						[
							'id' => 'sheet_id',
							'title' => __('Google Sheet ID', 'wp-form-sheets'),
							'description' => __('Masukkan ID Google Sheet yang akan digunakan untuk menyimpan data formulir ini.', 'wp-form-sheets'),
							'type' => 'input'
						],
						[
							'id' => 'sheet_tab',
							'title' => __('Google Sheet Tab', 'wp-form-sheets'),
							'description' => __('Masukkan nama tab Google Sheet yang akan digunakan untuk menyimpan data formulir ini.', 'wp-form-sheets'),
							'type' => 'input'
						],
						[
							'id' => 'fields',
							'title' => __('Form Fields', 'wp-form-sheets'),
							'description' => __('Tambahkan field yang akan digunakan pada formulir ini.', 'wp-form-sheets'),
							'type' => 'repeatable',
							'fields' => [
								[
									'id' => 'required',
									'title' => __('Required', 'wp-form-sheets'),
									'type' => 'checkbox',
									'style' => 'switcher',
									'options' => [
										'value' => [
											'label' => __('False/True', 'wp-form-sheets'),
										]
									]
								],
								[
									'id' => 'label',
									'title' => __('Label', 'wp-form-sheets'),
									'description' => __('Masukkan label yang akan ditampilkan pada formulir & Google sheet.', 'wp-form-sheets'),
									'type' => 'input'
								],
								[
									'id' => 'name',
									'title' => __('Name', 'wp-form-sheets'),
									'description' => __('Masukkan nama yang akan digunakan untuk menyimpan data pada database, harap gunakan huruf kecil dan tanpa spasi.', 'wp-form-sheets'),
									'type' => 'input'
								],
								[
									'id' => 'description',
									'title' => __('Description', 'wp-form-sheets'),
									'description' => __('Masukkan deskripsi yang akan ditampilkan pada formulir.', 'wp-form-sheets'),
									'type' => 'input'
								],
								[
									'id' => 'type',
									'title' => __('Type', 'wp-form-sheets'),
									'type' => 'select',
									'options' => [
										'text' => __('Text', 'wp-form-sheets'),
										'file' => __('File', 'wp-form-sheets'),
										// 'textarea' => __('Textarea', 'wp-form-sheets'),
										// 'checkbox' => __('Checkbox', 'wp-form-sheets'),
										// 'radio' => __('Radio', 'wp-form-sheets'),
										// 'select' => __('Select', 'wp-form-sheets'),
										// 'password' => __('Password', 'wp-form-sheets'),
										// 'email' => __('Email', 'wp-form-sheets'),
										// 'url' => __('URL', 'wp-form-sheets'),
										// 'number' => __('Number', 'wp-form-sheets'),
										// 'range' => __('Range', 'wp-form-sheets'),
										// 'date' => __('Date', 'wp-form-sheets'),
										// 'time' => __('Time', 'wp-form-sheets'),
									],
								],
								[
									'id' => 'options',
									'title' => __('Options', 'wp-form-sheets'),
									'type' => 'input',
									'description' => __('Masukkan opsi yang akan digunakan pada field ini. Pisahkan dengan koma (,) untuk setiap opsi.', 'wp-form-sheets'),
									'condition' => [
										'type' => ['checkbox', 'radio', 'select']
									],
								],
							],
						],
					]
				],
				[
					'id' => 'section_2',
					'title' => __('Data', 'wp-form-sheets'),
					'fields' => [
						[
							'id' => 'data',
							'title' => __('Data', 'wp-form-sheets'),
							'type' => 'repeatable',
							'description' => __('Tambahkan nama data yang akan disimpan pada post ini.', 'wp-form-sheets'),
							'fields' => [
							],
						],
					]
				]
			],
		]);
	}
}