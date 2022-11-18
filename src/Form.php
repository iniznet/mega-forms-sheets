<?php

namespace MegaFormsSheets;

use MegaFormsSheets\Collections\Sheet;
use WPTrait\Model;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Handle form submission from Mega Form that been configured in settings page
 */
class Form extends Model
{
	/** @var array */
	public $forms = [];

	/** @var Sheet */
	public $sheet;

	/**
	 * @param object|Model $plugin
	 * 
	 * @return void
	 */
	public function __construct($plugin)
	{
		parent::__construct($plugin);

		$this->sheet = new Sheet($this->plugin->prefix);
		$this->initializeForms();
		$this->listen();
	}

	/**
	 * Start listen to form hook names
	 * 
	 * @return void
	 */
	public function listen()
	{
		if (empty($this->forms)) {
			return;
		}

		foreach ($this->forms as $form) {
			add_action('mf_process_entry_actions', [$this, 'handle'], 20, 4);
		}
	}

	/**
	 * Handle the form submission and save the data to Google Sheets
	 * 
	 * @param int $entryId
	 * @param array $entryMeta
	 * @param object $form
	 * @param array $fields
	 * 
	 * @return void
	 */
	public function handle($entryId, $entryMeta, $form, $fields)
	{

		if (empty($fields)) {
			return;
		}

		$actionName = $this->getFormHookName($form);
		$settings = $this->getFormSettings($actionName);

		if (empty($settings)) {
			return;
		}

		$sheet = $this->sheet;
		$sheet->setSpreadsheetId($settings['spreadsheet_id']);
		$sheet->setSheetName($settings['sheet_name']);

		$postId = $settings['post_id'];
		$excludeFieldIds = isset($settings['exclude']) ? explode(',', $settings['exclude']) : [];
		$insertRowPosition = !empty($settings['insert']) ? $settings['insert'] : '%last%';
		$savesRowsToPost = isset($settings['saves']) ? explode(',', $settings['saves']) : [];
		$includeTimestamp = isset($settings['timestamp']) ? $settings['timestamp']['on'] : false;

		# Input to sheet
		$values = [];

		if ($includeTimestamp) {
			# 01/11/2022 12:03:40
			$values[] = wp_date('d/m/Y H:i:s');
		}

		foreach ($fields as $id => $field) {
			if (in_array($id, $excludeFieldIds)) {
				unset($fields[$id]);
				continue;
			}

			if ($field['type'] === 'file') {
				# href="https://example.com/wp-content/uploads/2021/11/IMG_20211101_120340.jpg"
				$values[] = preg_match('/href="([^"]+)"/', $field['values']['formatted_short'], $matches) ? $matches[1] : '';
				continue;
			}

			$values[] = $field['values']['formatted_short'];
		}

		$sheet->insertRow($values, $insertRowPosition);

		# Save specific cells to post meta from sheet
		if (empty($savesRowsToPost)) {
			return;
		}

		if (!is_array($savesRowsToPost)) {
			$savesRowsToPost = [$savesRowsToPost];
		}

		foreach ($savesRowsToPost as $row) {
			# row = %last%:5:jumlah_donasi, extract 'jumlah_donasi'
			$cells = explode(':', $row);
			$fieldName = isset($cells[2]) ? $cells[2] : null;
			$row = $cells[0];
			$column = --$cells[1];

			if (empty($fieldName)) {
				continue;
			}

			$rows = $sheet->getRows();
			$row = $sheet->convertRow($row, count($rows));

			if (empty($rows[$row])) {
				continue;
			}

			$cell = isset($rows[$row][$column]) ? $rows[$row][$column] : null;

			if (empty($cell)) {
				continue;
			}

			update_post_meta($postId, $fieldName, $cell);
		}
	}

	/**
	 * Initialize saved form from settings
	 * 
	 * @return array
	 */
	public function initializeForms()
	{
		$settings = $this->option($this->plugin->prefix . '_options')->get(['forms' => []]);
		// $syncInterval = $this->option($this->plugin->prefix . '_options')->get(['sync_interval' => 60]);
		$this->forms = $settings['forms'];

		return $this->forms;
	}

	/**
	 * Get the hook name of a form object
	 * 
	 * @param object $form
	 * 
	 * @return string
	 */
	private function getFormHookName($form)
	{
		# get the first item of 'actions' element array
		$action = current($form->actions);

		if (empty($action)) {
			return '';
		}

		return $action['hook_tag'];
	}

	/**
	 * Get form settings by hook name
	 * 
	 * @param string $hook
	 * 
	 * @return array
	 */
	private function getFormSettings($hook)
	{
		if (empty($this->forms)) {
			return [];
		}

		foreach ($this->forms as $form) {
			if ($form['hook'] === $hook) {
				return $form;
			}
		}

		return [];
	}
}
