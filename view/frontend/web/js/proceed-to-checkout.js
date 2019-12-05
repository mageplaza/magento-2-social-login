/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
    'jquery',
    'Magento_Customer/js/model/authentication-popup',
    'Magento_Customer/js/customer-data',
    ], function ($, authenticationPopup, customerData) {
        'use strict';

        return function (config, element) {
            var el = $(element);
            el.click(
                function (event) {
                    var cart = customerData.get('cart'),
                    customer = customerData.get('customer');
                    event.preventDefault();
                    if (!customer().firstname && cart().isGuestCheckoutAllowed === false) {
                        if (parseInt(cart().isReplaceAuthModal) !== 1) {
                            authenticationPopup.showModal();
                            return false;
                        }
                        return true;
                    }
                    $(element).attr('disabled', true);
                    location.href = config.checkoutUrl;
                }
            );
        };
    }
);
