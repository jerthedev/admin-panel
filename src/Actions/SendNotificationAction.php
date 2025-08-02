<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Send Notification Action
 * 
 * Bulk notification action for sending notifications to multiple
 * resources or users with customizable notification types.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Actions
 */
class SendNotificationAction extends Action
{
    /**
     * The action's name.
     */
    public string $name = 'Send Notification';

    /**
     * The action's URI key.
     */
    public string $uriKey = 'send-notification';

    /**
     * The action's icon.
     */
    public ?string $icon = 'BellIcon';

    /**
     * The notification class to send.
     */
    protected ?string $notificationClass = null;

    /**
     * The notification channels.
     */
    protected array $channels = ['mail'];

    /**
     * Custom notification data.
     */
    protected array $notificationData = [];

    /**
     * Whether to send to the models themselves or related users.
     */
    protected bool $sendToModels = true;

    /**
     * The relationship to get users from (if not sending to models).
     */
    protected ?string $userRelationship = null;

    /**
     * Create a new send notification action instance.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->confirmationMessage = 'Are you sure you want to send notifications to the selected resources?';
        $this->successMessage = 'Notifications sent successfully.';
        $this->errorMessage = 'Failed to send notifications.';
        $this->withTransaction = false; // Notifications don't need transactions
    }

    /**
     * Perform the action on the given models.
     */
    public function handle(Collection $models, Request $request): array
    {
        if (! $this->notificationClass) {
            return $this->error('No notification class specified.');
        }

        if (! class_exists($this->notificationClass)) {
            return $this->error("Notification class {$this->notificationClass} does not exist.");
        }

        $recipients = $this->getRecipients($models);
        
        if ($recipients->isEmpty()) {
            return $this->error('No valid recipients found.');
        }

        try {
            $notification = $this->createNotification($request);
            
            $sentCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($recipients as $recipient) {
                try {
                    $recipient->notify($notification);
                    $sentCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to send to {$recipient->getKey()}: {$e->getMessage()}";
                }
            }

            if ($failedCount === 0) {
                $message = $sentCount === 1 
                    ? '1 notification sent successfully.'
                    : "{$sentCount} notifications sent successfully.";
                    
                return $this->success($message);
            }

            if ($sentCount === 0) {
                return $this->error('Failed to send any notifications. ' . implode(' ', $errors));
            }

            $message = "{$sentCount} notifications sent successfully, {$failedCount} failed.";
            return $this->warning($message);
        } catch (\Exception $e) {
            return $this->error("Failed to send notifications: {$e->getMessage()}");
        }
    }

    /**
     * Set the notification class.
     */
    public function withNotification(string $notificationClass): static
    {
        $this->notificationClass = $notificationClass;
        
        return $this;
    }

    /**
     * Set the notification channels.
     */
    public function withChannels(array $channels): static
    {
        $this->channels = $channels;
        
        return $this;
    }

    /**
     * Set custom notification data.
     */
    public function withData(array $data): static
    {
        $this->notificationData = $data;
        
        return $this;
    }

    /**
     * Send notifications to related users instead of models.
     */
    public function toUsers(string $relationship = 'user'): static
    {
        $this->sendToModels = false;
        $this->userRelationship = $relationship;
        
        return $this;
    }

    /**
     * Get the recipients for the notification.
     */
    protected function getRecipients(Collection $models): Collection
    {
        if ($this->sendToModels) {
            // Filter models that can receive notifications
            return $models->filter(function ($model) {
                return method_exists($model, 'notify');
            });
        }

        // Get users from relationship
        if (! $this->userRelationship) {
            return collect();
        }

        $users = collect();
        
        foreach ($models as $model) {
            if (method_exists($model, $this->userRelationship)) {
                $relatedUser = $model->{$this->userRelationship};
                
                if ($relatedUser && method_exists($relatedUser, 'notify')) {
                    $users->push($relatedUser);
                }
            }
        }

        return $users->unique('id');
    }

    /**
     * Create the notification instance.
     */
    protected function createNotification(Request $request): Notification
    {
        $notificationClass = $this->notificationClass;
        $data = array_merge($this->notificationData, $request->all());

        // Try to create notification with data
        try {
            return new $notificationClass($data);
        } catch (\Exception $e) {
            // Fallback to no-argument constructor
            return new $notificationClass();
        }
    }

    /**
     * Create an email notification action.
     */
    public static function email(string $notificationClass): static
    {
        return (new static())
            ->withName('Send Email')
            ->withUriKey('send-email')
            ->withIcon('EnvelopeIcon')
            ->withNotification($notificationClass)
            ->withChannels(['mail']);
    }

    /**
     * Create an SMS notification action.
     */
    public static function sms(string $notificationClass): static
    {
        return (new static())
            ->withName('Send SMS')
            ->withUriKey('send-sms')
            ->withIcon('DevicePhoneMobileIcon')
            ->withNotification($notificationClass)
            ->withChannels(['sms']);
    }

    /**
     * Create a database notification action.
     */
    public static function database(string $notificationClass): static
    {
        return (new static())
            ->withName('Send Notification')
            ->withUriKey('send-database-notification')
            ->withIcon('BellIcon')
            ->withNotification($notificationClass)
            ->withChannels(['database']);
    }

    /**
     * Create a multi-channel notification action.
     */
    public static function multiChannel(string $notificationClass, array $channels): static
    {
        return (new static())
            ->withName('Send Multi-Channel Notification')
            ->withUriKey('send-multi-notification')
            ->withIcon('SpeakerWaveIcon')
            ->withNotification($notificationClass)
            ->withChannels($channels);
    }
}
