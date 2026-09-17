/**
 * JS ports of the fee/landed-cost helpers in includes/helpers.php — they must be
 * kept in sync with their PHP originals. Duplicated here (rather than fetched
 * from the server) so the estimate updates live as the user types, with no round
 * trip.
 */
(function (Bidwraith) {
    'use strict';

    /** JS port of estimate_buyer_protection_fee(). */
    Bidwraith.estimateBuyerProtectionFee = function (price) {
        var fee = 0.30;
        fee += 0.08 * Math.min(price, 20);
        if (price > 20) {
            fee += 0.06 * (Math.min(price, 500) - 20);
        }
        if (price > 500) {
            fee += 0.04 * (Math.min(price, 5000) - 500);
        }
        return Math.round(fee * 100) / 100;
    };

    /** JS port of estimate_landed_cost(). */
    Bidwraith.estimateLandedCost = function (price, shippingCost, itemCountry, homeCountry) {
        var shipping = (typeof shippingCost === 'number' && !isNaN(shippingCost)) ? shippingCost : 0;
        var buyerProtectionFee = Bidwraith.estimateBuyerProtectionFee(price);
        var isOverseas = !!(itemCountry && homeCountry && itemCountry.toUpperCase() !== homeCountry.toUpperCase());
        var gst = (isOverseas && (price + shipping) <= 1000) ? Math.round(0.10 * (price + shipping) * 100) / 100 : 0;

        return {
            shipping: Math.round(shipping * 100) / 100,
            buyer_protection_fee: buyerProtectionFee,
            gst: gst,
            is_overseas: isOverseas,
            total: Math.round((price + shipping + buyerProtectionFee + gst) * 100) / 100,
        };
    };
})(window.Bidwraith);
