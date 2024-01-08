<?php

namespace Morningtrain\WoocommerceEconomic\Services;

use Morningtrain\Economic\Services\EconomicLoggerService;
use Morningtrain\WoocommerceEconomic\Woocommerce\OrderService;

class ActionScheduleService
{
    public const CREATE_INVOICE = 'mt-wc-economic/create-invoice';

    public static function addCreateInvoiceJob(\WC_Order $order): void
    {
        \as_schedule_single_action(time(), static::CREATE_INVOICE, [$order->get_id()]);
    }

    public static function handleCreateInvoiceJob(int $orderId): void
    {
        $order = \wc_get_order($orderId);

        $paymentMethod = wc_get_payment_gateway_by_order($orderId);

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
