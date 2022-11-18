<?php

namespace MegaFormsSheets\Collections;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * API for Google Sheets
 */
class Sheet
{
	/** @var string */
	public $credentials;

	/** @var \Google_Client */
	public $client;

	/** @var \Google_Service_Sheets */
	public $service;

	/** @var string */
	public $spreadsheetId;

	/** @var string */
	public $sheetName;

	/** @var array */
	public $excludedFieldIds = [];

	/**
	 * @param string $prefix
	 * @param string $spreadsheetId
	 * @param string $sheetName
	 */
	public function __construct($prefix, $spreadsheetId = null, $sheetName = null)
	{
		$this->credentials = $this->loadCredentials($prefix);
		
		$this->initialize();
		$this->setSpreadsheetId($spreadsheetId);
		
		if ($sheetName) {
			$this->setSheetName($sheetName);
		}
	}

	/**
	 * Load the credentials from the database
	 * 
	 * @param string $prefix
	 * 
	 * @return string
	 */
	private function loadCredentials($prefix)
	{
		$settings = get_option($prefix . '_options');
		return isset($settings['google_service_account']) ? stripslashes(base64_decode($settings['google_service_account'])) : '';
	}

	/**
	 * Initialize the Google Sheets API
	 * 
	 * @return void
	 */
	private function initialize()
	{
		if (empty($this->credentials)) {
			return;
		}

		$this->client = new \Google_Client();
		$this->client->setApplicationName('Mega Forms Sheets');
		$this->client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
		$this->client->setAccessType('offline');
		# JSON string
		$this->client->setAuthConfig(json_decode($this->credentials, true));
		
		$this->service = new \Google_Service_Sheets($this->client);
	}

	/**
	 * TODO
	 * 
	 * Methods:
	 * 1. Get spreadsheet from ID
	 * 2. Fetch all sheets
	 * 3. Fetch all rows or specific row of a sheet
	 * 6. Insert row
	 * 8. Update row
	 * 10. Delete row
	 *
	 * Row can be a number or a string or %first% or %last%
	 * Both %first% and %last% can be used in combination with a number: %first%+1 or %last%-1
	 */

	 /**
	  * Get the spreadsheet from the ID
	  *
	  * @return \Google_Service_Sheets_Spreadsheet
	  */
	public function getSpreadsheet()
	{
		if (!$this->spreadsheetId) {
			throw new \Exception(__('No spreadsheet ID provided', 'mega-forms-sheets'));
		}

		$spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
		return $spreadsheet;
	}

	/**
	 * Get all sheets from the spreadsheet
	 *
	 * @return array
	 */
	public function getSheets()
	{
		$spreadsheet = $this->getSpreadsheet();
		$sheets = $spreadsheet->getSheets();
		return $sheets;
	}

	/**
	 * Get the sheet by name
	 * 
	 * @param string $sheetName
	 * 
	 * @return \Google_Service_Sheets_Sheet
	 */
	public function getSheet($sheetName = null)
	{
		$sheetName = $sheetName ?: $this->sheetName;

		# remove single quotes
		$sheetName = str_replace("'", '', $sheetName);

		$spreadsheet = $this->getSpreadsheet();
		$sheets = $spreadsheet->getSheets();

		foreach ($sheets as $sheet) {
			if ($sheet->getProperties()->getTitle() == $sheetName) {
				return $sheet;
			}
		}

		return null;
	}

	/**
	 * Get all rows from the sheet
	 * 
	 * @param string $sheetName
	 * 
	 * @return array
	 */
	public function getRows($sheetName = null)
	{
		$sheetName = $sheetName ?: $this->sheetName;

		$response = $this->service->spreadsheets_values->get($this->spreadsheetId, $sheetName);
		$values = $response->getValues();
		
		return $values ?: [];
	}

	/**
	 * Get a specific row from the sheet
	 * 
	 * @param string $sheetName
	 * @param string $row
	 * 
	 * @return array
	 */
	public function getRow($sheetName = null, $row = null)
	{
		$sheetName = $sheetName ?: $this->sheetName;

		$response = $this->service->spreadsheets_values->get($this->spreadsheetId, $sheetName . '!A' . $row);
		$values = $response->getValues();
		
		return $values ?: [];
	}

	/**
	 * Insert a row into the sheet at the specified position
	 * 
	 * @param array $values
	 * @param string $sheetName
	 * @param string $row
	 * 
	 * @return void
	 */
	public function insertRow($values, $row = null, $sheetName = null)
	{
		$sheetName = $sheetName ?: $this->sheetName;

		# check how many rows are in the sheet
		$rows = $this->getRows($sheetName);
		$lastRow = count($rows);
		
		# check if row is like %first% or %last% or also %first%+1 or %last%-1 and convert to number
		$row = $this->convertRow($row, $lastRow) + 1;

		# check if row is already exists and if so, move all rows below it down
		if ($row <= $lastRow) {
			$sheet = $this->getSheet($sheetName);

			$body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
				'requests' => [
					'insertDimension' => [
						'range' => [
							'sheetId' => $sheet->getProperties()->getSheetId(),
							'dimension' => 'ROWS',
							'startIndex' => $row,
							'endIndex' => $lastRow,
						],
						'inheritFromBefore' => true,
					],
				],
			]);

			$this->service->spreadsheets->batchUpdate($this->spreadsheetId, $body);
			++$row;
		}

		# insert the row
		$data = array();

		$data[] = new \Google_Service_Sheets_ValueRange([
			'range' => $sheetName . '!A' . $row,
			'values' => [$values],
		]);

		$body = new \Google_Service_Sheets_BatchUpdateValuesRequest([
			'valueInputOption' => 'USER_ENTERED',
			'data' => $data,
		]);

		$result = $this->service->spreadsheets_values->batchUpdate($this->spreadsheetId, $body);
	}

	/**
	 * Update a row in the sheet at the specified position
	 * 
	 * @param array $values
	 * @param string $sheetName
	 * @param string $row
	 * 
	 * @return void
	 */
	public function updateRow($values, $sheetName = null, $row = null)
	{
		$sheetName = $sheetName ?: $this->sheetName;

		# check how many rows are in the sheet
		$rows = $this->getRows($sheetName);
		$lastRow = count($rows);
		
		# check if row is like %first% or %last% or also %first%+1 or %last%-1 and convert to number
		$row = $this->convertRow($row, $lastRow);

		# update the row
		$body = new \Google_Service_Sheets_ValueRange([
			'values' => [$values]
		]);

		$params = [
			'valueInputOption' => 'USER_ENTERED'
		];

		$this->service->spreadsheets_values->update($this->spreadsheetId, $sheetName . '!A' . $row, $body, $params);
	}

	/**
	 * Delete a row from the sheet at the specified position
	 * 
	 * @param string $sheetName
	 * @param string $row
	 * 
	 * @return void
	 */
	public function deleteRow($sheetName = null, $row = null)
	{
		$sheetName = $sheetName ?: $this->sheetName;

		# check how many rows are in the sheet
		$rows = $this->getRows($sheetName);
		$lastRow = count($rows);
		
		# check if row is like %first% or %last% or also %first%+1 or %last%-1 and convert to number
		$row = $this->convertRow($row, $lastRow);

		# delete the row
		$body = new \Google_Service_Sheets_ClearValuesRequest();

		$this->service->spreadsheets_values->clear($this->spreadsheetId, $sheetName . '!A' . $row, $body);
	}

	/**
	 * Convert the row to a number
	 * 
	 * @param string $row
	 * @param int $lastRow
	 * 
	 * @return int
	 */
	public function convertRow($row, $lastRow)
	{
		# check if row is like %first% or %last% or also %first%+1 or %last%-1 and calculate into number
		if (strpos($row, '%first%') !== false) {
			$row = str_replace('%first%', 0, $row);
		} elseif (strpos($row, '%last%') !== false) {
			$row = str_replace('%last%', $lastRow - 1, $row);
		}

		# check if row is like %first%+1 or %last%-1 and calculate into number
		if (strpos($row, '+') !== false) {
			$row = explode('+', $row);
			$row = $row[0] + $row[1];
		} elseif (strpos($row, '-') !== false) {
			$row = explode('-', $row);
			$row = $row[0] - $row[1];
		}

		return $row;
	}

	/**
	 * Set the spreadsheet ID
	 * 
	 * @param string $spreadsheetId
	 * 
	 * @return void
	 */
	public function setSpreadsheetId($spreadsheetId = null)
	{
		$this->spreadsheetId = $spreadsheetId;
	}

	/**
	 * Get the spreadsheet ID
	 * 
	 * @return string
	 */
	public function getSpreadsheetId()
	{
		return $this->spreadsheetId;
	}

	/**
	 * Set the sheet name
	 * 
	 * @param string $sheetName
	 * 
	 * @return void
	 */
	public function setSheetName($sheetName = null)
	{
		if (empty($sheetName)) {
			$sheetName = $this->getDefaultSheetName();
		}

		# if contains space, then wrap in single quotes
		if (strpos($sheetName, ' ') !== false) {
			$sheetName = "'" . $sheetName . "'";
		}

		$this->sheetName = $sheetName;
	}

	/**
	 * Set default sheet name
	 * 
	 * @return string
	 */
	private function getDefaultSheetName()
	{
		if (!$this->spreadsheetId) {
			return;
		}

		$sheet = $this->getSheets()[0];
		$sheetName = $sheet->getProperties()->getTitle();

		return $sheetName;
	}

	/**
	 * Get the sheet name
	 * 
	 * @return string
	 */
	public function getSheetName()
	{
		return $this->sheetName;
	}
}