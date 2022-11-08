<?php

namespace WpFormSheets;

use WPTrait\Model;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Handle form render and submission
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
	}

	/**
	 * Render the form
	 * 
	 * @param array $args
	 * 
	 * @return string
	 */
	public function render($args = [])
	{
		$defaults = [
			'id' => null,
			'fields' => [],
		];

		$args = wp_parse_args($args, $defaults);

		# Check if the post exists
		if (!$args['id']) {
			return __('Post ID is missing.', 'wp-form-sheets');
		}

		# Check if the fields exists
		if (!$args['fields']) {
			return __('No fields found.', 'wp-form-sheets');
		}

		# Append nonce
		$args['nonce'] = $this->nonce->create($this->plugin->prefix . '_submission');

		return $this->view->render('form', $args);
	}

	/**
	 * Handle the form submission
	 * 
	 * @param array $args
	 * 
	 * @return string
	 */
	public function submit()
	{
		# Check if the nonce is valid
		if (!$this->nonce->verify($this->plugin->prefix . '_submission')) {
			return __('Invalid nonce.', 'wp-form-sheets');
		}

		# Check if the post exists
		if (!$this->request->numeric('post_id')) {
			return __('Post ID is missing.', 'wp-form-sheets');
		}

		# Check if the fields exists
		if (!$this->request->filled('fields')) {
			return __('No fields found.', 'wp-form-sheets');
		}
	}
}
