/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/* Vehicle form: models follow the chosen brand, and the owner can only be
 * changed once asked for.
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
