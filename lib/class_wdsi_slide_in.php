<?php

class Wdsi_SlideIn {
	
	private static $_instance;

	const NOT_IN_POOL_STATUS = 'wdsi_not_in_pool';
	const POST_TYPE = 'slide_in';
	
	private function __construct () {}
	
	/**
	 * Glues everything together and initialize singleton.
	 */
	public static function init () {
		if (!isset(self::$_instance)) self::$_instance = new self;

		add_action('init', array(self::$_instance, 'register_post_type'));
		add_action('widgets_init', array(self::$_instance, 'register_sidebar'));
		add_action('admin_init', array(self::$_instance, 'add_meta_boxes'));
		add_action('save_post', array(self::$_instance, 'save_meta'), 9); // Bind it a bit earlier, so we can kill Post Indexer actions.
		add_action('wp_insert_post_data', array(self::$_instance, 'set_up_post_status'));

		add_filter("manage_edit-" . self::POST_TYPE . "_columns", array(self::$_instance, "add_custom_columns"));
		add_action("manage_posts_custom_column",  array(self::$_instance, "fill_custom_columns"));
	}

	/**
	 * Prepared singleton object getting routine.
	 */
	public static function get_instance () {
		return self::$_instance;
	}


/* ----- Static info getters ----- */

	/**
	 * Get known themes.
	 */
	public static function get_appearance_themes () {
		return array(
			'minimal' => __('Minimal', 'wdsi'),
			'rounded' => __('Abgerundet', 'wdsi'),
		);
	}
	
	/**
	 * Get known theme variations.
	 */
	public static function get_theme_variations () {
		return array(
			'light' => __('Hell', 'wdsi'),
			'dark' => __('Dunkel', 'wdsi'),
		);
	}

	/**
	 * Get known variation schemes.
	 */
	public static function get_variation_schemes () {
		return array(
			'red' => __('Rot', 'wdsi'),
			'green' => __('Grün', 'wdsi'),
			'blue' => __('Blau', 'wdsi'),
			'orange' => __('Orange', 'wdsi'),
		);
	}

	public static function get_default_injection_hook () {
		$hook = defined('WDSI_INJECTION_HOOK') ? WDSI_INJECTION_HOOK : 'loop_end';
		return apply_filters('wdsi-core-injection_hook', $hook);
	}

	
/* ----- Handlers ----- */

	function register_sidebar () {
		$data = new Wdsi_Options;
		if (!$data->get_option('allow_widgets')) return false;
		register_sidebar(array(
			'name' => __('Slide-In', 'wdsi'),
			'id' => 'slide-in',
			'description' => __('Diese Seitenleiste kann als Slide-In Inhalt verwendet werden.', 'wdsi'),
			'class' => '',
			'before_widget' => '<aside id="%1$s" class="wdsi-widget wdsi-slide-col %2$s">',
			'after_widget' => '</aside>',
			'before_title' => '<h3 class="wdsi-widget_title">',
			'after_title' => '</h3>'
		));
	}
	
	function register_post_type () {
		$supports = apply_filters(
			'wdsi-slide_in-post_type-supports',
			array('title', 'editor')
		);
		// Force required support
		if (!in_array('title', $supports)) $supports[] = 'title';
		if (!in_array('editor', $supports)) $supports[] = 'editor';
		
		register_post_type(self::POST_TYPE, array(
			'labels' => array(
				'name' => __('Slide In', 'wdsi'),
				'singular_name' => __('Slide In Nachricht', 'wdsi'),
				'add_new_item' => __('Neue Slide In Nachricht erstellen', 'wdsi'),
				'edit_item' => __('Slide In Nachricht bearbeiten', 'wdsi'),
			),
			'menu_icon' => WDSI_PLUGIN_URL . '/img/admin-menu-icon.png',
			'public' => false,
			'show_ui' => true,
			'supports' => $supports,
		));
		register_post_status(self::NOT_IN_POOL_STATUS, array('protected' => true));
	}
	
	function add_meta_boxes () {
		add_meta_box(
			'wdsi_conditions',
			__('Bedingungen', 'wdsm'),
			array($this, 'render_conditions_box'),
			self::POST_TYPE,
			'side',
			'high'
		);
		add_meta_box(
			'wdsi_type',
			__('Inhaltstyp', 'wdsm'),
			array($this, 'render_content_type'),
			self::POST_TYPE,
			'normal',
			'high'
		);
		add_meta_box(
			'wdsi_show_override',
			__('Globale Einstellungen überschreiben', 'wdsm'),
			array($this, 'render_show_after_box'),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}
	
	function render_conditions_box () {
		global $post;
		$show_options = get_post_meta($post->ID, 'wdsi_show_if', true);
		
		echo '<div class="psource-ui">' .
			'<input type="checkbox" name="not_in_the_pool" id="wdsi-not_in_the_pool" value="1" ' .
				($post->post_status == self::NOT_IN_POOL_STATUS ? 'checked="checked"' : '') .
			' />' .
			'&nbsp;' .
			//'<label for="wdsi-not_in_the_pool">' . __('Nicht im Pool', 'wdsi') . '</label>' .
			'<label for="wdsi-not_in_the_pool">' . __('Dies ist ein postspezifisches Slide-In', 'wdsi') . '</label>' .
			$this->_create_hint(__('Slide Ins außerhalb des Pools können einzelnen Posts zugewiesen werden, wobei die Standardeinstellungen überschrieben werden', 'wdsi')) .
		'</div>';

		echo '<div id="wdsi-conditions-container" class="psource-ui" style="display:none">';
		
		echo '<h4>' . __('Nachricht anzeigen, wenn:', 'wdsi') . '</h4>';

		$show_if = wdsi_getval($show_options, 'user');
		echo '<fieldset id="wdsi-user_rules"><legend>' . __('Benutzerregeln', 'wdsi') . '</legend>';
		echo '' .
			'<input type="radio" name="show_if[user]" value="show_if_logged_in" id="show_if_logged_in-yes" ' .
				('show_if_logged_in' == $show_if ? 'checked="checked"' : '') .
			'/ >' .
			' <label for="show_if_logged_in-yes">' . __('der Benutzer ist angemeldet', 'wdsi') . '</label>' .
		'<br />';
		echo '' .
			'<input type="radio" name="show_if[user]" value="show_if_not_logged_in" id="show_if_not_logged_in-yes" ' .
				('show_if_not_logged_in' == $show_if ? 'checked="checked"' : '') .
			'/ >' .
			' <label for="show_if_not_logged_in-yes">' . __('der Benutzer ist <b>NICHT</b> angemeldet', 'wdsi') . '</label> ' .
		'<br />';
		echo '' .
			'<input type="radio" name="show_if[user]" value="show_if_never_commented" id="show_if_never_commented-yes" ' .
				('show_if_never_commented' == $show_if ? 'checked="checked"' : '') .
			'/ >' .
			' <label for="show_if_never_commented-yes">' . __('der Benutzer hat Deine Website noch nie kommentiert', 'wdsi') . '</label> ' .
		'<br />';
		echo '</fieldset>';

		echo '<h4>' . __('Nachricht anzeigen auf:', 'wdsi') . '</h4>';

		$show_if = wdsi_getval($show_options, 'page');
		echo '<fieldset id="wdsi-page_rules"><legend>' . __('Seitenregeln', 'wdsi') . '</legend>';
		echo '' .
			'<input type="radio" name="show_if[page]" value="show_if_singular" id="show_if_singular-yes" ' .
				('show_if_singular' == $show_if ? 'checked="checked"' : '') .
			'/ >' .
			' <label for="show_if_singular-yes">' . __('eine meiner einzelnen Seiten', 'wdsi') . '</label>' .
		'<br />';
		echo '' .
			'<input type="radio" name="show_if[page]" value="show_if_not_singular" id="show_if_not_singular-yes" ' .
				('show_if_not_singular' == $show_if ? 'checked="checked"' : '') .
			'/ >' .
			' <label for="show_if_not_singular-yes">' . __('eine meiner Archivseiten', 'wdsi') . '</label>' .
		'<br />';
		echo '' .
			'<input type="radio" name="show_if[page]" value="show_if_home" id="show_if_home-yes" ' .
				('show_if_home' == $show_if ? 'checked="checked"' : '') .
			'/ >' .
			' <label for="show_if_home-yes">' . __('Startseite', 'wdsi') . '</label>' .
		'<br />';
		$types = get_post_types(array('public' => true), 'objects');
		foreach ($types as $type => $obj) {
			if ('attachment' == $type) continue;
			$name = "show_if_{$type}";
			echo '' .
				'<input type="radio" name="show_if[page]" value="' . $name . '" id="' . $name . '-yes" ' .
					($name == $show_if ? 'checked="checked"' : '') .
				'/ >' .
				' <label for="' . $name . '-yes">' . sprintf(__('&quot;%s&quot; Beitragsseiten', 'wdsi'), $obj->label) . '</label>' .
			'<br />';
		}

		echo '</fieldset>';
		
		echo '</div>';
	}

	function render_content_type () {
		global $post;
		$opts = get_post_meta($post->ID, 'wdsi-type', true);
		$type = wdsi_getval($opts, 'content_type', 'text');

		echo '<div class="psource-ui">';

		echo '' .
			'<input type="radio" name="wdsi-type[content_type]" id="wdsi-content_type-text" value="text" ' . ('text' == $type ? 'checked="checked"' : '') . ' />' .
			'&nbsp;' .
			'<label for="wdsi-content_type-text">' . __('Textnachricht', 'wdsi') . '</label>' .
		'<br />';
		/*echo '' .
			'<input type="radio" name="wdsi-type[content_type]" id="wdsi-content_type-mailchimp" value="mailchimp" ' . ('mailchimp' == $type ? 'checked="checked"' : '') . ' />' .
			'&nbsp;' .
			'<label for="wdsi-content_type-mailchimp">' . __('MailChimp-Anmeldeformular', 'wdsi') . '</label>' .
		'<br />';*/
		echo '' .
			'<input type="radio" name="wdsi-type[content_type]" id="wdsi-content_type-related" value="related" ' . ('related' == $type ? 'checked="checked"' : '') . ' />' .
			'&nbsp;' .
			'<label for="wdsi-content_type-related">' . __('Zugehörige Beiträge', 'wdsi') . '</label>' .
		'<br />';
		$data = new Wdsi_Options;
		if ($data->get_option('allow_widgets')) {
			echo '' .
				'<input type="radio" name="wdsi-type[content_type]" id="wdsi-content_type-widgets" value="widgets" ' . ('widgets' == $type ? 'checked="checked"' : '') . ' />' .
				'&nbsp;' .
				'<label for="wdsi-content_type-widgets">' . __('Seitenleisten-Widgets', 'wdsi') . '</label>' .
			'<br />';
		}

		// --- Message
		echo '<div id="wdsi-content_type-options-text" class="wdsi-content_type" style="display:none"></div>';

		// --- MailChimp
		echo '<div id="wdsi-content_type-options-mailchimp" class="wdsi-content_type" style="display:none">';
		$defaults = get_option('wdsi');
		$api_key = wdsi_getval($opts, 'mailchimp-api_key', wdsi_getval($defaults, 'mailchimp-api_key'));
		echo '<label for="wdsi-mailchimp-api_key">' . __('MailChimp API key:') . '</label>' .
			'<input type="text" class="long" name="wdsi-type[mailchimp-api_key]" id="wdsi-mailchimp-api_key" value="' . esc_attr($api_key) . '" />' .
		'<br />';
		if (!$api_key) {
			echo $this->_create_hint(__('Gib hier Deinen API-Schlüssel ein und speichere den Beitrag, um fortzufahren', 'wdsi'));
		} else {
			$mailchimp = new Wdsi_Mailchimp($api_key);

			$lists = $mailchimp->get_lists();
			$current = wdsi_getval($opts, 'mailchimp-default_list', wdsi_getval($defaults, 'mailchimp-default_list'));

			echo '<label>' . __('Standardabonnementliste:', 'wdsi') . ' </label>';
			echo '<div class="psource-ui-select"><select name="wdsi-type[mailchimp-default_list]">';
			echo '<option></option>';
			foreach ($lists as $list) {
				$selected = $list['id'] == $current ? 'selected="selected"' : '';
				echo '<option value="' . esc_attr($list['id']) . '" ' . $selected . '>' . $list['name'] . '</option>';
			}
			echo '</select></div>';

			// We got this far, we have the API key
			echo '&nbsp;<a href="#mcls-refresh" id="wdcp-mcls-refresh">' . __('Neuladen', 'wdsi') . '</a>';
			echo $this->_create_hint(__('Wähle eine Standardliste aus, die Deine Besuchern abonnieren.', 'wdsi'));

			$placeholder = wdsi_getval($opts, 'mailchimp-placeholder', 'you@yourdomain.com');
			echo '<label for="wdsi-mailchimp-placeholder">' . __('Platzhaltertext:', 'wdsi') . '</label>' .
				'<input type="text" class="long" name="wdsi-type[mailchimp-placeholder]" id="wdsi-mailchimp-placeholder" value="' . esc_attr($placeholder) . '" />' .
			'<br />';

			$position = wdsi_getval($opts, 'mailchimp-position', 'after');
			echo '<label for="wdsi-mailchimp-position-after">' . __('Zeige mein Formular:', 'wdsi') . '</label><br />';
			echo '' . 
				'<input type="radio" name="wdsi-type[mailchimp-position]" id="wdsi-mailchimp-position-after" value="after" ' . checked('after', $position, false) . ' />' .
				'<label for="wdsi-mailchimp-position-after">' . __('Nach dem Nachrichtentext', 'wdsi') . '</label>' .
			'<br />';
			echo '' . 
				'<input type="radio" name="wdsi-type[mailchimp-position]" id="wdsi-mailchimp-position-before" value="before" ' . checked('before', $position, false) . ' />' .
				'<label for="wdsi-mailchimp-position-before">' . __('Vor dem Nachrichtentext', 'wdsi') . '</label>' .
			'<br />';

			$subscription_message = wdsi_getval($opts, 'mailchimp-subscription_message', wdsi_getval($defaults, 'mailchimp-subscription_message'));
			$subscription_message = $subscription_message ? $subscription_message : __('Alles gut, danke!', 'wdsi');
			$subscription_message = wp_strip_all_tags($subscription_message);
			echo '<br />' .
				'<label for="wdsi-mailchimp-subscription_message">' . __('Erfolgreiche Abonnementnachricht:', 'wdsi') . '</label>&nbsp;' .
				'<input type="text" class="long" name="wdsi-type[mailchimp-subscription_message]" id="wdsi-mailchimp-subscription_message" value="' . esc_attr($subscription_message) . '" />' .
			'';
		}
		echo '</div>';

		// --- Related posts
		echo '<div id="wdsi-content_type-options-related" class="wdsi-content_type" style="display:none">';
		$count = wdsi_getval($opts, 'related-posts_count', 3);
		echo '<label>' . __('Zeige so viele verwandte Beiträge:', 'wdsi') . ' </label>';
		echo '<div class="psource-ui-select"><select name="wdsi-type[related-posts_count]">';
		foreach (range(1, 10) as $item) {
			$selected = $item == $count ? 'selected="selected"' : '';
			echo '<option value="' . esc_attr($item) . '" ' . $selected . '>' . $item . '</option>';
		}
		echo '</select></div><br />';

		$taxonomies = get_taxonomies(array(
			'public' => true, 
		), 'objects');
		$related_tax = wdsi_getval($opts, 'related-taxonomy', 'post_tag');
		echo '<label>' . __('Verwandte Taxonomie:', 'wdsi') . ' </label>';
		echo '<div class="psource-ui-select"><select name="wdsi-type[related-taxonomy]">';
		foreach ($taxonomies as $tax => $item) {
			$selected = $tax == $related_tax ? 'selected="selected"' : '';
			echo '<option value="' . esc_attr($tax) . '" ' . $selected . '>' . $item->label . '</option>';
		}
		echo '</select></div><br />';
		echo $this->_create_hint(__('Verwandte Beiträge haben gemeinsame Begriffe mit angezeigten Beiträgen aus dieser Taxonomie', 'wdsi'));

		$has_thumbnails = wdsi_getval($opts, 'related-has_thumbnails');
		echo '' .
			'<input type="hidden" name="wdsi-type[related-has_thumbnails]" value="" />' .
			'<input type="checkbox" id="wdsi-has_thumbnails" name="wdsi-type[related-has_thumbnails]" value="1" ' . ($has_thumbnails ? 'checked="checked"' : '') . ' />' .
			'&nbsp;' .
			'<label for="wdsi-has_thumbnails">' . __('Vorschaubilder anzeigen?', 'wdsi') . '</label>' .
		'<br />';
		echo '</div>';

		// --- Widgets
		if ($data->get_option('allow_widgets')) {
			echo '<div id="wdsi-content_type-options-widgets" class="wdsi-content_type" style="display:none"></div>';
		}

		echo '</div>';
	}

	function render_show_after_box () {
		global $post;
		$opts = get_post_meta($post->ID, 'wdsi', true);
		$condition = wdsi_getval($opts, 'show_after-condition');
		$value = wdsi_getval($opts, 'show_after-rule');
		
		switch ($condition) {
			case "selector":
				$selector = 'checked="checked"';
				break;
			case "timeout":
				$timeout = 'checked="checked"';
				$value = (int)$value;
				break;
			case "percentage":
				$percentage = 'checked="checked"';
				$value = (int)$value;
				break;
		}

		$services = wdsi_getval($opts, 'services');
		$pos = wdsi_getval($opts, 'position');
		$width = wdsi_getval($opts, 'width');

		$override_checked = ($percentage || $timeout || $selector || $services || $pos || $width) ? 'checked="checked"' : '';
		echo '<p class="psource-ui">' .
			'<input type="checkbox" id="wdsi-override_show_if" name="wsdi-appearance_override" value="1" ' . $override_checked . ' /> ' .
			'<label for="wdsi-override_show_if">' . __('Globale Einstellungen überschreiben', 'wdsi') . '</label>' .
		'</p>';

		echo '<div id="wdsi-show_after_overrides-container" class="psource-ui" style="display:none">';

		// Initial condition
		echo '<fieldset id="wdsi-show_after"><legend>' . __('Zeigen nach', 'wdsi') . '</legend>';
		
		$percentage_select = '<div class="psource-ui-select"><select name="wdsi[show_after-rule]" ' . ($percentage ? '' : 'disabled="disabled"') . '>';
		for ($i=1; $i<100; $i++) {
			$selected = ($i == $value) ? 'selected="selected"' : '';
			$percentage_select .= "<option value='{$i}' {$selected}>{$i}&nbsp;</option>";
		}
		$percentage_select .= '</select></div>%';
		echo '<div>' .
			'<input type="radio" name="wdsi[show_after-condition]" value="percentage" id="wdsi-show_after-percentage" ' . $percentage . ' /> ' .
			'<label for="wdsi-show_after-percentage">' . 
				__('Nachricht anzeigen, nachdem so viel der Seite angesehen wurde', 'wdsi') .
				': ' .
			'</label>' .
			$percentage_select .
		'</div>';

		echo '<div>' .
			'<input type="radio" name="wdsi[show_after-condition]" value="selector" id="wdsi-show_after-selector" ' . $selector . ' /> ' .
			'<label for="wdsi-show_after-selector">' .
				__('Nachricht anzeigen, nachdem mit dieser ID an einem Element vorbei gescrollt wurde', 'wdsi') .
				': #' .
			'</label>' .
			'<input type="text" size="8" name="wdsi[show_after-rule]" id="" value="' . ($selector ? esc_attr($value) : '') . '" ' . ($selector ? '' : 'disabled="disabled"') . ' />' .
		'</div>';

		echo '<div>' .
			'<input type="radio" name="wdsi[show_after-condition]" value="timeout" id="wdsi-show_after-timeout" ' . $timeout . ' /> ' .
			'<label for="wdsi-show_after-timeout">' .
				__('Nachricht nach so vielen Sekunden anzeigen', 'wdsi') .
				': ' .
			'</label>' .
			'<input type="text" size="2" name="wdsi[show_after-rule]" id="" value="' . ($timeout ? esc_attr($value) : '') . '" ' . ($timeout ? '' : 'disabled="disabled"') . ' />' .
		'</div>';
		echo '</fieldset>';

		// Timeout
		echo '<fieldset id="wdsi-show_for"><legend>' . __('Zeigen für', 'wdsi') . '</legend>';
		$time = wdsi_getval($opts, 'show_for-time');
		$unit = wdsi_getval($opts, 'show_for-unit');

		$_times = array_combine(range(1,59), range(1,59));
		$_units = array(
			's' => __('Sekunden', 'wdsi'),
			'm' => __('Minuten', 'wdsi'),
			'h' => __('Stunden', 'wdsi'),
		);

		echo "<div class='psource-ui-select'><select name='wdsi[show_for-time]'>";
		foreach ($_times as $_time) {
			$selected = $_time == $time ? 'selected="selected"' : '';
			echo "<option value='{$_time}' {$selected}>{$_time}</option>";
		}
		echo "</select></div>";

		echo "<div class='psource-ui-select'><select name='wdsi[show_for-unit]'>";
		foreach ($_units as $key => $_unit) {
			$selected = $key == $unit ? 'selected="selected"' : '';
			echo "<option value='{$key}' {$selected}>{$_unit}</option>";
		}
		echo "</select></div>";
		echo '</fieldset>';

		// Position
		echo '<fieldset id="wdsi-position"><legend>' . __('Position', 'wdsi') . '</legend>';
		echo '<div  class="psource-ui-element_container">';
		echo '<div class="position-control">' .
			$this->_create_radiobox('position', 'left', $pos) .
			$this->_create_radiobox('position', 'top', $pos) .
			$this->_create_radiobox('position', 'right', $pos) .
			$this->_create_radiobox('position', 'bottom', $pos) .
		'</div>';
		echo '</div>';

		echo '<h4>' . __('Breite', 'wdsi') . '</h4>';
		$checked = (!(int)$width || 'full' == 'width') ? 'checked="checked"' : '';
		echo '' .
			'<input type="checkbox" name="wdsi[width]" value="full" id="wdsi-full_width" ' . $checked . ' autocomplete="off" />' .
			'&nbsp;' .
			'<label for="wdsi-full_width">' . __('Gesamtbreite', 'wdsi') . '</label>' .
		'';
		$display = $checked ? 'style="display:none"' : '';
		echo '<div id="wdsi-custom_width" ' . $display . '>';
		$disabled = $checked ? 'disabled="disabled"' : '';
		echo '' .
			'<label for="wdsi-width">' . __('Nachrichtenbreite', 'wdsi') . '</label>' .
			'&nbsp;' .
			'<input type="text" size="8" class="medium" name="wdsi[width]" id="wdsi-width" value="' . (int)$width . '" ' . $disabled . ' />px' .
		'';
		echo '</div>';
		echo '</fieldset>';

		// Theme
		echo '<fieldset id="wdsi-appearance"><legend>' . __('Appearance', 'wdsi') . '</legend>';
		echo '<h4>' . __('Design', 'wdsi') . '</h4>';
		$_themes = self::get_appearance_themes();
		foreach ($_themes as $theme => $label) {
			echo $this->_create_radiobox('theme', $theme, wdsi_getval($opts, 'theme')) .
				'<label for="theme-' . esc_attr($theme) . '">' . esc_html($label) . '</label><br />';
		}
		echo '<h4>' . __('Variation', 'wdsi') . '</h4>';
		$_themes = self::get_theme_variations();
		foreach ($_themes as $theme => $label) {
			echo $this->_create_radiobox('variation', $theme, wdsi_getval($opts, 'variation')) .
				'<label for="variation-' . esc_attr($theme) . '">' . esc_html($label) . '</label><br />';
		}
		echo '<h4>' . __('Farbschema', 'wdsi') . '</h4>';
		echo '<div class="wdsi-complex_element-container">';
		$_themes = self::get_variation_schemes();
		foreach ($_themes as $theme => $label) {
			echo $this->_create_color_radiobox('scheme', $theme, $label, wdsi_getval($opts, 'scheme')) .
				//'<label for="scheme-' . esc_attr($theme) . '">' . esc_html($label) . '</label><br />' .
			'';
		}
		echo '</div>';
		echo '</fieldset>';
		echo '<h4>' . __('Social Media Dienste', 'wdsi') . '</h4>';
        

		$this->render_services_box();
		echo '</div>';
		
	}

	function render_services_box () {
		global $post;
		$data = new Wdsi_Options;
		$opts = get_post_meta($post->ID, 'wdsi', true);

		$services = array (
			//'google' => 'Google',
			'facebook' => 'Facebook Like',
			'twitter' => 'Twittern',
			//'stumble_upon' => 'Stumble upon',
			//'delicious' => 'Del.icio.us',
			'reddit' => 'Reddit',
			'linkedin' => 'LinkedIn',
			'pinterest' => 'Pinterest',
			//'related_posts' => __('Related posts', 'wdsi'),
			//'mailchimp' => __('MailChimp subscription form', 'wdsi'),
		);
		if (function_exists('wdpv_get_vote_up_ms')) $services['post_voting'] = 'Post Voting'; 

		$load = wdsi_getval($opts, 'services');
		$load = is_array($load) ? $load : array();

		echo "<ul id='wdsi-services'>";
		foreach ($services as $key => $name) {
			$disabled = isset($load[$key]) ? '' : 'wdsi-disabled';
			if ('post_voting' === $key && !function_exists('wdpv_get_vote_up_ms')) continue;
			echo "<li class='wdsi-service-item {$disabled}'>";
			if (is_array($name)) {
				echo $name['name'] .
					"<br/><a href='#' class='wdsi_remove_service'>" . __('Entferne diesen Dienst', 'wdsi') . '</a>' .
					'<input type="hidden" name="wdsi[services][' . $key . '][name]" value="' . esc_attr($name['name']) . '" />' .
					'<input type="hidden" name="wdsi[services][' . $key . '][code]" value="' . esc_attr($name['code']) . '" />' .
				'</div>';
			} else {
				echo "<img src='" . WDSI_PLUGIN_URL . "/img/{$key}.png' width='50px' />" .
					"<input type='checkbox' name='wdsi[services][{$key}]' value='{$key}' " .
						"id='wdsi-services-{$key}' " .
						(in_array($key, $load) ? "checked='checked'" : "") .
					"/> " .
						"<label for='wdsi-services-{$key}'>{$name}</label>" .
					'<br />';
			}

			echo "<div class='clear'></div></li>";
		}
		echo "</ul><div class='clear'></div>";
	}

	/**
	 * Saves metabox data.
	 */
	function save_meta () {
		global $post;
		if ($post && self::POST_TYPE != $post->post_type) return false;

		if (wdsi_getval($_POST, 'show_if')) {
			// If we have Post Indexer present, remove the post save action for the moment.
			if (function_exists('post_indexer_post_insert_update')) {
				remove_action('save_post', 'post_indexer_post_insert_update');
			}
			if (!empty($_POST['not_in_the_pool'])) $_POST['show_if'] = false; // Cleaning up the message conditions if switched to pool rendering
			update_post_meta($post->ID, "wdsi_show_if", wdsi_getval($_POST, "show_if"));
		} else if (empty($_POST['show_if'])) update_post_meta($post->ID, 'wdsi_show_if', false); // Thanks, Vinod Dalvi

		if (wdsi_getval($_POST, 'wdsi')) {
			// If we have Post Indexer present, remove the post save action for the moment.
			if (function_exists('post_indexer_post_insert_update')) {
				remove_action('save_post', 'post_indexer_post_insert_update');
			}

			if (!empty($_POST['wsdi-appearance_override'])) update_post_meta($post->ID, "wdsi", wdsi_getval($_POST, "wdsi"));
			else update_post_meta($post->ID, "wdsi", false);

		}
		if (!empty($_POST['wdsi-type']['content_type'])) update_post_meta($post->ID, "wdsi-type", wdsi_getval($_POST, "wdsi-type"));
	}

	/**
	 * Updates pool status.
	 */
	function set_up_post_status ($data) {
		if (self::POST_TYPE != $data['post_type']) return $data;
		if (wdsi_getval($_POST, 'not_in_the_pool')) {
			$data['post_status'] = self::NOT_IN_POOL_STATUS;
		}
		return $data;
	}

	function add_custom_columns ($cols) {
		return array_merge($cols, array(
			'wdsi_type' => __('Inhaltstyp', 'wdsi'),
			'wdsi_pool' => __('Status', 'wdsi'),
			'wdsi_conditions' => __('Bedingungen', 'wdsi'),
		));
	}

	function fill_custom_columns ($col) {
		global $post;
		if ('wdsi_pool' != $col && 'wdsi_conditions' != $col && 'wdsi_type' != $col) return $col;
		
		switch ($col) {
			case 'wdsi_pool':
				//echo ('publish' == $post->post_status ? __('Im Pool', 'wdsi') : __('Not in pool', 'wdsi'));
				echo ('publish' == $post->post_status ? __('Global', 'wdsi') : __('Specific', 'wdsi'));
				break;
			case 'wdsi_type':
				$all_content_types = array(
					'text' => __('Textnachricht', 'wdsi'),
					//'mailchimp' => __('MailChimp-Anmeldeformular', 'wdsi'),
					'related' => __('Verwandte Beiträge', 'wdsi'),
					'widgets' => __('Seitenleisten Widgets', 'wdsi'),
				);
				$type = get_post_meta($post->ID, 'wdsi-type', true);
				$content_type = wdsi_getval($type, 'content_type', 'text');
				if (!empty($all_content_types[$content_type])) echo $all_content_types[$content_type];
				break;
			case 'wdsi_conditions':
				if (self::NOT_IN_POOL_STATUS == $post->post_status) {
					$post_links = array();
					global $wpdb;
					$appears_on = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='wdsi_message_id' AND meta_value=%d", $post->ID));
					if (empty($appears_on)) {
						_e("Not applicable", 'wdsi');
						break;
					} else foreach ($appears_on as $target_id) {
						$post_links[] = '<a href="' . admin_url('post.php?action=edit&post=' . $target_id) . '">' . get_the_title($target_id) . '</a>';
					}
					printf(__('Erscheint auf %s', 'wdsi'), join('<br />', $post_links));
					break;
				} else if ('publish' != $post->post_status) {
					printf(__('Nicht zutreffend, &quot;%s&quot; Slide-Ins werden nicht angezeigt.', 'wdsi'), $post->post_status);
					break;
				}
				$show = get_post_meta($post->ID, 'wdsi_show_if', true);
				switch (wdsi_getval($show, 'user')) {
					case "show_if_logged_in":
						_e("Wird für angemeldete Benutzer angezeigt", 'wdsi');
						break;
					case "show_if_not_logged_in":
						_e("Für Besucher gezeigt", 'wdsi');
						break;
					case "show_if_never_commented":
						_e("Wird für Nicht-Kommentatoren angezeigt", 'wdsi');
						break;
					default:
						_e("Kann für alle Benutzer angezeigt werden", 'wdsi');
				}
				echo '<br />';
				$types = get_post_types(array('public' => true), 'objects');
				$show_page = wdsi_getval($show, 'page');
				switch ($show_page) {
					case "show_if_singular":
						_e("Wird auf einzelnen Seiten angezeigt", 'wdsi');
						break;
					case "show_if_not_singular":
						_e("Auf Archivseiten angezeigt", 'wdsi');
						break;
					case "show_if_home":
						_e("Auf der Startseite angezeigt", 'wdsi');
						break;
					default:
						$shown_for_types = array();
						foreach ($types as $type => $obj) {
							if ("show_if_{$type}" == $show_page) $shown_for_types[] = sprintf(__('Wird auf %s Seiten angezeigt', 'wdsi'), $obj->labels->name);
						}
						if ($shown_for_types) {
							echo join('<br />', $shown_for_types);
						} else {
							_e("Kann auf allen Seiten erscheinen", 'wdsi');
						}
						break;
				}
				break;
		}
	}

	
/* ----- Model procedures: message ----- */


	public function get_message_data ($post) {
		$post_id = (is_object($post) && !empty($post->ID)) ? $post->ID : false;
		
		$pool = $this->_get_active_messages_pool($post_id);
		return $pool ? $pool[0] : false;
	}


/* ----- Model procedures: pool ----- */


	/**
	 * Fetching out all the currently active messages.
	 */
	private function _get_active_messages_pool ($specific_post_id=false) {
		$pool = array();

		$query = new WP_Query(array(
			'post_type' => self::POST_TYPE,
			'posts_per_page' => -1,
			'suppress_filters' => apply_filters('wdsi-pool_query-suppress_filters', class_exists('CoursePress_Virtual_Page')),
		));
		$pool = $query->posts ? $query->posts : array();

		if ($specific_post_id) {
			$msg_id = get_post_meta($specific_post_id, 'wdsi_message_id', true);
			if ($msg_id) $pool = array(get_post($msg_id));
		}
		$pool = array_filter($pool, array($this, '_filter_active_messages_pool'));
		shuffle($pool);

		return $pool;
	}

	/**
	 * Filters messages in pool to active ones.
	 * `array_filter` callback.
	 */
	function _filter_active_messages_pool ($msg) {
		$use = true;
		$show = get_post_meta($msg->ID, 'wdsi_show_if', true);
		switch (wdsi_getval($show, 'user')) {
			case "show_if_logged_in":
				$use = is_user_logged_in(); break;
			case "show_if_not_logged_in":
				$use = !(is_user_logged_in()); break;
			case "show_if_never_commented":
				$use = isset($_COOKIE['comment_author_'.COOKIEHASH]); break;
		}
		if (!$use) return $use;
		
		$page_condition = wdsi_getval($show, 'page');
		if (empty($page_condition)) return true;

		switch ($page_condition) {
			case "show_if_singular":
				$use = is_singular(); break;
			case "show_if_not_singular":
				$use = !(is_singular()); break;
			case "show_if_home":
				$use = is_front_page(); break;
		}
		if (!$use) return $use;
		$types = get_post_types(array('public' => true), 'objects');
		foreach ($types as $type => $obj) {
			$name = "show_if_{$type}";
			if ($name == $page_condition) $use = is_singular($type);
		}
		return $use; // In the pool, by default
	}

	function _create_radiobox ($name, $value, $option) {
		$checked = (@$option == $value) ? true : false;
		$class = $value ? "class='{$value}'" : '';
		return "<input type='radio' name='wdsi[{$name}]' {$class} id='{$name}-{$value}' value='{$value}' " . ($checked ? 'checked="checked" ' : '') . " /> ";
	}

	function _create_color_radiobox ($name, $value, $label, $option) {
		$color = esc_attr($value);
		$label= esc_attr($label);
		return "<label class='wdsi-color-container' for='{$name}-{$value}'>" .
			$this->_create_radiobox($name, $value, $option) .
			"<div class='wdsi-color wdsi-{$color}' title='{$label}'></div>" .
		'</label>';
	}

	function _create_hint ($text) {
		return "<p class='info'><span class='info'></span>{$text}</p>";
	}

	public static function message_markup ($message, $opts, $output=true) {
		if (empty($message->ID)) return false;

		$msg = get_post_meta($message->ID, 'wdsi', true);
		$type = get_post_meta($message->ID, 'wdsi-type', true);
		
		$services = wdsi_getval($msg, 'services');
		$services = $services ? $services : wdsi_getval($opts, 'services');
		$services = is_array($services) ? $services : array();

		$skip_script = wdsi_getval($opts, 'skip_script');
		$skip_script = is_array($skip_script) ? $skip_script : array();

		$no_count = wdsi_getval($opts, 'no_count');
		$no_count = is_array($no_count) ? $no_count : array();

		$content_type = wdsi_getval($type, 'content_type', 'text');

        $data = new Wdsi_Options;
        if ('widgets' == $content_type && !$data->get_option('allow_widgets')) return false; // Break on this

		$related_posts_count = wdsi_getval($type, 'related-posts_count', 3);
		$related_taxonomy = wdsi_getval($type, 'related-taxonomy', 'post_tag');
		$related_has_thumbnails = wdsi_getval($type, 'related-has_thumbnails');

		$mailchimp_placeholder = wdsi_getval($type, 'mailchimp-placeholder', 'you@yourdomain.com');
		$mailchimp_position = wdsi_getval($type, 'mailchimp-position', 'after');

		$position = wdsi_getval($msg, 'position') ? $msg['position'] : wdsi_getval($opts, 'position');
		$position = $position ? $position : 'left';

		$percentage = $selector = $timeout = false;
		$condition =  wdsi_getval($msg, 'show_after-condition') ? $msg['show_after-condition'] :wdsi_getval($opts, 'show_after-condition');
		$value = wdsi_getval($msg, 'show_after-rule') ? $msg['show_after-rule'] : wdsi_getval($opts, 'show_after-rule');
		switch ($condition) {
			case "selector":
				$selector = "#{$value}";
				$percentage = '0%';
				$timeout = '0s';
				break;
			case "timeout":
				$selector = false;
				$percentage = '0%';
				$timeout = sprintf('%ds', (int)$value);
				break;
			case "percentage":
			default:
				$selector = false;
				$percentage = sprintf('%d%%', (int)$value);
				$timeout = '0s';
				break;
		}

		$_theme = wdsi_getval($msg, 'theme') ? $msg['theme'] : wdsi_getval($opts, 'theme');
		$theme = $_theme && in_array($_theme, array_keys(Wdsi_SlideIn::get_appearance_themes())) ? $_theme : 'minimal';

		$_variation = wdsi_getval($msg, 'variation') ? $msg['variation'] : wdsi_getval($opts, 'variation');
		$variation = $_variation && in_array($_variation, array_keys(Wdsi_SlideIn::get_theme_variations())) ? $_variation : 'light';
		
		$_scheme = wdsi_getval($msg, 'scheme') ? $msg['scheme'] : wdsi_getval($opts, 'scheme');
		$scheme = $_scheme && in_array($_scheme, array_keys(Wdsi_SlideIn::get_variation_schemes())) ? $_scheme : 'red';

		$expire_after = wdsi_getval($msg, 'show_for-time') ? $msg['show_for-time'] : wdsi_getval($opts, 'show_for-time');
		$expire_after = $expire_after ? $expire_after : 10;
		$expire_unit = wdsi_getval($msg, 'show_for-unit') ? $msg['show_for-unit'] : wdsi_getval($opts, 'show_for-unit');
		$expire_unit = $expire_unit ? $expire_unit : 's';
		$expire_timeout = sprintf("%d%s", $expire_after, $expire_unit);

		$full_width = $width = false;
		$_width = wdsi_getval($msg, 'width') ? $msg['width'] : wdsi_getval($opts, 'width');
		if (!(int)$_width || 'full' == $width) {
			$full_width = 'slidein-full';
		} else {
			$width = 'style="width:' . (int)$_width . 'px;"';
		}
		
		$out = '';
		if (empty($output)) {
			ob_start();
		}
		require_once (WDSI_PLUGIN_BASE_DIR . '/lib/forms/box_output.php');
		if (empty($output)) {
			$out = ob_get_contents();
			ob_end_clean();
		}
		return $out;
	}
}
