<?php

namespace Drupal\commerce_coinpayments\PluginForm\CoinPayments;

use Drupal\commerce_coinpayments\Controller\ApiController;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class PaymentStepForm
 * @package Drupal\commerce_coinpayments\PluginForm\CoinPayments
 */
class PaymentStepForm extends BasePaymentOffsiteForm
{

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $payment->getOrder();

    $cid = 'commerce_coinpayments:' . $order->id();
    if (!$invoice = \Drupal::cache()->get($cid)) {
      $invoice = $this->createInvoice($payment);

      if (!empty($invoice['id'])) {
        $invoice['expire'] = \Drupal::time()->getRequestTime() + $invoice['timeout'];
        \Drupal::cache()->set($cid, $invoice, $invoice['expire']);
      }
    } else {
      $invoice = $invoice->data;
    }

    $form = $this->buildCoinRedirectForm($form, $form_state, $invoice, $order);

    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @param $invoice
   * @param $order
   * @return array
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  protected function buildCoinRedirectForm(array $form, FormStateInterface $form_state, $invoice, $order)
  {

    $data = [
      'invoice-id' => $invoice['id'],
      'cancel-url' => Url::fromRoute('commerce_payment.checkout.cancel', ['commerce_order' => $order->id(), 'step' => 'cancel'], ['absolute' => TRUE])->toString(),
      'success-url' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
    ];

    $redirect_url = sprintf('%s/%s/', ApiController::CHECKOUT_URL, ApiController::API_CHECKOUT_ACTION);

    foreach ($data as $name => $value) {
      if (isset($value)) {
        $form[$name] = ['#type' => 'hidden', '#value' => $value];
      }
    }

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, self::REDIRECT_GET);
  }

  /**
   * @param $payment
   * @return bool|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function createInvoice($payment)
  {

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $payment->getOrder();

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $payment_configuration = $payment_gateway_plugin->getConfiguration();

    $client_id = $payment_configuration['client_id'];
    $webhooks = $payment_configuration['webhooks'];
    $client_secret = $payment_configuration['client_secret'];

    $api = new ApiController($client_id, $webhooks, $client_secret);

    $coin_currency = $api->getCoinCurrency($order->getTotalPrice()->getCurrencyCode());
    $display_value = $order->getTotalPrice()->getNumber();
    $amount = intval(number_format($display_value, $coin_currency['decimalPlaces'], '', ''));

    $notes_link = sprintf(
      "%s|Store name: %s|Order #%s",
      \Drupal::request()->getSchemeAndHttpHost() . $order->toUrl()->toString(),
      \Drupal::config('system.site')->get('name'),
      $order->id());

    $billing_profile = $order->getBillingProfile()->get('address')->getValue();

    $invoice_params = array(
      'invoice_id' => sprintf('%s|%s', md5(\Drupal::request()->getSchemeAndHttpHost()), $order->id()),
      'currency_id' => $coin_currency['id'],
      'amount' => $amount,
      'display_value' => $display_value,
      'notes_link' => $notes_link,
      'billing_data' => array_merge(
        array('email' => $order->getEmail()),
        array_shift($billing_profile)
      ),
    );

    $coin_invoice = $api->createInvoice($invoice_params);
    if ($webhooks) {
      $coin_invoice = array_shift($coin_invoice['invoices']);
    }
    // Create a new payment transaction for the order.
    $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $transaction */
    $transaction = $payment_storage->create([
      'state' => 'new',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $order->get('payment_gateway')->first()->getString(),
      'order_id' => $order->id(),
      'remote_id' => $coin_invoice['id'],
    ]);
    $transaction->setState('new');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment_storage->save($transaction);

    return $coin_invoice;
  }

}
