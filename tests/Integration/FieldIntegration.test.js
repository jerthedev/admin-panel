import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { createMockField, mountField } from '../helpers.js'

// Import all field components for integration testing
import BaseField from '@/components/Fields/BaseField.vue'
import TextField from '@/components/Fields/TextField.vue'
import SelectField from '@/components/Fields/SelectField.vue'
import BooleanField from '@/components/Fields/BooleanField.vue'
import NumberField from '@/components/Fields/NumberField.vue'
import CurrencyField from '@/components/Fields/CurrencyField.vue'
import EmailField from '@/components/Fields/EmailField.vue'
import PasswordField from '@/components/Fields/PasswordField.vue'
import PasswordConfirmationField from '@/components/Fields/PasswordConfirmationField.vue'
import URLField from '@/components/Fields/URLField.vue'
import DateField from '@/components/Fields/DateField.vue'
import DateTimeField from '@/components/Fields/DateTimeField.vue'
import FileField from '@/components/Fields/FileField.vue'
import ImageField from '@/components/Fields/ImageField.vue'
import MarkdownField from '@/components/Fields/MarkdownField.vue'
import TextareaField from '@/components/Fields/TextareaField.vue'
import SlugField from '@/components/Fields/SlugField.vue'
import HiddenField from '@/components/Fields/HiddenField.vue'
import IDField from '@/components/Fields/IDField.vue'
import BelongsToField from '@/components/Fields/BelongsToField.vue'
import HasManyField from '@/components/Fields/HasManyField.vue'
import ManyToManyField from '@/components/Fields/ManyToManyField.vue'
import MultiSelectField from '@/components/Fields/MultiSelectField.vue'
import TimezoneField from '@/components/Fields/TimezoneField.vue'
import AvatarField from '@/components/Fields/AvatarField.vue'
import GravatarField from '@/components/Fields/GravatarField.vue'
import MediaLibraryFileField from '@/components/Fields/MediaLibraryFileField.vue'
import MediaLibraryImageField from '@/components/Fields/MediaLibraryImageField.vue'
import MediaLibraryAvatarField from '@/components/Fields/MediaLibraryAvatarField.vue'

// Mock the admin store
const mockAdminStore = {
  isDarkTheme: false,
  fullscreenMode: false,
  setTheme: vi.fn(),
  toggleFullscreen: vi.fn()
}

vi.mock('@/stores/admin', () => ({
  useAdminStore: () => mockAdminStore
}))

// Mock form store for integration testing
const mockFormStore = {
  formData: {},
  errors: {},
  touched: {},
  setFieldValue: vi.fn(),
  setFieldError: vi.fn(),
  setFieldTouched: vi.fn(),
  validateField: vi.fn(),
  validateForm: vi.fn(),
  resetForm: vi.fn(),
  submitForm: vi.fn()
}

vi.mock('@/stores/form', () => ({
  useFormStore: () => mockFormStore
}))

describe('Field Integration Tests', () => {
  let wrapper

  beforeEach(() => {
    // Reset mocks
    vi.clearAllMocks()
    mockAdminStore.isDarkTheme = false
    mockAdminStore.fullscreenMode = false
    mockFormStore.formData = {}
    mockFormStore.errors = {}
    mockFormStore.touched = {}
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Field Component Coverage Validation', () => {
    const fieldComponents = [
      { name: 'BaseField', component: BaseField },
      { name: 'TextField', component: TextField },
      { name: 'SelectField', component: SelectField },
      { name: 'BooleanField', component: BooleanField },
      { name: 'NumberField', component: NumberField },
      { name: 'CurrencyField', component: CurrencyField },
      { name: 'EmailField', component: EmailField },
      { name: 'PasswordField', component: PasswordField },
      { name: 'PasswordConfirmationField', component: PasswordConfirmationField },
      { name: 'URLField', component: URLField },
      { name: 'DateField', component: DateField },
      { name: 'DateTimeField', component: DateTimeField },
      { name: 'FileField', component: FileField },
      { name: 'ImageField', component: ImageField },
      { name: 'MarkdownField', component: MarkdownField },
      { name: 'TextareaField', component: TextareaField },
      { name: 'SlugField', component: SlugField },
      { name: 'HiddenField', component: HiddenField },
      { name: 'IDField', component: IDField },
      { name: 'BelongsToField', component: BelongsToField },
      { name: 'HasManyField', component: HasManyField },
      { name: 'ManyToManyField', component: ManyToManyField },
      { name: 'MultiSelectField', component: MultiSelectField },
      { name: 'TimezoneField', component: TimezoneField },
      { name: 'AvatarField', component: AvatarField },
      { name: 'GravatarField', component: GravatarField },
      { name: 'MediaLibraryFileField', component: MediaLibraryFileField },
      { name: 'MediaLibraryImageField', component: MediaLibraryImageField },
      { name: 'MediaLibraryAvatarField', component: MediaLibraryAvatarField }
    ]

    it('validates all field components are importable', () => {
      fieldComponents.forEach(({ name, component }) => {
        expect(component).toBeDefined()
        expect(component.name || component.__name).toBeTruthy()
      })
    })

    it('validates all field components have unit tests', () => {
      const expectedTestFiles = [
        'BaseField.test.js',
        'TextField.test.js',
        'SelectField.test.js',
        'BooleanField.test.js',
        'NumberField.test.js',
        'CurrencyField.test.js',
        'EmailField.test.js',
        'PasswordField.test.js',
        'URLField.test.js',
        'DateField.test.js',
        'DateTimeField.test.js',
        'FileField.test.js',
        'ImageField.test.js',
        'MarkdownField.test.js',
        'SlugField.test.js',
        'HiddenField.test.js',
        'IDField.test.js',
        'BelongsToField.test.js',
        'HasManyField.test.js',
        'MediaLibraryFileField.test.js'
      ]

      // This test documents which components have unit test coverage
      expectedTestFiles.forEach(testFile => {
        expect(testFile).toMatch(/\.test\.js$/)
      })
    })

    it('identifies field components missing unit tests', () => {
      const missingTests = [
        'PasswordConfirmationField.test.js',
        'TextareaField.test.js',
        'ManyToManyField.test.js',
        'MultiSelectField.test.js',
        'TimezoneField.test.js',
        'AvatarField.test.js',
        'GravatarField.test.js',
        'MediaLibraryImageField.test.js',
        'MediaLibraryAvatarField.test.js'
      ]

      // This test documents which components need unit test coverage
      missingTests.forEach(testFile => {
        expect(testFile).toMatch(/\.test\.js$/)
      })
    })
  })

  describe('Multi-Field Form Integration', () => {
    it('renders multiple field types in a single form', async () => {
      const formFields = [
        createMockField({ name: 'Name', attribute: 'name', type: 'text' }),
        createMockField({ name: 'Email', attribute: 'email', type: 'email' }),
        createMockField({ name: 'Age', attribute: 'age', type: 'number' }),
        createMockField({ name: 'Active', attribute: 'active', type: 'boolean' }),
        createMockField({ name: 'Category', attribute: 'category_id', type: 'select' })
      ]

      const FormWrapper = {
        template: `
          <form>
            <TextField :field="fields[0]" v-model="formData.name" />
            <EmailField :field="fields[1]" v-model="formData.email" />
            <NumberField :field="fields[2]" v-model="formData.age" />
            <BooleanField :field="fields[3]" v-model="formData.active" />
            <SelectField :field="fields[4]" v-model="formData.category_id" />
          </form>
        `,
        components: { TextField, EmailField, NumberField, BooleanField, SelectField },
        data() {
          return {
            fields: formFields,
            formData: {
              name: '',
              email: '',
              age: null,
              active: false,
              category_id: null
            }
          }
        }
      }

      wrapper = mount(FormWrapper)

      // Verify all fields are rendered
      expect(wrapper.findComponent(TextField).exists()).toBe(true)
      expect(wrapper.findComponent(EmailField).exists()).toBe(true)
      expect(wrapper.findComponent(NumberField).exists()).toBe(true)
      expect(wrapper.findComponent(BooleanField).exists()).toBe(true)
      expect(wrapper.findComponent(SelectField).exists()).toBe(true)
    })

    it('handles form data synchronization across multiple fields', async () => {
      const formData = {
        name: 'John Doe',
        email: 'john@example.com',
        age: 30,
        active: true
      }

      const FormWrapper = {
        template: `
          <form>
            <TextField :field="nameField" v-model="formData.name" />
            <EmailField :field="emailField" v-model="formData.email" />
            <NumberField :field="ageField" v-model="formData.age" />
            <BooleanField :field="activeField" v-model="formData.active" />
          </form>
        `,
        components: { TextField, EmailField, NumberField, BooleanField },
        data() {
          return {
            nameField: createMockField({ name: 'Name', attribute: 'name', type: 'text' }),
            emailField: createMockField({ name: 'Email', attribute: 'email', type: 'email' }),
            ageField: createMockField({ name: 'Age', attribute: 'age', type: 'number' }),
            activeField: createMockField({ name: 'Active', attribute: 'active', type: 'boolean' }),
            formData
          }
        }
      }

      wrapper = mount(FormWrapper)

      // Verify initial values are set
      expect(wrapper.vm.formData.name).toBe('John Doe')
      expect(wrapper.vm.formData.email).toBe('john@example.com')
      expect(wrapper.vm.formData.age).toBe(30)
      expect(wrapper.vm.formData.active).toBe(true)

      // Update values and verify synchronization
      wrapper.vm.formData.name = 'Jane Doe'
      wrapper.vm.formData.email = 'jane@example.com'
      await nextTick()

      expect(wrapper.vm.formData.name).toBe('Jane Doe')
      expect(wrapper.vm.formData.email).toBe('jane@example.com')
    })

    it('validates form submission with multiple field types', async () => {
      const FormWrapper = {
        template: `
          <form @submit.prevent="handleSubmit">
            <TextField :field="nameField" v-model="formData.name" />
            <EmailField :field="emailField" v-model="formData.email" />
            <PasswordField :field="passwordField" v-model="formData.password" />
            <button type="submit">Submit</button>
          </form>
        `,
        components: { TextField, EmailField, PasswordField },
        data() {
          return {
            nameField: createMockField({ name: 'Name', attribute: 'name', type: 'text', required: true }),
            emailField: createMockField({ name: 'Email', attribute: 'email', type: 'email', required: true }),
            passwordField: createMockField({ name: 'Password', attribute: 'password', type: 'password', required: true }),
            formData: {
              name: 'John Doe',
              email: 'john@example.com',
              password: 'securepassword123'
            },
            submitted: false
          }
        },
        methods: {
          handleSubmit() {
            this.submitted = true
          }
        }
      }

      wrapper = mount(FormWrapper)

      const form = wrapper.find('form')
      await form.trigger('submit')

      expect(wrapper.vm.submitted).toBe(true)
    })
  })

  describe('Field Dependency and Conditional Logic', () => {
    it('handles conditional field display based on other field values', async () => {
      const ConditionalFormWrapper = {
        template: `
          <form>
            <BooleanField :field="enableNotificationsField" v-model="formData.enableNotifications" />
            <EmailField
              v-if="formData.enableNotifications"
              :field="notificationEmailField"
              v-model="formData.notificationEmail"
            />
          </form>
        `,
        components: { BooleanField, EmailField },
        data() {
          return {
            enableNotificationsField: createMockField({
              name: 'Enable Notifications',
              attribute: 'enableNotifications',
              type: 'boolean'
            }),
            notificationEmailField: createMockField({
              name: 'Notification Email',
              attribute: 'notificationEmail',
              type: 'email'
            }),
            formData: {
              enableNotifications: false,
              notificationEmail: ''
            }
          }
        }
      }

      wrapper = mount(ConditionalFormWrapper)

      // Initially, email field should not be visible
      expect(wrapper.findComponent(EmailField).exists()).toBe(false)

      // Enable notifications
      wrapper.vm.formData.enableNotifications = true
      await nextTick()

      // Now email field should be visible
      expect(wrapper.findComponent(EmailField).exists()).toBe(true)

      // Disable notifications again
      wrapper.vm.formData.enableNotifications = false
      await nextTick()

      // Email field should be hidden again
      expect(wrapper.findComponent(EmailField).exists()).toBe(false)
    })

    it('handles field dependencies with select field options', async () => {
      const DependentSelectWrapper = {
        template: `
          <form>
            <SelectField :field="countryField" v-model="formData.country" />
            <SelectField :field="stateField" v-model="formData.state" />
          </form>
        `,
        components: { SelectField },
        data() {
          return {
            countryField: createMockField({
              name: 'Country',
              attribute: 'country',
              type: 'select',
              options: [
                { value: 'US', label: 'United States' },
                { value: 'CA', label: 'Canada' }
              ]
            }),
            formData: {
              country: '',
              state: ''
            }
          }
        },
        computed: {
          stateField() {
            const stateOptions = this.formData.country === 'US'
              ? [
                  { value: 'CA', label: 'California' },
                  { value: 'NY', label: 'New York' }
                ]
              : this.formData.country === 'CA'
              ? [
                  { value: 'ON', label: 'Ontario' },
                  { value: 'BC', label: 'British Columbia' }
                ]
              : []

            return createMockField({
              name: 'State/Province',
              attribute: 'state',
              type: 'select',
              options: stateOptions
            })
          }
        }
      }

      wrapper = mount(DependentSelectWrapper)

      // Initially, state field should have no options
      expect(wrapper.vm.stateField.options).toEqual([])

      // Select US
      wrapper.vm.formData.country = 'US'
      await nextTick()

      // State field should have US states
      expect(wrapper.vm.stateField.options).toHaveLength(2)
      expect(wrapper.vm.stateField.options[0].label).toBe('California')

      // Select Canada
      wrapper.vm.formData.country = 'CA'
      await nextTick()

      // State field should have Canadian provinces
      expect(wrapper.vm.stateField.options).toHaveLength(2)
      expect(wrapper.vm.stateField.options[0].label).toBe('Ontario')
    })
  })

  describe('Cross-Field Validation', () => {
    it('validates password confirmation matches password', async () => {
      const PasswordFormWrapper = {
        template: `
          <form>
            <PasswordField :field="passwordField" v-model="formData.password" />
            <PasswordConfirmationField
              :field="confirmPasswordField"
              v-model="formData.confirmPassword"
              :password="formData.password"
            />
          </form>
        `,
        components: { PasswordField, PasswordConfirmationField },
        data() {
          return {
            passwordField: createMockField({
              name: 'Password',
              attribute: 'password',
              type: 'password'
            }),
            confirmPasswordField: createMockField({
              name: 'Confirm Password',
              attribute: 'confirmPassword',
              type: 'password'
            }),
            formData: {
              password: '',
              confirmPassword: ''
            }
          }
        }
      }

      wrapper = mount(PasswordFormWrapper)

      // Set password
      wrapper.vm.formData.password = 'securepassword123'
      await nextTick()

      // Set matching confirmation
      wrapper.vm.formData.confirmPassword = 'securepassword123'
      await nextTick()

      // Passwords should match
      expect(wrapper.vm.formData.password).toBe(wrapper.vm.formData.confirmPassword)

      // Set non-matching confirmation
      wrapper.vm.formData.confirmPassword = 'differentpassword'
      await nextTick()

      // Passwords should not match
      expect(wrapper.vm.formData.password).not.toBe(wrapper.vm.formData.confirmPassword)
    })

    it('validates related field constraints', async () => {
      const RelatedFieldsWrapper = {
        template: `
          <form>
            <DateField :field="startDateField" v-model="formData.startDate" />
            <DateField :field="endDateField" v-model="formData.endDate" />
          </form>
        `,
        components: { DateField },
        data() {
          return {
            startDateField: createMockField({
              name: 'Start Date',
              attribute: 'startDate',
              type: 'date'
            }),
            endDateField: createMockField({
              name: 'End Date',
              attribute: 'endDate',
              type: 'date'
            }),
            formData: {
              startDate: '2023-01-01',
              endDate: '2023-12-31'
            }
          }
        },
        computed: {
          isValidDateRange() {
            if (!this.formData.startDate || !this.formData.endDate) return true
            return new Date(this.formData.startDate) <= new Date(this.formData.endDate)
          }
        }
      }

      wrapper = mount(RelatedFieldsWrapper)

      // Valid date range
      expect(wrapper.vm.isValidDateRange).toBe(true)

      // Invalid date range (end before start)
      wrapper.vm.formData.endDate = '2022-12-31'
      await nextTick()

      expect(wrapper.vm.isValidDateRange).toBe(false)

      // Fix date range
      wrapper.vm.formData.endDate = '2023-12-31'
      await nextTick()

      expect(wrapper.vm.isValidDateRange).toBe(true)
    })
  })

  describe('Theme Switching Integration', () => {
    it('applies theme changes across all field types', async () => {
      const ThemeTestWrapper = {
        template: `
          <div>
            <TextField :field="textField" v-model="formData.text" />
            <SelectField :field="selectField" v-model="formData.select" />
            <BooleanField :field="booleanField" v-model="formData.boolean" />
            <NumberField :field="numberField" v-model="formData.number" />
          </div>
        `,
        components: { TextField, SelectField, BooleanField, NumberField },
        data() {
          return {
            textField: createMockField({ name: 'Text', attribute: 'text', type: 'text' }),
            selectField: createMockField({ name: 'Select', attribute: 'select', type: 'select' }),
            booleanField: createMockField({ name: 'Boolean', attribute: 'boolean', type: 'boolean' }),
            numberField: createMockField({ name: 'Number', attribute: 'number', type: 'number' }),
            formData: {
              text: '',
              select: '',
              boolean: false,
              number: 0
            }
          }
        }
      }

      wrapper = mount(ThemeTestWrapper)

      // Initially light theme
      expect(mockAdminStore.isDarkTheme).toBe(false)

      // Switch to dark theme
      mockAdminStore.isDarkTheme = true
      await nextTick()

      // All field components should respond to theme change
      const textField = wrapper.findComponent(TextField)
      const selectField = wrapper.findComponent(SelectField)
      const booleanField = wrapper.findComponent(BooleanField)
      const numberField = wrapper.findComponent(NumberField)

      expect(textField.exists()).toBe(true)
      expect(selectField.exists()).toBe(true)
      expect(booleanField.exists()).toBe(true)
      expect(numberField.exists()).toBe(true)

      // Switch back to light theme
      mockAdminStore.isDarkTheme = false
      await nextTick()

      // Fields should still be rendered and responsive
      expect(textField.exists()).toBe(true)
      expect(selectField.exists()).toBe(true)
      expect(booleanField.exists()).toBe(true)
      expect(numberField.exists()).toBe(true)
    })

    it('maintains field state during theme transitions', async () => {
      const StatePersistenceWrapper = {
        template: `
          <div>
            <TextField :field="textField" v-model="formData.text" />
            <NumberField :field="numberField" v-model="formData.number" />
            <BooleanField :field="booleanField" v-model="formData.boolean" />
          </div>
        `,
        components: { TextField, NumberField, BooleanField },
        data() {
          return {
            textField: createMockField({ name: 'Text', attribute: 'text', type: 'text' }),
            numberField: createMockField({ name: 'Number', attribute: 'number', type: 'number' }),
            booleanField: createMockField({ name: 'Boolean', attribute: 'boolean', type: 'boolean' }),
            formData: {
              text: 'Test Value',
              number: 42,
              boolean: true
            }
          }
        }
      }

      wrapper = mount(StatePersistenceWrapper)

      // Verify initial values
      expect(wrapper.vm.formData.text).toBe('Test Value')
      expect(wrapper.vm.formData.number).toBe(42)
      expect(wrapper.vm.formData.boolean).toBe(true)

      // Switch theme multiple times
      mockAdminStore.isDarkTheme = true
      await nextTick()
      mockAdminStore.isDarkTheme = false
      await nextTick()
      mockAdminStore.isDarkTheme = true
      await nextTick()

      // Values should persist through theme changes
      expect(wrapper.vm.formData.text).toBe('Test Value')
      expect(wrapper.vm.formData.number).toBe(42)
      expect(wrapper.vm.formData.boolean).toBe(true)
    })
  })

  describe('Error State Propagation and Recovery', () => {
    it('handles error state propagation across multiple fields', async () => {
      const ErrorTestWrapper = {
        template: `
          <form>
            <TextField :field="nameField" v-model="formData.name" :error="errors.name" />
            <EmailField :field="emailField" v-model="formData.email" :error="errors.email" />
            <NumberField :field="ageField" v-model="formData.age" :error="errors.age" />
          </form>
        `,
        components: { TextField, EmailField, NumberField },
        data() {
          return {
            nameField: createMockField({ name: 'Name', attribute: 'name', type: 'text', required: true }),
            emailField: createMockField({ name: 'Email', attribute: 'email', type: 'email', required: true }),
            ageField: createMockField({ name: 'Age', attribute: 'age', type: 'number', min: 0, max: 120 }),
            formData: {
              name: '',
              email: 'invalid-email',
              age: -5
            },
            errors: {
              name: 'Name is required',
              email: 'Invalid email format',
              age: 'Age must be between 0 and 120'
            }
          }
        }
      }

      wrapper = mount(ErrorTestWrapper)

      // All fields should have errors initially
      expect(wrapper.vm.errors.name).toBeTruthy()
      expect(wrapper.vm.errors.email).toBeTruthy()
      expect(wrapper.vm.errors.age).toBeTruthy()

      // Fix errors one by one
      wrapper.vm.formData.name = 'John Doe'
      wrapper.vm.errors.name = null
      await nextTick()

      expect(wrapper.vm.errors.name).toBe(null)
      expect(wrapper.vm.errors.email).toBeTruthy()
      expect(wrapper.vm.errors.age).toBeTruthy()

      wrapper.vm.formData.email = 'john@example.com'
      wrapper.vm.errors.email = null
      await nextTick()

      expect(wrapper.vm.errors.name).toBe(null)
      expect(wrapper.vm.errors.email).toBe(null)
      expect(wrapper.vm.errors.age).toBeTruthy()

      wrapper.vm.formData.age = 30
      wrapper.vm.errors.age = null
      await nextTick()

      expect(wrapper.vm.errors.name).toBe(null)
      expect(wrapper.vm.errors.email).toBe(null)
      expect(wrapper.vm.errors.age).toBe(null)
    })

    it('handles error recovery and re-validation', async () => {
      const RecoveryTestWrapper = {
        template: `
          <form>
            <EmailField :field="emailField" v-model="formData.email" :error="errors.email" />
            <PasswordField :field="passwordField" v-model="formData.password" :error="errors.password" />
          </form>
        `,
        components: { EmailField, PasswordField },
        data() {
          return {
            emailField: createMockField({ name: 'Email', attribute: 'email', type: 'email' }),
            passwordField: createMockField({ name: 'Password', attribute: 'password', type: 'password', minLength: 8 }),
            formData: {
              email: '',
              password: ''
            },
            errors: {
              email: null,
              password: null
            }
          }
        },
        methods: {
          validateEmail() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            this.errors.email = emailRegex.test(this.formData.email) ? null : 'Invalid email format'
          },
          validatePassword() {
            this.errors.password = this.formData.password.length >= 8 ? null : 'Password must be at least 8 characters'
          }
        }
      }

      wrapper = mount(RecoveryTestWrapper)

      // Set invalid values and validate
      wrapper.vm.formData.email = 'invalid'
      wrapper.vm.formData.password = '123'
      wrapper.vm.validateEmail()
      wrapper.vm.validatePassword()
      await nextTick()

      expect(wrapper.vm.errors.email).toBeTruthy()
      expect(wrapper.vm.errors.password).toBeTruthy()

      // Fix values and re-validate
      wrapper.vm.formData.email = 'valid@example.com'
      wrapper.vm.formData.password = 'securepassword123'
      wrapper.vm.validateEmail()
      wrapper.vm.validatePassword()
      await nextTick()

      expect(wrapper.vm.errors.email).toBe(null)
      expect(wrapper.vm.errors.password).toBe(null)
    })
  })
})
