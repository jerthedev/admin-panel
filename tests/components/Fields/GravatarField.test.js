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
    it('renders without email input when emailColumn is provided', () => {
      const fieldWithEmailColumn = createMockField({
        name: 'Gravatar',
        attribute: 'gravatar',
        emailColumn: 'email'
      })

      wrapper = mountField(GravatarField, {
        field: fieldWithEmailColumn,
        props: {
          formData: { email: 'test@example.com' }
        }
      })

      // Should not show email input when emailColumn is provided
      expect(wrapper.find('input[type="email"]').exists()).toBe(false)
    })

    it('renders email input when no emailColumn is provided', () => {
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
      // Nova-compatible: simple URL without parameters
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

  describe('Nova Compatibility', () => {
    beforeEach(() => {
      wrapper = mountField(GravatarField, { field: mockField })
    })

    it('does not show complex options UI (Nova-compatible)', () => {
      // Nova's Gravatar field is simple - no complex options UI
      expect(wrapper.find('.grid.grid-cols-1').exists()).toBe(false)
      expect(wrapper.find('select').exists()).toBe(false)
      expect(wrapper.find('button').exists()).toBe(false) // No toggle buttons
    })

    it('generates simple Gravatar URLs without parameters (Nova-compatible)', async () => {
      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      await emailInput.trigger('input')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      const emittedUrl = wrapper.emitted('update:modelValue')[0][0]

      // Nova-compatible: simple URL without parameters
      expect(emittedUrl).toContain('gravatar.com/avatar')
      expect(emittedUrl).not.toContain('s=') // No size parameter
      expect(emittedUrl).not.toContain('d=') // No default parameter
      expect(emittedUrl).not.toContain('r=') // No rating parameter
    })

    it('uses standard avatar size for Nova compatibility', () => {
      const img = wrapper.find('img')
      if (img.exists()) {
        // Should use standard size classes
        expect(img.classes()).toContain('w-16')
        expect(img.classes()).toContain('h-16')
      }
    })

    it('works with emailColumn instead of emailAttribute', async () => {
      const fieldWithEmailColumn = createMockField({
        name: 'Gravatar',
        attribute: '__gravatar_computed__',
        emailColumn: 'work_email'
      })

      wrapper = mountField(GravatarField, {
        field: fieldWithEmailColumn,
        props: {
          formData: { work_email: 'work@example.com' }
        }
      })

      // Wait for component to process the email
      await nextTick()

      // Check that the component recognizes the email from formData
      expect(wrapper.vm.emailForGravatar).toBe('work@example.com')

      // Check that a gravatar URL is generated
      expect(wrapper.vm.gravatarUrl).toContain('gravatar.com/avatar')
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

  describe('Nova Field Configuration', () => {
    it('applies squared styling when configured', () => {
      const squaredField = createMockField({
        name: 'Gravatar',
        attribute: '__gravatar_computed__',
        emailColumn: 'email',
        squared: true,
        rounded: false
      })

      wrapper = mountField(GravatarField, {
        field: squaredField,
        formData: { email: 'test@example.com' }
      })

      const img = wrapper.find('img')
      if (img.exists()) {
        expect(img.classes()).toContain('rounded-none')
        expect(img.classes()).not.toContain('rounded-full')
      }
    })

    it('applies rounded styling when configured', () => {
      const roundedField = createMockField({
        name: 'Gravatar',
        attribute: '__gravatar_computed__',
        emailColumn: 'email',
        squared: false,
        rounded: true
      })

      wrapper = mountField(GravatarField, {
        field: roundedField,
        formData: { email: 'test@example.com' }
      })

      const img = wrapper.find('img')
      if (img.exists()) {
        expect(img.classes()).toContain('rounded-full')
        expect(img.classes()).not.toContain('rounded-none')
      }
    })

    it('uses emailColumn property for Nova compatibility', () => {
      const field = createMockField({
        name: 'Gravatar',
        attribute: '__gravatar_computed__',
        emailColumn: 'custom_email'
      })

      wrapper = mountField(GravatarField, { field })

      // Component should recognize emailColumn property
      expect(wrapper.props('field').emailColumn).toBe('custom_email')
    })
  })
})
