<?php

namespace MorningTrain\WoocommerceEconomic\Woocommerce;

class ProductService
{
    public static function addEconomicProductField(): void
    {
        woocommerce_wp_text_input([
            'id' => 'economic_product_id',
            'label' => __('E-conomic produkt id', 'mt-wc-economic'),
            'placeholder' => __('E-conomic produkt id', 'mt-wc-economic'),
            'desc_tip' => 'true',
            'description' => __('Udfyld varenummerer fra e-conomic', 'mt-wc-economic'),
            'type' => 'number',
        ]);
    }

    public static function saveEconomicProductField($post_id): void
    {
        $economic_product_id = $_POST['economic_product_id'] ?? '';
        update_post_meta($post_id, 'economic_product_id', sanitize_text_field($economic_product_id));
    }
}
