/**
 * Product page: "Fits your 2019 Toyota Corolla" / "Does not fit ..." using the vehicle in localStorage.
 */
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    return function (config, element) {
        var vehicle = null,
            $badge = $(element);

        try {
            vehicle = JSON.parse(window.localStorage.getItem('vehiclefit') || 'null');
        } catch (e) {
            vehicle = null;
        }
        if (!vehicle || !vehicle.id) {
            return;
        }

        $.getJSON(config.fitsUrl, {vehicle: vehicle.id, product: config.productId}).done(function (res) {
            $badge
                .toggleClass('is-fit', !!res.fits)
                .toggleClass('is-not-fit', !res.fits)
                .text((res.fits ? $t('Fits your %1') : $t('Does not fit your %1')).replace('%1', vehicle.label))
                .prop('hidden', false);
        });
    };
});
