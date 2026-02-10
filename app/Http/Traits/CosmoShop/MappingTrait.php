<?php

namespace App\Http\Traits\CosmoShop;

trait MappingTrait
{
    public function getMapping($mappingName)
    {
        switch ($mappingName) {
            case ('category_content'):
                return [
                    'category_code_ref' => 'rubnumref',
                    'language' => 'rubsprache',
                    'category_name' => 'rubnam',
                    'description' => 'rubtext',
                    'meta_keywords' => 'keywords',
                    'short_description' => 'rubtext_kurz',
                    'url_key' => 'urlkey',
                    'slider_id' => 'slider_id',
                    'category_id' => 'rubid',
                ];
            case ('product_content'):
                return [
                    'article_id' => 'artikelid',
                    'language' => 'sprache',
                    'title' => 'name',
                    'description' => 'bezeichnung',
                    'description_html' => 'bezeichnung_html',
                    'short_description' => 'bezeichnung_kurz',
                    'is_plain_description' => 'bezeichnung_plain',
                    'keywords' => 'keywords',
                    'image_alt_text' => 'bilder_alt',
                    'search_field' => 'suchfeld',
                    'live_shopping_text' => 'liveshopping_text',
                    'url_key' => 'urlkey',
                    'second_price_label' => 'second_price_label',
                    'features' => 'features',
                ];
            case ('delivery_content'):
                return [
                    'name' => 'name',
                    'language' => 'sprache'
                ];
        }
    }
}
