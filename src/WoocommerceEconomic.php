<?php

namespace Morningtrain\WoocommerceEconomic;

use Morningtrain\WoocommerceEconomic\Services\ActionScheduleService;
use Morningtrain\WoocommerceEconomic\Woocommerce\ProductService;

class WoocommerceEconomic
{
    public static function init(): void
    {
        add_filter('woocommerce_payment_gateways', [self::class, 'registerGateway'], 10, 1);
        add_action('init', [self::class, 'requireGateway'], 1);
        add_action('woocommerce_product_options_general_product_data', [ProductService::class, 'addEconomicProductFieldWithWrapper']);
        add_action('woocommerce_variation_options', [ProductService::class, 'addEconomicProductField']);
        add_action('woocommerce_save_product_variation', [ProductService::class, 'saveEconomicProductField'], 10, 2);
        add_action('woocommerce_process_product_meta', [ProductService::class, 'saveEconomicProductField'], 10, 2);
        add_action(ActionScheduleService::CREATE_INVOICE, [ActionScheduleService::class, 'handleCreateInvoiceJob'], 10, 1);
    }

    public static function registerGateway($gateways): array
    {
        $gateways[] = \WC_Gateway_Economic_Invoice::class;

        return $gateways;
    }

    public static function requireGateway(): void
    {
        require_once __DIR__.'/Woocommerce/WC_Gateway_Economic_Invoice.php';
    }
}
