<?php

namespace App\Admin\Pages;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Fields\Textarea;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Pages\Page;
use JTD\AdminPanel\Actions\Action;

/**
 * Onboarding Wizard Custom Page
 * 
 * Demonstrates multi-component architecture with a step-by-step
 * onboarding process. Shows how to coordinate multiple Vue components
 * within a single Custom Page.
 * 
 * Features:
 * - Multi-component architecture
 * - Step-by-step navigation
 * - Shared state across components
 * - Form data persistence
 * - Progress tracking
 * - Field reconciliation
 */
class OnboardingWizardPage extends Page
{
    /**
     * The Vue components for this page.
     * First component is primary, others are accessible via routing.
     */
    public static array $components = [
        'OnboardingWizard',    // Primary component with navigation
        'WizardStep1',         // Company Information
        'WizardStep2',         // User Preferences  
        'WizardStep3',         // Final Configuration
    ];

    /**
     * The menu group this page belongs to.
     */
    public static ?string $group = 'Setup';

    /**
     * The display title for this page.
     */
    public static ?string $title = 'Onboarding Wizard';

    /**
     * The icon for this page.
     */
    public static ?string $icon = 'academic-cap';

    /**
     * Get the fields for this page.
     * 
     * All fields are available to all components, but components
     * can choose which fields to display and when.
     */
    public function fields(Request $request): array
    {
        return [
            // Step 1: Company Information
            Text::make('Company Name')
                ->rules('required', 'max:255')
                ->help('Your company or organization name')
                ->meta(['step' => 1]),

            Text::make('Company Website')
                ->rules('url')
                ->help('Your company website URL')
                ->meta(['step' => 1]),

            Select::make('Industry')->options([
                'technology' => 'Technology',
                'healthcare' => 'Healthcare',
                'finance' => 'Finance',
                'education' => 'Education',
                'retail' => 'Retail',
                'manufacturing' => 'Manufacturing',
                'other' => 'Other',
            ])->rules('required')
            ->help('Select your primary industry')
            ->meta(['step' => 1]),

            Number::make('Company Size')
                ->min(1)
                ->max(10000)
                ->help('Number of employees')
                ->meta(['step' => 1]),

            // Step 2: User Preferences
            Select::make('Timezone')->options($this->getTimezoneOptions())
                ->rules('required')
                ->help('Your preferred timezone')
                ->meta(['step' => 2]),

            Select::make('Date Format')->options([
                'Y-m-d' => '2025-01-15 (ISO)',
                'm/d/Y' => '01/15/2025 (US)',
                'd/m/Y' => '15/01/2025 (EU)',
                'd.m.Y' => '15.01.2025 (DE)',
            ])->rules('required')
            ->help('Preferred date display format')
            ->meta(['step' => 2]),

            Boolean::make('Email Notifications')
                ->help('Receive email notifications for important events')
                ->meta(['step' => 2]),

            Boolean::make('SMS Notifications')
                ->help('Receive SMS notifications for critical alerts')
                ->meta(['step' => 2]),

            // Step 3: Final Configuration
            Textarea::make('Welcome Message')
                ->rows(4)
                ->help('Custom welcome message for your users')
                ->meta(['step' => 3]),

            Boolean::make('Enable Analytics')
                ->help('Enable usage analytics and reporting')
                ->meta(['step' => 3]),

            Boolean::make('Enable API Access')
                ->help('Enable API access for integrations')
                ->meta(['step' => 3]),

            Select::make('Default User Role')->options([
                'user' => 'Standard User',
                'editor' => 'Editor',
                'moderator' => 'Moderator',
            ])->rules('required')
            ->help('Default role for new users')
            ->meta(['step' => 3]),
        ];
    }

    /**
     * Get custom data for this page.
     */
    public function data(Request $request): array
    {
        $currentStep = $this->getCurrentStep($request);
        $wizardData = session('onboarding_wizard_data', []);

        return [
            'wizard' => [
                'currentStep' => $currentStep,
                'totalSteps' => 3,
                'completedSteps' => $this->getCompletedSteps($wizardData),
                'canProceed' => $this->canProceedToNextStep($currentStep, $wizardData),
            ],
            'steps' => [
                1 => [
                    'title' => 'Company Information',
                    'description' => 'Tell us about your company',
                    'fields' => ['company_name', 'company_website', 'industry', 'company_size'],
                ],
                2 => [
                    'title' => 'User Preferences', 
                    'description' => 'Configure your preferences',
                    'fields' => ['timezone', 'date_format', 'email_notifications', 'sms_notifications'],
                ],
                3 => [
                    'title' => 'Final Configuration',
                    'description' => 'Complete your setup',
                    'fields' => ['welcome_message', 'enable_analytics', 'enable_api_access', 'default_user_role'],
                ],
            ],
            'wizardData' => $wizardData,
            'progress' => [
                'percentage' => $this->getProgressPercentage($currentStep),
                'stepsCompleted' => count($this->getCompletedSteps($wizardData)),
                'stepsRemaining' => 3 - count($this->getCompletedSteps($wizardData)),
            ],
        ];
    }

    /**
     * Get actions for the wizard.
     */
    public function actions(Request $request): array
    {
        return [
            new SaveWizardStepAction(),
            new CompleteOnboardingAction(),
            new ResetWizardAction(),
        ];
    }

    /**
     * Authorization for the onboarding wizard.
     */
    public static function authorizedToViewAny(Request $request): bool
    {
        $user = $request->user();
        
        // Allow access for admin users or users who haven't completed onboarding
        return $user && (
            $user->hasRole('admin') || 
            !$user->hasCompletedOnboarding()
        );
    }

    // Helper methods for wizard logic
    private function getCurrentStep(Request $request): int
    {
        return (int) $request->get('step', 1);
    }

    private function getCompletedSteps(array $wizardData): array
    {
        $completed = [];
        
        // Step 1 is complete if company info is filled
        if (!empty($wizardData['company_name']) && !empty($wizardData['industry'])) {
            $completed[] = 1;
        }
        
        // Step 2 is complete if preferences are set
        if (!empty($wizardData['timezone']) && !empty($wizardData['date_format'])) {
            $completed[] = 2;
        }
        
        // Step 3 is complete if final config is set
        if (!empty($wizardData['default_user_role'])) {
            $completed[] = 3;
        }
        
        return $completed;
    }

    private function canProceedToNextStep(int $currentStep, array $wizardData): bool
    {
        $completedSteps = $this->getCompletedSteps($wizardData);
        
        // Can proceed if current step is completed
        return in_array($currentStep, $completedSteps);
    }

    private function getProgressPercentage(int $currentStep): int
    {
        return round(($currentStep / 3) * 100);
    }

    private function getTimezoneOptions(): array
    {
        return [
            'UTC' => 'UTC',
            'America/New_York' => 'Eastern Time',
            'America/Chicago' => 'Central Time', 
            'America/Denver' => 'Mountain Time',
            'America/Los_Angeles' => 'Pacific Time',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Asia/Tokyo' => 'Tokyo',
            'Australia/Sydney' => 'Sydney',
        ];
    }
}

// Example Action Classes
class SaveWizardStepAction extends Action
{
    public function handle(Request $request): array
    {
        $stepData = $request->get('stepData', []);
        $currentStep = $request->get('currentStep', 1);
        
        // Merge with existing wizard data
        $wizardData = session('onboarding_wizard_data', []);
        $wizardData = array_merge($wizardData, $stepData);
        
        session(['onboarding_wizard_data' => $wizardData]);
        
        return [
            'success' => true,
            'message' => "Step {$currentStep} saved successfully",
            'nextStep' => $currentStep + 1,
        ];
    }
}

class CompleteOnboardingAction extends Action
{
    public function handle(Request $request): array
    {
        $wizardData = session('onboarding_wizard_data', []);
        
        // Save final configuration
        $user = $request->user();
        $user->update([
            'company_name' => $wizardData['company_name'] ?? null,
            'timezone' => $wizardData['timezone'] ?? 'UTC',
            'onboarding_completed' => true,
        ]);
        
        // Clear wizard session data
        session()->forget('onboarding_wizard_data');
        
        return [
            'success' => true,
            'message' => 'Onboarding completed successfully!',
            'redirect' => route('admin-panel.dashboard'),
        ];
    }
}

class ResetWizardAction extends Action
{
    public function handle(Request $request): array
    {
        session()->forget('onboarding_wizard_data');
        
        return [
            'success' => true,
            'message' => 'Wizard reset successfully',
            'redirect' => route('admin-panel.pages.onboardingwizard'),
        ];
    }
}
