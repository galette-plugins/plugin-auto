/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/* Vehicle form: models follow the chosen brand, fields follow the chosen
 * type, and the owner can only be changed once asked for.
 */
var _autoVehicleForm = function() {
    var $form = $('#modifform');
    if ($form.length === 0) {
        return;
    }

    $('#brand').dropdown('setting', 'onChange', function(id_brand) {
        $.post(
            $form.data('models-url'),
            {
                brand: id_brand
            },
            function(data) {
                var _models = [{value: '-1', name: $form.data('models-placeholder')}];
                $(data).each(function(i) {
                    _models.push({name: data[i].model, value: data[i].id_model});
                });
                $('#model').dropdown('change values', _models);
                $('#model').dropdown('set selected', -1);
            },
            'json'
        );
    });

    /* modes are 0 (hidden), 1 (optional) and 2 (required) */
    var _typesFields = $form.data('types-fields') || {};
    $('#type').dropdown('setting', 'onChange', function(id_type) {
        var _fields = _typesFields[id_type];
        if (!_fields) {
            //no type chosen: every field is shown, none required
            _fields = {};
            $form.find('[id^="field_"]').each(function() {
                _fields[this.id.substring(6)] = 1;
            });
        }
        $.each(_fields, function(field, mode) {
            var _container = $('#field_' + field);
            var _input = _container.find('input, select, textarea').not('.search');
            _container.toggleClass('displaynone', mode === 0);
            _container.toggleClass('required', mode === 2);
            //a hidden field must not block the submission
            _input.prop('required', mode === 2);
        });
    });

    $('#change_owner-checkbox').checkbox({
        onChecked: function() {
            $('#owner_id_elt').removeClass('displaynone');
        },
        onUnchecked: function() {
            $('#owner_id_elt').addClass('displaynone');
        }
    });
};

$(function() {
    _autoVehicleForm();
});
