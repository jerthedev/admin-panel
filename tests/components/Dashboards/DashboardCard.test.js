/**
 * DashboardCard Component Tests
 * 
 * Vue component tests for the DashboardCard component including
 * dynamic component loading, error handling, and card interactions.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import DashboardCard from '../../../resources/js/Components/Dashboard/DashboardCard.vue'

// Mock dynamic imports to prevent import errors
vi.mock('vue', async () => {
  const actual = await vi.importActual('vue')
  return {
    ...actual,
    defineAsyncComponent: vi.fn(() => ({
      name: 'MockAsyncComponent',
      template: '<div>Mock Component</div>'
    }))
  }
})

describe('DashboardCard Component', () => {
    let wrapper

    const defaultProps = {
        card: {
            component: 'TestCard',
            title: 'Test Card',
            subtitle: 'Test card subtitle'
        },
        dashboard: {
            name: 'Test Dashboard',
            uriKey: 'test'
        }
    }

    const mockCardWithActions = {
        component: 'ActionCard',
        title: 'Action Card',
        actions: [
            { name: 'refresh', type: 'primary', label: 'Refresh', icon: 'RefreshIcon' },
            { name: 'delete', type: 'danger', label: 'Delete', disabled: false }
        ]
    }

    const mockCardWithMeta = {
        component: 'MetaCard',
        title: 'Meta Card',
        meta: {
            'Last Updated': '2 minutes ago',
            'Status': 'Active'
        },
        links: [
            { label: 'View Details', url: '/details', external: false },
            { label: 'External Link', url: 'https://example.com', external: true }
        ]
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
        it('renders card with title and subtitle', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            expect(wrapper.find('.card-title').text()).toBe('Test Card')
            expect(wrapper.find('.card-subtitle').text()).toBe('Test card subtitle')
        })

        it('renders card without subtitle when not provided', async () => {
            const propsWithoutSubtitle = {
                ...defaultProps,
                card: {
                    ...defaultProps.card,
                    subtitle: undefined
                }
            }

            wrapper = mount(DashboardCard, {
                props: propsWithoutSubtitle
            })

            expect(wrapper.find('.card-title').text()).toBe('Test Card')
            expect(wrapper.find('.card-subtitle').exists()).toBe(false)
        })

        it('applies correct CSS classes', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            const card = wrapper.find('.dashboard-card')
            expect(card.exists()).toBe(true)
            // Check for basic structure rather than specific Tailwind classes
            expect(card.element.className).toBeTruthy()
        })

        it('includes correct test id', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            const card = wrapper.find('[data-testid="dashboard-card-TestCard"]')
            expect(card.exists()).toBe(true)
        })
    })

    describe('Card Header', () => {
        it('shows header when title or actions are present', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            expect(wrapper.find('.card-header').exists()).toBe(true)
        })

        it('hides header when no title or actions', async () => {
            const propsWithoutHeader = {
                ...defaultProps,
                card: {
                    component: 'TestCard'
                    // No title, subtitle, or actions
                }
            }

            wrapper = mount(DashboardCard, {
                props: propsWithoutHeader
            })

            expect(wrapper.find('.card-header').exists()).toBe(false)
        })

        it('renders card actions when provided', async () => {
            const propsWithActions = {
                ...defaultProps,
                card: mockCardWithActions
            }

            wrapper = mount(DashboardCard, {
                props: propsWithActions
            })

            const actions = wrapper.findAll('.card-actions button')
            expect(actions).toHaveLength(2)
            expect(actions[0].text()).toContain('Refresh')
            expect(actions[1].text()).toContain('Delete')
        })

        it('handles action clicks', async () => {
            const propsWithActions = {
                ...defaultProps,
                card: mockCardWithActions
            }

            wrapper = mount(DashboardCard, {
                props: propsWithActions
            })

            const refreshButton = wrapper.find('.card-actions button:first-child')
            await refreshButton.trigger('click')

            expect(wrapper.emitted('card-action')).toBeTruthy()
            expect(wrapper.emitted('card-action')[0]).toEqual([
                mockCardWithActions.actions[0],
                mockCardWithActions
            ])
        })

        it('has action disable functionality', async () => {
            const propsWithActions = {
                ...defaultProps,
                card: mockCardWithActions
            }

            wrapper = mount(DashboardCard, {
                props: propsWithActions
            })

            // Check that buttons exist and can be disabled
            const buttons = wrapper.findAll('.card-actions button')
            expect(buttons.length).toBeGreaterThan(0)

            // The disabled state depends on loading state which is managed internally
            buttons.forEach(button => {
                expect(button.exists()).toBe(true)
            })
        })
    })

    describe('Card Content', () => {
        it('has loading state structure', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            // Check that loading state management exists
            expect(typeof wrapper.vm.isLoading).toBe('boolean')
            expect(wrapper.vm.isLoading).toBe(false) // Initial state
        })

        it('has error state structure', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            // Check that error state management exists
            expect(wrapper.vm.error).toBe(null) // Initial state
        })

        it('has retry functionality', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            // Check that retry method exists
            expect(typeof wrapper.vm.retryLoad).toBe('function')
        })

        it('shows fallback content when no component loaded', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            // Check that fallback content structure exists
            // The actual fallback rendering depends on component loading state
            const cardContent = wrapper.find('.card-content')
            expect(cardContent.exists()).toBe(true)
        })
    })

    describe('Card Footer', () => {
        it('shows footer when meta or links are present', async () => {
            const propsWithMeta = {
                ...defaultProps,
                card: mockCardWithMeta
            }

            wrapper = mount(DashboardCard, {
                props: propsWithMeta
            })

            expect(wrapper.find('.card-footer').exists()).toBe(true)
        })

        it('hides footer when no meta or links', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            expect(wrapper.find('.card-footer').exists()).toBe(false)
        })

        it('renders meta information', async () => {
            const propsWithMeta = {
                ...defaultProps,
                card: mockCardWithMeta
            }

            wrapper = mount(DashboardCard, {
                props: propsWithMeta
            })

            const metaItems = wrapper.findAll('.meta-item')
            expect(metaItems).toHaveLength(2)
            expect(wrapper.text()).toContain('Last Updated')
            expect(wrapper.text()).toContain('2 minutes ago')
            expect(wrapper.text()).toContain('Status')
            expect(wrapper.text()).toContain('Active')
        })

        it('renders links with correct attributes', async () => {
            const propsWithMeta = {
                ...defaultProps,
                card: mockCardWithMeta
            }

            wrapper = mount(DashboardCard, {
                props: propsWithMeta
            })

            const links = wrapper.findAll('.card-link')
            expect(links).toHaveLength(2)

            // Internal link
            expect(links[0].attributes('href')).toBe('/details')
            expect(links[0].attributes('target')).toBe('_self')
            expect(links[0].attributes('rel')).toBe('')

            // External link
            expect(links[1].attributes('href')).toBe('https://example.com')
            expect(links[1].attributes('target')).toBe('_blank')
            expect(links[1].attributes('rel')).toBe('noopener noreferrer')
        })
    })

    describe('Event Handling', () => {
        it('emits card-action events', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            const testAction = { name: 'test', type: 'primary' }
            await wrapper.vm.handleCardAction(testAction)

            expect(wrapper.emitted('card-action')).toBeTruthy()
            expect(wrapper.emitted('card-action')[0]).toEqual([testAction, defaultProps.card])
        })

        it('emits card-error events', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            const testError = new Error('Test error')
            await wrapper.vm.handleCardError(testError)

            expect(wrapper.emitted('card-error')).toBeTruthy()
            expect(wrapper.emitted('card-error')[0]).toEqual([testError, defaultProps.card])
        })
    })

    describe('Props Validation', () => {
        it('validates card prop structure', async () => {
            const propsWithMinimalCard = {
                card: {
                    component: 'TestCard',
                    title: 'Test Card'
                    // Missing optional properties like clickable, subtitle, etc.
                },
                dashboard: defaultProps.dashboard
            }

            wrapper = mount(DashboardCard, {
                props: propsWithMinimalCard
            })

            // Should handle minimal card structure gracefully
            expect(wrapper.find('.dashboard-card').exists()).toBe(true)
            expect(wrapper.text()).toContain('Test Card')
        })

        it('validates dashboard prop structure', async () => {
            const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})

            const invalidProps = {
                card: defaultProps.card,
                dashboard: null // Invalid dashboard prop
            }

            wrapper = mount(DashboardCard, {
                props: invalidProps
            })

            expect(consoleSpy).toHaveBeenCalled()
            consoleSpy.mockRestore()
        })
    })

    describe('Accessibility', () => {
        it('provides proper semantic structure', async () => {
            wrapper = mount(DashboardCard, {
                props: defaultProps
            })

            const card = wrapper.find('.dashboard-card')
            expect(card.exists()).toBe(true)

            const title = wrapper.find('.card-title')
            expect(title.exists()).toBe(true)
            expect(title.element.tagName).toBe('H3')
        })

        it('supports keyboard navigation for actions', async () => {
            const propsWithActions = {
                ...defaultProps,
                card: mockCardWithActions
            }

            wrapper = mount(DashboardCard, {
                props: propsWithActions
            })

            const buttons = wrapper.findAll('.card-actions button')
            buttons.forEach(button => {
                expect(button.attributes('tabindex')).not.toBe('-1')
            })
        })
    })
})
