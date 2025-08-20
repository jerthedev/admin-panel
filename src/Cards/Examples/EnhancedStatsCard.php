<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Cards\Examples;

use JTD\AdminPanel\Cards\Card;

/**
 * Enhanced Stats Card Example.
 *
 * Demonstrates the enhanced withMeta() functionality including fluent color/theme methods,
 * advanced theming options, label configuration, and custom styling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class EnhancedStatsCard extends Card
{
    /**
     * Create a new enhanced stats card instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setupEnhancedCard();
    }

    /**
     * Setup the enhanced card with all new features.
     */
    protected function setupEnhancedCard(): void
    {
        $this
            ->withTitle('Enhanced Statistics')
            ->withSubtitle('Demonstrating advanced card options')
            ->withDescription('This card showcases all the enhanced withMeta() functionality')
            ->withIcon('ChartBarIcon')
            ->withColor('primary')
            ->withVariant('elevated')
            ->refreshable()
            ->refreshEvery(30)
            ->withLabels([
                'status' => 'Active',
                'priority' => 'High',
                'category' => 'Analytics',
            ])
            ->withClasses(['enhanced-stats-card', 'featured'])
            ->withStyles([
                'borderRadius' => '12px',
                'boxShadow' => '0 8px 25px rgba(0, 0, 0, 0.1)',
            ]);
    }

    /**
     * Create a success variant of the card.
     */
    public static function success(): static
    {
        return static::make()
            ->withColor('success')
            ->withTitle('Success Metrics')
            ->withIcon('CheckCircleIcon')
            ->withBackgroundColor('#f0fdf4')
            ->withBorderColor('#22c55e');
    }

    /**
     * Create a warning variant of the card.
     */
    public static function warning(): static
    {
        return static::make()
            ->withColor('warning')
            ->withTitle('Warning Alerts')
            ->withIcon('ExclamationTriangleIcon')
            ->withVariant('bordered')
            ->withTextColor('#92400e');
    }

    /**
     * Create a danger variant of the card.
     */
    public static function danger(): static
    {
        return static::make()
            ->withColor('danger')
            ->withTitle('Critical Issues')
            ->withIcon('XCircleIcon')
            ->withVariant('elevated')
            ->withStyles([
                'background' => 'linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%)',
                'borderColor' => '#ef4444',
            ]);
    }

    /**
     * Create a gradient variant of the card.
     */
    public static function gradient(): static
    {
        return static::make()
            ->withTitle('Gradient Card')
            ->withSubtitle('Beautiful gradient styling')
            ->withVariant('gradient')
            ->withIcon('SparklesIcon')
            ->withStyles([
                'background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'color' => '#ffffff',
                'borderColor' => 'transparent',
            ]);
    }

    /**
     * Create a custom styled card.
     */
    public static function custom(): static
    {
        return static::make()
            ->withTitle('Custom Styled Card')
            ->withDescription('Fully customized appearance')
            ->withIcon('CogIcon')
            ->withSize('lg')
            ->withClasses(['custom-card', 'animated'])
            ->withStyles([
                'backgroundColor' => '#1f2937',
                'color' => '#f9fafb',
                'borderColor' => '#374151',
                'borderWidth' => '2px',
                'borderRadius' => '16px',
                'padding' => '24px',
                'boxShadow' => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
            ])
            ->withLabels([
                'theme' => 'Dark',
                'style' => 'Custom',
                'animation' => 'Enabled',
            ]);
    }

    /**
     * Get the card's data.
     */
    protected function getData(): array
    {
        return [
            'totalUsers' => 15420,
            'activeUsers' => 12350,
            'newUsersToday' => 89,
            'conversionRate' => 3.2,
            'revenue' => 45230.50,
            'lastUpdated' => now()->toISOString(),
        ];
    }

    /**
     * Get additional meta information to merge with the card payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'data' => $this->getData(),
            'timestamp' => now()->toISOString(),
            'version' => '2.0',
            'features' => [
                'enhanced_styling',
                'fluent_interface',
                'validation',
                'theming',
            ],
        ]);
    }
}
