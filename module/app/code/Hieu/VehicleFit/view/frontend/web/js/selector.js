/**
 * Vehicle selector: make -> model -> year/engine, then reload with ?vehicle=ID.
 * The choice is also kept in localStorage for the product page badge. No cookies, no session.
 */
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    var STORAGE_KEY = 'vehiclefit';

    function save(vehicle) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(vehicle));
        } catch (e) { /* private mode, nothing to do */ }
    }

    function load() {
        try {
            return JSON.parse(window.localStorage.getItem(STORAGE_KEY) || 'null');
        } catch (e) {
            return null;
        }
    }

    function forget() {
        try {
            window.localStorage.removeItem(STORAGE_KEY);
        } catch (e) { /* ignore */ }
    }

    return function (config, element) {
        var $form = $(element),
            $make = $form.find('select[name=make]'),
            $model = $form.find('select[name=model]'),
            $vehicle = $form.find('select[name=vehicle]'),
            $submit = $form.find('button[type=submit]'),
            $clear = $form.find('.vehiclefit-clear');

        function fill($select, placeholder, params) {
            $select.prop('disabled', true).empty().append(new Option(placeholder, ''));
            return $.getJSON(config.optionsUrl, params).done(function (res) {
                (res.items || []).forEach(function (item) {
                    $select.append(new Option(item.label, item.value));
                });
                $select.prop('disabled', !(res.items || []).length);
            });
        }

        function reset($select, placeholder) {
            $select.prop('disabled', true).empty().append(new Option(placeholder, ''));
        }

        var saved = load(),
            current = config.selectedVehicleId && saved && saved.id === parseInt(config.selectedVehicleId, 10) &&
                saved.make && saved.model ? saved : null;

        // page opened with ?vehicle=ID that the customer picked before: show that car in the dropdowns
        fill($make, $t('Make'), {}).done(function () {
            if (!current) {
                return;
            }
            $make.val(current.make);
            fill($model, $t('Model'), {make: current.make}).done(function () {
                $model.val(current.model);
                fill($vehicle, $t('Year / engine'), {make: current.make, model: current.model}).done(function () {
                    $vehicle.val(String(current.id));
                    $submit.prop('disabled', !$vehicle.val());
                });
            });
        });

        $make.on('change', function () {
            reset($vehicle, $t('Year / engine'));
            $submit.prop('disabled', true);
            if (this.value) {
                fill($model, $t('Model'), {make: this.value});
            } else {
                reset($model, $t('Model'));
            }
        });

        $model.on('change', function () {
            $submit.prop('disabled', true);
            if (this.value) {
                fill($vehicle, $t('Year / engine'), {make: $make.val(), model: this.value});
            } else {
                reset($vehicle, $t('Year / engine'));
            }
        });

        $vehicle.on('change', function () {
            $submit.prop('disabled', !this.value);
        });

        $form.on('submit', function (e) {
            var url = new URL(window.location.href);

            e.preventDefault();
            if (!$vehicle.val()) {
                return;
            }
            save({
                id: parseInt($vehicle.val(), 10),
                make: $make.val(),
                model: $model.val(),
                label: [$make.val(), $model.val(), $vehicle.find('option:selected').text()].join(' ')
            });
            url.searchParams.set('vehicle', $vehicle.val());
            url.searchParams.delete('p'); // back to page 1
            window.location.href = url.toString();
        });

        if (config.selectedVehicleId) {
            $clear.prop('hidden', false);
        }
        $clear.on('click', function () {
            var url = new URL(window.location.href);

            forget();
            url.searchParams.delete('vehicle');
            url.searchParams.delete('p');
            window.location.href = url.toString();
        });
    };
});
