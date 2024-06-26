<?php
/**
 * Handles options access.
 */
class Wdsi_Options {

	function __construct () {}

	/**
	 * Gets a single option from options storage.
	 */
	function get_option($key) {
    $opts = get_option('wdsi');
    if (is_array($opts) && array_key_exists($key, $opts)) {
        return $opts[$key];
    }
    return null; // Or handle the absence of the key however you prefer
	}

	/**
	 * Sets all stored options.
	 */
	function set_options ($opts) {
		return WP_NETWORK_ADMIN ? update_site_option('wdsi', $opts) : update_option('wdsi', $opts);
	}

	/**
	 * Populates options key for storage.
	 *
	 * @static
	 */
	function populate () {
		$site_opts = get_site_option('wdsi');
		$site_opts = is_array($site_opts) ? $site_opts : array();

		$opts = get_option('wdsi');
		$opts = is_array($opts) ? $opts : array();

		$res = array_merge($site_opts, $opts);
		update_option('wdsi', $res);
	}

}