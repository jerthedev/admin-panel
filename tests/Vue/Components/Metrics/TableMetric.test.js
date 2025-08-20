import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import TableMetric from '@/components/Metrics/TableMetric.vue'

// Mock the Pagination component to avoid Pinia dependency
vi.mock('@/components/Common/Pagination.vue', () => ({
  default: {
    name: 'Pagination',
    props: ['links'],
    template: '<div data-testid="pagination-mock">Pagination Component</div>'
  }
}))

describe('TableMetric.vue', () => {
  let wrapper

  const defaultProps = {
    title: 'User Activity Table',
    data: [
      { id: 1, name: 'John Doe', email: 'john@example.com', status: 'active', created_at: '2024-01-15' },
      { id: 2, name: 'Jane Smith', email: 'jane@example.com', status: 'inactive', created_at: '2024-01-10' },
      { id: 3, name: 'Bob Johnson', email: 'bob@example.com', status: 'active', created_at: '2024-01-20' },
    ],
    columns: [
      { key: 'name', label: 'Name', sortable: true },
      { key: 'email', label: 'Email', sortable: true },
      { key: 'status', label: 'Status', sortable: false, formatter: (value) => value.toUpperCase() },
      { key: 'created_at', label: 'Created', sortable: true, type: 'date' },
    ],
    actions: [
      { key: 'view', label: 'View', icon: 'eye', variant: 'primary' },
      { key: 'edit', label: 'Edit', icon: 'pencil', variant: 'secondary' },
      { key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' },
    ],
    loading: false,
    error: null,
    sortBy: null,
    sortDirection: 'asc',
    pagination: null,
    showActions: true,
    selectable: false,
  }

  beforeEach(() => {
    wrapper = mount(TableMetric, {
      props: defaultProps,
    })
  })

  describe('Component Rendering', () => {
    it('renders the component', () => {
      expect(wrapper.exists()).toBe(true)
    })

    it('displays the metric title', () => {
      expect(wrapper.text()).toContain('User Activity Table')
    })

    it('displays table headers', () => {
      expect(wrapper.text()).toContain('Name')
      expect(wrapper.text()).toContain('Email')
      expect(wrapper.text()).toContain('Status')
      expect(wrapper.text()).toContain('Created')
    })

    it('displays table data', () => {
      expect(wrapper.text()).toContain('John Doe')
      expect(wrapper.text()).toContain('john@example.com')
      expect(wrapper.text()).toContain('Jane Smith')
      expect(wrapper.text()).toContain('jane@example.com')
    })

    it('displays actions column when showActions is true', () => {
      expect(wrapper.find('[data-testid="actions-header"]').exists()).toBe(true)
    })

    it('hides actions column when showActions is false', async () => {
      await wrapper.setProps({ showActions: false })
      expect(wrapper.find('[data-testid="actions-header"]').exists()).toBe(false)
    })
  })

  describe('Table Functionality', () => {
    it('displays correct number of rows', () => {
      const rows = wrapper.findAll('[data-testid="table-row"]')
      expect(rows).toHaveLength(3)
    })

    it('applies custom formatting to columns', () => {
      // Status column should be uppercase due to formatter
      expect(wrapper.text()).toContain('ACTIVE')
      expect(wrapper.text()).toContain('INACTIVE')
    })

    it('handles nested object properties', async () => {
      await wrapper.setProps({
        data: [{ id: 1, user: { profile: { name: 'Test User' } } }],
        columns: [{ key: 'user.profile.name', label: 'Name', sortable: true }],
      })

      expect(wrapper.text()).toContain('Test User')
    })

    it('displays icons in action buttons', () => {
      const actionButtons = wrapper.findAll('[data-testid="action-button"]')
      expect(actionButtons.length).toBeGreaterThan(0)
    })
  })

  describe('Sorting Functionality', () => {
    it('displays sort indicators for sortable columns', () => {
      const sortableHeaders = wrapper.findAll('[data-testid="sortable-header"]')
      expect(sortableHeaders.length).toBeGreaterThan(0)
    })

    it('emits sort event when sortable header is clicked', async () => {
      const sortableHeader = wrapper.find('[data-testid="sortable-header"]')
      await sortableHeader.trigger('click')

      expect(wrapper.emitted('sort')).toBeTruthy()
      expect(wrapper.emitted('sort')[0]).toEqual(['name', 'asc'])
    })

    it('toggles sort direction on repeated clicks', async () => {
      await wrapper.setProps({ sortBy: 'name', sortDirection: 'asc' })
      
      const sortableHeader = wrapper.find('[data-testid="sortable-header"]')
      await sortableHeader.trigger('click')

      expect(wrapper.emitted('sort')[0]).toEqual(['name', 'desc'])
    })

    it('shows correct sort indicators', async () => {
      await wrapper.setProps({ sortBy: 'name', sortDirection: 'asc' })
      
      const sortIcon = wrapper.find('[data-testid="sort-asc"]')
      expect(sortIcon.exists()).toBe(true)
    })
  })

  describe('Row Actions', () => {
    it('displays action buttons for each row', () => {
      const actionButtons = wrapper.findAll('[data-testid="action-button"]')
      expect(actionButtons.length).toBe(9) // 3 actions × 3 rows
    })

    it('emits action event when action button is clicked', async () => {
      const actionButton = wrapper.find('[data-testid="action-button"]')
      await actionButton.trigger('click')

      expect(wrapper.emitted('action')).toBeTruthy()
      expect(wrapper.emitted('action')[0][0]).toEqual({
        action: 'view',
        item: defaultProps.data[0],
        index: 0,
      })
    })

    it('applies correct variant classes to action buttons', () => {
      const actionButtons = wrapper.findAll('[data-testid="action-button"]')
      
      // Check for primary variant (view button)
      expect(actionButtons[0].classes()).toContain('btn-primary')
      
      // Check for secondary variant (edit button)
      expect(actionButtons[1].classes()).toContain('btn-secondary')
      
      // Check for danger variant (delete button)
      expect(actionButtons[2].classes()).toContain('btn-danger')
    })
  })

  describe('Selection Functionality', () => {
    it('shows selection checkboxes when selectable is true', async () => {
      await wrapper.setProps({ selectable: true })
      
      expect(wrapper.find('[data-testid="select-all"]').exists()).toBe(true)
      expect(wrapper.findAll('[data-testid="row-checkbox"]')).toHaveLength(3)
    })

    it('hides selection checkboxes when selectable is false', () => {
      expect(wrapper.find('[data-testid="select-all"]').exists()).toBe(false)
      expect(wrapper.findAll('[data-testid="row-checkbox"]')).toHaveLength(0)
    })

    it('emits selection-change event when row is selected', async () => {
      await wrapper.setProps({ selectable: true })
      
      const checkbox = wrapper.find('[data-testid="row-checkbox"]')
      await checkbox.setChecked(true)

      expect(wrapper.emitted('selection-change')).toBeTruthy()
    })

    it('selects all rows when select-all is checked', async () => {
      await wrapper.setProps({ selectable: true })
      
      const selectAllCheckbox = wrapper.find('[data-testid="select-all"]')
      await selectAllCheckbox.setChecked(true)

      expect(wrapper.emitted('selection-change')).toBeTruthy()
      expect(wrapper.emitted('selection-change')[0][0]).toHaveLength(3)
    })
  })

  describe('Pagination', () => {
    it('displays pagination when pagination prop is provided', async () => {
      const paginationData = {
        links: {
          prev: '/users?page=1',
          next: '/users?page=3',
          links: [
            { url: null, label: '&laquo; Previous', active: false },
            { url: '/users?page=1', label: '1', active: false },
            { url: '/users?page=2', label: '2', active: true },
            { url: '/users?page=3', label: '3', active: false },
            { url: '/users?page=3', label: 'Next &raquo;', active: false },
          ],
        },
        meta: {
          current_page: 2,
          from: 11,
          last_page: 3,
          per_page: 10,
          to: 20,
          total: 25,
        },
      }

      await wrapper.setProps({ pagination: paginationData })
      
      expect(wrapper.find('[data-testid="pagination"]').exists()).toBe(true)
    })

    it('hides pagination when pagination prop is null', () => {
      expect(wrapper.find('[data-testid="pagination"]').exists()).toBe(false)
    })
  })

  describe('Loading State', () => {
    it('displays loading spinner when loading is true', async () => {
      await wrapper.setProps({ loading: true })
      expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
    })

    it('hides table when loading', async () => {
      await wrapper.setProps({ loading: true })
      expect(wrapper.find('[data-testid="data-table"]').exists()).toBe(false)
    })
  })

  describe('Error State', () => {
    it('displays error message when error is present', async () => {
      await wrapper.setProps({ error: 'Failed to load table data' })
      expect(wrapper.find('[data-testid="error-message"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Failed to load table data')
    })

    it('hides table when error is present', async () => {
      await wrapper.setProps({ error: 'Failed to load data' })
      expect(wrapper.find('[data-testid="data-table"]').exists()).toBe(false)
    })
  })

  describe('Empty State', () => {
    it('displays empty state when no data', async () => {
      await wrapper.setProps({ data: [] })
      expect(wrapper.find('[data-testid="empty-state"]').exists()).toBe(true)
    })

    it('displays custom empty message', async () => {
      await wrapper.setProps({ 
        data: [], 
        emptyText: 'No users found' 
      })
      expect(wrapper.text()).toContain('No users found')
    })
  })

  describe('Responsive Design', () => {
    it('applies responsive classes', () => {
      expect(wrapper.classes()).toContain('table-metric')
    })

    it('makes table horizontally scrollable', () => {
      expect(wrapper.find('.overflow-x-auto').exists()).toBe(true)
    })
  })

  describe('Dark Mode Support', () => {
    it('applies dark mode classes when enabled', async () => {
      await wrapper.setProps({ darkMode: true })
      expect(wrapper.find('.dark').exists()).toBe(true)
    })

    it('uses appropriate colors for dark mode', async () => {
      await wrapper.setProps({ darkMode: true })
      expect(wrapper.classes()).toContain('dark:bg-gray-800')
    })
  })

  describe('Accessibility', () => {
    it('has proper table structure', () => {
      expect(wrapper.find('table').exists()).toBe(true)
      expect(wrapper.find('thead').exists()).toBe(true)
      expect(wrapper.find('tbody').exists()).toBe(true)
    })

    it('has proper ARIA labels', () => {
      expect(wrapper.find('[aria-label]').exists()).toBe(true)
    })

    it('provides screen reader friendly content', () => {
      expect(wrapper.find('[data-testid="sr-only"]').exists()).toBe(true)
    })
  })

  describe('Edge Cases', () => {
    it('handles null/undefined cell values', async () => {
      await wrapper.setProps({
        data: [{ id: 1, name: null, email: undefined, status: 'active' }],
      })

      expect(wrapper.text()).toContain('-') // Default null display
    })

    it('handles empty column configuration', async () => {
      await wrapper.setProps({ columns: [] })
      expect(wrapper.find('[data-testid="data-table"]').exists()).toBe(true)
    })

    it('handles missing action configuration', async () => {
      await wrapper.setProps({ actions: [] })
      expect(wrapper.findAll('[data-testid="action-button"]')).toHaveLength(0)
    })
  })
})
