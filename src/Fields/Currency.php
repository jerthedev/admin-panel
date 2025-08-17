<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;
use NumberFormatter;

/**
 * Currency Field
 *
 * A currency input field with locale-based formatting, currency symbols,
 * and decimal precision support.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Currency extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'CurrencyField';

    /**
     * The locale for currency formatting.
     */
    public string $locale = 'en_US';

    /**
     * The currency code (ISO 4217).
     */
    public string $currency = 'USD';

    /**
     * The minimum value allowed.
     */
    public ?float $minValue = null;

    /**
     * The maximum value allowed.
     */
    public ?float $maxValue = null;

    /**
     * The step value for input.
     */
    public float $step = 0.01;

    /**
     * Whether the underlying stored value is in minor units (e.g., cents).
     * When enabled, values will be divided by 100 for display and
     * multiplied by 100 for storage to maintain Nova compatibility.
     */
    public bool $asMinorUnits = false;

    /**
     * Treat value as stored in minor units (e.g., cents).
     */
    public function asMinorUnits(bool $asMinorUnits = true): static
    {
        $this->asMinorUnits = $asMinorUnits;

        // Nova sets step to 1 when using minor units
        if ($asMinorUnits) {
            $this->step = 1;
        }

        return $this;
    }

    /**
     * Treat value as stored in major units (default - decimals).
     */
    public function asMajorUnits(): static
    {
        $this->asMinorUnits = false;
        $this->step = 0.01; // Reset to default step

        return $this;
    }

    /**
     * Set the locale for currency formatting.
     */
    public function locale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set the currency code.
     */
    public function currency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }



    /**
     * Set the minimum value allowed.
     */
    public function min(float $min): static
    {
        $this->minValue = $min;

        return $this;
    }

    /**
     * Set the maximum value allowed.
     */
    public function max(float $max): static
    {
        $this->maxValue = $max;

        return $this;
    }



    /**
     * Set the step value for input.
     */
    public function step(float $step): static
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // If values are stored in minor units, convert to major units for display / client
        if ($this->asMinorUnits && is_numeric($this->value)) {
            $this->value = ((float) $this->value) / 100;
        }

        // Only format for display if there's a display callback that expects formatting
        // The raw value should be preserved for form inputs and API responses
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        } elseif ($request->exists($this->attribute)) {
            $value = $request->input($this->attribute);

            if ($value !== null && $value !== '') {
                // Remove currency symbols and formatting for storage
                $cleanValue = $this->cleanCurrencyValue($value);

                if ($this->asMinorUnits && $cleanValue !== null) {
                    // Store as integer/float representing minor units (cents)
                    $cleanValue = (float) round($cleanValue * 100);
                }

                $model->{$this->attribute} = $cleanValue;
            } else {
                $model->{$this->attribute} = null;
            }
        }
    }

    /**
     * Format a numeric value as currency.
     */
    protected function formatCurrency(float $value): string
    {
        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $this->precision);

            return $formatter->formatCurrency($value, $this->currency);
        }

        // Fallback formatting if NumberFormatter is not available
        $symbol = $this->symbol ?? $this->getCurrencySymbol();

        return $symbol . number_format($value, $this->precision);
    }

    /**
     * Clean currency value for storage.
     */
    protected function cleanCurrencyValue(string $value): ?float
    {
        // Remove currency symbols, spaces, and non-numeric characters except decimal point and minus
        $cleaned = preg_replace('/[^\d\.\-]/', '', $value);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Get the currency symbol for the current currency using Intl.
     * Falls back to currency code if Intl is not available.
     */
    protected function getCurrencySymbol(): string
    {
        if (class_exists('NumberFormatter')) {
            $formatter = new \NumberFormatter($this->locale, \NumberFormatter::CURRENCY);
            $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
            if ($symbol && $symbol !== $this->currency) {
                return $symbol;
            }
        }

        // Fallback to common currency symbols
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'CHF' => 'CHF',
            'CNY' => '¥',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
        ];

        return $symbols[$this->currency] ?? $this->currency;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'locale' => $this->locale,
            'currency' => $this->currency,
            'symbol' => $this->getCurrencySymbol(),
            'minValue' => $this->minValue,
            'maxValue' => $this->maxValue,
            'step' => $this->step,
            'asMinorUnits' => $this->asMinorUnits,
        ]);
    }
}
