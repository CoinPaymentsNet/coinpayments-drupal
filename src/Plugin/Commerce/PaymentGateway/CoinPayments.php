<?php

namespace Drupal\commerce_coinpayments\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_coinpayments\Controller\ApiController;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "coinpayments",
 *   label = "CoinPayments.net - Pay with Bitcoin, Litecoin, and other cryptocurrencies (Off-site payment)",
 *   display_label = "CoinPayments.net Off-site",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_coinpayments\PluginForm\CoinPayments\PaymentStepForm",
 *   },
 *   payment_method_types = {"coinpayments"},
 * )
 */
class CoinPayments extends OffsitePaymentGatewayBase
{
    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    public function validateCredentialsAjax(array $form, FormStateInterface $form_state)
    {

        $credentials = $form_state->getValue($form['#parents']);
        $credentials = $credentials['configuration']['coinpayments'];

        $messenger = \Drupal::messenger();
        $messenger->deleteAll();

        $client_id = $credentials['client_id'];
        $webhooks = $credentials['webhooks'];
        $client_secret = $credentials['client_secret'];

        if (!empty($client_id) && empty($webhooks)) {
            $form['configuration']["form"]['client_secret']['#required'] = false;
            if (static::validateInvoice($client_id)) {
                $form['configuration']["form"]['actions']['#access'] = false;
            } else {
                $messenger->addError(t('CoinPayments.net credentials invalid!'));
            }
        } elseif (!empty($client_id) && !empty($webhooks) && !empty($client_secret)) {
            $form['configuration']["form"]['client_secret']['#required'] = true;
            if (static::validateWebhook($client_id, $client_secret)) {
                $form['configuration']["form"]['actions']['#access'] = false;
            } else {
                $messenger->addError(t('CoinPayments.net credentials invalid!'));
            }
        } else {
            $messenger->addError(t('CoinPayments.net credentials required!'));
        }

        return array($form['configuration']["form"]['actions']);
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {

        $form = parent::buildConfigurationForm($form, $form_state);

        $client_id = !empty($this->configuration['client_id']) ? $this->configuration['client_id'] : '';
        $webhooks = !empty($this->configuration['webhooks']) ? $this->configuration['webhooks'] : '';
        $client_secret = !empty($this->configuration['client_secret']) ? $this->configuration['client_secret'] : '';

        $form['client_id'] = [
            '#type' => 'textfield',
            '#title' => t('Client ID'),
            '#default_value' => $client_id,
            '#description' => t('The Client ID of your CoinPayments.net account.'),
            '#required' => TRUE,
        ];

        $form['webhooks'] = [
            '#type' => 'select',
            '#title' => t('Gateway webhooks'),
            '#options' => [
                0 => t('Disabled'),
                1 => t('Enabled'),
            ],
            '#default_value' => $webhooks,
            '#description' => t('Enable CoinPayments.net gateway webhooks.'),
        ];

        $form['client_secret'] = [
            '#type' => 'textfield',
            '#title' => t('Client Secret'),
            '#default_value' => $client_secret,
            '#description' => t('Client Secret of your CoinPayments.net account.'),
            '#states' => array(
                'required' => array(
                    '#edit-configuration-coinpayments-webhooks' => array('value' => 1),
                ),
                'visible' => array(
                    '#edit-configuration-coinpayments-webhooks' => array('value' => 1),
                ),
            ),
        ];


        if ($form_state->getFormObject()->getFormId() == 'commerce_payment_gateway_add_form') {

            $form['actions'] = [
                '#type' => 'button',
                '#value' => t('Validate credentials'),
                '#ajax' => [
                    'callback' => array('Drupal\commerce_coinpayments\Plugin\Commerce\PaymentGateway\CoinPayments', 'validateCredentialsAjax'),
                    'event' => 'click',
                    'progress' => array(
                        'type' => 'throbber',
                        'message' => t('Verifying CoinPayments.Net credentials...'),
                    ),
                    'wrapper' => 'validate-credentials-actions',
                ],
                '#limit_validation_errors' => [],
                '#attributes' => ['class' => ['validate-credential']],
                '#access' => true,
                '#prefix' => '<div id="validate-credentials-actions">',
                '#suffix' => '</div>',
            ];

            $form['#attached']['library'][] = 'commerce_coinpayments/admin-gateway';
        }

        return $form;
    }

    /**
     * @return array|string[]
     */
    public function defaultConfiguration()
    {
        return [
                "client_id" => '',
                "webhooks" => '',
                "client_secret" => '',
            ] + parent::defaultConfiguration();
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateConfigurationForm($form, $form_state);

        if (!$form_state->getErrors() && $form_state->isSubmitted()) {
            $values = $form_state->getValue($form['#parents']);
            $this->configuration['client_id'] = $values['client_id'];
            $this->configuration['webhooks'] = $values['webhooks'];
            $this->configuration['client_secret'] = $values['client_secret'];
        }
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);
        if (!$form_state->getErrors()) {
            $values = $form_state->getValue($form['#parents']);
            $this->configuration['client_id'] = $values['client_id'];
            $this->configuration['webhooks'] = $values['webhooks'];
            $this->configuration['client_secret'] = $values['client_secret'];
        }
    }

    /**
     * @param OrderInterface $order
     * @param Request $request
     */
    public function onCancel(OrderInterface $order, Request $request)
    {
        $status = $request->get('status');
        drupal_set_message($this->t('Payment @status on @gateway but may resume the checkout process here when you are ready.', [
            '@status' => $status,
            '@gateway' => $this->getDisplayLabel(),
        ]), 'error');
    }

    /**
     * @param $client_id
     * @param $client_secret
     */
    protected static function validateWebhook($client_id, $client_secret)
    {
        $api = new ApiController($client_id, true, $client_secret);
        return $api->checkWebhook();
    }

    /**
     * @param $client_id
     * @return bool
     */
    protected static function validateInvoice($client_id)
    {
        $api = new ApiController($client_id);
        $invoice = $api->createInvoice();
        return !empty($invoice['id']);
    }
}
