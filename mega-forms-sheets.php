<?php
/**
 * Plugin Name:       Mega Forms Sheets
 * Plugin URI:        https://github.com/iniznet/mega-forms-sheets
 * Description:       Provide Google Sheets integration to Mega Forms
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            niznet
 * Author URI:        https://niznet.my.id
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mega-forms-sheets
 * Domain Path:       /languages
 */

require_once __DIR__ . '/vendor/autoload.php';

class MegaFormsSheets extends \WPTrait\Plugin
{
	/**
	 * @param string $slug
	 * @param array $args
	 * 
	 * @return void
	 */
	public function __construct($slug, $args = [])
	{
		parent::__construct($slug, $args);
	}

	public function instantiate()
	{
		$this->SettingsFields = new \MegaFormsSheets\Options\Settings($this->plugin);
		$this->Form = new \MegaFormsSheets\Form($this->plugin);
	}
}

new MegaFormsSheets('mega-forms-sheets');