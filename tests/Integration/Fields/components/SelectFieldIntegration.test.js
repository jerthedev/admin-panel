import { describe, it, expect, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import SelectField from '@/components/Fields/SelectField.vue'
import BaseField from '@/components/Fields/BaseField.vue'
import { createMockField } from '../../../helpers.js'

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

describe('SelectField Integration', () => {
  let wrapper

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('receives PHP meta and renders correctly', () => {
    const phpField = createMockField({
      name: 'Status',
      attribute: 'status',
      component: 'SelectField',
      options: { draft: 'Draft', published: 'Published' },
      searchable: true,
      displayUsingLabels: true,
      required: true,
      helpText: 'Choose a status'
    })

    wrapper = mount(SelectField, {
      props: {
        field: phpField,
        modelValue: 'draft',
        errors: []
      },
      global: { components: { BaseField } }
    })

    // Renders the searchable version
    expect(wrapper.find('button').exists()).toBe(true)
    expect(wrapper.find('select').exists()).toBe(false)

    // Displays selected label in button
    const button = wrapper.find('button')
    expect(button.text()).toContain('Draft')
  })

  it('processes non-searchable mode', () => {
    const phpField = createMockField({
      name: 'Status',
      attribute: 'status',
      component: 'SelectField',
      options: { draft: 'Draft', published: 'Published' },
      searchable: false
    })

    wrapper = mount(SelectField, {
      props: {
        field: phpField,
        modelValue: 'published',
        errors: []
      },
      global: { components: { BaseField } }
    })

    // Renders simple select (no searchable trigger button)
    expect(wrapper.find('select').exists()).toBe(true)
    expect(wrapper.find('button.admin-input').exists()).toBe(false)

    // Displays selected option
    const select = wrapper.find('select')
    expect(select.element.value).toBe('published')
  })
})

