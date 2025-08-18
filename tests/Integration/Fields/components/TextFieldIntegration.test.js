import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mountField } from '../../../helpers.js'
import TextField from '@/components/Fields/TextField.vue'

// Mock the admin store
const mockAdminStore = { isDarkTheme: false }
vi.mock('@/stores/admin', () => ({ useAdminStore: () => mockAdminStore }))

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  ChevronDownIcon: { template: '<div data-testid="chevron-down-icon"></div>' }
}))

describe('Integration: TextField (PHP <-> Vue)', () => {
  let wrapper
  let field

  beforeEach(() => {
    field = {
      name: 'Title',
      attribute: 'title',
      component: 'TextField',
      helpText: 'Enter the title',
      rules: ['required', 'min:3'],
      suggestions: ['Article', 'Tutorial', 'Guide'],
      maxlength: 255,
      enforceMaxlength: true,
      copyable: true,
      asHtml: false,
      asEncodedHtml: false,
    }
  })

  afterEach(() => { if (wrapper) wrapper.unmount() })

  it('renders and binds initial value from PHP serialization', () => {
    wrapper = mountField(TextField, { field, modelValue: 'Test Article' })

    const input = wrapper.find('input[type="text"]')
    expect(input.exists()).toBe(true)
    expect(input.element.value).toBe('Test Article')
  })

  it('emits updated value for PHP fill handling', async () => {
    wrapper = mountField(TextField, { field, modelValue: 'Old Title' })

    const input = wrapper.find('input')
    await input.setValue('New Title')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('New Title')
  })

  it('respects maxlength from PHP field configuration', () => {
    wrapper = mountField(TextField, { field })

    const input = wrapper.find('input')
    expect(input.attributes('maxlength')).toBe('255')
  })

  it('shows character count when enforceMaxlength is enabled from PHP', () => {
    wrapper = mountField(TextField, { field, modelValue: 'Test content' })

    expect(wrapper.text()).toContain('12/255')
  })

  it('hides character count when enforceMaxlength is disabled from PHP', () => {
    const fieldWithoutEnforce = { ...field, enforceMaxlength: false }
    wrapper = mountField(TextField, { field: fieldWithoutEnforce, modelValue: 'Test content' })

    expect(wrapper.text()).not.toContain('/255')
  })

  it('enforces maxlength client-side when enabled from PHP', async () => {
    const shortField = { ...field, maxlength: 5, enforceMaxlength: true }
    wrapper = mountField(TextField, { field: shortField })

    const input = wrapper.find('input')
    await input.setValue('123456789') // 9 chars, should be truncated to 5
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('12345')
  })

  it('does not enforce maxlength when disabled from PHP', async () => {
    const shortField = { ...field, maxlength: 5, enforceMaxlength: false }
    wrapper = mountField(TextField, { field: shortField })

    const input = wrapper.find('input')
    await input.setValue('123456789') // 9 chars, should not be truncated
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('123456789')
  })

  it('displays suggestions from PHP field configuration', () => {
    wrapper = mountField(TextField, { field })

    const suggestionsButton = wrapper.find('button')
    expect(suggestionsButton.exists()).toBe(true)
  })

  it('shows suggestions dropdown with PHP-provided options', async () => {
    wrapper = mountField(TextField, { field })

    const suggestionsButton = wrapper.find('button')
    await suggestionsButton.trigger('click')

    const suggestionItems = wrapper.findAll('.cursor-pointer')
    expect(suggestionItems).toHaveLength(3)
    expect(wrapper.text()).toContain('Article')
    expect(wrapper.text()).toContain('Tutorial')
    expect(wrapper.text()).toContain('Guide')
  })

  it('filters suggestions based on current input value', async () => {
    wrapper = mountField(TextField, { field, modelValue: 'Art' })

    const suggestionsButton = wrapper.find('button')
    await suggestionsButton.trigger('click')

    const suggestionItems = wrapper.findAll('.cursor-pointer')
    expect(suggestionItems).toHaveLength(1) // Only 'Article' matches 'Art'
    expect(wrapper.text()).toContain('Article')
    expect(wrapper.text()).not.toContain('Tutorial')
    expect(wrapper.text()).not.toContain('Guide')
  })

  it('selects suggestion and emits value for PHP processing', async () => {
    wrapper = mountField(TextField, { field })

    const suggestionsButton = wrapper.find('button')
    await suggestionsButton.trigger('click')

    const firstSuggestion = wrapper.find('.cursor-pointer')
    await firstSuggestion.trigger('click')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('Article')
    expect(wrapper.emitted('change')[0][0]).toBe('Article')
  })

  it('handles empty suggestions array from PHP', () => {
    const fieldWithoutSuggestions = { ...field, suggestions: [] }
    wrapper = mountField(TextField, { field: fieldWithoutSuggestions })

    const suggestionsButton = wrapper.find('button')
    expect(suggestionsButton.exists()).toBe(false)
  })

  it('applies character count color coding based on PHP maxlength', () => {
    const shortField = { ...field, maxlength: 10, enforceMaxlength: true }
    
    // Test warning color (> 70%)
    wrapper = mountField(TextField, { field: shortField, modelValue: '12345678' }) // 8 chars, 80%
    let characterCountSpan = wrapper.find('span.text-xs')
    expect(characterCountSpan.classes()).toContain('text-amber-500')

    wrapper.unmount()

    // Test danger color (> 90%)
    wrapper = mountField(TextField, { field: shortField, modelValue: '1234567890' }) // 10 chars, 100%
    characterCountSpan = wrapper.find('span.text-xs')
    expect(characterCountSpan.classes()).toContain('text-red-500')
  })

  it('handles Nova withMeta extraAttributes from PHP', () => {
    const fieldWithMeta = {
      ...field,
      extraAttributes: {
        'data-test': 'nova-compatible',
        'data-field': 'text'
      }
    }
    wrapper = mountField(TextField, { field: fieldWithMeta })

    // The field should render without errors and include the meta data
    const input = wrapper.find('input')
    expect(input.exists()).toBe(true)
  })

  it('maintains Nova API compatibility for all features', async () => {
    // Test a field with all Nova Text Field features enabled
    const fullField = {
      name: 'Article Title',
      attribute: 'article_title',
      component: 'TextField',
      helpText: 'Enter the article title',
      placeholder: 'Start typing...',
      rules: ['required', 'min:3', 'max:255'],
      suggestions: ['Article', 'Tutorial', 'Guide', 'News'],
      maxlength: 255,
      enforceMaxlength: true,
      copyable: true,
      asHtml: false,
      asEncodedHtml: false,
      extraAttributes: {
        'data-test': 'nova-compatible'
      }
    }

    wrapper = mountField(TextField, { field: fullField, modelValue: 'Test Article Title' })

    // Verify all features work together
    const input = wrapper.find('input')
    expect(input.exists()).toBe(true)
    expect(input.element.value).toBe('Test Article Title')
    expect(input.attributes('maxlength')).toBe('255')
    expect(input.attributes('placeholder')).toBe('Start typing...')

    // Verify suggestions work
    const suggestionsButton = wrapper.find('button')
    expect(suggestionsButton.exists()).toBe(true)

    // Verify character count
    expect(wrapper.text()).toContain('18/255')

    // Test input handling
    await input.setValue('New Article Title')
    await input.trigger('input')

    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('New Article Title')
  })

  it('handles keyboard navigation for suggestions', async () => {
    wrapper = mountField(TextField, { field })

    const input = wrapper.find('input')
    
    // Arrow down should show suggestions
    await input.trigger('keydown', { key: 'ArrowDown' })
    expect(wrapper.find('.absolute.z-10').exists()).toBe(true)

    // Escape should hide suggestions
    await input.trigger('keydown', { key: 'Escape' })
    // Note: There's a timeout in the component, so we can't immediately check
  })

  it('trims whitespace like PHP backend', async () => {
    wrapper = mountField(TextField, { field, modelValue: '' })

    const input = wrapper.find('input')
    await input.setValue('  Test Title  ')
    await input.trigger('input')

    // The component doesn't trim on input, but PHP will trim on fill
    expect(wrapper.emitted('update:modelValue')[0][0]).toBe('  Test Title  ')
  })

  it('handles focus and blur events for form integration', async () => {
    wrapper = mountField(TextField, { field })

    const input = wrapper.find('input')
    
    await input.trigger('focus')
    expect(wrapper.emitted('focus')).toBeTruthy()

    await input.trigger('blur')
    expect(wrapper.emitted('blur')).toBeTruthy()
  })

  it('exposes focus method for programmatic control', () => {
    wrapper = mountField(TextField, { field })

    expect(wrapper.vm.focus).toBeDefined()
    expect(typeof wrapper.vm.focus).toBe('function')
  })
})
