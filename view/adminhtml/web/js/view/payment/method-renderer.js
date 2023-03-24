define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'boodil',
                component: 'Boodil_Payment/js/view/payment/method-renderer/boodil-method'
            }
        );
        return Component.extend({});
    }
);
