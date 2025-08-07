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
     * The currency symbol.
     */
    public ?string $symbol = null;

    /**
     * The number of decimal places.
     */
    public int $precision = 2;

    /**
     * The minimum value allowed.
     */
    public ?float $minValue = null;

    /**
     * The maximum value allowed.
     */
    public ?float $maxValue = null;

    /**
     * The display format (symbol, code, name).
     */
    public string $displayFormat = 'symbol';

    /**
     * The step value for input.
     */
    public float $step = 0.01;

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
     * Set the currency symbol.
     */
    public function symbol(string $symbol): static
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * Set the number of decimal places.
     */
    public function precision(int $precision): static
    {
        $this->precision = $precision;

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
     * Set the display format.
     */
    public function displayFormat(string $format): static
    {
        $this->displayFormat = $format;

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
     * Get the currency symbol for the current currency.
     */
    protected function getCurrencySymbol(): string
    {
        if ($this->symbol) {
            return $this->symbol;
        }

        // Common currency symbols
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
            'symbol' => $this->symbol ?? $this->getCurrencySymbol(),
            'precision' => $this->precision,
            'minValue' => $this->minValue,
            'maxValue' => $this->maxValue,
            'displayFormat' => $this->displayFormat,
            'step' => $this->step,
        ]);
    }
}
