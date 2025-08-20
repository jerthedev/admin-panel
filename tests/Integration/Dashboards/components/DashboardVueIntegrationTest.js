/**
 * Dashboard Vue Integration Tests
 * 
 * Integration tests for Vue dashboard components with Laravel backend,
 * testing data flow, API interactions, and real-world scenarios.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import axios from 'axios'
import Dashboard from '../../../../resources/js/Pages/Dashboard.vue'

// Mock axios for API calls
vi.mock('axios')
const mockedAxios = vi.mocked(axios)

describe('Dashboard Vue Integration', () => {
    let wrapper

    beforeEach(() => {
        vi.clearAllMocks()
        
        // Mock successful API responses
        mockedAxios.get.mockResolvedValue({
            data: {
                dashboard: {
                    name: 'Main',
                    uriKey: 'main',
                    showRefreshButton: true
                },
                cards: [
                    {
                        component: 'user-count-card',
                        data: { count: 150, trend: 'up' },
                        title: 'Total Users',
                        size: 'md'
                    }
                ],
                metrics: [
                    { name: 'Active Users', value: 89, change: '+12%' }
                ]
            }
        })
    })

    afterEach(() => {
        if (wrapper) {
            wrapper.unmount()
        }
    })

    it('integrates with Laravel backend for dashboard data', async () => {
        const props = {
            dashboard: { name: 'Main', uriKey: 'main', showRefreshButton: true },
            cards: [],
            metrics: [],
            recentActivity: [],
            quickActions: [],
            systemInfo: {}
        }

        wrapper = mount(Dashboard, {
            props,
            global: {
                mocks: {
                    $inertia: {
                        reload: vi.fn(),
                        visit: vi.fn()
                    }
                }
            }
        })

        // Trigger data refresh
        await wrapper.vm.refreshDashboard()
        await flushPromises()

        expect(mockedAxios.get).toHaveBeenCalledWith('/admin/api/dashboard/main')
    })

    it('handles real-time card updates', async () => {
        const initialProps = {
            dashboard: { name: 'Main', uriKey: 'main', showRefreshButton: true },
            cards: [
                {
                    component: 'user-count-card',
                    data: { count: 100 },
                    title: 'Total Users',
                    size: 'md'
                }
            ],
            metrics: [],
            recentActivity: [],
            quickActions: [],
            systemInfo: {}
        }

        wrapper = mount(Dashboard, {
            props: initialProps,
            global: {
                mocks: {
                    $inertia: {
                        reload: vi.fn(),
                        visit: vi.fn()
                    }
                }
            }
        })

        // Simulate real-time update
        mockedAxios.get.mockResolvedValueOnce({
            data: {
                cards: [
                    {
                        component: 'user-count-card',
                        data: { count: 150 },
                        title: 'Total Users',
                        size: 'md'
                    }
                ]
            }
        })

        await wrapper.vm.updateCardData('user-count-card')
        await flushPromises()

        expect(wrapper.vm.cards[0].data.count).toBe(150)
    })

    it('handles dashboard switching with proper data loading', async () => {
        const props = {
            dashboard: { name: 'Main', uriKey: 'main', showRefreshButton: false },
            cards: [],
            metrics: [],
            recentActivity: [],
            quickActions: [],
            systemInfo: {}
        }

        const mockVisit = vi.fn()
        
        wrapper = mount(Dashboard, {
            props,
            global: {
                mocks: {
                    $inertia: {
                        visit: mockVisit,
                        reload: vi.fn()
                    }
                }
            }
        })

        // Mock analytics dashboard data
        mockedAxios.get.mockResolvedValueOnce({
            data: {
                dashboard: {
                    name: 'Analytics',
                    uriKey: 'analytics',
                    showRefreshButton: true
                },
                cards: [
                    {
                        component: 'analytics-card',
                        data: { views: 5000 },
                        title: 'Page Views',
                        size: 'lg'
                    }
                ]
            }
        })

        await wrapper.vm.switchDashboard('analytics')

        expect(mockVisit).toHaveBeenCalledWith('/admin/dashboards/analytics')
    })

    it('handles API errors gracefully', async () => {
        const props = {
            dashboard: { name: 'Main', uriKey: 'main', showRefreshButton: true },
            cards: [],
            metrics: [],
            recentActivity: [],
            quickActions: [],
            systemInfo: {}
        }

        wrapper = mount(Dashboard, {
            props,
            global: {
                mocks: {
                    $inertia: {
                        reload: vi.fn(),
                        visit: vi.fn()
                    }
                }
            }
        })

        // Mock API error
        mockedAxios.get.mockRejectedValueOnce(new Error('Network error'))

        await wrapper.vm.refreshDashboard()
        await flushPromises()

        expect(wrapper.vm.error).toBe('Failed to refresh dashboard data')
        expect(wrapper.find('[data-testid="error-message"]').exists()).toBe(true)
    })

    it('integrates with Laravel authorization', async () => {
        const props = {
            dashboard: { name: 'Admin', uriKey: 'admin', showRefreshButton: false },
            cards: [],
            metrics: [],
            recentActivity: [],
            quickActions: [],
            systemInfo: {}
        }

        wrapper = mount(Dashboard, {
            props,
            global: {
                mocks: {
                    $inertia: {
                        visit: vi.fn(),
                        reload: vi.fn()
                    }
                }
            }
        })

        // Mock unauthorized response
        mockedAxios.get.mockRejectedValueOnce({
            response: { status: 403, data: { message: 'Unauthorized' } }
        })

        await wrapper.vm.refreshDashboard()
        await flushPromises()

        expect(wrapper.vm.error).toBe('You are not authorized to view this dashboard')
    })

    it('handles card interactions with backend', async () => {
        const props = {
            dashboard: { name: 'Main', uriKey: 'main', showRefreshButton: false },
            cards: [
                {
                    component: 'interactive-card',
                    data: { interactive: true },
                    title: 'Interactive Card',
                    size: 'md'
                }
            ],
            metrics: [],
            recentActivity: [],
            quickActions: [],
            systemInfo: {}
        }

        wrapper = mount(Dashboard, {
            props,
            global: {
                mocks: {
                    $inertia: {
                        visit: vi.fn(),
                        reload: vi.fn()
                    }
                }
            }
        })

        // Mock card action response
        mockedAxios.post.mockResolvedValueOnce({
            data: { success: true, message: 'Action completed' }
        })

        await wrapper.vm.handleCardAction('interactive-card', 'click')
        await flushPromises()

        expect(mockedAxios.post).toHaveBeenCalledWith(
            '/admin/api/cards/interactive-card/action',
            { action: 'click' }
        )
    })

    it('maintains state during navigation', async () => {
        const props = {
            dashboard: { name: 'Main', uriKey: 'main', showRefreshButton: true },
            cards: [
                {
                    component: 'stateful-card',
                    data: { value: 100 },
                    title: 'Stateful Card',
                    size: 'md'
                }
            ],
            metrics: [],
            recentActivity: [],
            quickActions: [],
            systemInfo: {}
        }

        wrapper = mount(Dashboard, {
            props,
            global: {
                mocks: {
                    $inertia: {
                        visit: vi.fn(),
                        reload: vi.fn()
                    }
                }
            }
        })

        // Modify card state
        await wrapper.vm.updateCardState('stateful-card', { value: 200 })

        // Simulate navigation back
        await wrapper.vm.restoreState()

        expect(wrapper.vm.cardStates['stateful-card'].value).toBe(200)
    })

    it('handles WebSocket connections for real-time updates', async () => {
        const mockWebSocket = {
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            close: vi.fn()
        }

        global.WebSocket = vi.fn(() => mockWebSocket)

        const props = {
            dashboard: { name: 'Main', uriKey: 'main', showRefreshButton: false },
            cards: [],
            metrics: [],
            recentActivity: [],
            quickActions: [],
            systemInfo: {}
        }

        wrapper = mount(Dashboard, {
            props,
            global: {
                mocks: {
                    $inertia: {
                        visit: vi.fn(),
                        reload: vi.fn()
                    }
                }
            }
        })

        await wrapper.vm.connectWebSocket()

        expect(global.WebSocket).toHaveBeenCalledWith('ws://localhost:6001/dashboard/main')
        expect(mockWebSocket.addEventListener).toHaveBeenCalledWith('message', expect.any(Function))
    })

    it('handles dashboard permissions and visibility', async () => {
        const props = {
            dashboard: { name: 'Restricted', uriKey: 'restricted', showRefreshButton: false },
            cards: [
                {
                    component: 'restricted-card',
                    data: { restricted: true },
                    title: 'Restricted Card',
                    size: 'md',
                    visible: false
                }
            ],
            metrics: [],
            recentActivity: [],
            quickActions: [],
            systemInfo: {}
        }

        wrapper = mount(Dashboard, {
            props,
            global: {
                mocks: {
                    $inertia: {
                        visit: vi.fn(),
                        reload: vi.fn()
                    }
                }
            }
        })

        const visibleCards = wrapper.findAll('[data-testid="dashboard-card"]:not(.hidden)')
        expect(visibleCards).toHaveLength(0)
    })

    it('integrates with Laravel validation for card actions', async () => {
        const props = {
            dashboard: { name: 'Main', uriKey: 'main', showRefreshButton: false },
            cards: [
                {
                    component: 'form-card',
                    data: { hasForm: true },
                    title: 'Form Card',
                    size: 'md'
                }
            ],
            metrics: [],
            recentActivity: [],
            quickActions: [],
            systemInfo: {}
        }

        wrapper = mount(Dashboard, {
            props,
            global: {
                mocks: {
                    $inertia: {
                        visit: vi.fn(),
                        reload: vi.fn()
                    }
                }
            }
        })

        // Mock validation error response
        mockedAxios.post.mockRejectedValueOnce({
            response: {
                status: 422,
                data: {
                    errors: {
                        field: ['This field is required']
                    }
                }
            }
        })

        await wrapper.vm.submitCardForm('form-card', { field: '' })
        await flushPromises()

        expect(wrapper.vm.validationErrors).toEqual({
            field: ['This field is required']
        })
    })
})
