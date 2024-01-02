<?php

namespace MorningTrain\WoocommerceEconomic\Services;

class EconomicService
{

    public static function createInvoice($orderId)
    {
        $order = \wc_get_order(4351);

        $customer = Customer::where('email', 'ms@morningtrain.dk')->first();

        if (! $customer) {
            $customer = Customer::create(
                name: 'Mikkel Sciegienny',
                customerGroup: 1,
                currency: $order->get_currency(),
                vatZone: 1,
                paymentTerms: 3,
                email: 'ms@morningtrain.dk',
            );
        }

        $vatZone = VatZone::all()->first();

        $recipient = Recipient::new(
            name: 'Mikkel Sciegienny',
            vatZone: $vatZone,
        );

        DraftInvoice::new(customer: $customer,
            layout: 1,
            currency: $order->get_currency(),
            paymentTerms: 3,
            date: current_datetime(),
            recipient: $recipient)
            ->addLine(ProductLine::new(
                product: new Product(),
                quantity: 1,
                unitNetPrice: 500
            ))
            ->create()
            ?->book();
    }

}
