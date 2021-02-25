<?php

namespace Drupal\commerce_coinpayments\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Provides the CoinPayments payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "coinpayments",
 *   label = @Translation("CoinPayments"),
 * )
 */
class Coinpayments extends PaymentMethodTypeBase
{

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method)
  {

    $args = [
      '@name' => t('Redirect'),
    ];
    return $this->t('CoinPayments.Net @name', $args);
  }

}
