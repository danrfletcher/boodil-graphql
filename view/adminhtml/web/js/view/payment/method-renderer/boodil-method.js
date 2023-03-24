define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Ui/js/model/messageList'
    ],
    function (Component, $, messageList) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Boodil_Payment/payment/boodil'
            },

            /**
             * Return Logo
             * @returns {string}
             */
            displayLogo: function () {
                return 'Boodil_Payment/images/' + window.checkoutConfig.logo + '_logo.svg';
            },

            placeOrder: function () {
                var widget = "";
                $('body').trigger('processStart');
                if (window.checkoutConfig.environment === 'production') {
                    widget = "https://widget.boodil.com/prod.js";
                } else {
                    widget = "https://widget.boodil.com/test.js";
                }

                require([widget], function () {
                    $.ajax({
                        url: "/boodil/index/index",
                        type: 'post',
                        dataType: 'json',
                        data: {
                            request: 'transaction'
                        },
                        success: function (data) {
                            if (data.uuid) {
                                jQuery('boodil-widget').attr( 'id', data.uuid );
                            } else {
                                messageList.addErrorMessage({
                                    message: data.error
                                });
                            }
                        },
                        complete: function () {
                            setTimeout(function () {
                                $('body').trigger('processStop');
                            }, 3000);
                        }
                    });
                });
            }
        });
    }
);
