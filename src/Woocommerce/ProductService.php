<?php

namespace Morningtrain\WoocommerceEconomic\Woocommerce;

class ProductService
{
    public static function addEconomicProductFieldWithWrapper(): void
    {
        echo '<div class="options_group pricing show_if_simple hidden">';
        woocommerce_wp_text_input([
            'id' => 'economic_product_id',
            'label' => __('E-conomic produkt id', 'mt-wc-economic'),
            'placeholder' => __('E-conomic produkt id', 'mt-wc-economic'),
            'desc_tip' => 'true',
            'description' => __('Udfyld varenummerer fra e-conomic', 'mt-wc-economic'),
            'type' => 'number',
            'value' => get_post_meta(get_the_ID(), 'economic_product_id', true),
        ]);
        echo '</div>';
    }

    public static function addEconomicProductField( $loop, $variation_data, $variation): void
    {
        woocommerce_wp_text_input([
            'id' => 'economic_product_id_'."[{$loop}]",
            'label' => __('E-conomic produkt id', 'mt-wc-economic'),
            'placeholder' => __('E-conomic produkt id', 'mt-wc-economic'),
            'desc_tip' => 'true',
            'description' => __('Udfyld varenummerer fra e-conomic', 'mt-wc-economic'),
            'type' => 'number',
            'value' => get_post_meta($variation->ID, 'economic_product_id', true),
        ]);
    }

    public static function saveEconomicProductField($post_id): void
    {
        $economic_product_id = $_POST['economic_product_id'] ?? '';

        update_post_meta($post_id, 'economic_product_id', $economic_product_id);
    }

    public static function saveVariationEconomicProductField($post_id, $id): void
    {
        $economic_product_id = $_POST['economic_product_id_'][$id] ?? '';

        update_post_meta($post_id, 'economic_product_id', $economic_product_id);
    }
}
