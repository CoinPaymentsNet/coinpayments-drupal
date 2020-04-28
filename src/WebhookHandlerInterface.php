<?php

namespace Drupal\commerce_coinpayments;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a handler for Webhook requests from commerce_coinpayments.
 */
interface WebhookHandlerInterface
{

    /**
     * Processes an incoming Webhook request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     *
     * @return mixed
     *   The request data array.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function process(Request $request);

}
