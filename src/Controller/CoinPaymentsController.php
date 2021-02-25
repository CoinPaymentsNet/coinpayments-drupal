<?php

namespace Drupal\commerce_coinpayments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_coinpayments\WebhookHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CoinPaymentsController
 * @package Drupal\commerce_coinpayments\Controller
 */
class CoinPaymentsController extends ControllerBase
{

  /**
   * The Webhook handler.
   *
   * @var \Drupal\commerce_coinpayments\WebhookHandlerInterface
   */
  protected $webhook_handler;

  /**
   * The Webhook handler.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config_factory;

  /**
   * Constructs a \Drupal\commerce_coinpayments\Controller\CoinPaymentsController object.
   *
   * @param \Drupal\commerce_coinpayments\WebhookHandlerInterface $webhook_handler
   *   The Webhook handler.
   */
  public function __construct(WebhookHandlerInterface $webhook_handler)
  {
    $this->webhook_handler = $webhook_handler;
  }

  /**
   * @param ContainerInterface $container
   * @return CoinPaymentsController|static
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('commerce_coinpayments.webhook_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * Process the Webhook by calling WebhookHandler service object.
   *
   * @return object
   *   A json object.
   */
  public function processWebhook(Request $request)
  {

    // Get Webhook request data and basic processing for the IPN request.
    $webhook_data = $this->webhook_handler->process($request);

    $response = new Response();
    $response->setContent(json_encode(['Status' => $webhook_data['invoice']['status']]));
    $response->headers->set('Content-Type', 'application/json');

    return $response;
  }

}
