<?php

namespace Drupal\commerce_coinpayments;

use Drupal\commerce_coinpayments\Controller\ApiController;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_order\Entity\Order;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class WebhookHandler
 * @package Drupal\commerce_coinpayments
 */
class WebhookHandler implements WebhookHandlerInterface
{

    /**
     * The database connection to use.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $connection;

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * Constructs a new PaymentGatewayBase object.
     *
     * @param \Drupal\Core\Database\Connection $connection
     *   The database connection to use.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   Config object.
     */
    public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $configFactory)
    {
        $this->connection = $connection;
        $this->entityTypeManager = $entity_type_manager;
        $this->configFactory = $configFactory;
    }

    /**
     * @param Request $request
     * @return bool|mixed
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function process(Request $request)
    {

        $content = $request->getContent();

        $webhook_data = json_decode($content, true);

        if (empty($signature = $_SERVER['HTTP_X_COINPAYMENTS_SIGNATURE'])) {
            return false;
        }

        if (!isset($webhook_data['invoice']['invoiceId'])) {
            return false;
        }

        $invoice_str = $webhook_data['invoice']['invoiceId'];
        $invoice_str = explode('|', $invoice_str);
        $host_hash = array_shift($invoice_str);
        $order_id = array_shift($invoice_str);

        if ($host_hash != md5(\Drupal::request()->getSchemeAndHttpHost())) {
            return false;
        }

        /** @var \Drupal\commerce_order\Entity\Order $order */
        $order = $this->commerce_coinpayments_order_load($order_id);

        if (empty($order)) {
            return false;
        }

        $payment = $order->get('payment_gateway')->first()->entity;
        $config = $payment->get('configuration');

        if (empty($config['webhooks'])) {
            return false;
        }

        $api = new ApiController($config['client_id'], $config['webhooks'], $config['client_secret']);
        if (!$api->checkDataSignature($signature, $content)) {
            return false;
        }

        /** @var \Drupal\commerce_payment\PaymentStorage $storage */
        $storage = $this->entityTypeManager->getStorage('commerce_payment');
        $transaction_array = $storage->loadByProperties(['remote_id' => $webhook_data['invoice']['id']]);
        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $transaction */
        $transaction = array_shift($transaction_array);


        if (empty($transaction)) {
            // Create a new payment transaction for the order.
            $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
            /** @var \Drupal\commerce_payment\Entity\PaymentInterface $transaction */
            $transaction = $payment_storage->create([
                'state' => 'new',
                'amount' => $order->getTotalPrice(),
                'payment_gateway' => $order->get('payment_gateway')->getString(),
                'order_id' => $order->id(),
                'remote_id' => $webhook_data['invoice']['id'],
            ]);
            $transaction->setState('new');
        }

        if ($webhook_data['invoice']['status'] == 'Completed') {

            $transaction->setRemoteState($webhook_data['invoice']['status']);
            $transaction->setState('completed');
            $transition = $order->getState()->getWorkflow()->getTransition('place');
            $order->getState()->applyTransition($transition);
            $order->save();

        } else if ($webhook_data['invoice']['status'] == 'Cancelled') {

            $transaction->setRemoteState($webhook_data['invoice']['status']);
            $transaction->setState('failed');
            $order_state = $order->getState();
            $order_state_transitions = $order_state->getTransitions();
            $order_state->applyTransition($order_state_transitions['cancel']);
            $order->save();

        } else {
            $transaction->setRemoteState($webhook_data['invoice']['status']);
            $transaction->setState('authorization');
        }

        // Save the transaction information.
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment_storage->save($transaction);

        return $webhook_data;
    }

    /**
     * Loads the commerce order.
     *
     * @param $order_id
     *   The order ID.
     *
     * @return object
     *   The commerce order object.
     */
    protected function commerce_coinpayments_order_load($order_id)
    {
        $order = Order::load($order_id);
        return $order ? $order : FALSE;
    }

}
