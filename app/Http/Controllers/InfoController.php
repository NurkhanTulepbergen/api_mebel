<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
/**
 * @group GeneralInfo
 */
class InfoController extends Controller
{
    public function database(){
        $jv = [
            'jv.de' => 'jvmoebel.de',
            'jv.co.uk' => 'jvfurniture.co.uk',
            'jv.ch' => 'jvmoebel.ch',
            'jv.at' => 'jvmoebel.at',
        ];
        $oc = [
            'b2b.de' => 'moebelb2b.de',
            'be' => 'xlmeubella.be',
            'de' => 'xlmoebel.de',
            'lu' => 'xlmoebel.lu',
            'depotum.ch' => 'depotum.ch',
            'xl.ie' => 'xlfurniture.ie',
            'xl.gr' => 'xlhome.gr',
            'xl.ro' => 'xlmobila.ro',
            'xl.fi' => 'xlhuonekalut.fi',
            'xl.pt' => 'xlmobiliario.pt',
            'xl.se' => 'xlmobler.se',
            'xl.sk' => 'xlnabytok.sk',
            'xl.si' => 'xlposlovno.si',
            'xl.dk' => 'xxlmobler.dk',
            'turkey' => 'furniture-from-turkey.com',
            'xl.co.uk' => 'xlfurniture.co.uk',
            'xl.pl' => 'xlmeble.pl',
            'xl.fr' => 'xlmeubles.fr',
            'xl.at' => 'xlmoebel.at',
            'xl.ch' => 'xlmoebel.ch',
            'xlnabytek.cz' => 'xlnabytek.cz',
        ];
        $databases = [
            'jv' => $jv,
            'oc' => $oc
        ];
        return response()->json([
            'data' => $databases
        ], 200);
    }

    public function productFields(){
        $fields = [
            'article_id' => [
                'base_name'     => 'artikelid',
                'description'   => 'Уникальный идентификатор товара (первичный ключ)',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'int(10)'
            ],
            'article_base_id' => [
                'base_name'     => 'artikelbaseid',
                'description'   => 'Идентификатор базового товара. Может использоваться для связывания вариаций или модификаций с исходным товаром',
                'type'          => 'int(10)'
            ],
            'article_number' => [
                'base_name'     => 'artikelnr',
                'description'   => 'Артикульный номер товара, то есть уникальный код или номер, по которому товар идентифицируется',
                'type'          => 'varchar(200)'
            ],
            'is_auto' => [
                'base_name'     => 'auto',
                'description'   => 'Флаг (0 или 1), указывающий, возможно, на автоматическое создание или обновление записи товара',
                'type'          => 'tinyint(4)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'is_inactive' => [
                'base_name'     => 'inaktiv',
                'description'   => 'Флаг неактивности. Если значение равно 1, товар считается неактивным (не отображается или недоступен для заказа)',
                'type'          => 'tinyint(4)'
            ],
            'type' => [
                'base_name'     => 'type',
                'description'   => 'Тип товара. Значение может использоваться для классификации товаров (например, обычный, комплект или услуга)',
                'type'          => 'tinyint(4)'
            ],
            'created_at' => [
                'base_name'     => 'erfasst',
                'description'   => 'Дата и время, когда товар был впервые внесён в базу (момент создания записи)',
                'type'          => 'datetime'
            ],
            'updated_at' => [
                'base_name'     => 'geaendert',
                'description'   => 'Временная метка, фиксирующая дату и время последнего изменения записи. Значение обновляется автоматически при изменении данных',
                'type'          => 'timestamp'
            ],
            'updated_by' => [
                'base_name'     => 'user',
                'description'   => 'Имя пользователя, который создал или последний раз изменил запись товара',
                'type'          => 'varchar(45)'
            ],
            'tax_id' => [
                'base_name'     => 'mwstid',
                'description'   => 'Идентификатор ставки налога (например, НДС) для товара',
                'type'          => 'tinyint(3)'
            ],
            'discount_group_id' => [
                'base_name'     => 'rabattgruppeid',
                'description'   => 'Идентификатор группы скидок, применяемой к товару, если такая предусмотрена',
                'type'          => 'tinyint(4)'
            ],
            'delivery_time_id' => [
                'base_name'     => 'lieferzeitid',
                'description'   => 'Идентификатор времени доставки. Позволяет связать товар с определённым сроком доставки',
                'type'          => 'tinyint(3)'
            ],
            'unit_id' => [
                'base_name'     => 'einheitid',
                'description'   => 'Идентификатор единицы измерения товара (например, штука, килограмм, литр)',
                'type'          => 'tinyint(3)'
            ],
            'content' => [
                'base_name'     => 'inhalt',
                'description'   => 'Количественное значение, обозначающее объём или количество содержимого товара (например, объем жидкости или масса)',
                'type'          => 'decimal(20,10)'
            ],
            'base_unit' => [
                'base_name'     => 'grundeinheit',
                'description'   => 'Базовая единица измерения товара. Может использоваться для определения единицы, в которой товар оптово закупается или хранится',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'int(11)'
            ],
            'package_unit' => [
                'base_name'     => 'vpe',
                'description'   => 'Количество единиц товара в упаковке (Verpackungseinheit). Может задавать минимальный объём упаковки',
                'type'          => 'int(11)'
            ],
            'weight_net' => [
                'base_name'     => 'gewicht_netto',
                'description'   => 'Чистый вес товара (без упаковки) в заданных единицах измерения',
                'type'          => 'decimal(10,3)'
            ],
            'weight_gross' => [
                'base_name'     => 'gewicht_brutto',
                'description'   => 'Брутто вес товара, то есть вес с упаковкой',
                'type'          => 'decimal(10,3)'
            ],
            'is_special_price' => [
                'base_name'     => 'special_price',
                'description'   => 'Флаг специальной цены. Если установлен (обычно 1), то для товара может действовать специальное ценовое предложение',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'is_new' => [
                'base_name'     => 'neu',
                'description'   => 'Флаг, указывающий, что товар новый. Позволяет выделять новинки в каталоге',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True',
            ],
            'is_price_request' => [
                'base_name'     => 'preiswunsch',
                'description'   => 'Флаг, возможно, указывающий на возможность запроса цены или отображения "желанной цены" (например, в случаях, когда цена обсуждаема)',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'is_ebay' => [
                'base_name'     => 'ebay',
                'description'   => 'Флаг, обозначающий, что товар предназначен для экспорта или синхронизации с площадкой eBay',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'kelkoo_category' => [
                'base_name'     => 'kelkoo_category',
                'description'   => 'Категория товара согласно системе Kelkoo – одной из платформ для сравнительного анализа цен',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'varchar(250)',
                'comment'       => 'В бд она прописана как required, но в данной мне БД нет ни одного товара у которого прописано это поле',
                'doesnt_have_non_default_value' => 'True'
            ],
            'manufacturer' => [
                'base_name'     => 'hersteller',
                'description'   => 'Наименование производителя товара',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'varchar(250)'
            ],
            'ean' => [
                'base_name'     => 'ean',
                'description'   => 'Штрихкод товара по стандарту EAN, используемый для уникальной идентификации в торговле',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'varchar(13)'
            ],
            'request_count' => [
                'base_name'     => 'anfrage',
                'description'   => 'Флаг или счётчик запросов по товару. Может использоваться для учета запросов или специального статуса',
                'type'          => 'tinyint(3)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'is_downloadable' => [
                'base_name'     => 'download',
                'description'   => 'Флаг, обозначающий, что товар является цифровым (имеет возможность скачивания)',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'url_key' => [
                'base_name'     => 'urlkey',
                'description'   => 'Уникальный ключ для формирования понятного URL-адреса товара на сайте',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'varchar(100)'
            ],
            'suggested_price' => [
                'base_name'     => 'uvp',
                'description'   => 'Рекомендуемая розничная цена (Unverbindliche Preisempfehlung) товара. Может использоваться для сравнения с фактической ценой',
                'type'          => 'decimal(20,10)'
            ],
            'condition' => [
                'base_name'     => 'zustand',
                'description'   => 'Состояние товара. Например, новое, б/у, восстановленное – числовое значение, обозначающее конкретное состояние',
                'type'          => 'tinyint(4)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'bulk_pricing_mode' => [
                'base_name'     => 'staffelhandling_ausf',
                'description'   => 'Метод обработки товарных партий или наценок, представленный в виде перечисления (enum):',
                'enum'          => [
                                        'd – по умолчанию',
                                        'je_ausf – за каждое выполнение',
                                        'je_artikel – за каждый товар'
                                    ],
            ],
            'stock_threshold_1' => [
                'base_name'     => 'bestand_schwelle_1',
                'description'   => 'Первый порог запаса. При достижении этого уровня могут запускаться определённые действия (например, пополнение склада)',
                'type'          => 'int(11)'
            ],
            'stock_lead_time_1' => [
                'base_name'     => 'bestand_lz_1',
                'description'   => 'Значение длительности или параметр запаса, связанного с первым порогом. Может задавать время реакции или задержку при достижении порога',
                'type'          => 'int(11)'
            ],
            'stock_threshold_2' => [
                'base_name'     => 'bestand_schwelle_2',
                'description'   => 'Второй порог запаса, позволяющий задать альтернативные условия управления запасами',
                'type'          => 'int(11)'
            ],
            'stock_lead_time_2' => [
                'base_name'     => 'bestand_lz_2',
                'description'   => 'Аналог второго порога по времени или количеству для управления запасами',
                'type'          => 'int(11)'
            ],
            'is_stock_disabled' => [
                'base_name'     => 'bestand_deaktiv',
                'description'   => 'Флаг, указывающий на автоматическую деактивацию товара при достижении определённых условий по запасу',
                'type'          => 'tinyint(1)'
            ],
            'max_order_quantity' => [
                'base_name'     => 'max_bestellmenge',
                'description'   => 'Максимально допустимое количество товара, которое можно заказать за один раз',
                'type'          => 'int(11)'
            ],
            'stock_display_mode' => [
                'base_name'     => 'bestand_anzeigen',
                'description'   => 'Настройка отображения информации о запасах. Возможные значения:',
                'enum' => [
                    'default – по умолчанию',
                    'nein – не отображать',
                    'nur_detail – отображать только в детальном просмотре',
                    'detail_und_vorschau – отображать и в детальном, и в предварительном просмотре',
                ],
            ],
            'is_stock_hidden' => [
                'base_name'     => 'bestand_ausblenden',
                'description'   => 'Флаг, скрывающий информацию о запасах товара (например, на витрине сайта)',
                'type'          => 'tinyint(1)'
            ],
            'ignore_all_variants_stock' => [
                'base_name'     => 'bestand_ignore_all_variants',
                'description'   => 'Флаг, указывающий на игнорирование запасов для всех вариантов товара (если у товара есть варианты, как разные размеры или цвета)',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'product_group_id' => [
                'base_name'     => 'produktgruppeid',
                'description'   => 'Идентификатор группы или категории, к которой относится товар. Позволяет группировать схожие товары',
                'type'          => 'int(11)'
            ],
            'google_category' => [
                'base_name'     => 'google_category',
                'description'   => 'Категория товара согласно классификации Google, что помогает при интеграции с сервисами Google',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'varchar(255)'
            ],
            'is_live_shopping_active' => [
                'base_name'     => 'liveshopping_aktiv',
                'description'   => 'Флаг, активирующий режим живых продаж (liveshopping) для товара',
                'type'          => 'tinyint(4)'
            ],
            'live_shopping_start' => [
                'base_name'     => 'liveshopping_anfang',
                'description'   => 'Дата и время начала мероприятия liveshopping, когда товар выставлен для продажи в режиме онлайн',
                'type'          => 'datetime',
                'doesnt_have_non_default_value' => 'True'
            ],
            'live_shopping_end' => [
                'base_name'     => 'liveshopping_ende',
                'description'   => 'Дата и время окончания liveshopping',
                'type'          => 'datetime',
                'doesnt_have_non_default_value' => 'True'
            ],
            'live_shopping_discount' => [
                'base_name'     => 'liveshopping_rabatt',
                'description'   => 'Процент скидки, применяемый во время liveshopping',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'decimal(4,2)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'exclude_from_portal_export' => [
                'base_name'     => 'no_portal_export',
                'description'   => 'Флаг, указывающий, что товар не подлежит экспорту на сторонние порталы или маркетплейсы',
                'type'          => 'tinyint(4)'
            ],
            'disable_amazon_payment' => [
                'base_name'     => 'no_amazon_payment',
                'description'   => 'Флаг, запрещающий использование Amazon Payment для данного товара',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'has_fixed_bundle_price' => [
                'base_name'     => 'fixed_bundle_price',
                'description'   => 'Флаг, обозначающий, что цена набора (bundle) фиксированная и не изменяется в зависимости от компонентов',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'subsequent_article' => [
                'base_name'     => 'subsequent',
                'description'   => 'Поле, которое может использоваться для указания последовательного (связанного) товара или варианта',
                'type'          => 'int(11)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'requires_proof' => [
                'base_name'     => 'nachweispflicht',
                'description'   => 'Флаг, указывающий на обязательность предоставления подтверждающих документов или данных (например, для сертифицированных товаров)',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'time_planner_start' => [
                'base_name'     => 'timeplaner_start',
                'description'   => 'Дата и время начала планируемого периода действия товара (например, временного предложения или акции)',
                'type'          => 'datetime',
                'doesnt_have_non_default_value' => 'True'
            ],
            'time_planner_end' => [
                'base_name'     => 'timeplaner_ende',
                'description'   => 'Дата и время окончания планируемого периода',
                'type'          => 'datetime',
                'doesnt_have_non_default_value' => 'True'
            ],
            'min_order_quantity' => [
                'base_name'     => 'min_bestellmenge',
                'description'   => 'Минимальное количество товара, которое можно заказать',
                'type'          => 'int(11)'
            ],
            'is_bundle_configurable' => [
                'base_name'     => 'bundle_configarticle',
                'description'   => 'Флаг, обозначающий, что данный товар является частью конфигурации набора или бандла',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'second_price_multiplier' => [
                'base_name'     => 'second_price_multiplier',
                'description'   => 'Множитель, используемый для расчёта второй цены, например, при изменении объёма закупки или при скидках',
                'type'          => 'decimal(10,2)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'is_configurable_article' => [
                'base_name'     => 'is_configarticle',
                'description'   => 'Флаг, указывающий, что товар является конфигурационным (то есть, его можно настроить под заказчика)',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'is_configurable_component' => [
                'base_name'     => 'is_configarticle_component',
                'description'   => 'Флаг, показывающий, что данный товар является компонентом конфигурационного товара',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'has_flexible_shipping' => [
                'base_name'     => 'flexible_shipping',
                'description'   => 'Флаг, разрешающий гибкие условия доставки для товара (например, выбор способа доставки)',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'is_sample_allowed' => [
                'base_name'     => 'sample_allowed',
                'description'   => 'Флаг, разрешающий запрос образца товара (пробник)',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'is_search_indexed' => [
                'base_name'     => 'indexed_for_search',
                'description'   => 'Флаг, указывающий, что товар включён в индекс поиска на сайте (для быстрого и точного поиска)',
                'type'          => 'tinyint(1)'
            ],
            'has_fixed_bulk_pricing' => [
                'base_name'     => 'staffel_fixed',
                'description'   => 'Флаг, обозначающий фиксированную обработку товарных партий, возможно, влияющую на ценообразование при покупке оптом',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'jf_sku' => [
                'base_name'     => 'jfsku',
                'description'   => 'Идентификатор товара по системе JF SKU. Используется для синхронизации с внешними системами',
                'type'          => 'varchar(32)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'sync_with_jtl' => [
                'base_name'     => 'jtl_product_sync',
                'description'   => 'Флаг синхронизации товара с системой JTL (немецкая ERP/OMS-система для торговли)',
                'type'          => 'tinyint(1)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'jtl_length' => [
                'base_name'     => 'jtl_dimensions_length',
                'description'   => 'Длина товара для системы JTL (может использоваться для расчёта доставки или упаковки)',
                'type'          => 'decimal(10,3)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'jtl_width' => [
                'base_name'     => 'jtl_dimensions_width',
                'description'   => 'Ширина товара для системы JTL',
                'type'          => 'decimal(10,3)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'jtl_height' => [
                'base_name'     => 'jtl_dimensions_height',
                'description'   => 'Высота товара для системы JTL',
                'type'          => 'decimal(10,3)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'custom_data' => [
                'base_name'     => 'custom',
                'description'   => 'Поле для хранения пользовательских или дополнительных данных в формате текста (например, JSON или иной формат), где могут быть заданы специфичные для бизнеса параметры',
                'not_nullable && no_default_value'  => 'True',
                'type'          => 'mediumtext',
                'comment'       => 'В бд она прописана как required, но в данной мне БД нет ни одного товара у которого прописано это поле',
                'doesnt_have_non_default_value' => 'True'
            ],
        ];
        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productPricesFields(){
        $fields = [
            'article_id' => [
                'base_name'     => 'artikelid',
                'description'   => 'Уникальный идентификатор товара (первичный ключ)',
                'type'          => 'int(10) unsigned'
            ],
            'price_tier' => [
                'base_name'     => 'staffel',
                'description'   => 'Уровень ценовой ступени или шкалы. Первичный ключ. Позволяет устанавливать разные цены в зависимости от количества покупаемого товара (например, 1-5 штук одна цена, 6-10 другая и т.д.). Значение 1 - это базовая цена.',
                'type'          => 'int(11)'
            ],
            'price' => [
                'base_name'     => 'preis',
                'description'   => 'Непосредственно цена товара с высокой точностью (до 10 знаков после запятой). Основная цена для данной ступени',
                'type'          => 'decimal(20,10)'
            ],
            'percentage' => [
                'base_name'     => 'prozent',
                'description'   => 'Процентная скидка или наценка. Может использоваться для расчета финальной цены от базовой',
                'type'          => 'decimal(5,2)',
                'doesnt_have_non_default_value' => 'True'
            ],
            'price_basis' => [
                'base_name'     => 'basis',
                'description'   => 'char(10)',
                'type'          => 'Основа или база цены. Первичный ключ. Вероятно указывает, от какой базовой цены рассчитывается значение (например, от закупочной, рекомендованной производителем и т.д.)'
            ],
            'filter' => [
                'base_name'     => 'price_filter',
                'description'   => 'char(15)',
                'type'          => 'Фильтр применения цены. Первичный ключ. Может указывать, для каких условий применяется данная цена (например, определенный регион, тип клиента и т.п.)'
            ],
            'currency' => [
                'base_name'     => 'waehrung',
                'description'   => 'char(5)',
                'type'          => 'Валюта цены. Первичный ключ. Позволяет хранить цены в разных валютах для одного товара (USD, EUR, RUB и т.д.)'
            ],
            'auto_calculate' => [
                'base_name'     => 'auto',
                'description'   => 'tinyint(4)',
                'type'          => 'Флаг автоматического расчета. Если значение 1, то цена, вероятно, рассчитывается автоматически по какому-то алгоритму системы',
                'doesnt_have_non_default_value' => 'True'
            ],
            'tier_id' => [
                'base_name'     => 'staffelid',
                'description'   => 'int(10)',
                'type'          => 'Идентификатор ценовой ступени. Позволяет группировать ступени в более сложные структуры ценообразования или связывать с другими таблицами',
                'doesnt_have_non_default_value' => 'True'
            ],
            'alternative_price' => [
                'base_name'     => 'second_price',
                'description'   => 'decimal(20,10)',
                'type'          => 'Вторая или альтернативная цена. Может использоваться для различных целей, например:',
                'options'       => [
                    'Цена до скидки для отображения перечеркнутой старой цены',
                    'Специальная цена для определенных групп клиентов',
                    'Цена для сравнения с конкурентами',
                ],
                'doesnt_have_non_default_value' => 'True'
            ],
        ];
        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productContentFields(){
        $fields = [
            'article_id' => [
                'base_name'   => 'artikelid',
                'description' => 'Уникальный идентификатор товара (первичный ключ)',
                'type'        => 'int(10) unsigned'
            ],
            'language' => [
                'base_name'   => 'sprache',
                'description' => 'Язык контента (например, "de" для немецкого, "en" для английского)',
                'type'        => 'char(3)'
            ],
            'title' => [
                'base_name'   => 'name',
                'description' => 'Название товара',
                'type'        => 'varchar(250)'
            ],
            'description' => [
                'base_name'   => 'bezeichnung',
                'description' => 'Основное описание товара',
                'type'        => 'text'
            ],
            'description_html' => [
                'base_name'   => 'bezeichnung_html',
                'description' => 'Описание товара в формате HTML',
                'type'        => 'text'
            ],
            'short_description' => [
                'base_name'   => 'bezeichnung_kurz',
                'description' => 'Краткое описание товара',
                'type'        => 'text'
            ],
            'is_plain_description' => [
                'base_name'   => 'bezeichnung_plain',
                'description' => 'Флаг, указывающий, что описание без HTML (0 - нет, 1 - да)',
                'type'        => 'tinyint(1)'
            ],
            'keywords' => [
                'base_name'   => 'keywords',
                'description' => 'Ключевые слова для поиска',
                'type'        => 'text'
            ],
            'image_alt_text' => [
                'base_name'   => 'bilder_alt',
                'description' => 'ALT-текст изображений',
                'type'        => 'varchar(250)'
            ],
            'search_field' => [
                'base_name'   => 'suchfeld',
                'description' => 'Поле с данными для поиска',
                'type'        => 'text'
            ],
            'live_shopping_text' => [
                'base_name'   => 'liveshopping_text',
                'description' => 'Текст, используемый в живых распродажах',
                'type'        => 'text'
            ],
            'url_key' => [
                'base_name'   => 'urlkey',
                'description' => 'SEO-friendly URL товара',
                'type'        => 'varchar(255)'
            ],
            'second_price_label' => [
                'base_name'   => 'second_price_label',
                'description' => 'Заголовок для второй цены',
                'type'        => 'text'
            ],
            'features' => [
                'base_name'   => 'features',
                'description' => 'Дополнительные характеристики товара',
                'type'        => 'text'
            ]
        ];
        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productStockFields(){
        $fields = [
            'article_id' => [
                'base_name'   => 'artikelid',
                'description' => 'Уникальный идентификатор товара (связь с таблицей товаров)',
                'type'        => 'int(11)'
            ],
            'stock' => [
                'base_name'   => 'bestand',
                'description' => 'Текущее количество товара на складе',
                'type'        => 'int(11)'
            ],
            'min_stock' => [
                'base_name'   => 'bestand_min',
                'description' => 'Минимальный порог товара на складе, при достижении которого может срабатывать уведомление',
                'type'        => 'int(11)'
            ],
            'ignore_stock' => [
                'base_name'   => 'bestand_ignore',
                'description' => 'Флаг игнорирования остатков (0 - учитывать, 1 - не учитывать)',
                'type'        => 'tinyint(1)'
            ],
            'created_at' => [
                'base_name'   => 'timestamp',
                'description' => 'Последнее обновление данных о наличии товара',
                'type'        => 'timestamp'
            ],
            'storage_location' => [
                'base_name'   => 'storage',
                'description' => 'Местоположение хранения товара (например, склад или магазин)',
                'type'        => 'varchar(250)'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productCategoriesFields(){
        $fields = [
            'article_id' => [
                'base_name'   => 'artikelid',
                'description' => 'Уникальный идентификатор товара (связь с таблицей товаров)',
                'type'        => 'bigint(20)'
            ],
            'category_number' => [
                'base_name'   => 'rubnum',
                'description' => 'Уникальный номер рубрики или категории',
                'type'        => 'varchar(250)'
            ],
            'sort_order' => [
                'base_name'   => 'ordnum',
                'description' => 'Порядковый номер в сортировке внутри категории',
                'type'        => 'smallint(5) unsigned'
            ],
            'priority_level' => [
                'base_name'   => 'priority',
                'description' => 'Приоритет данной записи',
                'type'        => 'tinyint(4)'
            ],
            'category_id' => [
                'base_name'   => 'rubid',
                'description' => 'Идентификатор рубрики или категории, к которой привязан товар',
                'type'        => 'int(10) unsigned'
            ],
        ];


        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productVariantFields(){
        $fields = [
            'variant_id' => [
                'base_name'   => 'artikelvarianteid',
                'description' => 'Уникальный идентификатор варианта товара',
                'type'        => 'int(10) unsigned'
            ],
            'is_inactive' => [
                'base_name'   => 'inaktiv',
                'description' => 'Флаг активности варианта (0 - активен, 1 - неактивен)',
                'type'        => 'tinyint(1)'
            ],
            'execution_id' => [
                'base_name'   => 'artikelausfuehrungid',
                'description' => 'Идентификатор исполнения товара',
                'type'        => 'int(10) unsigned'
            ],
            'variant_number' => [
                'base_name'   => 'variantenr',
                'description' => 'Номер варианта товара',
                'type'        => 'int(10) unsigned'
            ],
            'variant_article_id' => [
                'base_name'   => 'artikelidvariante',
                'description' => 'Идентификатор основного товара, к которому относится вариант',
                'type'        => 'bigint(20) unsigned'
            ],
            'sort_order' => [
                'base_name'   => 'order',
                'description' => 'Порядковый номер варианта в списке',
                'type'        => 'int(10) unsigned'
            ],
            'custom_key' => [
                'base_name'   => 'custom_key',
                'description' => 'Пользовательский ключ варианта, если задан',
                'type'        => 'varchar(50)'
            ],
            'attribute_id' => [
                'base_name'   => 'attributid',
                'description' => 'Идентификатор атрибута, связанного с вариантом',
                'type'        => 'int(11)'
            ],
            'second_price_multiplier' => [
                'base_name'   => 'second_price_multiplier',
                'description' => 'Множитель для второй цены варианта товара',
                'type'        => 'decimal(5,2)'
            ],
            'show_text_input' => [
                'base_name'   => 'show_text_input',
                'description' => 'Флаг, разрешающий ввод текста пользователем (0 - нет, 1 - да)',
                'type'        => 'tinyint(1)'
            ],
            'custom_data' => [
                'base_name'   => 'custom',
                'description' => 'Дополнительные пользовательские данные в формате текста',
                'type'        => 'mediumtext'
            ],
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productVariantContentFields(){
        $fields = [
            'variant_id' => [
                'base_name' => 'artikelvarianteid',
                'description' => 'Уникальный идентификатор варианта товара',
                'type' => 'int(10) unsigned'
            ],
            'language' => [
                'base_name' => 'sprache',
                'description' => 'Языковой код (например, de, en, fr)',
                'type' => 'char(3)'
            ],
            'designation' => [
                'base_name' => 'bezeichnung',
                'description' => 'Название варианта товара',
                'type' => 'varchar(50)'
            ],
            'description' => [
                'base_name' => 'beschreibung',
                'description' => 'Описание варианта товара',
                'type' => 'text'
            ],
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productAttributesFields(){
        $fields = [
            'article_id' => [
                'base_name' => 'artikelid',
                'description' => 'Уникальный идентификатор конкретного артикула в системе',
                'type' => 'int(11)'
            ],
            'article_base_id' => [
                'base_name' => 'artikelbaseid',
                'description' => 'Базовый идентификатор артикула, к которому относится текущая запись',
                'type' => 'int(11)'
            ],
            'attribute_id' => [
                'base_name' => 'attributid',
                'description' => 'Идентификатор атрибута, связанного с артикулом',
                'type' => 'int(11)'
            ],
            'updated_at' => [
                'base_name' => 'changed',
                'description' => 'Метка времени последнего изменения записи',
                'type' => 'timestamp'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function attributesFields(){
        $fields = [
            'id' => [
                'description' => 'Уникальный идентификатор атрибута. Используется для связи атрибутов и продуктов.',
                'type' => 'integer',
            ],
            'level' => [
                'description' => 'Уровень атрибута в иерархии (1, 2, 3). Определяет вложенность атрибутов.',
                'type' => 'enum("1", "2", "3")',
            ],
            'label' => [
                'description' => 'Название атрибута, отображаемое пользователю.',
                'type' => 'string',
            ],
            'sort' => [
                'description' => 'Порядок сортировки атрибутов. Используется для упорядочивания атрибутов на странице.',
                'type' => 'integer',
            ],
            'refid' => [
                'description' => 'Идентификатор родительского атрибута. Используется для создания иерархических структур атрибутов.',
                'type' => 'integer',
            ],
            'google_category' => [
                'description' => 'Категория атрибута в Google Shopping. Используется для интеграции с Google Merchant Center.',
                'type' => 'string',
            ],
            'root_display_group' => [
                'description' => 'Флаг, указывающий, является ли атрибут корневой группой для отображения.',
                'type' => 'boolean',
            ],
            'display_type' => [
                'description' => 'Тип отображения атрибута на странице (например, checkboxes, select).',
                'type' => 'string',
            ],
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productAttributesAssignmentFields(){
        $fields = [
            'article_id' => [
                'base_name' => 'element',
                'description' => 'Идентификатор элемента, который относится к определённому атрибуту',
                'type' => 'int(11)'
            ],
            'attribute_id' => [
                'base_name' => 'gruppe',
                'description' => 'Идентификатор группы атрибутов, к которой относится элемент',
                'type' => 'int(11)'
            ],
            'sort_order' => [
                'base_name' => 'sort',
                'description' => 'Порядковый номер для сортировки элементов внутри группы',
                'type' => 'int(11) DEFAULT 0'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function attributeContentFields(){
        $fields = [
            'attribute_id' => [
                'base_name' => 'id',
                'description' => 'Уникальный идентификатор содержания атрибута',
                'type' => 'int(11)'
            ],
            'language_code' => [
                'base_name' => 'sprache',
                'description' => 'Код языка (например, de, en, fr)',
                'type' => "char(3) NOT NULL DEFAULT 'de'"
            ],
            'attribute_name' => [
                'base_name' => 'name',
                'description' => 'Название или значение атрибута на указанном языке',
                'type' => 'text NOT NULL'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function propertiesFields(){
        $fields = [
            'property_id' => [
                'base_name' => 'id',
                'description' => 'Уникальный идентификатор свойства',
                'type' => 'int(11)'
            ],
            'property_name' => [
                'base_name' => 'propertyname',
                'description' => 'Название свойства',
                'type' => 'varchar(255) NOT NULL'
            ],
            'property_code' => [
                'base_name' => 'propertycode',
                'description' => 'Код свойства, используемый в системе',
                'type' => 'varchar(50) NOT NULL'
            ],
            'is_visible_frontend' => [
                'base_name' => 'frontendavailable',
                'description' => 'Доступно ли свойство на фронтенде (0 - нет, 1 - да)',
                'type' => 'tinyint(1) NOT NULL DEFAULT 0'
            ],
            'is_visible_backend' => [
                'base_name' => 'backendavailable',
                'description' => 'Доступно ли свойство в админке (0 - нет, 1 - да)',
                'type' => 'tinyint(1) NOT NULL DEFAULT 1'
            ],
            'plugin_name' => [
                'base_name' => 'plugin',
                'description' => 'Название плагина, который использует это свойство (если есть)',
                'type' => "varchar(50) NOT NULL DEFAULT ''"
            ],
            'sort_order' => [
                'base_name' => 'sort',
                'description' => 'Порядковый номер для сортировки свойств',
                'type' => 'int(11) NOT NULL DEFAULT 99999999'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productPropertiesFields(){
        $fields = [
            'property_id' => [
                'base_name' => 'id',
                'description' => 'Уникальный идентификатор свойства',
                'type' => 'int(11)'
            ],
            'property_name' => [
                'base_name' => 'propertyname',
                'description' => 'Название свойства',
                'type' => 'varchar(255) NOT NULL'
            ],
            'property_code' => [
                'base_name' => 'propertycode',
                'description' => 'Код свойства, используемый в системе',
                'type' => 'varchar(50) NOT NULL'
            ],
            'is_visible_frontend' => [
                'base_name' => 'frontendavailable',
                'description' => 'Доступно ли свойство на фронтенде (0 - нет, 1 - да)',
                'type' => 'tinyint(1) NOT NULL DEFAULT 0'
            ],
            'is_visible_backend' => [
                'base_name' => 'backendavailable',
                'description' => 'Доступно ли свойство в админке (0 - нет, 1 - да)',
                'type' => 'tinyint(1) NOT NULL DEFAULT 1'
            ],
            'plugin_name' => [
                'base_name' => 'plugin',
                'description' => 'Название плагина, который использует это свойство (если есть)',
                'type' => "varchar(50) NOT NULL DEFAULT ''"
            ],
            'sort_order' => [
                'base_name' => 'sort',
                'description' => 'Порядковый номер для сортировки свойств',
                'type' => 'int(11) NOT NULL DEFAULT 99999999'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function stockMovementsFields(){
        $fields = [
            'booking_id' => [
                'base_name' => 'buchung_id',
                'description' => 'Уникальный идентификатор записи о движении товара',
                'type' => 'int(10) UNSIGNED NOT NULL'
            ],
            'article_id' => [
                'base_name' => 'artikelid',
                'description' => 'Уникальный идентификатор конкретного артикула в системе',
                'type' => 'int(10) UNSIGNED NOT NULL'
            ],
            'booking_time' => [
                'base_name' => 'buchungszeit',
                'description' => 'Временная метка совершения движения товара',
                'type' => 'timestamp NOT NULL DEFAULT current_timestamp()'
            ],
            'booking_type' => [
                'base_name' => 'typ',
                'description' => 'Тип движения товара (например, бронирование, удаление, абсолютное значение)',
                'type' => "enum('buchung','reservierung','absolut','delete') NOT NULL"
            ],
            'booking_quantity' => [
                'base_name' => 'buchung',
                'description' => 'Количество товара, участвующего в движении',
                'type' => 'int(11) NOT NULL'
            ],
            'origin' => [
                'base_name' => 'herkunft',
                'description' => 'Источник, из которого произошло изменение',
                'type' => 'varchar(120) NOT NULL'
            ],
            'order_number' => [
                'base_name' => 'best_nr',
                'description' => 'Номер заказа, связанный с движением товара',
                'type' => "varchar(20) NOT NULL DEFAULT ''"
            ],
            'current_stock' => [
                'base_name' => 'stand_aktuell',
                'description' => 'Текущее состояние складского запаса',
                'type' => 'varchar(250) NOT NULL'
            ],
            'previous_stock' => [
                'base_name' => 'stand_zuvor',
                'description' => 'Состояние складского запаса до изменений',
                'type' => 'varchar(250) NOT NULL'
            ],
            'details' => [
                'base_name' => 'detail',
                'description' => 'Дополнительные детали о движении товара',
                'type' => 'text NOT NULL'
            ],
            'stack_trace' => [
                'base_name' => 'stacktrace',
                'description' => 'Технический лог вызовов для отладки',
                'type' => 'text NOT NULL'
            ],
            'storage_location' => [
                'base_name' => 'storage',
                'description' => 'Название склада или хранилища',
                'type' => "varchar(250) DEFAULT 'default'"
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productVariationFields(){
        $fields = [
            'execution_id' => [
                'base_name' => 'artikelausfuehrungid',
                'description' => 'Уникальный идентификатор исполнения для конкретного артикула',
                'type' => 'int(10) UNSIGNED'
            ],
            'article_id' => [
                'base_name' => 'artikelid',
                'description' => 'Идентификатор артикула, к которому относится исполнение',
                'type' => 'int(10) UNSIGNED'
            ],
            'execution_number' => [
                'base_name' => 'ausfuehrungnr',
                'description' => 'Номер исполнения артикула',
                'type' => 'int(11)'
            ],
            'order' => [
                'base_name' => 'order',
                'description' => 'Порядковый номер исполнения артикула',
                'type' => 'int(10) UNSIGNED'
            ],
            'custom_key' => [
                'base_name' => 'custom_key',
                'description' => 'Кастомный ключ для исполнения артикула',
                'type' => 'varchar(50) DEFAULT NULL'
            ],
            'attribute_class_id' => [
                'base_name' => 'attributsklasseid',
                'description' => 'Идентификатор класса атрибута, к которому относится исполнение',
                'type' => 'int(11)'
            ],
            'attribute_class_lock' => [
                'base_name' => 'attributsklasse_lock',
                'description' => 'Флаг блокировки класса атрибутов для исполнения артикула',
                'type' => 'tinyint(1) NOT NULL DEFAULT 0'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productVariationContentFields(){
        $fields = [
            'execution_id' => [
                'base_name' => 'artikelausfuehrungid',
                'description' => 'Идентификатор вариации товара (исполнения товара)',
                'type' => 'int(10) UNSIGNED NOT NULL'
            ],
            'language_code' => [
                'base_name' => 'sprache',
                'description' => 'Код языка (например, de, en, fr)',
                'type' => 'char(3) NOT NULL'
            ],
            'name' => [
                'base_name' => 'bezeichnung',
                'description' => 'Название вариации товара на указанном языке',
                'type' => 'varchar(50) NOT NULL'
            ],
            'name_wk' => [
                'base_name' => 'bezeichnungwk',
                'description' => 'Дополнительное название или описание для вариации товара (например, на других языках)',
                'type' => 'varchar(255) DEFAULT NULL'
            ],
            'description' => [
                'base_name' => 'beschreibung',
                'description' => 'Описание вариации товара на указанном языке',
                'type' => 'text DEFAULT NULL'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productReviewFields(){
        $fields = [
            'review_id' => [
                'base_name' => 'id',
                'description' => 'Уникальный идентификатор отзыва',
                'type' => 'int(11)'
            ],
            'article_number' => [
                'base_name' => 'artnum',
                'description' => 'Артикул товара, к которому относится отзыв',
                'type' => 'varchar(100)'
            ],
            'customer_id' => [
                'base_name' => 'kd_id',
                'description' => 'Идентификатор клиента, оставившего отзыв',
                'type' => 'int(10) UNSIGNED'
            ],
            'last_updated' => [
                'base_name' => 'letzter',
                'description' => 'Последнее обновление (неясное поле, требует уточнения)',
                'type' => 'int(10) UNSIGNED'
            ],
            'date' => [
                'base_name' => 'datum',
                'description' => 'Дата публикации отзыва',
                'type' => 'date'
            ],
            'is_read' => [
                'base_name' => 'gelesen',
                'description' => 'Флаг, прочитан ли отзыв (1 - да, 0 - нет)',
                'type' => 'int(10) UNSIGNED'
            ],
            'helpful_count' => [
                'base_name' => 'hilfreich',
                'description' => 'Количество пользователей, которым отзыв оказался полезным',
                'type' => 'int(10) UNSIGNED'
            ],
            'title' => [
                'base_name' => 'titel',
                'description' => 'Заголовок отзыва',
                'type' => 'varchar(255)'
            ],
            'review_text' => [
                'base_name' => 'bewertung',
                'description' => 'Текст отзыва',
                'type' => 'text'
            ],
            'rating' => [
                'base_name' => 'sterne',
                'description' => 'Оценка в звездах (0-5)',
                'type' => 'tinyint(1)'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productRecomendationFields(){
        $fields = [
            'primary_product_id' => [
                'base_name' => 'artikel',
                'description' => 'Артикул основного товара',
                'type' => 'varchar(100)'
            ],
            'recommended_product_id' => [
                'base_name' => 'empfehlung',
                'description' => 'Артикул рекомендуемого товара',
                'type' => 'varchar(220)'
            ],
            'purchase_count' => [
                'base_name' => 'anzahl',
                'description' => 'Количество раз, когда рекомендованный товар был куплен вместе с основным',
                'type' => 'int(10) UNSIGNED'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productBestsellerFields(){
        $fields = [
            'article_number' => [
                'base_name' => 'artnum',
                'description' => 'Артикул товара',
                'type' => 'varchar(250)'
            ],
            'sold_count' => [
                'base_name' => 'verkauft',
                'description' => 'Количество проданных единиц товара',
                'type' => 'int(11)'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productSupplierInfoFields(){
        $fields = [
            'supplier_info_id' => [
                'base_name' => 'lieferanteninfoid',
                'description' => 'Уникальный идентификатор информации о поставщике',
                'type' => 'int(10) UNSIGNED AUTO_INCREMENT'
            ],
            'article_id' => [
                'base_name' => 'artikelid',
                'description' => 'Уникальный идентификатор товара',
                'type' => 'int(10) UNSIGNED'
            ],
            'supplier_id' => [
                'base_name' => 'lieferantid',
                'description' => 'Уникальный идентификатор поставщика',
                'type' => 'int(11)'
            ],
            'supplier_article_number' => [
                'base_name' => 'liefernr',
                'description' => 'Номер артикула у поставщика',
                'type' => 'varchar(200)'
            ],
            'purchase_price' => [
                'base_name' => 'preis_ek',
                'description' => 'Закупочная цена товара',
                'type' => 'decimal(20,10)'
            ],
            'sold_quantity' => [
                'base_name' => 'abverkauf',
                'description' => 'Количество проданных единиц у поставщика',
                'type' => 'int(11)'
            ],
            'delivery_time' => [
                'base_name' => 'lieferzeit',
                'description' => 'Срок поставки в днях',
                'type' => 'int(11)'
            ],
            'delivery_date' => [
                'base_name' => 'lieferdatum',
                'description' => 'Дата поставки',
                'type' => 'date'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function productCustomerGroupsFields(){
        $fields = [
            'article_id' => [
                'base_name' => 'artikelid',
                'description' => 'ID товара',
                'type' => 'int(10) UNSIGNED'
            ],
            'customer_group_id' => [
                'base_name' => 'kdgrpid',
                'description' => 'ID группы клиентов',
                'type' => 'tinyint(3) UNSIGNED'
            ],
            'is_active' => [
                'base_name' => 'aktiv',
                'description' => 'Флаг активности товара для группы клиентов (1 - активен, 0 - не активен)',
                'type' => 'tinyint(4)'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function unitOfMeasurmentFields(){
        $fields = [
            'unit_id' => [
                'base_name' => 'id',
                'description' => 'Уникальный идентификатор единицы измерения',
                'type' => 'int(11)'
            ],
            'internal_name' => [
                'base_name' => 'name_intern',
                'description' => 'Название единицы измерения (внутреннее представление)',
                'type' => 'varchar(250)'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function unitOfMeasurmentContentFields(){
        $fields = [
            'unit_id' => [
                'base_name' => 'parent_id',
                'description' => 'Идентификатор единицы измерения, к которой относится перевод',
                'type' => 'int(11)'
            ],
            'language_code' => [
                'base_name' => 'sprache',
                'description' => 'Код языка (ISO 639-1)',
                'type' => 'char(2)'
            ],
            'name' => [
                'base_name' => 'bezeichnung',
                'description' => 'Название единицы измерения на соответствующем языке',
                'type' => 'varchar(250)'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function unitOfMeasurmentRelationtFields(){
        $fields = [
            'base_unit_id' => [
                'base_name' => 'ober_einheit',
                'description' => 'Идентификатор базовой единицы измерения',
                'type' => 'int(11)'
            ],
            'sub_unit_id' => [
                'base_name' => 'unter_einheit',
                'description' => 'Идентификатор производной единицы измерения',
                'type' => 'varchar(250)'
            ],
            'conversion_factor' => [
                'base_name' => 'divisor',
                'description' => 'Коэффициент перевода из базовой единицы в производную (делитель)',
                'type' => 'int(11)'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function categoryFields(){
        $fields = [
            'category_id' => [
                'base_name' => 'rubid',
                'description' => 'Уникальный идентификатор категории',
                'type' => 'int(11)'
            ],
            'category_code' => [
                'base_name' => 'rubnum',
                'description' => 'Код категории в системе',
                'type' => 'varchar(200)'
            ],
            'category_image' => [
                'base_name' => 'rubbild',
                'description' => 'Изображение категории',
                'type' => 'varchar(250)'
            ],
            'sort_type' => [
                'base_name' => 'rubsort',
                'description' => 'Тип сортировки категории',
                'type' => 'tinyint(4)'
            ],
            'mode' => [
                'base_name' => 'rubmode',
                'description' => 'Режим отображения категории',
                'type' => 'tinyint(4)'
            ],
            'sort_order' => [
                'base_name' => 'ruborder',
                'description' => 'Порядок сортировки в списке категорий',
                'type' => 'smallint(6)'
            ],
            'customer_groups' => [
                'base_name' => 'rub_kdgruppen',
                'description' => 'Группы клиентов, имеющие доступ к категории',
                'type' => 'varchar(250)'
            ],
            'payment_selection' => [
                'base_name' => 'rub_bpsel',
                'description' => 'Выбор способов оплаты для категории',
                'type' => 'varchar(255)'
            ],
            'parent_path' => [
                'base_name' => 'rub_parent',
                'description' => 'Путь к родительской категории',
                'type' => 'varchar(250)'
            ],
            'url_key' => [
                'base_name' => 'ruburlkey',
                'description' => 'Ключ URL для SEO-оптимизации',
                'type' => 'varchar(100)'
            ],
            'parent_id' => [
                'base_name' => 'parentid',
                'description' => 'Идентификатор родительской категории',
                'type' => 'int(11)'
            ],
            'updated_at' => [
                'base_name' => 'geaendert',
                'description' => 'Время последнего изменения',
                'type' => 'timestamp'
            ],
            'article_sort_string' => [
                'base_name' => 'rubartikel_sort_str',
                'description' => 'Строка для сортировки товаров внутри категории',
                'type' => 'varchar(250)'
            ],
            'disable_amazon_payment' => [
                'base_name' => 'no_amazon_payment',
                'description' => 'Флаг отключения оплаты через Amazon для этой категории',
                'type' => 'tinyint(1)'
            ],
            'mega_menu_data' => [
                'base_name' => 'mega_menu',
                'description' => 'Данные мега-меню в бинарном формате',
                'type' => 'blob'
            ],
            'disable_indexing' => [
                'base_name' => 'noindex',
                'description' => 'Флаг запрета индексации категории поисковыми системами',
                'type' => 'tinyint(1)'
            ],
            'external_key' => [
                'base_name' => 'key_extern',
                'description' => 'Внешний ключ для интеграции с другими системами',
                'type' => 'varchar(255)'
            ],
            'disable_additional_popup' => [
                'base_name' => 'rub_zusatz_no_popup',
                'description' => 'Флаг отключения дополнительных всплывающих окон',
                'type' => 'tinyint(1)'
            ],
            'show_additional_bottom' => [
                'base_name' => 'rub_zusatz_bottom',
                'description' => 'Флаг отображения дополнительной информации внизу',
                'type' => 'tinyint(1)'
            ],
            'disable_article_listing' => [
                'base_name' => 'disable_articlelisting',
                'description' => 'Флаг отключения списка товаров в категории',
                'type' => 'tinyint(1)'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

    public function categoryContentFields(){
        $fields = [
            'category_code_ref' => [
                'base_name' => 'rubnumref',
                'description' => 'Ссылка на код категории в таблице shoprubriken',
                'type' => 'varchar(200)'
            ],
            'language' => [
                'base_name' => 'rubsprache',
                'description' => 'Код языка для мультиязычного контента категории',
                'type' => 'char(3)'
            ],
            'category_name' => [
                'base_name' => 'rubnam',
                'description' => 'Название категории на указанном языке',
                'type' => 'varchar(250)'
            ],
            'description' => [
                'base_name' => 'rubtext',
                'description' => 'Полное описание категории на указанном языке',
                'type' => 'text'
            ],
            'meta_keywords' => [
                'base_name' => 'keywords',
                'description' => 'Мета-ключевые слова для SEO оптимизации',
                'type' => 'text'
            ],
            'short_description' => [
                'base_name' => 'rubtext_kurz',
                'description' => 'Краткое описание категории на указанном языке',
                'type' => 'text'
            ],
            'url_key' => [
                'base_name' => 'urlkey',
                'description' => 'URL ключ для формирования ЧПУ категории на указанном языке',
                'type' => 'varchar(255)'
            ],
            'slider_id' => [
                'base_name' => 'slider_id',
                'description' => 'Идентификатор слайдера, связанного с категорией',
                'type' => 'varchar(255)'
            ],
            'category_id' => [
                'base_name' => 'rubid',
                'description' => 'Уникальный идентификатор категории, соответствующий rubid в таблице shoprubriken',
                'type' => 'int(10) unsigned'
            ]
        ];

        return response()->json([
            'data' => $fields
        ], 200);
    }

}
