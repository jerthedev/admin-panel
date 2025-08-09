# Multi-Component Wizard Custom Page Example

This example demonstrates the multi-component architecture of Custom Pages by creating a comprehensive onboarding wizard with multiple steps and components.

## What This Example Covers

- **Multi-Component Architecture**: Using multiple Vue components in a single Custom Page
- **Component Routing**: Navigation between wizard steps
- **Shared Data**: How components share data and state
- **Form Handling**: Multi-step form submission and validation
- **Progress Tracking**: Visual progress indicators
- **Field Reconciliation**: Coordinating field changes across components

## Use Cases

This type of Custom Page is perfect for:
- **User Onboarding**: Multi-step user setup processes
- **System Configuration**: Complex configuration wizards
- **Data Import**: Step-by-step data import processes
- **Setup Wizards**: Application or feature setup flows
- **Multi-Step Forms**: Complex forms broken into manageable steps

## Files in This Example

1. **OnboardingWizardPage.php** - The page class with multi-component setup
2. **OnboardingWizard.vue** - Primary component with navigation and progress
3. **WizardStep1.vue** - Company information step
4. **WizardStep2.vue** - User preferences step  
5. **WizardStep3.vue** - Final configuration step
6. **multi-component-routing.md** - Detailed routing explanation

## Multi-Component Architecture

### Component Array Structure
```php
public static array $components = [
    'OnboardingWizard',    // Primary component (loads by default)
    'WizardStep1',         // Company Information step
    'WizardStep2',         // User Preferences step
    'WizardStep3',         // Final Configuration step
];
```

### Generated Routes
| Component | Route | URL |
|-----------|-------|-----|
| Primary | `admin-panel.pages.onboardingwizard` | `/admin/pages/onboardingwizard` |
| Step 1 | `admin-panel.pages.onboardingwizard.component` | `/admin/pages/onboardingwizard/WizardStep1` |
| Step 2 | `admin-panel.pages.onboardingwizard.component` | `/admin/pages/onboardingwizard/WizardStep2` |
| Step 3 | `admin-panel.pages.onboardingwizard.component` | `/admin/pages/onboardingwizard/WizardStep3` |

## Key Features Demonstrated

### Shared State Management
All components receive the same props:
- **fields**: Field definitions from `fields()` method
- **data**: Custom data from `data()` method including current step
- **actions**: Available actions (save, next, previous)

### Field Reconciliation
When users modify fields in different components, changes are reconciled:
- Form data persists across component navigation
- Validation errors are maintained per step
- Final submission includes all step data

### Progress Tracking
Visual progress indicator shows:
- Current step position
- Completed steps
- Available next steps
- Step validation status

## Installation Steps

1. **Copy all files** to your application:
   - `OnboardingWizardPage.php` → `app/Admin/Pages/`
   - Vue components → `resources/js/admin-pages/`

2. **Run setup command** if needed:
   ```bash
   php artisan admin-panel:setup-custom-pages
   ```

3. **Compile assets**:
   ```bash
   npm run build
   ```

4. **Access the wizard** at `/admin/pages/onboardingwizard`

## Navigation Flow

1. **Start**: User accesses primary route, sees OnboardingWizard.vue
2. **Step Navigation**: User clicks step buttons, navigates to specific components
3. **Data Persistence**: Form data maintained across all steps
4. **Completion**: Final step submits all collected data

## Customization Ideas

- Add conditional steps based on user selections
- Implement step validation before allowing progression
- Add save-and-resume functionality
- Include file upload steps
- Add confirmation and review step
- Implement branching logic for different user types

## Advanced Features

### Dynamic Step Generation
```php
public function data(Request $request): array
{
    $steps = $this->getWizardSteps($request->user());
    
    return [
        'steps' => $steps,
        'currentStep' => $request->get('step', 1),
        'totalSteps' => count($steps),
        'wizardData' => session('wizard_data', []),
    ];
}
```

### Conditional Component Loading
```php
public static array $components = [
    'OnboardingWizard',
    'WizardStep1',
    'WizardStep2',
    // Step 3 only for admin users
    ...(auth()->user()?->hasRole('admin') ? ['WizardStep3'] : []),
];
```

## Next Steps

After implementing this example:
- Explore the [Dashboard Page Example](../dashboard-page/) for single-component pages
- Check the [Settings Page Example](../settings-page/) for form handling
- Review the [Report Page Example](../report-page/) for data visualization
- See [Package Developer Guide](../../custom-pages-for-package-developers.md) for package integration
