<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Connector\FlatTranslator\Header;

use Akeneo\Pim\Enrichment\Component\Product\Connector\ArrayConverter\FlatToStandard\AttributeColumnInfoExtractor;
use Akeneo\Pim\Enrichment\Component\Product\Connector\ArrayConverter\FlatToStandard\AttributeColumnsResolver;
use Akeneo\Pim\Structure\Component\Query\PublicApi\Attribute\GetAttributeTranslations;
use Akeneo\Pim\Structure\Component\Query\PublicApi\Channel\GetChannelTranslations;
use Akeneo\Tool\Component\Localization\CurrencyTranslator;
use Akeneo\Tool\Component\Localization\LabelTranslatorInterface;
use Akeneo\Tool\Component\Localization\LanguageTranslator;

class AttributeTranslator implements FlatHeaderTranslatorInterface
{
    /** @var LabelTranslatorInterface */
    private $labelTranslator;

    /** @var AttributeColumnsResolver */
    private $attributeColumnsResolver;

    /** @var AttributeColumnInfoExtractor */
    private $attributeColumnInfoExtractor;

    /** @var GetChannelTranslations */
    private $getChannelTranslations;

    /** @var LanguageTranslator */
    private $languageTranslator;

    /** @var CurrencyTranslator */
    private $currencyTranslator;

    private $channelTranslationCache = null;

    private $attributeTranslations = [];

    /** @var GetAttributeTranslations */
    private $getAttributeTranslations;

    public function __construct(
        LabelTranslatorInterface $labelTranslator,
        AttributeColumnsResolver $attributeColumnsResolver,
        AttributeColumnInfoExtractor $attributeColumnInfoExtractor,
        GetChannelTranslations $getChannelTranslations,
        LanguageTranslator $languageTranslator,
        CurrencyTranslator $currencyTranslator,
        GetAttributeTranslations $getAttributeTranslations
    ) {
        $this->labelTranslator = $labelTranslator;
        $this->attributeColumnsResolver = $attributeColumnsResolver;
        $this->attributeColumnInfoExtractor = $attributeColumnInfoExtractor;
        $this->getChannelTranslations = $getChannelTranslations;
        $this->languageTranslator = $languageTranslator;
        $this->currencyTranslator = $currencyTranslator;
        $this->getAttributeTranslations = $getAttributeTranslations;
    }

    public function supports(string $columnName): bool
    {
        $attributeColumns = $this->attributeColumnsResolver->resolveAttributeColumns();

        return in_array($columnName, $attributeColumns);
    }

    public function warmup(array $columnNames, string $locale): void
    {
        $attributeCodes = $this->extractAttributeCodes($columnNames);
        $this->attributeTranslations = $this->getAttributeTranslations->byAttributeCodesAndLocale($attributeCodes, $locale);
    }

    public function translate(string $columnName, string $locale): string
    {
        $columnInformations = $this->attributeColumnInfoExtractor->extractColumnInfo($columnName);
        $attribute = $columnInformations['attribute'];
        $attributeCode = $attribute->getCode();

        $columnLabelized = isset($this->attributeTranslations[$attributeCode]) ?
            $this->attributeTranslations[$attributeCode] : sprintf('[%s]', $attributeCode);

        $extraInformation = [];
        if ($attribute->isLocalizable()) {
            $localeCode = $columnInformations['locale_code'];
            $extraInformation[] = $this->languageTranslator->translate(
                $localeCode,
                $locale,
                sprintf('[%s]', $localeCode)
            );
        }

        if ($attribute->isScopable()) {
            $channelCode = $columnInformations['scope_code'];
            $channelTranslations = $this->getChannelTranslations($locale);

            $extraInformation[] = $channelTranslations[$channelCode] ?? sprintf('[%s]', $channelCode);
        }

        if (!empty($extraInformation)) {
            $columnLabelized = $columnLabelized . " (".implode(', ', $extraInformation) . ")";
        }

        if ($attribute->getType() === 'pim_catalog_price_collection') {
            $currencyCode  = $columnInformations['price_currency'];
            $currencyLabelized = $this->currencyTranslator->translate(
                $currencyCode,
                $locale,
                sprintf('[%s]', $currencyCode)
            );

            $columnLabelized = sprintf('%s (%s)', $columnLabelized, $currencyLabelized);
        } elseif ($attribute->getType() === 'pim_catalog_metric' && strpos($columnName, '-unit') !== false) {
            $metricLabelized = $this->labelTranslator->translate('pim_common.unit', $locale, '[unit]');

            $columnLabelized = sprintf('%s (%s)', $columnLabelized, $metricLabelized);
        }

        return $columnLabelized;
    }

    private function getChannelTranslations($locale)
    {
        if ($this->channelTranslationCache === null) {
            $this->channelTranslationCache = $this->getChannelTranslations->byLocale($locale);
        }

        return $this->channelTranslationCache;
    }

    private function extractAttributeCodes(array $columnNames): array
    {
        $attributeCodes = [];
        foreach ($columnNames as $columnName) {
            if ($this->isAttributeColumn($columnName)) {
                $columnInformations = $this->attributeColumnInfoExtractor->extractColumnInfo($columnName);

                $attributeCodes[] = $columnInformations['attribute']->getCode();
            }
        }

        return array_unique($attributeCodes);
    }

    private function isAttributeColumn(string $column): bool
    {
        $attributeColumns = $this->attributeColumnsResolver->resolveAttributeColumns();

        return in_array($column, $attributeColumns);
    }
}
