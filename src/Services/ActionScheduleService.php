<?php

namespace MorningTrain\WoocommerceEconomic\Services;

use MorningTrain\Economic\Services\EconomicLoggerService;
use MorningTrain\WoocommerceEconomic\Woocommerce\OrderService;

class ActionScheduleService
{
    public const CREATE_INVOICE = 'mt-wc-economic/create-invoice';

    public static function addCreateInvoiceJob(\WC_Order $order, \WC_Payment_Gateway $paymentMethod): void
    {
        \as_schedule_single_action(time(), static::CREATE_INVOICE, ['order' => $order, 'paymentMethod' => $paymentMethod]);
    }

    public static function handleCreateInvoiceJob(\WC_Order $order, \WC_Payment_Gateway $paymentMethod): void
    {
        try {
            OrderService::createInvoice($order, $paymentMethod);
        } catch (\Exception $e) {
            EconomicLoggerService::critical('Could not create invoice', [
                'exception' => $e,
            ]);

            $order->update_status('failed');
        }

    }
}
