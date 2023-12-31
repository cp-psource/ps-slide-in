<?php
class Wdsi_AdminFormRenderer {

	function _get_option ($key=false, $pfx='wdsi') {
		$opts = get_option($pfx);
		if (!$key) return $opts;
		return @$opts[$key];
	}

	function _create_checkbox ($name, $pfx='wdsi') {
		$opt = $this->_get_option($name, $pfx);
		$value = @$opt[$name];
		return
			"<input type='radio' name='{$pfx}[{$name}]' id='{$name}-yes' value='1' " . ((int)$value ? 'checked="checked" ' : '') . " /> " .
				"<label for='{$name}-yes'>" . __('Ja', 'wdsi') . "</label>" .
			'&nbsp;' .
			"<input type='radio' name='{$pfx}[{$name}]' id='{$name}-no' value='0' " . (!(int)$value ? 'checked="checked" ' : '') . " /> " .
				"<label for='{$name}-no'>" . __('Nein', 'wdsi') . "</label>" .
		"";
	}

	function _create_hint ($text) {
		return "<p class='info'><span class='info'></span>{$text}</p>";
	}

	function _create_radiobox ($name, $value, $value_as_class=false) {
		$opt = $this->_get_option($name);
		$checked = (@$opt == $value) ? true : false;
		$name = esc_attr($name);
		$class = $value_as_class ? "class='{$name} {$value}'" : '';
		return "<input type='radio' name='wdsi[{$name}]' {$class} id='{$name}-{$value}' value='{$value}' " . ($checked ? 'checked="checked" ' : '') . " /> ";
	}

	function _create_color_radiobox ($name, $value, $label) {
		$color = esc_attr($value);
		$label= esc_attr($label);
		return "<label class='wdsi-color-container' for='{$name}-{$value}'>" .
			$this->_create_radiobox($name, $value) .
			"<div class='wdsi-color wdsi-{$color}' title='{$label}'></div>" .
		'</label>';
	}
	
	function create_show_after_box () {
		$percentage = $selector = $timeout = false;
		$condition = $this->_get_option('show_after-condition');
		$value = $this->_get_option('show_after-rule');
		
		switch ($condition) {
			case "selector":
				$selector = 'checked="checked"';
				break;
			case "timeout":
				$timeout = 'checked="checked"';
				$value = (int)$value;
				break;
			case "percentage":
			default:
				$percentage = 'checked="checked"';
				$value = (int)$value;
				break;
		}
		
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
				__('Nachricht anzeigen, nachdem an einem Element mit dieser ID vorbei gescrollt wurde', 'wdsi') .
				': #' .
			'</label>' .
			'<input type="text" size="8" class="medium" name="wdsi[show_after-rule]" id="" value="' . ($selector ? esc_attr($value) : '') . '" ' . ($selector ? '' : 'disabled="disabled"') . ' />' .
		'</div>';

		echo '<div>' .
			'<input type="radio" name="wdsi[show_after-condition]" value="timeout" id="wdsi-show_after-timeout" ' . $timeout . ' /> ' .
			'<label for="wdsi-show_after-timeout">' .
				__('Nachricht nach so vielen Sekunden anzeigen', 'wdsi') .
				': ' .
			'</label>' .
			'<input type="text" size="2" class="short" name="wdsi[show_after-rule]" id="" value="' . ($timeout ? esc_attr($value) : '') . '" ' . ($timeout ? '' : 'disabled="disabled"') . ' />' .
		'</div>';
	}

	function create_show_for_box () {
		$time = $this->_get_option('show_for-time');
		$unit = $this->_get_option('show_for-unit');

		$_times = array_combine(range(1,59), range(1,59));
		$_units = array(
			's' => __('Sekunden', 'wdsi'),
			'm' => __('Minuten', 'wdsi'),
			'h' => __('Stunden', 'wdsi'),
		);

		// Time
		echo "<div class='psource-ui-select'><select name='wdsi[show_for-time]'>";
		foreach ($_times as $_time) {
			$selected = $_time == $time ? 'selected="selected"' : '';
			echo "<option value='{$_time}' {$selected}>{$_time}</option>";
		}
		echo "</select></div>";

		// Unit
		echo "<div class='psource-ui-select'><select name='wdsi[show_for-unit]'>";
		foreach ($_units as $key => $_unit) {
			$selected = $key == $unit ? 'selected="selected"' : '';
			echo "<option value='{$key}' {$selected}>{$_unit}</option>";
		}
		echo "</select></div>";
	}

	function create_closing_box () {
		echo $this->_create_hint(__('Wenn ein Besucher eine Slide In Nachricht schließt, möchte ich', 'wdsi'));
		echo '<div class="wdsi-on_hide-condition">' .
			$this->_create_radiobox('on_hide', '', true) .
			'<label for="on_hide-">' . __('dem Besucher weiterhin Nachrichten anzeigen', 'wdsi') . '</label>' .
		'</div>';
		echo '<div class="wdsi-on_hide-condition">' .
			$this->_create_radiobox('on_hide', 'page', true) .
			'<label for="on_hide-page">' . __('Nachrichten auf dieser Seite oder diesem Beitrag für den Besucher verstecken', 'wdsi') . '</label>' .
		'</div>';
		echo '<div class="wdsi-on_hide-condition">' .
			$this->_create_radiobox('on_hide', 'all', true) .
			'<label for="on_hide-all">' . __('alle Nachrichten für den Besucher verstecken', 'wdsi') . '</label>' .
		'</div>';

		$_times = array_combine(range(1,31), range(1,31));
		$_units = array(
			'hours' => __('Stunden', 'wdsi'),
			'days' => __('Tage', 'wdsi'),
			'weeks' => __('Wochen', 'wdsi'),
		);
		$on_hide = $this->_get_option('on_hide');
		$enabled = !empty($on_hide);
		$reshow_after_time = $enabled ? $this->_get_option('reshow_after-time') : false;
		$reshow_after_units = $enabled ? $this->_get_option('reshow_after-units') : false;

		$time_box = "<div class='psource-ui-select'><select name='wdsi[reshow_after-time]'><option value=''></option>";
		foreach ($_times as $_time) {
			$selected = $_time == $reshow_after_time ? 'selected="selected"' : '';
			$time_box .= "<option value='{$_time}' {$selected}>{$_time}</option>";
		}
		$time_box .= "</select></div>";

		$unit_box = "<div class='psource-ui-select'><select name='wdsi[reshow_after-units]'><option value=''></option>";
		foreach ($_units as $key => $_unit) {
			$selected = $key == $reshow_after_units ? 'selected="selected"' : '';
			$unit_box .= "<option value='{$key}' {$selected}>{$_unit}</option>";
		}
		$unit_box .= "</select></div>";

		echo '<div class="wdsi-reshow_after" ' . ($enabled ? '' : 'style="display:none"') . ' >' .
		'<label for="">' . __('Zeige die Nachrichten erneut an, nach:', 'wdsi') . '</label><br />' .
		"{$time_box} {$unit_box}" .
	'</div>';
	}
	
	function create_position_box () {
		echo '<div class="position-control">' .
			$this->_create_radiobox('position', 'left', true) .
			$this->_create_radiobox('position', 'top', true) .
			$this->_create_radiobox('position', 'right', true) .
			$this->_create_radiobox('position', 'bottom', true) .
		'</div>';
		echo '<br /><br />' .
			$this->_create_hint(__('Hier wird Deine Nachricht angezeigt.', 'wdsi'))
		;
	}

	function create_msg_width_box () {
		$width = $this->_get_option('width');
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
	}

	function create_appearance_box () {
		echo '<h4>' . __('Theme', 'wdsi') . '</h4>';
		$_themes = Wdsi_SlideIn::get_appearance_themes();
		foreach ($_themes as $theme => $label) {
			echo $this->_create_radiobox('theme', $theme) .
				'<label for="theme-' . esc_attr($theme) . '">' . esc_html($label) . '</label><br />';
		}
		echo '<h4>' . __('Variation', 'wdsi') . '</h4>';
		$_themes = Wdsi_SlideIn::get_theme_variations();
		foreach ($_themes as $theme => $label) {
			echo $this->_create_radiobox('variation', $theme) .
				'<label for="variation-' . esc_attr($theme) . '">' . esc_html($label) . '</label><br />';
		}
	}

	function create_color_scheme_box () {
		echo '<div class="wdsi-complex_element-container">';
		$_themes = Wdsi_SlideIn::get_variation_schemes();
		foreach ($_themes as $theme => $label) {
			echo $this->_create_color_radiobox('scheme', $theme, $label) .
				//'<label for="scheme-' . esc_attr($theme) . '">' . esc_html($label) . '</label><br />' .
			'';
		}
		echo '<p class="wdsi-preview_slide"><a href="#preview" data-working="' . esc_attr(__('Verarbeite... bitte warte', 'wdsi')) . '">' . __('Vorschau', 'wdsi') . '</a></p>';
		echo '</div>';
	}
	
	function create_services_box () {
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
		$externals = array (
			//'google',
			'twitter',
			'linkedin',
			'pinterest',
		);
		$countable = array(
			//'google',
			'twitter',
			'pinterest',
		);

		$load = $this->_get_option('services');
		$load = is_array($load) ? $load : array();

		$services = array_merge($load, $services);

		$skip = $this->_get_option('skip_script');
		$skip = is_array($skip) ? $skip : array();

		$no_count = $this->_get_option('no_count');
		$no_count = is_array($no_count) ? $no_count : array();

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
				if (in_array($key, $countable)) echo
					"<input type='checkbox' name='wdsi[no_count][{$key}]' value='{$key}' " .
						"id='wdsi-no_count-{$key}' " .
						(in_array($key, $no_count) ? "checked='checked'" : "") .
					"/> " .
						"<label for='wdsi-no_count-{$key}'>" .
							'<small>' . __('Zählungen nicht anzeigen', 'wdsi') . '</small>' .
						"</label>" .
					"<br />";
				if (in_array($key, $externals)) echo
					"<input type='checkbox' name='wdsi[skip_script][{$key}]' value='{$key}' " .
						"id='wdsi-skip_script-{$key}' " .
						(in_array($key, $skip) ? "checked='checked'" : "") .
					"/> " .
						"<label for='wdsi-skip_script-{$key}'>" .
							'<small>' . __('Meine Seite verwendet bereits Skripte von diesem Dienst', 'wdsi') . '</small>' .
						"</label>" .
					"<br />";
			}

			echo "<div class='clear'></div></li>";
		}
		echo "</ul>";

		echo '<h4>' . __('Füge dein eigenes hinzu:', 'wdsi') . '</h4>';
		echo '' .
			'<input type="text" name="wdsi[new_service][name]" id="wdsi_new_custom_service-name" placeholder="' . esc_attr(__('Name', 'wdsi')) . '" class="medium" />' .
			'&nbsp;' .
			'<input type="text" name="wdsi[new_service][code]" id="wdsi_new_custom_service-code" placeholder="' . esc_attr(__('Code', 'wdsi')) . '" class="long" />' .
			'&nbsp;' .
			'<button type="submit">' . __('Hinzufügen', 'wdsi') . '</button>' .
		'';
	}

	function create_mailchimp_box () {
		/*
		echo '<label for="mailchimp-enabled-yes">' . __('Enable MailChimp integration:', 'wdsi') . ' </label>' .
			$this->_create_checkbox('mailchimp-enabled') .
		'<br />';
		*/
		$api_key = $this->_get_option('mailchimp-api_key');
		echo '<label for="wdsi-mailchimp-api_key">' . __('MailChimp API Key:') . '</label>' .
			'<input type="text" class="long" name="wdsi[mailchimp-api_key]" id="wdsi-mailchimp-api_key" value="' . esc_attr($api_key) . '" />' .
		'<br />';
		if (!$api_key) {
			echo $this->_create_hint(__('Gib hier Deinen API-Schlüssel ein und speichere die Einstellungen, um fortzufahren', 'wdsi'));
			return false;
		}

		$mailchimp = new Wdsi_Mailchimp($api_key);

		$lists = $mailchimp->get_lists();
		$current = $this->_get_option('mailchimp-default_list');

		echo '<label>' . __('Standardabonnementliste:', 'wdsi') . ' </label>';
		echo '<div class="psource-ui-select"><select name="wdsi[mailchimp-default_list]">';
		echo '<option></option>';
		foreach ($lists as $list) {
			$selected = $list['id'] == $current ? 'selected="selected"' : '';
			echo '<option value="' . esc_attr($list['id']) . '" ' . $selected . '>' . $list['name'] . '</option>';
		}
		echo '</select></div>';

		// We got this far, we have the API key
		echo '&nbsp;<a href="#mcls-refresh" id="wdcp-mcls-refresh">' . __('Aktualisieren', 'wdsi') . '</a>';
		echo $this->_create_hint(__('Wähle eine Standardliste aus, die Deine Besuchern abonnieren.', 'wdsi'));

		$subscription_message = $this->_get_option('mailchimp-subscription_message');
		$subscription_message = $subscription_message ? $subscription_message : __('Alles gut, danke!', 'wdsi');
		$subscription_message = wp_strip_all_tags($subscription_message);
		echo '<br />' .
			'<label for="wdsi-mailchimp-subscription_message">' . __('Erfolgreiche Abonnementnachricht:', 'wdsi') . '</label>&nbsp;' .
			'<input type="text" class="long" name="wdsi[mailchimp-subscription_message]" id="wdsi-mailchimp-subscription_message" value="' . esc_attr($subscription_message) . '" />' .
		'';
	}

	function create_custom_css_box () {
		$css = esc_textarea(wp_strip_all_tags($this->_get_option('css-custom_styles')));
		$placeholder = esc_attr(__('Zusätzliche CSS-Stile', 'wdsi'));
		echo "<textarea class='widefat' rows='8' name='wdsi[css-custom_styles]' placeholder='{$placeholder}'>{$css}</textarea>";
		echo $this->_create_hint(__('Füge zusätzlichen CSS-Regeln hinzu, die Du einschließen möchtest', 'wdsi'));
	}

	function create_advanced_box () {
		echo '' .
			'<input type="hidden" name="wdsi[allow_shortcodes]" value="" />' .
			'<input type="checkbox" name="wdsi[allow_shortcodes]" id="wdsi-allow_shortcodes" value="1" ' . ($this->_get_option('allow_shortcodes') ? 'checked="checked"' : '') . ' />' .
			'&nbsp;' .
			'<label for="wdsi-allow_shortcodes">' . __('Erlaube Shortcodes', 'wdsi') . '</label>' . 
			$this->_create_hint(__('Durch Aktivieren dieser Option können Shortcodes in Deinen Slide-In-Nachrichten verarbeitet werden.', 'wdsi')) .
		'';
		echo '' .
			'<input type="hidden" name="wdsi[allow_widgets]" value="" />' .
			'<input type="checkbox" name="wdsi[allow_widgets]" id="wdsi-allow_widgets" value="1" ' . ($this->_get_option('allow_widgets') ? 'checked="checked"' : '') . ' />' .
			'&nbsp;' .
			'<label for="wdsi-allow_widgets">' . __('Erlaube Widgets', 'wdsi') . '</label>' . 
			$this->_create_hint(__('Durch Aktivieren dieser Option wird eine neue Seitenleiste hinzugefügt, die Du in Designs &gt; Widgets mit Widgets füllen kannst.', 'wdsi')) .
		'';

		if (class_exists('PSeCommerce')) {
			echo '' .
				'<input type="hidden" name="wdsi[show_on_psecommerce_pages]" value="" />' .
				'<input type="checkbox" name="wdsi[show_on_psecommerce_pages]" id="wdsi-show_on_psecommerce_pages" value="1" ' . ($this->_get_option('show_on_psecommerce_pages') ? 'checked="checked"' : '') . ' />' .
				'&nbsp;' .
				'<label for="wdsi-show_on_psecommerce_pages">' . __('Auf PSeCommerce-Seiten anzeigen (außer Produkte):', 'wdsi') . '</label>' . 
				$this->_create_hint(__('Entscheide ob Deine Nachrichten auf virtuellen PSeCommerce-Seiten angezeigt werden sollen.', 'wdsi')) .
			'';
		}


		$hook = $this->_get_option('custom_injection_hook');
		$hook = $hook ? $hook : Wdsi_SlideIn::get_default_injection_hook();
		echo '' .
			'<label for="wdsi-custom_injection_hook">' . __('Kundenspezifischer Injektionshaken', 'wdsi') . '</label>' . 
			'&nbsp;' .
			'<input type="text" class="long" name="wdsi[custom_injection_hook]" id="wdsi-custom_injection_hook" value="' . esc_attr($hook) . '" />' .
			$this->_create_hint(__('Versuche es mit einem anderen Injektionshaken, wenn Du Probleme mit dem Standardhaken hast. Standardmäßig leer lassen.', 'wdsi')) .
		'';
	}
}