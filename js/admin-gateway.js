window.coinpayments = {
    'client-id': false,
    'webhooks': false,
    'client-secret': false,
};

(function ($, Drupal) {

    Drupal.behaviors.coin_credentials = {
        attach: function (context, settings) {
            updateCoinVisibility();
        }
    };

    function updateCoinVisibility() {

        if (document.querySelector('input[name="plugin"][value="coinpayments"]').checked) {

            var hidebleDataSelectors = [
                'edit-conditions',
                'edit-conditionoperator',
                'edit-status',
                'edit-actions',
                'edit-configuration-coinpayments-mode',
            ];

            var watches = [
                'edit-configuration-coinpayments-client-id',
                'edit-configuration-coinpayments-webhooks',
                'edit-configuration-coinpayments-client-secret',
            ];

            watches.forEach(function (selector) {
                var elem = document.querySelector('[data-drupal-selector="' + selector + '"]');
                if (elem) {
                    elem.onkeyup = credentials_input_change;
                }
            });

            if (document.querySelector('[data-drupal-selector="edit-configuration-coinpayments-actions"]')) {
                hidebleDataSelectors.forEach(function (selector) {
                    coin_hide('[data-drupal-selector="' + selector + '"]');
                });
            } else {
                hidebleDataSelectors.forEach(function (selector) {
                    coin_show('[data-drupal-selector="' + selector + '"]');
                });
            }


            var validateButton;
            if (validateButton = document.querySelector('#validate-credentials-actions input')) {
                validateButton.disabled = !credentials_filled();
            }
        }
    }

    function coin_hide(selector) {
        var elems = document.querySelectorAll(selector);
        if (elems.length) {
            elems.forEach(function (elem) {
                elem.classList.add("coinpayments-hidden");
            })
        }
    }

    function coin_show(selector) {
        var elems = document.querySelectorAll(selector);
        if (elems.length) {
            elems.forEach(function (elem) {
                elem.classList.remove("coinpayments-hidden");
            })
        }
    }

    function credentials_input_change() {
        var key = $(this).attr('data-drupal-selector').replace('edit-configuration-coinpayments-', '');
        window.coinpayments[key] = this.value;

        var validateButton = document.querySelector('#validate-credentials-actions input');
        if (validateButton) {
            validateButton.disabled = !credentials_filled();
        }

    }

    function credentials_filled() {
        return (window.coinpayments['client-id'] != false && window.coinpayments['webhooks'] == false) ||
            (window.coinpayments['client-id'] != false && window.coinpayments['webhooks'] != false && window.coinpayments['client-secret'] != false);
    }

})(jQuery, Drupal);
