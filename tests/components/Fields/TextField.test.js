import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import TextField from '@/components/Fields/TextField.vue'
import { createMockField, mountField, triggerInput } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  ChevronDownIcon: { template: '<div data-testid="chevron-down-icon"></div>' }
}))

describe('TextField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Test Field',
      attribute: 'test_field',
      placeholder: 'Enter text here',
      type: 'text'
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders text input field', () => {
      wrapper = mountField(TextField, { field: mockField })

      const input = wrapper.find('input[type="text"]')
      expect(input.exists()).toBe(true)
      expect(input.attributes('placeholder')).toBe('Enter text here')
    })

    it('renders with model value', () => {
      wrapper = mountField(TextField, {
        field: mockField,
        modelValue: 'Test Value'
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('Test Value')
    })

    it('uses field name as placeholder when no placeholder provided', () => {
      const fieldWithoutPlaceholder = createMockField({
        name: 'Field Name',
        attribute: 'field_name'
      })

      wrapper = mountField(TextField, { field: fieldWithoutPlaceholder })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('Field Name')
    })

    it('applies disabled state', () => {
      wrapper = mountField(TextField, {
        field: mockField,
        props: {
          field: mockField,
          disabled: true
        }
      })

      const input = wrapper.find('input')
      expect(input.element.disabled).toBe(true)
    })

    it('applies readonly state', () => {
      wrapper = mountField(TextField, {
        field: mockField,
        props: {
          field: mockField,
          readonly: true
        }
      })

      const input = wrapper.find('input')
      expect(input.element.readOnly).toBe(true)
    })
  })

  describe('Password Mode', () => {
    it('renders password input when asPassword is true', () => {
      const passwordField = createMockField({
        name: 'Password',
        attribute: 'password',
        asPassword: true
      })

      wrapper = mountField(TextField, { field: passwordField })

      const input = wrapper.find('input[type="password"]')
      expect(input.exists()).toBe(true)
    })

    it('hides suggestions in password mode', () => {
      const passwordField = createMockField({
        name: 'Password',
        attribute: 'password',
        asPassword: true,
        suggestions: ['suggestion1', 'suggestion2']
      })

      wrapper = mountField(TextField, { field: passwordField })

      expect(wrapper.find('button').exists()).toBe(false)
    })

    it('hides character count in password mode', () => {
      const passwordField = createMockField({
        name: 'Password',
        attribute: 'password',
        asPassword: true,
        maxLength: 20
      })

      wrapper = mountField(TextField, { field: passwordField })

      // Character count should not be shown in password mode
      expect(wrapper.text()).not.toContain('/')
    })
  })

  describe('Character Limit', () => {
    it('displays character count when maxLength is set', () => {
      const fieldWithLimit = createMockField({
        name: 'Limited Field',
        attribute: 'limited_field',
        maxLength: 50
      })

      wrapper = mountField(TextField, {
        field: fieldWithLimit,
        modelValue: 'Test text'
      })

      // Character count should be displayed
      expect(wrapper.text()).toContain('9/50')
    })

    it('applies warning color when approaching limit', () => {
      const fieldWithLimit = createMockField({
        name: 'Limited Field',
        attribute: 'limited_field',
        maxLength: 10
      })

      wrapper = mountField(TextField, {
        field: fieldWithLimit,
        modelValue: '12345678' // 8 chars, 80% of 10 (> 70%)
      })

      const characterCountSpan = wrapper.find('span.text-xs')
      expect(characterCountSpan.classes()).toContain('text-amber-500')
    })

    it('applies danger color when near limit', () => {
      const fieldWithLimit = createMockField({
        name: 'Limited Field',
        attribute: 'limited_field',
        maxLength: 10
      })

      wrapper = mountField(TextField, {
        field: fieldWithLimit,
        modelValue: '1234567890' // 10 chars, 100% of 10 (> 90%)
      })

      const characterCountSpan = wrapper.find('span.text-xs')
      expect(characterCountSpan.classes()).toContain('text-red-500')
    })

    it('enforces maxLength on input', async () => {
      const fieldWithLimit = createMockField({
        name: 'Limited Field',
        attribute: 'limited_field',
        maxLength: 5
      })

      wrapper = mountField(TextField, { field: fieldWithLimit })

      const input = wrapper.find('input')
      await input.setValue('123456789') // 9 chars, should be truncated to 5
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('12345')
    })
  })

  describe('Suggestions', () => {
    it('shows suggestions button when suggestions are available', () => {
      const fieldWithSuggestions = createMockField({
        name: 'Field with Suggestions',
        attribute: 'field_suggestions',
        suggestions: ['suggestion1', 'suggestion2', 'suggestion3']
      })

      wrapper = mountField(TextField, { field: fieldWithSuggestions })

      const suggestionsButton = wrapper.find('button')
      expect(suggestionsButton.exists()).toBe(true)
    })

    it('toggles suggestions dropdown on button click', async () => {
      const fieldWithSuggestions = createMockField({
        name: 'Field with Suggestions',
        attribute: 'field_suggestions',
        suggestions: ['suggestion1', 'suggestion2']
      })

      wrapper = mountField(TextField, { field: fieldWithSuggestions })

      const suggestionsButton = wrapper.find('button')
      await suggestionsButton.trigger('click')

      // Check for the dropdown container with suggestions
      expect(wrapper.find('.absolute.z-10').exists()).toBe(true)
    })

    it('filters suggestions based on input value', async () => {
      const fieldWithSuggestions = createMockField({
        name: 'Field with Suggestions',
        attribute: 'field_suggestions',
        suggestions: ['apple', 'banana', 'apricot', 'cherry']
      })

      wrapper = mountField(TextField, {
        field: fieldWithSuggestions,
        modelValue: 'ap'
      })

      const suggestionsButton = wrapper.find('button')
      await suggestionsButton.trigger('click')

      const suggestionItems = wrapper.findAll('.cursor-pointer')
      expect(suggestionItems).toHaveLength(2) // 'apple' and 'apricot'
    })

    it('selects suggestion on click', async () => {
      const fieldWithSuggestions = createMockField({
        name: 'Field with Suggestions',
        attribute: 'field_suggestions',
        suggestions: ['suggestion1', 'suggestion2']
      })

      wrapper = mountField(TextField, { field: fieldWithSuggestions })

      const suggestionsButton = wrapper.find('button')
      await suggestionsButton.trigger('click')

      const firstSuggestion = wrapper.find('.cursor-pointer')
      await firstSuggestion.trigger('click')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('suggestion1')
      expect(wrapper.emitted('change')[0][0]).toBe('suggestion1')
    })

    it('shows "No suggestions found" when no matches', async () => {
      const fieldWithSuggestions = createMockField({
        name: 'Field with Suggestions',
        attribute: 'field_suggestions',
        suggestions: ['apple', 'banana']
      })

      wrapper = mountField(TextField, {
        field: fieldWithSuggestions,
        modelValue: 'xyz'
      })

      const suggestionsButton = wrapper.find('button')
      await suggestionsButton.trigger('click')

      expect(wrapper.text()).toContain('No suggestions found')
    })
  })

  describe('Event Handling', () => {
    it('emits update:modelValue on input', async () => {
      wrapper = mountField(TextField, { field: mockField })

      const input = wrapper.find('input')
      await input.setValue('New Value')
      await input.trigger('input')

      expect(wrapper.emitted('update:modelValue')[0][0]).toBe('New Value')
      expect(wrapper.emitted('change')[0][0]).toBe('New Value')
    })

    it('emits focus event', async () => {
      wrapper = mountField(TextField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(TextField, { field: mockField })

      const input = wrapper.find('input')
      await input.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('handles arrow down key to show suggestions', async () => {
      const fieldWithSuggestions = createMockField({
        name: 'Field with Suggestions',
        attribute: 'field_suggestions',
        suggestions: ['suggestion1', 'suggestion2']
      })

      wrapper = mountField(TextField, { field: fieldWithSuggestions })

      const input = wrapper.find('input')
      await input.trigger('keydown', { key: 'ArrowDown' })

      expect(wrapper.find('.absolute.z-10').exists()).toBe(true)
    })

    it('handles escape key to hide suggestions and blur', async () => {
      const fieldWithSuggestions = createMockField({
        name: 'Field with Suggestions',
        attribute: 'field_suggestions',
        suggestions: ['suggestion1', 'suggestion2']
      })

      wrapper = mountField(TextField, { field: fieldWithSuggestions })

      // First show suggestions
      const suggestionsButton = wrapper.find('button')
      await suggestionsButton.trigger('click')

      // Then press escape
      const input = wrapper.find('input')
      await input.trigger('keydown', { key: 'Escape' })

      await nextTick()
      expect(wrapper.find('.absolute.z-10').exists()).toBe(false)
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(TextField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).toContain('admin-input-dark')
    })

    it('does not apply dark theme classes when light theme is active', () => {
      mockAdminStore.isDarkTheme = false

      wrapper = mountField(TextField, { field: mockField })

      const input = wrapper.find('input')
      expect(input.classes()).not.toContain('admin-input-dark')
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(TextField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the input', async () => {
      wrapper = mountField(TextField, { field: mockField })

      const input = wrapper.find('input')
      const focusSpy = vi.spyOn(input.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })
})
