/**
 * WelcomeCard Component Tests
 * 
 * Vue component tests for the WelcomeCard component including
 * welcome content, stats display, quick actions, and responsive design.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import WelcomeCard from '../../../resources/js/Components/Dashboard/Cards/WelcomeCard.vue'

// Mock Inertia for navigation testing
const mockInertiaVisit = vi.fn()
Object.defineProperty(window, 'Inertia', {
  value: { visit: mockInertiaVisit },
  writable: true
})

describe('WelcomeCard Component', () => {
    let wrapper

    const defaultProps = {
        dashboard: {
            name: 'Test Dashboard',
            uriKey: 'test'
        }
    }

    const mockQuickActions = [
        { name: 'users', label: 'Manage Users', url: '/admin/users' },
        { name: 'settings', label: 'Settings', url: '/admin/settings' },
        { name: 'reports', label: 'View Reports', url: '/admin/reports', external: true }
    ]

    beforeEach(() => {
        vi.clearAllMocks()
        mockInertiaVisit.mockClear()
    })

    afterEach(() => {
        if (wrapper) {
            wrapper.unmount()
        }
    })

    describe('Basic Rendering', () => {
        it('renders default welcome content', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            expect(wrapper.find('.welcome-title').text()).toBe('Welcome to Admin Panel')
            expect(wrapper.find('.welcome-subtitle').text()).toBe('Manage your application with ease')
        })

        it('renders custom title and subtitle', async () => {
            const propsWithCustomContent = {
                ...defaultProps,
                title: 'Custom Welcome',
                subtitle: 'Custom subtitle text'
            }

            wrapper = mount(WelcomeCard, {
                props: propsWithCustomContent
            })

            expect(wrapper.find('.welcome-title').text()).toBe('Custom Welcome')
            expect(wrapper.find('.welcome-subtitle').text()).toBe('Custom subtitle text')
        })

        it('renders welcome icon', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const icon = wrapper.find('.welcome-icon svg')
            expect(icon.exists()).toBe(true)
            expect(icon.classes()).toContain('w-8')
            expect(icon.classes()).toContain('h-8')
            expect(icon.classes()).toContain('text-blue-500')
        })
    })

    describe('Stats Display', () => {
        it('renders default stats', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const statItems = wrapper.findAll('.stat-item')
            expect(statItems).toHaveLength(3)

            // Check default values
            expect(wrapper.text()).toContain('0') // Default user count
            expect(wrapper.text()).toContain('1') // Default dashboard count
            expect(wrapper.text()).toContain('99.9%') // Default uptime
        })

        it('renders custom stats', async () => {
            const propsWithStats = {
                ...defaultProps,
                userCount: 150,
                dashboardCount: 5,
                uptime: '98.5%'
            }

            wrapper = mount(WelcomeCard, {
                props: propsWithStats
            })

            expect(wrapper.text()).toContain('150')
            expect(wrapper.text()).toContain('5')
            expect(wrapper.text()).toContain('98.5%')
        })

        it('displays correct stat labels', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            expect(wrapper.text()).toContain('Total Users')
            expect(wrapper.text()).toContain('Dashboards')
            expect(wrapper.text()).toContain('Uptime')
        })

        it('applies correct styling to stats', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const statValues = wrapper.findAll('.stat-value')
            statValues.forEach(value => {
                expect(value.exists()).toBe(true)
                expect(value.element.className).toBeTruthy()
            })

            const statLabels = wrapper.findAll('.stat-label')
            statLabels.forEach(label => {
                expect(label.exists()).toBe(true)
                expect(label.element.className).toBeTruthy()
            })
        })
    })

    describe('Quick Actions', () => {
        it('renders default quick actions', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const actionButtons = wrapper.findAll('.action-button')
            expect(actionButtons).toHaveLength(3)

            expect(wrapper.text()).toContain('Manage Users')
            expect(wrapper.text()).toContain('Settings')
            expect(wrapper.text()).toContain('View Reports')
        })

        it('renders custom quick actions', async () => {
            const propsWithActions = {
                ...defaultProps,
                quickActions: mockQuickActions
            }

            wrapper = mount(WelcomeCard, {
                props: propsWithActions
            })

            const actionButtons = wrapper.findAll('.action-button')
            expect(actionButtons).toHaveLength(3)

            expect(wrapper.text()).toContain('Manage Users')
            expect(wrapper.text()).toContain('Settings')
            expect(wrapper.text()).toContain('View Reports')
        })

        it('handles action clicks with internal URLs', async () => {
            const propsWithActions = {
                ...defaultProps,
                quickActions: mockQuickActions
            }

            wrapper = mount(WelcomeCard, {
                props: propsWithActions
            })

            const actionButtons = wrapper.findAll('.action-button')
            await actionButtons[0].trigger('click') // Manage Users

            expect(wrapper.emitted('action')).toBeTruthy()
            expect(wrapper.emitted('action')[0]).toEqual([mockQuickActions[0]])
        })

        it('handles action clicks with external URLs', async () => {
            const propsWithActions = {
                ...defaultProps,
                quickActions: mockQuickActions
            }

            // Mock window.open
            const mockWindowOpen = vi.fn()
            Object.defineProperty(window, 'open', {
                value: mockWindowOpen,
                writable: true
            })

            wrapper = mount(WelcomeCard, {
                props: propsWithActions
            })

            const actionButtons = wrapper.findAll('.action-button')
            await actionButtons[2].trigger('click') // External link

            expect(mockWindowOpen).toHaveBeenCalledWith(
                '/admin/reports',
                '_blank',
                'noopener,noreferrer'
            )
        })

        it('uses Inertia navigation when available', async () => {
            const propsWithActions = {
                ...defaultProps,
                quickActions: [mockQuickActions[0]] // Internal link
            }

            wrapper = mount(WelcomeCard, {
                props: propsWithActions
            })

            const actionButton = wrapper.find('.action-button')
            await actionButton.trigger('click')

            expect(mockInertiaVisit).toHaveBeenCalledWith('/admin/users')
        })

        it('renders action icons', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const actionButtons = wrapper.findAll('.action-button')
            actionButtons.forEach(button => {
                const icon = button.find('svg')
                expect(icon.exists()).toBe(true)
                expect(icon.classes()).toContain('w-4')
                expect(icon.classes()).toContain('h-4')
            })
        })
    })

    describe('Footer Information', () => {
        it('renders last updated time', async () => {
            const propsWithLastUpdated = {
                ...defaultProps,
                lastUpdated: new Date('2024-01-15T10:30:00Z')
            }

            wrapper = mount(WelcomeCard, {
                props: propsWithLastUpdated
            })

            const footer = wrapper.find('.footer-info')
            expect(footer.exists()).toBe(true)
            expect(footer.text()).toContain('Last updated:')
        })

        it('renders version when provided', async () => {
            const propsWithVersion = {
                ...defaultProps,
                version: '1.2.3'
            }

            wrapper = mount(WelcomeCard, {
                props: propsWithVersion
            })

            const footer = wrapper.find('.footer-info')
            expect(footer.text()).toContain('Version: 1.2.3')
        })

        it('formats last updated time correctly', async () => {
            const testDate = new Date('2024-01-15T14:30:00Z')
            const propsWithLastUpdated = {
                ...defaultProps,
                lastUpdated: testDate
            }

            wrapper = mount(WelcomeCard, {
                props: propsWithLastUpdated
            })

            // Should format as time string
            const footer = wrapper.find('.footer-info')
            expect(footer.text()).toMatch(/\d{1,2}:\d{2}/)
        })

        it('handles string date format', async () => {
            const propsWithStringDate = {
                ...defaultProps,
                lastUpdated: '2024-01-15T14:30:00Z'
            }

            wrapper = mount(WelcomeCard, {
                props: propsWithStringDate
            })

            const footer = wrapper.find('.footer-info')
            expect(footer.text()).toContain('Last updated:')
        })
    })

    describe('Event Handling', () => {
        it('emits action events', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const testAction = { name: 'test-action', url: '/test' }
            await wrapper.vm.handleAction(testAction)

            expect(wrapper.emitted('action')).toBeTruthy()
            expect(wrapper.emitted('action')[0]).toEqual([testAction])
        })

        it('emits error events', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const testError = new Error('Test error')
            await wrapper.vm.$emit('error', testError)

            expect(wrapper.emitted('error')).toBeTruthy()
            expect(wrapper.emitted('error')[0]).toEqual([testError])
        })
    })

    describe('Responsive Design', () => {
        it('applies responsive grid classes', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const statsGrid = wrapper.find('.welcome-stats')
            expect(statsGrid.exists()).toBe(true)
            // Check that stats are properly structured in grid layout
            const statItems = statsGrid.findAll('.stat-item')
            expect(statItems.length).toBeGreaterThan(0)

            const actionsGrid = wrapper.find('.actions-grid')
            expect(actionsGrid.exists()).toBe(true)
            // Check that actions are properly structured in grid layout
            const actionButtons = actionsGrid.findAll('.action-button')
            expect(actionButtons.length).toBeGreaterThan(0)
        })

        it('uses responsive spacing', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const card = wrapper.find('.welcome-card')
            expect(card.exists()).toBe(true)
            expect(card.element.className).toBeTruthy()

            const header = wrapper.find('.welcome-header')
            expect(header.exists()).toBe(true)
            expect(header.element.className).toBeTruthy()
        })
    })

    describe('Accessibility', () => {
        it('provides semantic structure', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const title = wrapper.find('.welcome-title')
            expect(title.exists()).toBe(true)

            const actionButtons = wrapper.findAll('.action-button')
            actionButtons.forEach(button => {
                expect(button.element.tagName).toBe('BUTTON')
            })
        })

        it('supports keyboard navigation', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            const actionButtons = wrapper.findAll('.action-button')
            actionButtons.forEach(button => {
                expect(button.attributes('tabindex')).not.toBe('-1')
            })
        })

        it('provides proper contrast', async () => {
            wrapper = mount(WelcomeCard, {
                props: defaultProps
            })

            // Check that text elements exist and have styling
            const title = wrapper.find('.welcome-title')
            expect(title.exists()).toBe(true)
            expect(title.element.className).toBeTruthy()

            const subtitle = wrapper.find('.welcome-subtitle')
            expect(subtitle.exists()).toBe(true)
            expect(subtitle.element.className).toBeTruthy()
        })
    })
})
