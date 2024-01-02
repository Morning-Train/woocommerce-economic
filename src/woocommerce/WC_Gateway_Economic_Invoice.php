<?php

namespace MorningTrain\WoocommerceEconomic;

class WC_Gateway_Economic_Invoice extends \WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'economic_invoice';
        $this->has_fields = false;
        $this->title = __('Betal med faktura', 'mt-wc-economic');
        $this->method_title = __('Betal med faktura', 'mt-wc-economic');
        $this->method_description = __('Economic Faktura Betaling', 'mt-wc-economic');
        $this->init_form_fields();
        $this->init_settings();
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [

            'enabled' => [
                'title' => __('Aktiver/Deaktiver', 'mt-wc-economic'),
                'type' => 'checkbox',
                'label' => __('Aktiver Faktura Betaling', 'mt-wc-economic'),
                'default' => 'yes',
            ],

            'title' => [
                'title' => __('Titel', 'mt-wc-economic'),
                'type' => 'text',
                'description' => __('Dette styrer title for betalingsmetoden som brugere ser under tjek-ud'),
                'default' => __('Fakturabetaling', 'mt-wc-economic'),
                'desc_tip' => true,
            ],

            'description' => [
                'title' => __('Beskrivelse'), 'mt-wc-economic',
                'type' => 'textarea',
                'description' => __('Dette styrer beskrivelsen for betalingsmetoden som brugere ser under tjek-ud'), 'mt-wc-economic',
                'default' => '',
                'desc_tip' => true,
            ],

            'instructions' => [
                'title' => __('Instruktioner'),
                'type' => 'textarea',
                'description' => __('Disse instruktioner gives til brugeren på Tak-siden og i emails', 'mt-wc-economic'),
                'default' => '',
                'desc_tip' => true,
            ],
            'economic_layout_number' => [
                'title' => __('Economic layout ID'),
                'type' => 'number',
                'description' => __('indtast id til det ønskede layout i economic', 'mt-wc-economic'),
                'default' => '',
                'desc_tip' => true,
            ],
            'economic_invoice_term' => [
                'title' => __('Economic betalingsfrist'),
                'type' => 'number',
                'description' => __('indtast  id til den ønskede betalingsfrist i economic', 'mt-wc-economic'),
                'default' => '',
                'desc_tip' => true,
            ],
            'economic_invoice_draft' => [
                'title' => __('Economic udkast'),
                'type' => 'select',
                'options' => [
                    'draft' => 'Draft',
                    'book' => 'Rigtig', //TODO: rename this
                ],
                'description' => __('Vælg hvordan fakturaen skal oprettes', 'mt-wc-economic'),
                'default' => '',
                'desc_tip' => true,
            ],

        ];
    }

    public function process_payment($order_id): array
    {
        $order = \wc_get_order($order_id);

        $order->update_status('processing', __('Afventer færdiggørelse', 'mt-wc-economic'));

        WC()->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}
