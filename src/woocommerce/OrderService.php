<?php

namespace MorningTrain\WoocommerceEconomic\Woocommerce;

use MorningTrain\Economic\DTOs\Recipient;
use MorningTrain\Economic\Resources\Customer;
use MorningTrain\Economic\Resources\Invoice\DraftInvoice;
use MorningTrain\Economic\Resources\Invoice\ProductLine;
use MorningTrain\Economic\Resources\Layout;
use MorningTrain\Economic\Resources\PaymentTerm;
use MorningTrain\Economic\Resources\Product;
use MorningTrain\Economic\Resources\VatZone;
use MorningTrain\Economic\Services\EconomicLoggerService;

class OrderService
{
    public static function createInvoice(\WC_Order $order, $paymentMethod): void
    {

            $vatZone = self::getVatZone($paymentMethod);

            $layout = self::getLayout($paymentMethod);

            $paymentTerm = self::getPaymentTerm($paymentMethod);

            $customer = self::getCustomer($order, $vatZone, $paymentTerm, $paymentMethod);

            $recipient = self::getRecipient($order, $vatZone);

            $invoice = self::getDraftInvoice($customer, $layout, $order, $paymentTerm, $recipient);

            $invoice = self::addLineItems($order, $invoice, $paymentMethod);

            self::createAndBookInvoice($paymentMethod, $invoice);
    }

    private static function getVatZone($paymentMethod): ?VatZone
    {
        $vatZone = apply_filters('woocommerce_economic_invoice_get_vat_zone', null);

        if ($vatZone) {
            return $vatZone;
        }

        return VatZone::find($paymentMethod->get_option('economic_vat_zone'));
    }

    private static function getLayout($paymentMethod): ?Layout
    {
        $layout = apply_filters('woocommerce_economic_invoice_get_layout', null);

        if ($layout) {
            return $layout;
        }
        return Layout::find($paymentMethod->get_option('economic_layout_number'));
    }

    private static function getPaymentTerm($paymentMethod): PaymentTerm
    {
        $paymentTerm = apply_filters('woocommerce_economic_invoice_get_payment_term', null);

        if ($paymentTerm) {
            return $paymentTerm;
        }

        return PaymentTerm::new(
            paymentTermsNumber: $paymentMethod->get_option('economic_invoice_term'),
        );
    }

    private static function getCustomer(\WC_Order $order, ?VatZone $vatZone, PaymentTerm $paymentTerm, \WC_Gateway_Economic_Invoice $paymentMethod): mixed
    {
        $customer = apply_filters('woocommerce_economic_invoice_get_customer', null);

        if (!$customer) {
            $customer = Customer::where('email', $order->get_billing_email())->first();

            if (!$customer) {
                $customerGroup = apply_filters('woocommerce_economic_invoice_customer_group', null);

                if(! $customerGroup) {
                    $customerGroup = $paymentMethod->get_option('economic_customer_group');
                }

                $customer = Customer::create(
                    name: $order->get_billing_email(),
                    customerGroup:$customerGroup,
                    currency: $order->get_currency(),
                    vatZone: $vatZone,
                    paymentTerms: $paymentTerm,
                    email: $order->get_billing_email(),
                    ean: get_metadata('post', $order->get_id(), 'economic_billing_ean', true)
                );
            }
        }

        return $customer;
    }

    private static function getRecipient(\WC_Order $order, ?VatZone $vatZone): Recipient
    {
        $recipient = apply_filters('woocommerce_economic_invoice_get_recipient', null);

        if ($recipient) {
            return $recipient;
        }

        return Recipient::new(
            name: $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
            vatZone: $vatZone,
            address: $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
            zip: $order->get_shipping_postcode(),
            city: $order->get_shipping_city(),
            country: $order->get_shipping_country(),
        );
    }

    private static function getDraftInvoice(Customer $customer, ?Layout $layout, \WC_Order $order, PaymentTerm $paymentTerm, Recipient $recipient): DraftInvoice
    {
        $invoice = apply_filters('woocommerce_economic_invoice_draft_get_invoice', null);

        if ($invoice) {
            return $invoice;
        }

        return DraftInvoice::new(customer: $customer->customerNumber,
            layout: $layout,
            currency: $order->get_currency(),
            paymentTerms: $paymentTerm,
            date: new \DateTime(current_time('mysql')),
            recipient: $recipient,
        );
    }

    private static function addLineItems(\WC_Order $order, DraftInvoice $invoice, \WC_Payment_Gateway $paymentMethod): DraftInvoice
    {
        collect($order->get_items())->each(function ($item) use ($invoice) {
            $customProductLine = apply_filters('woocommerce_economic_invoice_add_product_line', null, $item, $invoice);

            if (is_a($customProductLine, ProductLine::class)) {
                $invoice->addLine($customProductLine);
                return;
            }

            $product = self::getEconomicProduct($item, $invoice);


            if($product){
                EconomicLoggerService::critical('Product not found', [
                    'product_id' => $item->get_product_id(),
                    'economic_product_id' => $item->get_meta('economic_product_id'),
                ]);

                throw new \Exception('Product not found');
            }

            $invoice->addLine(ProductLine::new(
                product: $product->productNumber,
                quantity: $item->get_quantity(),
                unitNetPrice: $item->get_total(),
            ));
        });

       $customShippingLine =  apply_filters('woocommerce_economic_invoice_add_shipping_line', null, $order, $invoice);

       if($customShippingLine){
           $invoice->addLine($customShippingLine);
           return $invoice;
       }

        $invoice->addLine(ProductLine::new(
            product: $paymentMethod->get_option('economic_shipping_product'),
            quantity: 1,
            unitNetPrice: $order->get_shipping_total(),
        ));

        return $invoice;
    }

    private static function createAndBookInvoice($paymentMethod, DraftInvoice $invoice): void
    {
        $customInvoice = apply_filters('woocommerce_economic_invoice_create_and_book_invoice', null, $paymentMethod, $invoice);

        if ($customInvoice !== null) {
            return;
        }

        if ($paymentMethod->get_option('economic_invoice_draft') === 'book') {
            $invoice->create()?->book();
            return;
        }

        $invoice->create();
    }

   private static function getEconomicProduct($item, DraftInvoice $invoice): ?Product
    {
        $product = apply_filters('woocommerce_economic_invoice_get_economic_product_line', null, $item, $invoice);

        if (!$product) {
            $productEconomicId = get_post_meta($item->get_product_id(), 'economic_product_id', true);

            $product = Product::find($productEconomicId);
        }

        return $product;
    }

}
