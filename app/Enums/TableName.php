<?php

namespace App\Enums;

enum TableName: string
{
    case Product = 'shopartikel';
    case ProductContent = 'shopartikelcontent';
    case AttributeContent = 'shopattribute_content';
    case Attribute = 'shopattribute';
    case Category = 'shoprubriken';
    case CategoryContent = 'shoprubrikencontent';
    case Media = 'shopmedia';
    case ProductAttributeAssignment = 'shopattribute_zuordnung';
    case ProductAttribute = 'shopartikel_attribute';
    case ProductBestSeller = 'shopbestseller';
    case ProductCategories = 'shoprubrikartikel';
    case ProductCustomerGroup = 'shopartikelkdgrp';
    case ProductPrice = 'shopartikelpreise';
    case ProductProperty = 'shopartikelproperties';
    case ProductRecomendation = 'shopartikelempfehlung';
    case Seo = 'shopseo';
    case ProductExtendedInformation = "shopartikellieferanteninfo";
}
