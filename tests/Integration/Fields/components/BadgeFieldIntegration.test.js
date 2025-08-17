import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import BadgeField from '@/components/Fields/BadgeField.vue'
import { createMockField } from '../../../helpers.js'

/**
 * Badge Field Integration Tests
 *
 * Tests the integration between the PHP Badge field class and Vue component,
 * ensuring proper data flow, API compatibility, and Nova-style behavior.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false,
  fullscreenMode: false,
  sidebarCollapsed: false,
  toggleDarkTheme: vi.fn(),
  toggleFullscreen: vi.fn(),
  toggleSidebar: vi.fn()
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

describe('BadgeField Integration', () => {
  let wrapper

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('PHP to Vue Integration', () => {
    it('receives and processes PHP field configuration correctly', async () => {
      const phpFieldConfig = createMockField({
        name: 'Post Status',
        attribute: 'status',
        component: 'BadgeField',
        builtInTypes: {
          info: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
          success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
          danger: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
          warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        },
        valueMap: {
          'draft': 'danger',
          'published': 'success',
          'archived': 'warning'
        },
        customTypes: {
          'danger': 'bg-red-50 text-red-700 ring-red-600/20',
          'success': 'bg-green-50 text-green-700 ring-green-600/20'
        },
        withIcons: true,
        iconMap: {
          'danger': 'exclamation-triangle',
          'success': 'check-circle',
          'warning': 'exclamation-circle'
        },
        labelMap: {
          'draft': 'Draft Post',
          'published': 'Published Post',
          'archived': 'Archived Post'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'draft'
        }
      })

      // Verify PHP configuration is properly received
      expect(wrapper.vm.field.name).toBe('Post Status')
      expect(wrapper.vm.field.valueMap).toEqual({
        'draft': 'danger',
        'published': 'success',
        'archived': 'warning'
      })
      expect(wrapper.vm.field.customTypes).toEqual({
        'danger': 'bg-red-50 text-red-700 ring-red-600/20',
        'success': 'bg-green-50 text-green-700 ring-green-600/20'
      })
      expect(wrapper.vm.field.withIcons).toBe(true)
      expect(wrapper.vm.field.iconMap).toEqual({
        'danger': 'exclamation-triangle',
        'success': 'check-circle',
        'warning': 'exclamation-circle'
      })
      expect(wrapper.vm.field.labelMap).toEqual({
        'draft': 'Draft Post',
        'published': 'Published Post',
        'archived': 'Archived Post'
      })
    })

    it('correctly processes Nova API map() method output', async () => {
      const phpFieldConfig = createMockField({
        valueMap: {
          'draft': 'danger',
          'published': 'success',
          'review': 'warning'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'draft'
        }
      })

      expect(wrapper.vm.badgeType).toBe('danger')

      // Test different values
      await await wrapper.setProps({ modelValue: 'published' })
      expect(wrapper.vm.badgeType).toBe('success')

      await await wrapper.setProps({ modelValue: 'review' })
      expect(wrapper.vm.badgeType).toBe('warning')
    })

    it('correctly processes Nova API types() method output', async () => {
      const phpFieldConfig = createMockField({
        valueMap: {
          'draft': 'danger'
        },
        customTypes: {
          'danger': 'bg-red-50 text-red-700 ring-red-600/20 font-medium'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'draft'
        }
      })

      expect(wrapper.vm.badgeClasses).toBe('bg-red-50 text-red-700 ring-red-600/20 font-medium')
    })

    it('correctly processes Nova API addTypes() method output', async () => {
      const phpFieldConfig = createMockField({
        valueMap: {
          'draft': 'danger',
          'custom': 'special'
        },
        customTypes: {
          'danger': 'bg-red-50 text-red-700',
          'special': 'bg-purple-50 text-purple-700' // Added via addTypes()
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'custom'
        }
      })

      expect(wrapper.vm.badgeType).toBe('special')
      expect(wrapper.vm.badgeClasses).toBe('bg-purple-50 text-purple-700')
    })

    it('correctly processes Nova API withIcons() method output', async () => {
      const phpFieldConfig = createMockField({
        withIcons: true,
        valueMap: {
          'draft': 'danger'
        },
        iconMap: {
          'danger': 'exclamation-triangle'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'draft'
        }
      })

      expect(wrapper.vm.showIcon).toBe(true)
      expect(wrapper.vm.iconName).toBe('exclamation-triangle')
    })

    it('correctly processes Nova API icons() method output', async () => {
      const phpFieldConfig = createMockField({
        withIcons: true,
        valueMap: {
          'draft': 'danger',
          'published': 'success',
          'archived': 'warning'
        },
        iconMap: {
          'danger': 'exclamation-triangle',
          'success': 'check-circle',
          'warning': 'exclamation-circle'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'draft'
        }
      })

      expect(wrapper.vm.iconName).toBe('exclamation-triangle')

      await wrapper.setProps({ modelValue: 'published' })
      expect(wrapper.vm.iconName).toBe('check-circle')

      await wrapper.setProps({ modelValue: 'archived' })
      expect(wrapper.vm.iconName).toBe('exclamation-circle')
    })

    it('correctly processes Nova API labels() method output', async () => {
      const phpFieldConfig = createMockField({
        labelMap: {
          'draft': 'Draft Article',
          'published': 'Published Article',
          'archived': 'Archived Article'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'draft'
        }
      })

      expect(wrapper.vm.displayLabel).toBe('Draft Article')
      expect(wrapper.find('.inline-flex').text()).toBe('Draft Article')

      await wrapper.setProps({ modelValue: 'published' })
      expect(wrapper.vm.displayLabel).toBe('Published Article')

      await wrapper.setProps({ modelValue: 'archived' })
      expect(wrapper.vm.displayLabel).toBe('Archived Article')
    })
  })

  describe('Nova API Compatibility Integration', () => {
    it('integrates all Nova API methods correctly', async () => {
      const phpFieldConfig = createMockField({
        valueMap: {
          'draft': 'danger',
          'published': 'success'
        },
        customTypes: {
          'danger': 'bg-red-50 text-red-700 ring-red-600/20',
          'success': 'bg-green-50 text-green-700 ring-green-600/20'
        },
        withIcons: true,
        iconMap: {
          'danger': 'exclamation-triangle',
          'success': 'check-circle'
        },
        labelMap: {
          'draft': 'Draft Post',
          'published': 'Published Post'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'draft'
        }
      })

      // Test complete integration
      expect(wrapper.vm.badgeType).toBe('danger')
      expect(wrapper.vm.badgeClasses).toBe('bg-red-50 text-red-700 ring-red-600/20')
      expect(wrapper.vm.displayLabel).toBe('Draft Post')
      expect(wrapper.vm.showIcon).toBe(true)
      expect(wrapper.vm.iconName).toBe('exclamation-triangle')

      // Test switching values
      await wrapper.setProps({ modelValue: 'published' })

      expect(wrapper.vm.badgeType).toBe('success')
      expect(wrapper.vm.badgeClasses).toBe('bg-green-50 text-green-700 ring-green-600/20')
      expect(wrapper.vm.displayLabel).toBe('Published Post')
      expect(wrapper.vm.showIcon).toBe(true)
      expect(wrapper.vm.iconName).toBe('check-circle')
    })

    it('handles fallback behavior correctly', async () => {
      const phpFieldConfig = createMockField({
        valueMap: {
          'draft': 'danger'
        },
        customTypes: {
          'danger': 'custom-danger-class'
        },
        withIcons: true,
        iconMap: {
          'danger': 'exclamation-triangle'
        },
        labelMap: {
          'draft': 'Draft'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'unknown' // Unmapped value
        }
      })

      // Should fall back to defaults
      expect(wrapper.vm.badgeType).toBe('info') // Default badge type
      expect(wrapper.vm.badgeClasses).toBe('bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') // Built-in info class
      expect(wrapper.vm.displayLabel).toBe('unknown') // Value itself
      expect(wrapper.vm.showIcon).toBe(false) // No icon for unmapped type
      expect(wrapper.vm.iconName).toBe(null)
    })
  })

  describe('CRUD Operations Integration', () => {
    it('handles create operation with badge field', async () => {
      const phpFieldConfig = createMockField({
        valueMap: {
          'draft': 'danger'
        },
        labelMap: {
          'draft': 'Draft'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: null // New record
        }
      })

      expect(wrapper.vm.displayLabel).toBe('')
      expect(wrapper.vm.badgeType).toBe('info')

      // Simulate setting initial value
      await wrapper.setProps({ modelValue: 'draft' })

      expect(wrapper.vm.displayLabel).toBe('Draft')
      expect(wrapper.vm.badgeType).toBe('danger')
    })

    it('handles read operation with badge field', async () => {
      const phpFieldConfig = createMockField({
        valueMap: {
          'published': 'success'
        },
        labelMap: {
          'published': 'Published'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'published' // Existing record
        }
      })

      expect(wrapper.vm.displayLabel).toBe('Published')
      expect(wrapper.vm.badgeType).toBe('success')
      expect(wrapper.find('.inline-flex').text()).toBe('Published')
    })

    it('handles update operation with badge field', async () => {
      const phpFieldConfig = createMockField({
        valueMap: {
          'draft': 'danger',
          'published': 'success'
        },
        labelMap: {
          'draft': 'Draft',
          'published': 'Published'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'draft' // Current value
        }
      })

      expect(wrapper.vm.displayLabel).toBe('Draft')
      expect(wrapper.vm.badgeType).toBe('danger')

      // Simulate update
      await wrapper.setProps({ modelValue: 'published' })

      expect(wrapper.vm.displayLabel).toBe('Published')
      expect(wrapper.vm.badgeType).toBe('success')
    })
  })

  describe('Validation Integration', () => {
    it('displays badge field correctly regardless of validation state', async () => {
      const phpFieldConfig = createMockField({
        valueMap: {
          'invalid': 'danger'
        },
        labelMap: {
          'invalid': 'Invalid Status'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'invalid',
          errors: ['Status is invalid'] // Validation errors
        }
      })

      // Badge field should still display correctly even with errors
      expect(wrapper.vm.displayLabel).toBe('Invalid Status')
      expect(wrapper.vm.badgeType).toBe('danger')
      expect(wrapper.find('.inline-flex').text()).toBe('Invalid Status')
    })
  })

  describe('Advanced Integration Scenarios', () => {
    it('handles dynamic field configuration changes', async () => {
      let phpFieldConfig = createMockField({
        valueMap: {
          'draft': 'danger'
        },
        labelMap: {
          'draft': 'Draft'
        }
      })

      wrapper = mount(BadgeField, {
        props: {
          field: phpFieldConfig,
          modelValue: 'draft'
        }
      })

      expect(wrapper.vm.displayLabel).toBe('Draft')

      // Simulate field configuration change (e.g., from PHP backend)
      phpFieldConfig = createMockField({
        valueMap: {
          'draft': 'warning'
        },
        labelMap: {
          'draft': 'Draft Article'
        }
      })

      await await wrapper.setProps({ field: phpFieldConfig })

      expect(wrapper.vm.displayLabel).toBe('Draft Article')
      expect(wrapper.vm.badgeType).toBe('warning')
    })

    it('handles complex Nova configuration from PHP', async () => {
      const phpFieldConfig = createMockField({
        valueMap: {
          'draft': 'danger',
          'review': 'warning',
          'published': 'success',
          'archived': 'info'
        },
        customTypes: {
          'danger': 'bg-red-50 text-red-700 ring-red-600/20 font-medium',
          'warning': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 font-medium',
          'success': 'bg-green-50 text-green-700 ring-green-600/20 font-medium'
        },
        withIcons: true,
        iconMap: {
          'danger': 'exclamation-triangle',
          'warning': 'exclamation-circle',
          'success': 'check-circle',
          'info': 'information-circle'
        },
        labelMap: {
          'draft': 'Draft Article',
          'review': 'Under Review',
          'published': 'Published Article',
          'archived': 'Archived Article'
        }
      })

      const testCases = [
        ['draft', 'danger', 'bg-red-50 text-red-700 ring-red-600/20 font-medium', 'Draft Article', 'exclamation-triangle'],
        ['review', 'warning', 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 font-medium', 'Under Review', 'exclamation-circle'],
        ['published', 'success', 'bg-green-50 text-green-700 ring-green-600/20 font-medium', 'Published Article', 'check-circle'],
        ['archived', 'info', 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200', 'Archived Article', 'information-circle']
      ]

      testCases.forEach(([value, expectedType, expectedClasses, expectedLabel, expectedIcon]) => {
        wrapper = mount(BadgeField, {
          props: {
            field: phpFieldConfig,
            modelValue: value
          }
        })

        expect(wrapper.vm.badgeType).toBe(expectedType)
        expect(wrapper.vm.badgeClasses).toBe(expectedClasses)
        expect(wrapper.vm.displayLabel).toBe(expectedLabel)
        expect(wrapper.vm.iconName).toBe(expectedIcon)
        expect(wrapper.vm.showIcon).toBe(true)
      })
    })
  })
})
