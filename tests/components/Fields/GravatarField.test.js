import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import GravatarField from '@/components/Fields/GravatarField.vue'
import { createMockField, mountField } from '../../helpers.js'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock btoa for email hashing
global.btoa = vi.fn((str) => Buffer.from(str).toString('base64'))

describe('GravatarField', () => {
  let wrapper
  let mockField

  beforeEach(() => {
    mockField = createMockField({
      name: 'Gravatar',
      attribute: 'gravatar',
      type: 'gravatar'
    })

    // Reset btoa mock
    global.btoa.mockClear()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Basic Rendering', () => {
    it('renders without email input when emailAttribute is provided', () => {
      const fieldWithEmailAttribute = createMockField({
        name: 'Gravatar',
        attribute: 'gravatar',
        emailAttribute: 'email'
      })

      wrapper = mountField(GravatarField, {
        field: fieldWithEmailAttribute,
        formData: { email: 'test@example.com' }
      })

      // Should not show email input when emailAttribute is provided
      expect(wrapper.find('input[type="email"]').exists()).toBe(false)
    })

    it('renders email input when no emailAttribute is provided', () => {
      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.exists()).toBe(true)
      expect(emailInput.attributes('placeholder')).toBe('Enter email for Gravatar...')
    })


  })

  describe('Gravatar URL Generation', () => {
    it('generates gravatar URL when email is provided', async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      await emailInput.trigger('input')

      // Should generate a gravatar URL
      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      const emittedUrl = wrapper.emitted('update:modelValue')[0][0]
      expect(emittedUrl).toContain('https://www.gravatar.com/avatar/')
      expect(emittedUrl).toContain('s=80') // default size
      expect(emittedUrl).toContain('d=mp') // default fallback
      expect(emittedUrl).toContain('r=g') // default rating
    })



    it('displays gravatar image when URL is generated', async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      await emailInput.trigger('input')
      await nextTick()

      const gravatarImg = wrapper.find('img')
      expect(gravatarImg.exists()).toBe(true)
      expect(gravatarImg.attributes('src')).toContain('gravatar.com/avatar')
      expect(gravatarImg.attributes('alt')).toBe(mockField.name)
    })

    it('shows email address in gravatar display', async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      await emailInput.trigger('input')
      await nextTick()

      expect(wrapper.text()).toContain('test@example.com')
    })
  })

  describe('Gravatar Options', () => {
    beforeEach(async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      // Set an email to enable gravatar display
      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      await emailInput.trigger('input')
      await nextTick()
    })

    it('shows options toggle button', () => {
      const toggleButton = wrapper.find('button')
      expect(toggleButton.exists()).toBe(true)
      expect(toggleButton.text()).toBe('Show Options')
    })

    it('toggles options visibility', async () => {
      const toggleButton = wrapper.find('button')

      // Initially options should be hidden
      expect(wrapper.find('.grid.grid-cols-1').exists()).toBe(false)

      // Click to show options
      await toggleButton.trigger('click')
      expect(wrapper.find('.grid.grid-cols-1').exists()).toBe(true)
      expect(toggleButton.text()).toBe('Hide Options')

      // Click to hide options
      await toggleButton.trigger('click')
      expect(wrapper.find('.grid.grid-cols-1').exists()).toBe(false)
      expect(toggleButton.text()).toBe('Show Options')
    })

    it('displays size selector with default value', async () => {
      const toggleButton = wrapper.find('button')
      await toggleButton.trigger('click')

      const selects = wrapper.findAll('select')
      const sizeSelect = selects[0]
      expect(sizeSelect.exists()).toBe(true)
      expect(sizeSelect.element.value).toBe('80') // default size

      const options = sizeSelect.findAll('option')
      expect(options).toHaveLength(4)
      expect(options[0].text()).toBe('40px')
      expect(options[1].text()).toBe('80px')
      expect(options[2].text()).toBe('120px')
      expect(options[3].text()).toBe('200px')
    })

    it('displays default fallback selector', async () => {
      const toggleButton = wrapper.find('button')
      await toggleButton.trigger('click')

      const selects = wrapper.findAll('select')
      const defaultSelect = selects[1]
      expect(defaultSelect.exists()).toBe(true)
      expect(defaultSelect.element.value).toBe('mp') // default fallback

      const options = defaultSelect.findAll('option')
      expect(options).toHaveLength(7)
      expect(options[0].text()).toBe('Mystery Person')
      expect(options[1].text()).toBe('Identicon')
    })

    it('displays rating selector', async () => {
      const toggleButton = wrapper.find('button')
      await toggleButton.trigger('click')

      const selects = wrapper.findAll('select')
      const ratingSelect = selects[2]
      expect(ratingSelect.exists()).toBe(true)
      expect(ratingSelect.element.value).toBe('g') // default rating

      const options = ratingSelect.findAll('option')
      expect(options).toHaveLength(4)
      expect(options[0].text()).toBe('G (General)')
      expect(options[1].text()).toBe('PG (Parental Guidance)')
    })

    it('updates gravatar URL when size changes', async () => {
      const toggleButton = wrapper.find('button')
      await toggleButton.trigger('click')

      const selects = wrapper.findAll('select')
      const sizeSelect = selects[0]
      await sizeSelect.setValue('120')
      await sizeSelect.trigger('change')

      const emittedValues = wrapper.emitted('update:modelValue')
      const lastEmittedUrl = emittedValues[emittedValues.length - 1][0]
      expect(lastEmittedUrl).toContain('s=120')
    })

    it('updates gravatar URL when default fallback changes', async () => {
      const toggleButton = wrapper.find('button')
      await toggleButton.trigger('click')

      const selects = wrapper.findAll('select')
      const defaultSelect = selects[1]
      await defaultSelect.setValue('identicon')
      await defaultSelect.trigger('change')

      const emittedValues = wrapper.emitted('update:modelValue')
      const lastEmittedUrl = emittedValues[emittedValues.length - 1][0]
      expect(lastEmittedUrl).toContain('d=identicon')
    })

    it('updates gravatar URL when rating changes', async () => {
      const toggleButton = wrapper.find('button')
      await toggleButton.trigger('click')

      const selects = wrapper.findAll('select')
      const ratingSelect = selects[2]
      await ratingSelect.setValue('pg')
      await ratingSelect.trigger('change')

      const emittedValues = wrapper.emitted('update:modelValue')
      const lastEmittedUrl = emittedValues[emittedValues.length - 1][0]
      expect(lastEmittedUrl).toContain('r=pg')
    })
  })

  describe('Refresh Functionality', () => {
    beforeEach(async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      // Set an email to enable gravatar display
      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      await emailInput.trigger('input')
      await nextTick()
    })

    it('shows refresh button', () => {
      const refreshButton = wrapper.findAll('button').find(btn => btn.text() === 'Refresh')
      expect(refreshButton).toBeTruthy()
    })


  })

  describe('Event Handling', () => {
    it('emits focus event', async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.trigger('focus')

      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event', async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.trigger('blur')

      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('emits change event when gravatar updates', async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      await emailInput.trigger('input')

      expect(wrapper.emitted('change')).toBeTruthy()
    })
  })

  describe('Image Error Handling', () => {
    it('handles image load errors', async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      await emailInput.trigger('input')
      await nextTick()

      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})

      const gravatarImg = wrapper.find('img')
      await gravatarImg.trigger('error')

      expect(consoleSpy).toHaveBeenCalledWith(
        'Gravatar image failed to load:',
        expect.any(String)
      )

      consoleSpy.mockRestore()
    })
  })

  describe('Theme Support', () => {
    it('applies dark theme classes when dark theme is active', () => {
      mockAdminStore.isDarkTheme = true

      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.classes()).toContain('admin-input-dark')
    })

    it('does not apply dark theme classes when light theme is active', () => {
      mockAdminStore.isDarkTheme = false

      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.classes()).not.toContain('admin-input-dark')
    })
  })

  describe('External Links', () => {
    beforeEach(async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      // Set an email to enable gravatar display
      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      await emailInput.trigger('input')
      await nextTick()
    })

    it('displays edit on gravatar link', () => {
      const editLink = wrapper.find('a[href*="gravatar.com"]')
      expect(editLink.exists()).toBe(true)
      expect(editLink.text()).toBe('Edit on Gravatar')
      expect(editLink.attributes('target')).toBe('_blank')
      expect(editLink.attributes('rel')).toBe('noopener noreferrer')
    })

    it('displays gravatar info with create link', () => {
      expect(wrapper.text()).toContain('Gravatar is a service that provides globally recognized avatars')

      const createLink = wrapper.find('a[href="https://gravatar.com"]')
      expect(createLink.exists()).toBe(true)
      expect(createLink.text()).toBe('Create or update your Gravatar')
    })
  })

  describe('Exposed Methods', () => {
    it('exposes focus method', () => {
      wrapper = mountField(GravatarField, { field: mockField })

      expect(wrapper.vm.focus).toBeDefined()
      expect(typeof wrapper.vm.focus).toBe('function')
    })

    it('focus method focuses the email input', async () => {
      wrapper = mountField(GravatarField, { field: mockField })

      const emailInput = wrapper.find('input[type="email"]')
      const focusSpy = vi.spyOn(emailInput.element, 'focus')

      wrapper.vm.focus()
      await nextTick()

      expect(focusSpy).toHaveBeenCalled()
    })
  })

  describe('Field Configuration', () => {
    it('uses custom field size when provided', () => {
      const fieldWithSize = createMockField({
        name: 'Gravatar',
        attribute: 'gravatar',
        size: 120
      })

      wrapper = mountField(GravatarField, { field: fieldWithSize })

      // Check that the component uses the custom size
      expect(wrapper.vm.localSize).toBe(120)
    })

    it('uses custom default fallback when provided', () => {
      const fieldWithDefault = createMockField({
        name: 'Gravatar',
        attribute: 'gravatar',
        defaultFallback: 'identicon'
      })

      wrapper = mountField(GravatarField, { field: fieldWithDefault })

      expect(wrapper.vm.localDefault).toBe('identicon')
    })

    it('uses custom rating when provided', () => {
      const fieldWithRating = createMockField({
        name: 'Gravatar',
        attribute: 'gravatar',
        rating: 'pg'
      })

      wrapper = mountField(GravatarField, { field: fieldWithRating })

      expect(wrapper.vm.localRating).toBe('pg')
    })
  })
})
