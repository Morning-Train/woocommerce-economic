<?php

namespace MorningTrain\WoocommerceEconomic;

use MorningTrain\WoocommerceEconomic\Services\EconomicService;

class WoocommerceEconomic
{
    public static function init()
    {
        add_filter('woocommerce_payment_gateways', [self::class, 'registerGateway']);
        add_action('woocommerce_new_order', [self::class, 'sendEconomicInvoice'], 10, 1);
    }

    public static function registerGateway($gateways): array
    {
        $gateways['economic_invoice'] = WC_Gateway_Economic_Invoice::class;

        return $gateways;
    }

    public static function SendEconomicInvoice($orderId): void
    {
        EconomicService::createInvoice($orderId);
    }
}
