var tteEntityRequest = null;

function tteGetEntityGroups() {
    const entityChoice = $('select[name=entity_choice]');
    const groupChoice = $('select[name=group_choice]');
    const groupChoiceValue = $('input[type=hidden][name=group_choice]');
    const categoryChoice = $('select[name=category_choice]');
    const form = $('#tte_form');
    const selectedEntity = entityChoice.val();
    form.removeData('entity-right');
    groupChoice.closest('.form-field').hide();
    groupChoiceValue.val('0');
    categoryChoice.closest('.form-field').hide();
    categoryChoice.prop('disabled', true);
    if (tteEntityRequest) {
        tteEntityRequest.abort();
        tteEntityRequest = null;
    }
    if (selectedEntity === '-1' || selectedEntity === null) {
        return;
    }

    tteEntityRequest = $.ajax({
        url: CFG_GLPI.root_doc + '/plugins/transferticketentity/ajax/get_entities_rights.php',
        method: "GET",
        data: {
            entity_id: selectedEntity
        },
        success: function (data) {
            if (entityChoice.val() !== selectedEntity) {
                return;
            }
            if (!data.rights || parseInt(data.rights.allow_transfer, 10) !== 1 || !Array.isArray(data.groups) || !Array.isArray(data.categories)) {
                glpi_toast_error(__('Unable to load destination entity settings. Please try again.', 'transferticketentity'));
                return;
            }
            form.data('entity-right', data.rights);

            $(groupChoice).find('option[value!="0"]').remove();
            const groupChoiceFormField = groupChoice.closest('.form-field');
            {
                data.groups.forEach((group) => {
                    $(groupChoice).append(new Option(group.name, group.id, false, false)).trigger('change');
                });
                const groupRequired = parseInt(data.rights.allow_entity_only_transfer, 10) === 1;
                const defaultGroupId = String(parseInt(data.rights.groups_id, 10) || 0);
                if (groupRequired && defaultGroupId !== '0' && groupChoice.find('option[value="' + defaultGroupId + '"]').length) {
                    groupChoice.val(defaultGroupId).trigger('change');
                    groupChoiceValue.val(defaultGroupId);
                    groupChoiceFormField.show();
                } else if (groupRequired) {
                    groupChoice.val('0').trigger('change');
                    groupChoiceValue.val('0');
                    groupChoiceFormField.show();
                } else {
                    groupChoice.val('0').trigger('change');
                    groupChoiceValue.val('0');
                    groupChoiceFormField.hide();
                }
                categoryChoice.find('option[value!="0"]').remove();
                data.categories.forEach((category) => {
                    categoryChoice.append(new Option(category.completename || category.name, category.id, false, false));
                });
                const currentCategoryId = String(parseInt(form.attr('data-current-category-id'), 10) || 0);
                const defaultCategoryId = parseInt(data.rights.keep_category, 10) === 1
                    ? '0'
                    : String(parseInt(data.rights.itilcategories_id, 10) || 0);
                const selectedCategoryId = defaultCategoryId !== '0' ? defaultCategoryId : currentCategoryId;
                const categoryAvailable = selectedCategoryId === '0'
                    || categoryChoice.find('option[value="' + selectedCategoryId + '"]').length > 0;
                if (!categoryAvailable) {
                    const categoryName = defaultCategoryId !== '0'
                        ? '#' + selectedCategoryId
                        : form.attr('data-current-category-name');
                    const unavailable = new Option(categoryName + ' (' + __('Unavailable') + ')', selectedCategoryId, false, false);
                    unavailable.disabled = true;
                    categoryChoice.append(unavailable);
                }
                categoryChoice.prop('disabled', defaultCategoryId !== '0');
                categoryChoice.val(selectedCategoryId).trigger('change');
                categoryChoice.closest('.form-field').show();
                const justificationText = $('#transferticketentity_dialog').find('#tte_justification');
                const optionalText = ' (' + __('optional', 'transferticketentity') + ')'
                if (data.rights.justification_transfer) {
                    justificationText.prop('required', true);
                    justificationText.prev().text(justificationText.prev().text().replace(optionalText, ''));
                } else {
                    justificationText.prop('required', false);
                    justificationText.prev().text(justificationText.prev().text() + optionalText);
                }
            }
        },
        error: function (xhr, status) {
            if (status !== 'abort') {
                glpi_toast_error(__('Unable to load destination entity settings. Please try again.', 'transferticketentity'));
            }
        },
        complete: function () {
            tteEntityRequest = null;
        }
    });
}

$(function() {
    const form = $('#tte_form');
    form.on('submit', (event) => {
        event.preventDefault();
        const entityRights = form.data('entity-right');
        const entityChoice = $('select[name=entity_choice]');
        const groupChoice = $('select[name=group_choice]');
        const groupChoiceValue = $('input[type=hidden][name=group_choice]');
        const categoryChoice = $('select[name=category_choice]');
        if (entityChoice.val() === '-1') {
            glpi_toast_error(__('Mandatory fields are not filled. Please correct: %s').replace("%s", __('Entity')));
            return;
        }
        if (!entityRights) {
            glpi_toast_error(__('Unable to load destination entity settings. Please try again.', 'transferticketentity'));
            return;
        }

        const defaultCategoryId = parseInt(entityRights.keep_category, 10) === 1
            ? '0'
            : String(parseInt(entityRights.itilcategories_id, 10) || 0);
        const currentCategoryId = String(parseInt(form.attr('data-current-category-id'), 10) || 0);
        const categoryValue = categoryChoice.val();
        if (defaultCategoryId !== '0' && (!categoryValue || categoryChoice.find('option:selected').prop('disabled'))) {
            glpi_toast_error(__('The default category is not available in the destination entity. Contact an administrator.', 'transferticketentity'));
            return;
        }
        const currentCategoryUnavailable = currentCategoryId !== '0'
            && categoryChoice.find('option[value="' + currentCategoryId + '"]').prop('disabled');
        if (defaultCategoryId === '0' && (!categoryValue || (currentCategoryUnavailable && categoryValue === '0'))) {
            glpi_toast_error(__('The current category is not available in the destination entity. Select another category.', 'transferticketentity'));
            return;
        }

        const groupRequired = parseInt(entityRights.allow_entity_only_transfer, 10) === 1;
        const assignedGroupId = String(parseInt(entityRights.groups_id, 10) || 0);
        if (groupRequired && (assignedGroupId === '0' || groupChoice.val() !== assignedGroupId)) {
            glpi_toast_error(__("No group found with « Assigned to » right while a group is required. Transfer impossible.", "transferticketentity"));
            return;
        }
        groupChoiceValue.val(groupRequired ? assignedGroupId : '0');

        const modal = glpi_confirm({
            title: __("Confirm transfer ?", "transferticketentity"),
            message: $('#transferticketentity_dialog').html().trim(),
            confirm_callback: () => {
                const justificationText = $('#' + modal).find('#tte_justification');
                if (justificationText.prop('required') && !justificationText.val().length) {
                    glpi_toast_error(__("Justification required", "transferticketentity"));
                } else {
                    form.off('submit');
                    form.submit();
                }
            }
        });
        $('#' + modal).on('hide.bs.modal', function (event) {
            form.find('input[name=justification]').val($('#' + modal).find('#tte_justification').val());
        })
    })
})
