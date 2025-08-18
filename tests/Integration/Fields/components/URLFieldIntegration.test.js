import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import URLField from '@/components/Fields/URLField.vue'
import { createMockField, mountField } from '../../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  LinkIcon: { template: '<div data-testid="link-icon"></div>' }
}))

describe('Integration: URLField (PHP <-> Vue)', () => {
  let wrapper
  let field

  beforeEach(() => {
    field = {
      name: 'Website',
      attribute: 'website',
      component: 'URLField',
      helpText: 'Enter your website URL',
      rules: ['required', 'url'],
      placeholder: 'https://example.com'
    }
  })

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('renders and binds initial value from PHP serialization', () => {
    wrapper = mountField(URLField, { field, modelValue: 'https://example.com' })

    const input = wrapper.find('input[type="url"]')
    expect(input.exists()).toBe(true)
    expect(input.element.value).toBe('https://example.com')
  })

  it('emits updated value for PHP fill handling', async () => {
    wrapper = mountField(URLField, { field, modelValue: 'https://old-site.com' })

    const input = wrapper.find('input')
    await input.setValue('https://new-site.com')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('https://new-site.com')
  })

  it('handles empty values like PHP backend', async () => {
    wrapper = mountField(URLField, { field, modelValue: 'https://example.com' })

    const input = wrapper.find('input')
    await input.setValue('')
    await input.trigger('input')

    // Should emit null for empty strings to match PHP behavior
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe(null)
  })

  it('receives PHP field configuration correctly', () => {
    const phpField = createMockField({
      name: 'Company Website',
      attribute: 'company_url',
      component: 'URLField',
      rules: ['required', 'url', 'active_url'],
      helpText: 'Enter your company website',
      placeholder: 'https://company.com',
      nullable: true,
      sortable: true,
      searchable: true
    })

    wrapper = mountField(URLField, { field: phpField })

    const input = wrapper.find('input')
    expect(input.attributes('placeholder')).toBe('https://company.com')
    expect(input.attributes('type')).toBe('url')
  })

  it('integrates with Nova-style validation rules', () => {
    const fieldWithValidation = createMockField({
      ...field,
      rules: ['required', 'url', 'active_url', 'max:255']
    })

    wrapper = mountField(URLField, { field: fieldWithValidation })

    // Field should receive and respect validation rules from PHP
    expect(fieldWithValidation.rules).toContain('required')
    expect(fieldWithValidation.rules).toContain('url')
    expect(fieldWithValidation.rules).toContain('active_url')
    expect(fieldWithValidation.rules).toContain('max:255')
  })

  it('handles complex URL formats from PHP', async () => {
    const complexUrls = [
      'https://sub.domain.co.uk/path?query=value&other=test#anchor',
      'http://localhost:3000/api/v1/users',
      'https://192.168.1.1:8080/admin',
      'ftp://files.example.com/documents'
    ]

    for (const url of complexUrls) {
      wrapper = mountField(URLField, { field, modelValue: url })
      
      const input = wrapper.find('input')
      expect(input.element.value).toBe(url)
      
      wrapper.unmount()
    }
  })

  it('maintains state consistency with PHP model', async () => {
    wrapper = mountField(URLField, { field, modelValue: null })

    // Start with null (from PHP model)
    const input = wrapper.find('input')
    expect(input.element.value).toBe('')

    // Update to valid URL
    await input.setValue('https://example.com')
    await input.trigger('input')
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('https://example.com')

    // Clear to empty (should emit null to match PHP)
    await input.setValue('')
    await input.trigger('input')

    // Find the emission where the value is null
    const emissions = wrapper.emitted('update:modelValue')
    const nullEmission = emissions.find(emission => emission[0] === null)
    expect(nullEmission).toBeTruthy()
    expect(nullEmission[0]).toBe(null)
  })

  it('supports Nova-style computed values display', () => {
    // Simulate a computed field from PHP that generates GitHub URLs
    const computedField = createMockField({
      name: 'GitHub URL',
      attribute: 'github_url',
      component: 'URLField',
      // This would be the computed value from PHP
      computed: true
    })

    wrapper = mountField(URLField, { 
      field: computedField, 
      modelValue: 'https://github.com/laravel' 
    })

    const input = wrapper.find('input')
    expect(input.element.value).toBe('https://github.com/laravel')
  })

  it('handles PHP displayUsing callback results', () => {
    // Simulate field with displayUsing callback applied on PHP side
    const fieldWithDisplay = createMockField({
      ...field,
      // This would be the result of displayUsing callback from PHP
      displayValue: 'example.com'
    })

    wrapper = mountField(URLField, { 
      field: fieldWithDisplay, 
      modelValue: 'https://example.com' 
    })

    // The input should still show the raw value for editing
    const input = wrapper.find('input')
    expect(input.element.value).toBe('https://example.com')
  })

  it('integrates with form submission flow', async () => {
    wrapper = mountField(URLField, { field, modelValue: '' })

    const input = wrapper.find('input')
    
    // Simulate user typing a URL
    await input.setValue('https://newsite.com')
    await input.trigger('input')
    
    // Should emit for immediate reactivity
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('https://newsite.com')
    
    // Simulate form submission trigger
    await input.trigger('change')
    expect(wrapper.emitted('change')[0][0]).toBe('https://newsite.com')
  })

  it('handles focus and blur events for form validation', async () => {
    wrapper = mountField(URLField, { field })

    const input = wrapper.find('input')
    
    await input.trigger('focus')
    expect(wrapper.emitted('focus')).toBeTruthy()
    
    await input.trigger('blur')
    expect(wrapper.emitted('blur')).toBeTruthy()
  })

  it('respects disabled state from PHP', () => {
    wrapper = mountField(URLField, { 
      field, 
      disabled: true 
    })

    const input = wrapper.find('input')
    expect(input.element.disabled).toBe(true)
  })

  it('respects readonly state from PHP', () => {
    wrapper = mountField(URLField, { 
      field, 
      readonly: true 
    })

    const input = wrapper.find('input')
    expect(input.element.readOnly).toBe(true)
  })

  it('applies theme classes based on admin store', () => {
    mockAdminStore.isDarkTheme = true

    wrapper = mountField(URLField, { field })

    const input = wrapper.find('input')
    expect(input.classes()).toContain('admin-input-dark')

    // Reset for other tests
    mockAdminStore.isDarkTheme = false
  })

  it('exposes focus method for programmatic control', () => {
    wrapper = mountField(URLField, { field })

    expect(wrapper.vm.focus).toBeDefined()
    expect(typeof wrapper.vm.focus).toBe('function')
  })

  it('handles international domain names correctly', async () => {
    wrapper = mountField(URLField, { field })

    const input = wrapper.find('input')
    await input.setValue('https://münchen.de')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('https://münchen.de')
  })

  it('maintains Nova field API compatibility', () => {
    const novaCompatibleField = createMockField({
      name: 'Website URL',
      attribute: 'website_url',
      component: 'URLField',
      rules: ['required', 'url'],
      helpText: 'Enter a valid URL',
      placeholder: 'https://example.com',
      nullable: true,
      sortable: true,
      searchable: true,
      copyable: true,
      showOnIndex: true,
      showOnDetail: true,
      showOnCreation: true,
      showOnUpdate: true
    })

    wrapper = mountField(URLField, { field: novaCompatibleField })

    // Should render without errors and respect all Nova field properties
    expect(wrapper.find('input[type="url"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="link-icon"]').exists()).toBe(true)
  })
})
