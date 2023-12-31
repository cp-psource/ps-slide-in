(function ($) {

function toggle_pool_conditions () {
	var $check = $("#wdsi-not_in_the_pool"),
		$target = $("#wdsi-conditions-container")
	;
	if (!$check.is(":checked")) $target.show();
	else $target.hide();
}

function toggle_show_after_overrides () {
	var $check = $("#wdsi-override_show_if"),
		$target = $("#wdsi-show_after_overrides-container")
	;
	if ($check.is(":checked")) $target.show();
	else $target.hide();
}

function toggle_content_types () {
	var $check = $(':radio[name="wdsi-type[content_type]"]');
	if (!$check.length) return false;

	var selected_raw = $check.filter(":checked").val(),
		selected = selected_raw || 'text',
		$item = $("#wdsi-content_type-options-" + selected),
		$editor = $(".postarea")
	;
	if (!$item.length) return false;

	$('.wdsi-content_type').hide();
	$item.show();

	if ('related' == selected || 'widgets' == selected) $editor.hide();
	else $editor.show();
}

function toggle_reshow_conditions () {
	var $check = $(':radio[name="wdsi[on_hide]"]');
	if (!$check.length) return false;

	var selected = $check.filter(":checked").val(),
		reshow = !!selected,
		$item = $(".wdsi-reshow_after")
	;
	if (reshow) $item.show('medium');
	else $item.hide('medium');
	return false;
}

function init_services() {
    /* ----- Sortables ----- */
    var $enabled = $("#wdsi-services");
    var $disabled = $("#wdsi-disabled_services");
    var $lis = $enabled.find("li");

    function init_sortables() {
        $lis.each(function() {
            var $me = $(this);
            var $hub = $me.is(".wdsi-disabled") ? $disabled : $enabled;

            $hub.append($me);
        });

        $enabled.sortable({
            items: "li",
            update: function(event, ui) {
                // Code to handle sorting update if needed
            }
        });

        $lis.find('input[name*="services"]').on("change", function() {
            var $in = $(this);
            var $me = $in.closest("li");
            
            if ($in.is(":checked")) {
                $me.removeClass("wdsi-disabled");
            } else {
                $me.addClass("wdsi-disabled");
            }

            init_sortables();
        });
    }

    init_sortables();

    $(".wdsi_remove_service").on("click", function() {
        $(this).closest('li.wdsi-service-item').remove();
        return false;
    });
}

// Aufrufen der Funktion nach dem Laden der Seite
$(document).ready(function() {
    init_services();
});

$(function () {

	init_services();

	$(':radio[name="wdsi-type[content_type]"]').on("change", toggle_content_types);
	toggle_content_types();

	$("#wdsi-not_in_the_pool").on("change", toggle_pool_conditions);
	toggle_pool_conditions();

	$("#wdsi-override_show_if").on("change", toggle_show_after_overrides);
	$('[name="wdsi[show_after-condition]"]').on("change", function () {
		$('[name="wdsi[show_after-rule]"]').attr("disabled", true);
		$(this).parent("div").find('[name="wdsi[show_after-rule]"]').attr("disabled", false);
	});
	toggle_show_after_overrides();

	$(':radio[name="wdsi[on_hide]"]').on("change", toggle_reshow_conditions);
	toggle_reshow_conditions();

	// Add fieldset clearing links
	$("#wdsi-conditions-container fieldset").each(function () {
		$(this)
			.append('<a href="#clear-set" class="wdsi-clear_set">' + l10nWdsi.clear_set + '</a>')
			.find("a.wdsi-clear_set").on("click", function () {
				$(this).parents("fieldset").first().find(":radio").attr("checked", false);
				return false;
			});
		;
	});

	// Width toggling
	$("#wdsi-full_width").on("change", function () {
		if (!$("#wdsi-full_width").is(":checked")) {
			$("#wdsi-custom_width").show().find("input").attr("disabled", false);
			$('label[for="wdsi-full_width"]').addClass("wdsi-not_applicable");
		} else {
			$("#wdsi-custom_width").hide().find("input").attr("disabled", true);
			$('label[for="wdsi-full_width"]').removeClass("wdsi-not_applicable");
		}
	});
});

})(jQuery);


(function ($) {
	var _timeout = 1;
	function preview () {
		return $.post(ajaxurl, {
			action: "wdsi_preview_slide",
			opts: {
				"theme": $('[name="wdsi[theme]"]:checked').val(),
				"show_after-condition": "timeout",
				"show_after-rule": "" + _timeout,
				"variation": $('[name="wdsi[variation]"]:checked').val(),
				"position": $('[name="wdsi[position]"]:checked').val(),
				"scheme": $('[name="wdsi[scheme]"]:checked').val(),
				"width": ($("#wdsi-full_width").is(":checked") ? 'full' : $("#wdsi-width").val())
			}
		}, function (resp) {
			if (!(resp && "data" in resp && resp.data && "out" in resp.data && resp.data.out)) return false;
			$("body")
				.find("#wdsi-slide_in").remove().end()
				.append(resp.data.out)
			;
			$(document).trigger("wdsi-init");
		}, 'json');
	}
	function preview_slide (e) {
		if (e && e.preventDefault) {
			e.preventDefault();
			e.stopPropagation();
		}
		var $me = $(this),
			normal = $me.text(),
			working = $me.attr("data-working"),
			promise = preview()
		;
		$me.text(working);
		promise.always(function () {
			setTimeout(function () {
				$me.text(normal);
			}, _timeout * 1100);
		});
		return false;
	}
	function init () {
		if (!("_wdsi_data" in window)) return false;
		$(document).on("click", ".wdsi-preview_slide a", preview_slide);
	}
	init();
})(jQuery);