<?php

use Morningtrain\Economic\Resources\CustomerGroup;
use Morningtrain\Economic\Resources\Layout;
use Morningtrain\Economic\Resources\PaymentTerm;
use Morningtrain\Economic\Resources\VatZone;
use Morningtrain\WoocommerceEconomic\Services\ActionScheduleService;

class WC_Gateway_Economic_Invoice extends \WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'economic_invoice';
        $this->icon = '';
        $this->has_fields = false;
        $this->title = __('Betal med faktura', 'mt-wc-economic');
        $this->method_title = __('Betal med faktura', 'mt-wc-economic');
        $this->method_description = __('Economic Faktura Betaling', 'mt-wc-economic');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, [$this, 'process_admin_options']);

        add_action('woocommerce_new_order', [$this, 'onNewOrder'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'onOrderCompleted'], 10, 1);
        add_filter('woocommerce_checkout_fields', [$this, 'addEanField']);
        add_action('woocommerce_checkout_process', [$this, 'validateEanField']);
        add_action('woocommerce_after_checkout_validation', [$this, 'validateEanField']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'eanFieldUpdateOrderMeta']);
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'addEanFieldFisplayAdminOrderMeta'], 10, 1);
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Aktiver/Deaktiver', 'mt-wc-economic'),
                'type' => 'checkbox',
                'label' => __('Aktiver Faktura Betaling', 'mt-wc-economic'),
                'default' => 'no',
            ],

            'title' => [
                'title' => __('Titel', 'mt-wc-economic'),
                'type' => 'text',
                'description' => __('Dette styrer title for betalingsmetoden som brugere ser under tjek-ud'),
                'default' => __('Fakturabetaling', 'mt-wc-economic'),
                'desc_tip' => true,
            ],

            'description' => [
                'title' => __('Beskrivelse', 'mt-wc-economic'),
                'type' => 'textarea',
                'description' => __('Dette styrer beskrivelsen for betalingsmetoden som brugere ser under tjek-ud', 'mt-wc-economic'),
                'default' => '',
                'desc_tip' => true,
            ],

            'instructions' => [
                'title' => __('Instruktioner', 'mt-wc-economic'),
                'type' => 'textarea',
                'description' => __('Disse instruktioner gives til brugeren på Tak-siden og i emails', 'mt-wc-economic'),
                'default' => '',
                'desc_tip' => true,
            ],

            'economic_layout_number' => [
                'title' => __('Economic layout', 'mt-wc-economic'),
                'type' => 'select',
                'options' => $this->getLayouts(),
                'description' => __('Vælg det ønskede layout i economic', 'mt-wc-economic'),
                'default' => null,
                'desc_tip' => true,
            ],

            'economic_invoice_term' => [
                'title' => __('Economic betalingsfrist', 'mt-wc-economic'),
                'type' => 'select',
                'options' => $this->getPaymentTerms(),
                'description' => __('indtast id til den ønskede betalingsfrist i economic', 'mt-wc-economic'),
                'default' => null,
                'desc_tip' => true,
            ],

            'economic_invoice_draft' => [
                'title' => __('Economic udkast', 'mt-wc-economic'),
                'type' => 'select',
                'options' => [
                    'draft' => 'Kladde',
                    'book' => 'Bogført',
                ],
                'description' => __('Vælg hvordan fakturaen skal oprettes', 'mt-wc-economic'),
                'default' => 'draft',
                'desc_tip' => true,
            ],

            'economic_invoice_event' => [
                'title' => __('Faktura oprettelse', 'mt-wc-economic'),
                'type' => 'select',
                'options' => [
                    'creation' => 'Ordrer oprettelse',
                    'completed' => 'Ordrer færdiggørelse',
                ],
                'description' => __('Vælg hvornår fakturaen skal oprettes', 'mt-wc-economic'),
                'default' => 'creation',
                'desc_tip' => true,
            ],

            'economic_vat_zone' => [
                'title' => __('Economic momszone', 'mt-wc-economic'),
                'type' => 'select',
                'options' => $this->getVatZones(),
                'description' => __('Vælg den ønskede momszone i economic', 'mt-wc-economic'),
                'default' => null,
                'desc_tip' => true,
            ],

            'economic_customer_group' => [
                'title' => __('Economic Kundegruppe', 'mt-wc-economic'),
                'type' => 'select',
                'options' => $this->getCustomerGroup(),
                'description' => __('Vælg den ønskede kundegruppe i economic', 'mt-wc-economic'),
                'default' => null,
                'desc_tip' => true,
            ],

            'economic_ean' => [
                'title' => __('Tilføj EAN', 'mt-wc-economic'),
                'label' => __('Tilføj EAN', 'mt-wc-economic'),
                'type' => 'checkbox',
                'description' => __('Skal der være mulighed for at tilføje EAN?', 'mt-wc-economic'),
                'default' => 'no',
                'desc_tip' => true,
            ],

            'economic_shipping_product' => [
                'title' => __('Economic forsendelses produkt ID', 'mt-wc-economic'),
                'type' => 'number',
                'description' => __('Indtast produkt ID tilknyttet fragt i economic', 'mt-wc-economic'),
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

    private function createEconomicInvoice(int $orderId): void
    {

        $order = wc_get_order($orderId);

        $paymentMethod = $order->get_payment_method();

        if ($paymentMethod !== 'economic_invoice') {
            return;
        }

        ActionScheduleService::addCreateInvoiceJob($order);
    }

    public function onNewOrder(int $orderId): void
    {
        if ($this->get_option('economic_invoice_event') === 'creation') {
            $this->createEconomicInvoice($orderId);
        }
    }

    public function onOrderCompleted(int $orderId): void
    {
        if ($this->get_option('economic_invoice_event') === 'completed') {

            $this->createEconomicInvoice($orderId);
        }
    }

    private function getLayouts(): array
    {
        if (! $this->isSettingsPage()) {
            return [];
        }

        return Layout::all()->mapWithKeys(function ($layout) {
            return [$layout->layoutNumber => $layout->name];
        })->toArray();
    }

    private function getPaymentTerms()
    {
        if (! $this->isSettingsPage()) {
            return [];
        }

        return PaymentTerm::all()->mapWithKeys(function ($paymentTerm) {
            return [$paymentTerm->paymentTermsNumber => $paymentTerm->name];
        })->toArray();
    }

    private function getVatZones()
    {
        if (! $this->isSettingsPage()) {
            return [];
        }

        return VatZone::all()->mapWithKeys(function ($vatZone) {
            return [$vatZone->vatZoneNumber => $vatZone->name];
        })->toArray();
    }

    private function getCustomerGroup()
    {
        if (! $this->isSettingsPage()) {
            return [];
        }

        return CustomerGroup::all()->mapWithKeys(function ($customerGroup) {
            return [$customerGroup->customerGroupNumber => $customerGroup->name];
        })->toArray();
    }

    public function addEanField($fields): array
    {
        if ($this->get_option('economic_ean') === 'no') {
            return $fields;
        }

        $fields['billing']['economic_billing_ean'] = [
            'label' => __('EAN Nummer', 'woocommerce'),
            'placeholder' => __('EAN', 'EAN Nummer', 'woocommerce'),
            'required' => false,
            'class' => ['form-row-wide'],
            'clear' => true,
            'priority' => 31, //after billing_company
        ];

        return $fields;
    }

    public function validateEanField(): void
    {
        if ($this->get_option('economic_ean') === 'no') {
            return;
        }

        if (! empty($_POST['economic_billing_ean']) && strlen($_POST['economic_billing_ean']) !== 13) {
            wc_add_notice(__('EAN nummer ikke gyldigt!', 'woocommerce'), 'error');
        }
    }

    public function eanFieldUpdateOrderMeta($order_id): void
    {
        if (! empty($_POST['economic_billing_ean'])) {
            update_post_meta($order_id, 'economic_billing_ean', sanitize_text_field($_POST['economic_billing_ean']));
        }
    }

    public function addEanFieldFisplayAdminOrderMeta($order): void
    {
        echo '<p><strong>'.__('EAN nummer', 'woocommerce').':</strong> '.get_post_meta($order->get_id(), 'economic_billing_ean', true).'</p>';
    }

    public function isSettingsPage()
    {
        if (! is_admin() || ! function_exists('get_current_screen')) {
            return false;
        }

        $currentScreen = get_current_screen();

        return $currentScreen->base === 'woocommerce_page_wc-settings' &&
            isset($_GET['tab']) && $_GET['tab'] === 'checkout' &&
            isset($_GET['section']) && $_GET['section'] === 'economic_invoice';
    }
}
