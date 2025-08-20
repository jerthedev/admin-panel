/**
 * DashboardSelector Component Tests
 * 
 * Vue component tests for the DashboardSelector component including
 * dropdown functionality, keyboard navigation, and dashboard selection.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import DashboardSelector from '../../../resources/js/Components/Dashboard/DashboardSelector.vue'

describe('DashboardSelector Component', () => {
    let wrapper

    const mockDashboards = [
        { 
            name: 'Main Dashboard', 
            uriKey: 'main', 
            description: 'Main application dashboard',
            icon: 'HomeIcon'
        },
        { 
            name: 'Analytics Dashboard', 
            uriKey: 'analytics', 
            description: 'Analytics and metrics dashboard',
            icon: 'ChartBarIcon'
        },
        { 
            name: 'Reports Dashboard', 
            uriKey: 'reports', 
            description: 'Reports and data dashboard',
            badge: 'New',
            badgeType: 'info'
        }
    ]

    const mockCurrentDashboard = mockDashboards[0]

    const defaultProps = {
        dashboards: mockDashboards,
        currentDashboard: mockCurrentDashboard
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
        it('renders selector button with current dashboard name', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            expect(button.exists()).toBe(true)
            expect(button.text()).toContain('Main Dashboard')
        })

        it('includes correct test id', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const selector = wrapper.find('[data-testid="dashboard-selector"]')
            expect(selector.exists()).toBe(true)
        })

        it('shows dropdown arrow icon', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const arrow = wrapper.find('svg')
            expect(arrow.exists()).toBe(true)
        })

        it('applies correct CSS classes to button', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            expect(button.element.className).toBeTruthy()
            expect(button.element.tagName).toBe('BUTTON')
        })
    })

    describe('Dropdown Functionality', () => {
        it('opens dropdown when button is clicked', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            expect(wrapper.find('.dashboard-dropdown').exists()).toBe(true)
            expect(button.attributes('aria-expanded')).toBe('true')
        })

        it('closes dropdown when button is clicked again', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            
            // Open dropdown
            await button.trigger('click')
            expect(wrapper.find('.dashboard-dropdown').exists()).toBe(true)

            // Close dropdown
            await button.trigger('click')
            expect(wrapper.find('.dashboard-dropdown').exists()).toBe(false)
            expect(button.attributes('aria-expanded')).toBe('false')
        })

        it('closes dropdown when clicking outside', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps,
                attachTo: document.body
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            expect(wrapper.find('.dashboard-dropdown').exists()).toBe(true)

            // Simulate click outside
            const backdrop = wrapper.find('.fixed.inset-0')
            await backdrop.trigger('click')

            expect(wrapper.find('.dashboard-dropdown').exists()).toBe(false)
        })

        it('rotates arrow icon when dropdown is open', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            const arrow = wrapper.find('svg')

            // Initially closed
            expect(arrow.classes()).not.toContain('rotate-180')

            // Open dropdown
            await button.trigger('click')
            expect(arrow.classes()).toContain('rotate-180')
        })
    })

    describe('Dashboard List', () => {
        it('renders all available dashboards', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const menuItems = wrapper.findAll('[role="menuitem"]')
            expect(menuItems).toHaveLength(3)
        })

        it('displays dashboard names and descriptions', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            expect(wrapper.text()).toContain('Main Dashboard')
            expect(wrapper.text()).toContain('Main application dashboard')
            expect(wrapper.text()).toContain('Analytics Dashboard')
            expect(wrapper.text()).toContain('Analytics and metrics dashboard')
        })

        it('highlights current dashboard', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const menuItems = wrapper.findAll('[role="menuitem"]')

            // Find the current dashboard item by checking aria-selected
            const currentItem = menuItems.find(item =>
                item.attributes('aria-selected') === 'true'
            )

            expect(currentItem).toBeDefined()
            expect(currentItem.attributes('aria-selected')).toBe('true')
            expect(currentItem.element.className).toBeTruthy()
        })

        it('shows current indicator for active dashboard', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const currentIndicator = wrapper.find('.current-indicator')
            expect(currentIndicator.exists()).toBe(true)
        })

        it('renders dashboard badges when present', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const badge = wrapper.find('.dashboard-badge')
            expect(badge.exists()).toBe(true)
            expect(badge.text()).toContain('New')
        })

        it('applies correct badge styling', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const badge = wrapper.find('.dashboard-badge span')
            expect(badge.exists()).toBe(true)
            expect(badge.element.className).toBeTruthy()
        })
    })

    describe('Dashboard Selection', () => {
        it('emits dashboard-changed event when selecting different dashboard', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const menuItems = wrapper.findAll('[role="menuitem"]')

            // Find a non-current dashboard item
            const analyticsItem = menuItems.find(item =>
                item.attributes('aria-selected') !== 'true'
            )

            expect(analyticsItem).toBeDefined()
            await analyticsItem.trigger('click')

            expect(wrapper.emitted('dashboard-changed')).toBeTruthy()
            expect(wrapper.emitted('dashboard-changed')[0]).toBeDefined()
        })

        it('does not emit event when selecting current dashboard', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const menuItems = wrapper.findAll('[role="menuitem"]')

            // Find the current dashboard item
            const currentItem = menuItems.find(item =>
                item.attributes('aria-selected') === 'true'
            )

            expect(currentItem).toBeDefined()
            await currentItem.trigger('click')

            // Should not emit dashboard-changed event for current dashboard
            expect(wrapper.emitted('dashboard-changed')).toBeFalsy()
        })

        it('closes dropdown after selection', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            expect(wrapper.find('.dashboard-dropdown').exists()).toBe(true)

            const menuItems = wrapper.findAll('[role="menuitem"]')
            await menuItems[1].trigger('click')

            expect(wrapper.find('.dashboard-dropdown').exists()).toBe(false)
        })
    })

    describe('Keyboard Navigation', () => {
        it('closes dropdown on escape key', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            expect(wrapper.find('.dashboard-dropdown').exists()).toBe(true)

            await button.trigger('keydown.escape')

            expect(wrapper.find('.dashboard-dropdown').exists()).toBe(false)
        })

        it('navigates with arrow keys', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            // Get initial focused index (should be current dashboard)
            const initialFocusedIndex = wrapper.vm.focusedIndex

            // Test arrow down navigation
            await button.trigger('keydown.arrow-down')

            // Should focus next item
            expect(wrapper.vm.focusedIndex).toBe(initialFocusedIndex + 1)

            // Test arrow up navigation
            await button.trigger('keydown.arrow-up')
            expect(wrapper.vm.focusedIndex).toBe(initialFocusedIndex)
        })

        it('selects focused item on enter key', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            // Find a non-current dashboard item to test selection
            const menuItems = wrapper.findAll('[role="menuitem"]')
            expect(menuItems.length).toBeGreaterThan(1)

            const nonCurrentItem = menuItems.find(item =>
                item.attributes('aria-selected') !== 'true'
            )

            expect(nonCurrentItem).toBeDefined()
            await nonCurrentItem.trigger('click')

            // Should emit dashboard-changed event
            expect(wrapper.emitted('dashboard-changed')).toBeTruthy()
            expect(wrapper.emitted('dashboard-changed')[0]).toBeDefined()
        })
    })

    describe('Footer', () => {
        it('shows footer with dashboard count by default', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const footer = wrapper.find('.dropdown-footer')
            expect(footer.exists()).toBe(true)
            expect(footer.text()).toContain('3 dashboards available')
        })

        it('hides footer when showFooter is false', async () => {
            const propsWithoutFooter = {
                ...defaultProps,
                showFooter: false
            }

            wrapper = mount(DashboardSelector, {
                props: propsWithoutFooter
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const footer = wrapper.find('.dropdown-footer')
            expect(footer.exists()).toBe(false)
        })

        it('shows correct singular/plural text', async () => {
            const propsWithSingleDashboard = {
                dashboards: [mockDashboards[0]],
                currentDashboard: mockDashboards[0]
            }

            wrapper = mount(DashboardSelector, {
                props: propsWithSingleDashboard
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const footer = wrapper.find('.dropdown-footer')
            expect(footer.text()).toContain('1 dashboard available')
        })
    })

    describe('Props Validation', () => {
        it('validates dashboards prop structure', async () => {
            const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})

            const invalidProps = {
                dashboards: [{ name: 'Invalid' }], // Missing uriKey
                currentDashboard: mockCurrentDashboard
            }

            wrapper = mount(DashboardSelector, {
                props: invalidProps
            })

            expect(consoleSpy).toHaveBeenCalled()
            consoleSpy.mockRestore()
        })

        it('validates currentDashboard prop structure', async () => {
            const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})

            const invalidProps = {
                dashboards: mockDashboards,
                currentDashboard: { name: 'Invalid' } // Missing uriKey
            }

            wrapper = mount(DashboardSelector, {
                props: invalidProps
            })

            expect(consoleSpy).toHaveBeenCalled()
            consoleSpy.mockRestore()
        })
    })

    describe('Accessibility', () => {
        it('provides proper ARIA attributes', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            expect(button.attributes('aria-haspopup')).toBe('true')
            expect(button.attributes('aria-expanded')).toBe('false')

            await button.trigger('click')
            expect(button.attributes('aria-expanded')).toBe('true')

            const dropdown = wrapper.find('.dashboard-dropdown')
            expect(dropdown.attributes('role')).toBe('menu')
            expect(dropdown.attributes('aria-orientation')).toBe('vertical')

            const menuItems = wrapper.findAll('[role="menuitem"]')
            expect(menuItems).toHaveLength(3)
        })

        it('supports screen reader navigation', async () => {
            wrapper = mount(DashboardSelector, {
                props: defaultProps
            })

            const button = wrapper.find('button')
            await button.trigger('click')

            const header = wrapper.find('.dropdown-header h3')
            expect(header.text()).toBe('Available Dashboards')
        })
    })

    describe('Enhanced Features', () => {
        describe('Search Functionality', () => {
            const dashboardsWithSearch = [
                ...mockDashboards,
                { name: 'User Management', uriKey: 'users', description: 'Manage users and permissions' },
                { name: 'Settings', uriKey: 'settings', description: 'Application settings' }
            ]

            it('shows search input when enabled and above threshold', async () => {
                wrapper = mount(DashboardSelector, {
                    props: {
                        ...defaultProps,
                        dashboards: dashboardsWithSearch,
                        enableSearch: true,
                        searchThreshold: 3
                    }
                })

                const button = wrapper.find('button')
                await button.trigger('click')

                const searchInput = wrapper.find('.search-input')
                expect(searchInput.exists()).toBe(true)
            })

            it('hides search input when below threshold', async () => {
                wrapper = mount(DashboardSelector, {
                    props: {
                        ...defaultProps,
                        enableSearch: true,
                        searchThreshold: 10
                    }
                })

                const button = wrapper.find('button')
                await button.trigger('click')

                const searchInput = wrapper.find('.search-input')
                expect(searchInput.exists()).toBe(false)
            })
        })

        describe('Dashboard Grouping', () => {
            const dashboardsWithCategories = [
                { name: 'Main Dashboard', uriKey: 'main', category: 'Overview' },
                { name: 'Analytics Dashboard', uriKey: 'analytics', category: 'Analytics' },
                { name: 'Reports Dashboard', uriKey: 'reports', category: 'Analytics' },
                { name: 'Settings', uriKey: 'settings', category: 'Configuration' }
            ]

            it('groups dashboards by category when enabled', async () => {
                wrapper = mount(DashboardSelector, {
                    props: {
                        ...defaultProps,
                        dashboards: dashboardsWithCategories,
                        groupBy: 'category',
                        showGroupHeaders: true
                    }
                })

                const button = wrapper.find('button')
                await button.trigger('click')

                const groupHeaders = wrapper.findAll('.group-header')
                expect(groupHeaders.length).toBeGreaterThan(0)
            })

            it('shows no groups when groupBy is none', async () => {
                wrapper = mount(DashboardSelector, {
                    props: {
                        ...defaultProps,
                        dashboards: dashboardsWithCategories,
                        groupBy: 'none',
                        showGroupHeaders: false
                    }
                })

                const button = wrapper.find('button')
                await button.trigger('click')

                const groupHeaders = wrapper.findAll('.group-header')
                expect(groupHeaders.length).toBe(0)
            })
        })

        describe('Quick Actions', () => {
            it('shows quick action buttons when enabled', async () => {
                wrapper = mount(DashboardSelector, {
                    props: {
                        ...defaultProps,
                        enableQuickActions: true,
                        canCreateDashboard: true,
                        canManageDashboards: true
                    }
                })

                const button = wrapper.find('button')
                await button.trigger('click')

                const footer = wrapper.find('.dropdown-footer')
                expect(footer.text()).toContain('New')
                expect(footer.text()).toContain('Manage')
            })
        })
    })
})
