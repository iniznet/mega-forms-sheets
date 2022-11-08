<?php
/**
 * Plugin Name:       WP Form Sheets
 * Plugin URI:        https://github.com/iniznet/authcred
 * Description:       Provide a form templates with Google Sheets integration.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            niznet
 * Author URI:        https://niznet.my.id
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-form-sheets
 * Domain Path:       /languages
 */

require_once __DIR__ . '/vendor/autoload.php';

class WpFormSheets extends \WPTrait\Plugin
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
		$this->SettingsFields = new \WpFormSheets\Options\Settings($this->plugin);
		$this->FormFields = new \WpFormSheets\Fields\Form($this->plugin);
		// $this->Sheet = new \WpFormSheets\Sheet($this);
		// $this->Form = new \WpFormSheets\Form($this);
		// $this->Shortcode = new \WpFormSheets\Shortcode($this);
	}
}

new WpFormSheets('wp-form-sheets');