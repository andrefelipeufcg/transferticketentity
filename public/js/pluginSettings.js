$(function() {
    const root = $('#tte-settings');
    if (root.length === 0) {
        return;
    }

    const ajaxUrl = root.data('ajax-url');
    const text = (key) => root.attr('data-text-' + key) || '';
    const overlay = $('#tte-settings-overlay');
    const overlayText = $('#tte-settings-overlay-text');
    const checkboxNames = {
        entities: 'entities_id[]',
        profiles: 'profiles_id[]',
        groups: 'groups_id[]',
    };
    const activeOverlays = new Map();
    let overlayId = 0;

    function showOverlay(message) {
        const id = ++overlayId;
        activeOverlays.set(id, message);
        overlayText.text(message);
        overlay.css('display', 'flex');
        return id;
    }

    function hideOverlay(id) {
        activeOverlays.delete(id);
        const messages = Array.from(activeOverlays.values());
        if (messages.length === 0) {
            overlay.hide();
        } else {
            overlayText.text(messages[messages.length - 1]);
        }
    }

    function showError(message) {
        if (typeof glpi_toast_error === 'function') {
            glpi_toast_error(message);
        } else {
            window.alert(message);
        }
    }

    function responseMessage(xhr, fallback) {
        const body = xhr.responseJSON;
        if (body && typeof body.message === 'string' && body.message !== '') {
            return body.message;
        }
        return fallback;
    }

    function checkboxes(form, kind) {
        return form.find('input[type="checkbox"][name="' + checkboxNames[kind] + '"]');
    }

    // The native GLPI tab loader requests these URLs: show the loading overlay while they run.
    function isSettingsTabRequest(settings) {
        const url = decodeURIComponent(String(settings.url || ''));
        return url.indexOf('common.tabs.php') !== -1 && url.indexOf('Transferticketentity') !== -1;
    }

    function initEntityForms() {
        $('form[data-form="entities"]').each(function() {
            const form = $(this);
            if (form.data('tteInitialized')) {
                return;
            }
            form.data('tteInitialized', true);
            refreshEntityForm(form);
        });
    }

    function isTransferAllowed(form) {
        return String(form.find('select[name="allow_transfer"]').val()) === '1';
    }

    // Shows only the fields that apply to the current choices and resets the hidden ones.
    function refreshEntityForm(form) {
        const field = (name) => form.find('select[name="' + name + '"]');
        const hide = (name, hidden) => {
            const select = field(name);
            select.closest('.form-field').toggle(!hidden);
            if (hidden && String(select.val()) !== '0') {
                select.val('0').trigger('change');
            }
        };

        const allowed = isTransferAllowed(form);
        hide('allow_entity_only_transfer', !allowed);
        hide('justification_transfer', !allowed);
        hide('keep_category', !allowed);
        hide('groups_id', !allowed || String(field('allow_entity_only_transfer').val()) !== '1');
        hide('itilcategories_id', !allowed || String(field('keep_category').val()) === '1');
    }

    $(document).on('ajaxSend', function(event, xhr, settings) {
        if (isSettingsTabRequest(settings)) {
            settings.tteOverlayId = showOverlay(text('loading'));
        }
    });

    $(document).on('ajaxComplete', function(event, xhr, settings) {
        if (settings.tteOverlayId) {
            hideOverlay(settings.tteOverlayId);
            setTimeout(initEntityForms, 0);
        }
    });

    $(document).on('glpi.tab.loaded', initEntityForms);

    // Tabs already loaded keep their old HTML: reload them so every view shows the data that was just saved.
    function refreshOtherPanes(form) {
        const ownPane = form.closest('[data-glpi-tab-content]');
        $('a[data-glpi-ajax-content]').each(function() {
            const link = $(this);
            const pane = $(link.attr('data-bs-target'));
            if (pane.length === 0 || pane.is(ownPane) || pane.html().trim() === '') {
                return;
            }
            $.get(link.attr('data-glpi-ajax-content'), function(html) {
                pane.html(html);
            });
        });
    }

    $(document).on('click', '[data-select-all], [data-clear-all]', function() {
        const button = $(this);
        const checked = button.is('[data-select-all]');
        const kind = button.attr(checked ? 'data-select-all' : 'data-clear-all');
        checkboxes(button.closest('form'), kind).prop('checked', checked);
    });

    $(document).on('change', 'form[data-form="entities"] select[name="allow_transfer"]', function() {
        refreshEntityForm($(this).closest('form'));
    });

    $(document).on('change', 'form[data-form="entities"] select[name="allow_entity_only_transfer"]', function() {
        const form = $(this).closest('form');
        refreshEntityForm(form);
        if ($(this).val() !== '1') {
            return;
        }
        const groupSelect = form.find('select[name="groups_id"]');
        const defaultGroup = parseInt(form.attr('data-default-group-id'), 10) || 0;
        if (String(groupSelect.val() || '0') === '0' && groupSelect.find('option[value="' + defaultGroup + '"]').length) {
            groupSelect.val(String(defaultGroup)).trigger('change');
        }
    });

    $(document).on('change', 'form[data-form="entities"] select[name="keep_category"]', function() {
        refreshEntityForm($(this).closest('form'));
    });

    $(document).on('submit', 'form.tte-settings-form', function(event) {
        event.preventDefault();
        const form = $(this);
        const kind = form.attr('data-form');
        const messages = {entities: text('select-entity'), profiles: text('select-profile'), groups: text('select-group')};
        if (checkboxes(form, kind).filter(':checked').length === 0) {
            showError(messages[kind]);
            return;
        }

        const overlayId = showOverlay(text('saving'));
        $.ajax({
            url: ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: form.serialize(),
        }).done(function(response) {
            if (typeof glpi_toast_success === 'function') {
                glpi_toast_success(response.message);
            }
            refreshOtherPanes(form);
            if (kind === 'entities' && typeof response.assigned_group_id !== 'undefined') {
                form.attr('data-default-group-id', response.assigned_group_id);
                // Keep the read-only field of the Groups tab in sync when it is displayed on the same page.
                $('select[name="assigned_group_id_display"]').val(String(response.assigned_group_id)).trigger('change');
            }
        }).fail(function(xhr) {
            showError(responseMessage(xhr, text('save-error')));
        }).always(function() {
            hideOverlay(overlayId);
        });
    });
});
