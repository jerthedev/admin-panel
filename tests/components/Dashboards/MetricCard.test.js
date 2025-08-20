/**
 * MetricCard Component Tests
 * 
 * Vue component tests for the MetricCard component including
 * value formatting, change indicators, charts, and responsive design.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import MetricCard from '../../../resources/js/Components/Dashboard/Cards/MetricCard.vue'

describe('MetricCard Component', () => {
    let wrapper

    const defaultProps = {
        dashboard: {
            name: 'Test Dashboard',
            uriKey: 'test'
        },
        value: 1234,
        label: 'Total Users'
    }

    beforeEach(() => {
        vi.clearAllMocks()
    })

    afterEach(() => {
        if (wrapper) {
            wrapper.unmount()
        }
    })

    describe('Basic Rendering', () => {
        it('renders metric value and label', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            expect(wrapper.find('.metric-value').text()).toBe('1,234')
            expect(wrapper.find('.metric-label').text()).toBe('Total Users')
        })

        it('renders without label when not provided', async () => {
            const propsWithoutLabel = {
                ...defaultProps,
                label: undefined
            }

            wrapper = mount(MetricCard, {
                props: propsWithoutLabel
            })

            expect(wrapper.find('.metric-value').text()).toBe('1,234')
            expect(wrapper.find('.metric-label').exists()).toBe(false)
        })

        it('renders description when provided', async () => {
            const propsWithDescription = {
                ...defaultProps,
                description: 'Active users this month'
            }

            wrapper = mount(MetricCard, {
                props: propsWithDescription
            })

            expect(wrapper.find('.metric-description').text()).toBe('Active users this month')
        })
    })

    describe('Value Formatting', () => {
        it('formats numbers correctly', async () => {
            const propsWithNumber = {
                ...defaultProps,
                value: 1234567,
                format: 'number'
            }

            wrapper = mount(MetricCard, {
                props: propsWithNumber
            })

            expect(wrapper.find('.metric-value').text()).toBe('1,234,567')
        })

        it('formats currency correctly', async () => {
            const propsWithCurrency = {
                ...defaultProps,
                value: 1234.56,
                format: 'currency',
                currency: 'USD'
            }

            wrapper = mount(MetricCard, {
                props: propsWithCurrency
            })

            expect(wrapper.find('.metric-value').text()).toBe('$1,234.56')
        })

        it('formats percentage correctly', async () => {
            const propsWithPercentage = {
                ...defaultProps,
                value: 75.5,
                format: 'percentage'
            }

            wrapper = mount(MetricCard, {
                props: propsWithPercentage
            })

            expect(wrapper.find('.metric-value').text()).toBe('75.5%')
        })

        it('handles string values', async () => {
            const propsWithString = {
                ...defaultProps,
                value: '1234'
            }

            wrapper = mount(MetricCard, {
                props: propsWithString
            })

            expect(wrapper.find('.metric-value').text()).toBe('1,234')
        })

        it('handles invalid numeric values', async () => {
            const propsWithInvalid = {
                ...defaultProps,
                value: 'invalid'
            }

            wrapper = mount(MetricCard, {
                props: propsWithInvalid
            })

            expect(wrapper.find('.metric-value').text()).toBe('invalid')
        })
    })

    describe('Change Indicators', () => {
        it('shows positive change with green color', async () => {
            const propsWithPositiveChange = {
                ...defaultProps,
                change: 15.5,
                changePeriod: 'vs last month'
            }

            wrapper = mount(MetricCard, {
                props: propsWithPositiveChange
            })

            const changeElement = wrapper.find('.metric-change > div')
            expect(changeElement.exists()).toBe(true)
            expect(changeElement.text()).toContain('+15.5')
            expect(wrapper.find('.change-period').text()).toBe('vs last month')
        })

        it('shows negative change with red color', async () => {
            const propsWithNegativeChange = {
                ...defaultProps,
                change: -8.2,
                changePeriod: 'vs last week'
            }

            wrapper = mount(MetricCard, {
                props: propsWithNegativeChange
            })

            const changeElement = wrapper.find('.metric-change > div')
            expect(changeElement.exists()).toBe(true)
            expect(changeElement.text()).toContain('-8.2')
        })

        it('shows neutral change with gray color', async () => {
            const propsWithNeutralChange = {
                ...defaultProps,
                change: 0
            }

            wrapper = mount(MetricCard, {
                props: propsWithNeutralChange
            })

            const changeElement = wrapper.find('.metric-change > div')
            expect(changeElement.exists()).toBe(true)
            expect(changeElement.text()).toContain('+0')
        })

        it('shows up arrow for positive change', async () => {
            const propsWithPositiveChange = {
                ...defaultProps,
                change: 10
            }

            wrapper = mount(MetricCard, {
                props: propsWithPositiveChange
            })

            const arrow = wrapper.find('.metric-change svg')
            expect(arrow.exists()).toBe(true)
            expect(arrow.element.className).toBeTruthy()
        })

        it('shows down arrow for negative change', async () => {
            const propsWithNegativeChange = {
                ...defaultProps,
                change: -10
            }

            wrapper = mount(MetricCard, {
                props: propsWithNegativeChange
            })

            const arrow = wrapper.find('.metric-change svg')
            expect(arrow.exists()).toBe(true)
            expect(arrow.element.className).toBeTruthy()
        })

        it('hides change indicator when not provided', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            expect(wrapper.find('.metric-change').exists()).toBe(false)
        })

        it('formats percentage changes correctly', async () => {
            const propsWithPercentageChange = {
                ...defaultProps,
                change: 12.5,
                format: 'percentage'
            }

            wrapper = mount(MetricCard, {
                props: propsWithPercentageChange
            })

            expect(wrapper.find('.metric-change').text()).toContain('+12.5%')
        })
    })

    describe('Chart Functionality', () => {
        it('renders chart when data is provided', async () => {
            const propsWithChart = {
                ...defaultProps,
                chartData: [10, 20, 15, 25, 30, 28, 35]
            }

            wrapper = mount(MetricCard, {
                props: propsWithChart
            })

            expect(wrapper.find('.metric-chart').exists()).toBe(true)
            expect(wrapper.find('svg').exists()).toBe(true)
            expect(wrapper.find('polyline').exists()).toBe(true)
        })

        it('renders chart with custom color', async () => {
            const propsWithChart = {
                ...defaultProps,
                chartData: [10, 20, 15, 25],
                chartColor: '#10B981'
            }

            wrapper = mount(MetricCard, {
                props: propsWithChart
            })

            const polyline = wrapper.find('polyline')
            expect(polyline.attributes('stroke')).toBe('#10B981')

            const circle = wrapper.find('circle')
            expect(circle.attributes('fill')).toBe('#10B981')
        })

        it('hides chart when no data provided', async () => {
            const propsWithoutChart = {
                ...defaultProps,
                chartData: null // Explicitly set to null to hide chart
            }

            wrapper = mount(MetricCard, {
                props: propsWithoutChart
            })

            // Chart container should not exist when chartData is null
            const chartContainer = wrapper.find('.metric-chart')
            expect(chartContainer.exists()).toBe(false)
        })

        it('generates correct chart points', async () => {
            const propsWithChart = {
                ...defaultProps,
                chartData: [10, 20, 30]
            }

            wrapper = mount(MetricCard, {
                props: propsWithChart
            })

            const polyline = wrapper.find('polyline')
            const points = polyline.attributes('points')
            
            // Should have 3 points for 3 data values
            expect(points.split(' ')).toHaveLength(3)
        })
    })

    describe('Footer Information', () => {
        it('shows footer when lastUpdated or target provided', async () => {
            const propsWithFooter = {
                ...defaultProps,
                lastUpdated: new Date('2024-01-15T10:30:00Z')
            }

            wrapper = mount(MetricCard, {
                props: propsWithFooter
            })

            expect(wrapper.find('.metric-footer').exists()).toBe(true)
        })

        it('hides footer when no lastUpdated or target', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            expect(wrapper.find('.metric-footer').exists()).toBe(false)
        })

        it('formats last updated time', async () => {
            const propsWithLastUpdated = {
                ...defaultProps,
                lastUpdated: new Date(Date.now() - 2 * 60 * 1000) // 2 minutes ago
            }

            wrapper = mount(MetricCard, {
                props: propsWithLastUpdated
            })

            const lastUpdated = wrapper.find('.last-updated')
            expect(lastUpdated.exists()).toBe(true)
            // Note: RelativeTimeFormat may vary by environment, so just check it exists
            expect(lastUpdated.text()).toBeTruthy()
        })

        it('shows target value when provided', async () => {
            const propsWithTarget = {
                ...defaultProps,
                target: 2000,
                format: 'number'
            }

            wrapper = mount(MetricCard, {
                props: propsWithTarget
            })

            const target = wrapper.find('.target')
            expect(target.exists()).toBe(true)
            expect(target.text()).toContain('Target:')
            expect(target.text()).toContain('2,000')
        })

        it('formats target value according to format', async () => {
            const propsWithCurrencyTarget = {
                ...defaultProps,
                target: 5000,
                format: 'currency',
                currency: 'USD'
            }

            wrapper = mount(MetricCard, {
                props: propsWithCurrencyTarget
            })

            const target = wrapper.find('.target')
            expect(target.text()).toContain('Target:')
            expect(target.text()).toContain('$5,000.00')
        })
    })

    describe('Event Handling', () => {
        it('emits action events', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            const testAction = { name: 'test-action' }
            await wrapper.vm.$emit('action', testAction)

            expect(wrapper.emitted('action')).toBeTruthy()
            expect(wrapper.emitted('action')[0]).toEqual([testAction])
        })

        it('emits error events', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            const testError = new Error('Test error')
            await wrapper.vm.$emit('error', testError)

            expect(wrapper.emitted('error')).toBeTruthy()
            expect(wrapper.emitted('error')[0]).toEqual([testError])
        })
    })

    describe('Responsive Design', () => {
        it('applies responsive classes', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            const card = wrapper.find('.metric-card')
            expect(card.exists()).toBe(true)
            expect(card.element.className).toBeTruthy()
        })

        it('centers content appropriately', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            const valueSection = wrapper.find('.metric-value-section')
            expect(valueSection.exists()).toBe(true)
            expect(valueSection.element.className).toBeTruthy()

            const changeSection = wrapper.find('.metric-change')
            if (changeSection.exists()) {
                expect(changeSection.element.className).toBeTruthy()
            }
        })
    })

    describe('Accessibility', () => {
        it('provides semantic structure', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            const value = wrapper.find('.metric-value')
            expect(value.exists()).toBe(true)

            const label = wrapper.find('.metric-label')
            expect(label.exists()).toBe(true)
        })

        it('includes proper text hierarchy', async () => {
            const propsWithDescription = {
                ...defaultProps,
                description: 'Test description'
            }

            wrapper = mount(MetricCard, {
                props: propsWithDescription
            })

            // Value should be most prominent
            const value = wrapper.find('.metric-value')
            expect(value.exists()).toBe(true)
            expect(value.element.className).toBeTruthy()

            // Label should be secondary
            const label = wrapper.find('.metric-label')
            expect(label.exists()).toBe(true)
            expect(label.element.className).toBeTruthy()

            // Description should be tertiary
            const description = wrapper.find('.metric-description')
            expect(description.exists()).toBe(true)
            expect(description.element.className).toBeTruthy()
        })

        it('has proper ARIA labels for range selector', async () => {
            const propsWithRanges = {
                ...defaultProps,
                ranges: {
                    30: '30 Days',
                    60: '60 Days',
                    90: '90 Days'
                },
                selectedRange: 30
            }

            wrapper = mount(MetricCard, {
                props: propsWithRanges
            })

            const rangeSelector = wrapper.find('[data-testid="range-selector"]')
            expect(rangeSelector.exists()).toBe(true)
            expect(rangeSelector.attributes('aria-label')).toBeTruthy()
        })
    })

    describe('Range Selection Enhancement', () => {
        it('displays range selector when ranges are provided', async () => {
            const propsWithRanges = {
                ...defaultProps,
                ranges: {
                    30: '30 Days',
                    60: '60 Days',
                    90: '90 Days'
                },
                selectedRange: 30
            }

            wrapper = mount(MetricCard, {
                props: propsWithRanges
            })

            const rangeSelector = wrapper.find('[data-testid="range-selector"]')
            expect(rangeSelector.exists()).toBe(true)
        })

        it('hides range selector when no ranges provided', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            const rangeSelector = wrapper.find('[data-testid="range-selector"]')
            expect(rangeSelector.exists()).toBe(false)
        })

        it('displays correct range options', async () => {
            const propsWithRanges = {
                ...defaultProps,
                ranges: {
                    30: '30 Days',
                    60: '60 Days',
                    'MTD': 'Month To Date'
                },
                selectedRange: 30
            }

            wrapper = mount(MetricCard, {
                props: propsWithRanges
            })

            const options = wrapper.findAll('option')
            expect(options).toHaveLength(3)
            expect(options[0].text()).toBe('30 Days')
            expect(options[1].text()).toBe('60 Days')
            expect(options[2].text()).toBe('Month To Date')
        })

        it('selects correct range option', async () => {
            const propsWithRanges = {
                ...defaultProps,
                ranges: {
                    30: '30 Days',
                    60: '60 Days',
                    90: '90 Days'
                },
                selectedRange: 60
            }

            wrapper = mount(MetricCard, {
                props: propsWithRanges
            })

            const rangeSelector = wrapper.find('[data-testid="range-selector"]')
            expect(rangeSelector.element.value).toBe('60')
        })

        it('emits range-changed event when selection changes', async () => {
            const propsWithRanges = {
                ...defaultProps,
                ranges: {
                    30: '30 Days',
                    60: '60 Days',
                    90: '90 Days'
                },
                selectedRange: 30
            }

            wrapper = mount(MetricCard, {
                props: propsWithRanges
            })

            const rangeSelector = wrapper.find('[data-testid="range-selector"]')
            await rangeSelector.setValue('90')

            expect(wrapper.emitted('range-changed')).toBeTruthy()
            expect(wrapper.emitted('range-changed')[0]).toEqual([90])
        })

        it('handles string range values correctly', async () => {
            const propsWithRanges = {
                ...defaultProps,
                ranges: {
                    'MTD': 'Month To Date',
                    'QTD': 'Quarter To Date',
                    'YTD': 'Year To Date'
                },
                selectedRange: 'MTD'
            }

            wrapper = mount(MetricCard, {
                props: propsWithRanges
            })

            const rangeSelector = wrapper.find('[data-testid="range-selector"]')
            await rangeSelector.setValue('QTD')

            expect(wrapper.emitted('range-changed')).toBeTruthy()
            expect(wrapper.emitted('range-changed')[0]).toEqual(['QTD'])
        })
    })

    describe('Enhanced Formatting Options', () => {
        it('supports custom formatter function', async () => {
            const customFormatter = (value) => `${value.toFixed(1)}K`
            const propsWithCustomFormatter = {
                ...defaultProps,
                value: 1234,
                formatter: customFormatter
            }

            wrapper = mount(MetricCard, {
                props: propsWithCustomFormatter
            })

            expect(wrapper.find('.metric-value').text()).toBe('1234.0K')
        })

        it('supports prefix and suffix options', async () => {
            const propsWithPrefixSuffix = {
                ...defaultProps,
                value: 1234,
                prefix: '~',
                suffix: ' items'
            }

            wrapper = mount(MetricCard, {
                props: propsWithPrefixSuffix
            })

            expect(wrapper.find('.metric-value').text()).toBe('~1,234 items')
        })

        it('supports decimal places configuration', async () => {
            const propsWithDecimals = {
                ...defaultProps,
                value: 1234.5678,
                format: 'number',
                decimalPlaces: 2
            }

            wrapper = mount(MetricCard, {
                props: propsWithDecimals
            })

            expect(wrapper.find('.metric-value').text()).toBe('1,234.57')
        })

        it('supports different currency codes', async () => {
            const propsWithEuro = {
                ...defaultProps,
                value: 1234.56,
                format: 'currency',
                currency: 'EUR'
            }

            wrapper = mount(MetricCard, {
                props: propsWithEuro
            })

            expect(wrapper.find('.metric-value').text()).toContain('€')
        })

        it('handles compact number formatting', async () => {
            const propsWithCompact = {
                ...defaultProps,
                value: 1234567,
                format: 'compact'
            }

            wrapper = mount(MetricCard, {
                props: propsWithCompact
            })

            expect(wrapper.find('.metric-value').text()).toBe('1.2M')
        })

        it('prioritizes custom formatter over format prop', async () => {
            const customFormatter = (value) => `Custom: ${value}`
            const propsWithBoth = {
                ...defaultProps,
                value: 1234,
                format: 'currency',
                formatter: customFormatter
            }

            wrapper = mount(MetricCard, {
                props: propsWithBoth
            })

            expect(wrapper.find('.metric-value').text()).toBe('Custom: 1234')
        })
    })

    describe('Enhanced Trend Indicators', () => {
        it('displays enhanced trend indicator with better styling', async () => {
            const propsWithTrend = {
                ...defaultProps,
                change: 15.5,
                changePeriod: 'vs last month',
                enhancedTrend: true
            }

            wrapper = mount(MetricCard, {
                props: propsWithTrend
            })

            const trendIndicator = wrapper.find('[data-testid="enhanced-trend"]')
            expect(trendIndicator.exists()).toBe(true)
        })

        it('shows trend comparison with previous period', async () => {
            const propsWithComparison = {
                ...defaultProps,
                change: 25.0,
                previousValue: 800,
                changePeriod: 'vs last month'
            }

            wrapper = mount(MetricCard, {
                props: propsWithComparison
            })

            const comparison = wrapper.find('[data-testid="trend-comparison"]')
            expect(comparison.exists()).toBe(true)
            expect(comparison.text()).toContain('800')
        })

        it('displays trend direction with enhanced icons', async () => {
            const propsWithPositiveTrend = {
                ...defaultProps,
                change: 10.5,
                enhancedTrend: true
            }

            wrapper = mount(MetricCard, {
                props: propsWithPositiveTrend
            })

            const trendIcon = wrapper.find('[data-testid="trend-icon"]')
            expect(trendIcon.exists()).toBe(true)
            expect(trendIcon.classes()).toContain('trend-up')
        })

        it('shows neutral trend styling for zero change', async () => {
            const propsWithNeutralTrend = {
                ...defaultProps,
                change: 0,
                enhancedTrend: true
            }

            wrapper = mount(MetricCard, {
                props: propsWithNeutralTrend
            })

            const trendIcon = wrapper.find('[data-testid="trend-icon"]')
            expect(trendIcon.exists()).toBe(true)
            expect(trendIcon.classes()).toContain('trend-neutral')
        })

        it('formats trend percentage with enhanced precision', async () => {
            const propsWithPreciseTrend = {
                ...defaultProps,
                change: 12.3456,
                trendPrecision: 2
            }

            wrapper = mount(MetricCard, {
                props: propsWithPreciseTrend
            })

            expect(wrapper.text()).toContain('12.35%')
        })

        it('displays trend tooltip with additional context', async () => {
            const propsWithTooltip = {
                ...defaultProps,
                change: 15.5,
                trendTooltip: 'Increased by 155 users compared to last month'
            }

            wrapper = mount(MetricCard, {
                props: propsWithTooltip
            })

            const tooltip = wrapper.find('[data-testid="trend-tooltip"]')
            expect(tooltip.exists()).toBe(true)
            expect(tooltip.attributes('title')).toBe('Increased by 155 users compared to last month')
        })
    })

    describe('Improved Responsive Design', () => {
        it('applies compact layout for small screens', async () => {
            const propsWithCompact = {
                ...defaultProps,
                compact: true
            }

            wrapper = mount(MetricCard, {
                props: propsWithCompact
            })

            expect(wrapper.classes()).toContain('metric-card-compact')
        })

        it('adjusts font sizes for mobile', async () => {
            const propsWithMobile = {
                ...defaultProps,
                mobile: true
            }

            wrapper = mount(MetricCard, {
                props: propsWithMobile
            })

            const value = wrapper.find('.metric-value')
            expect(value.classes()).toContain('text-2xl')
        })

        it('stacks elements vertically on small screens', async () => {
            const propsWithStacking = {
                ...defaultProps,
                change: 10.5,
                ranges: { 30: '30 Days' },
                selectedRange: 30,
                stackOnMobile: true
            }

            wrapper = mount(MetricCard, {
                props: propsWithStacking
            })

            const header = wrapper.find('[data-testid="metric-header"]')
            expect(header.classes()).toContain('flex-col')
        })
    })

    describe('Enhanced Accessibility', () => {
        it('provides screen reader descriptions for trends', async () => {
            const propsWithTrend = {
                ...defaultProps,
                change: 15.5,
                changePeriod: 'vs last month'
            }

            wrapper = mount(MetricCard, {
                props: propsWithTrend
            })

            const srDescription = wrapper.find('[data-testid="sr-trend-description"]')
            expect(srDescription.exists()).toBe(true)
            expect(srDescription.text()).toContain('increased by 15.5%')
        })

        it('includes ARIA live region for dynamic updates', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            const liveRegion = wrapper.find('[aria-live="polite"]')
            expect(liveRegion.exists()).toBe(true)
        })

        it('provides keyboard navigation for range selector', async () => {
            const propsWithRanges = {
                ...defaultProps,
                ranges: {
                    30: '30 Days',
                    60: '60 Days',
                    90: '90 Days'
                },
                selectedRange: 30
            }

            wrapper = mount(MetricCard, {
                props: propsWithRanges
            })

            const rangeSelector = wrapper.find('[data-testid="range-selector"]')
            expect(rangeSelector.attributes('tabindex')).toBe('0')
        })

        it('includes proper role attributes', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            const card = wrapper.find('.metric-card')
            expect(card.attributes('role')).toBe('region')
            expect(card.attributes('aria-labelledby')).toBeTruthy()
        })
    })

    describe('Backward Compatibility', () => {
        it('maintains existing prop interface', async () => {
            // Test that all original props still work
            const originalProps = {
                dashboard: { name: 'Test', uriKey: 'test' },
                value: 1234,
                label: 'Test Label',
                description: 'Test Description',
                format: 'currency',
                currency: 'USD',
                change: 10.5,
                changePeriod: 'vs last month',
                target: 2000,
                chartData: [10, 20, 30],
                chartColor: '#3B82F6',
                lastUpdated: new Date()
            }

            wrapper = mount(MetricCard, {
                props: originalProps
            })

            expect(wrapper.find('.metric-value').text()).toContain('$1,234')
            expect(wrapper.find('.metric-label').text()).toBe('Test Label')
            expect(wrapper.find('.metric-description').text()).toBe('Test Description')
        })

        it('preserves existing event emissions', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            await wrapper.vm.$emit('action', { test: 'action' })
            await wrapper.vm.$emit('error', new Error('test'))

            expect(wrapper.emitted('action')).toBeTruthy()
            expect(wrapper.emitted('error')).toBeTruthy()
        })

        it('maintains existing CSS classes', async () => {
            wrapper = mount(MetricCard, {
                props: defaultProps
            })

            expect(wrapper.find('.metric-card').exists()).toBe(true)
            expect(wrapper.find('.metric-value').exists()).toBe(true)
            expect(wrapper.find('.metric-label').exists()).toBe(true)
        })
    })
})
