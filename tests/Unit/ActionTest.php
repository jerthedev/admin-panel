<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use JTD\AdminPanel\Actions\DeleteAction;
use JTD\AdminPanel\Actions\ExportAction;
use JTD\AdminPanel\Actions\UpdateStatusAction;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Action Unit Tests
 * 
 * Tests for action classes including execution, authorization,
 * and configuration.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ActionTest extends TestCase
{
    public function test_delete_action_creation(): void
    {
        $action = new DeleteAction();
        
        $this->assertEquals('Delete', $action->name());
        $this->assertEquals('delete', $action->uriKey());
        $this->assertEquals('TrashIcon', $action->icon());
        $this->assertTrue($action->destructive);
    }

    public function test_delete_action_handles_single_model(): void
    {
        $action = new DeleteAction();
        $user = User::factory()->create();
        $models = new Collection([$user]);
        $request = new Request();
        
        $result = $action->execute($models, $request);
        
        $this->assertEquals('success', $result['type']);
        $this->assertStringContains('deleted successfully', $result['message']);
        $this->assertDatabaseMissingModel($user);
    }

    public function test_delete_action_handles_multiple_models(): void
    {
        $action = new DeleteAction();
        $users = User::factory()->count(3)->create();
        $models = new Collection($users->all());
        $request = new Request();
        
        $result = $action->execute($models, $request);
        
        $this->assertEquals('success', $result['type']);
        $this->assertStringContains('3 resources deleted successfully', $result['message']);
        
        foreach ($users as $user) {
            $this->assertDatabaseMissingModel($user);
        }
    }

    public function test_update_status_action_creation(): void
    {
        $action = new UpdateStatusAction();
        
        $this->assertEquals('Update Status', $action->name());
        $this->assertEquals('update-status', $action->uriKey());
        $this->assertEquals('PencilSquareIcon', $action->icon());
        $this->assertFalse($action->destructive);
    }

    public function test_update_status_action_activate(): void
    {
        $action = UpdateStatusAction::activate();
        
        $this->assertEquals('Activate', $action->name());
        $this->assertEquals('activate', $action->uriKey());
        $this->assertEquals('CheckCircleIcon', $action->icon());
    }

    public function test_update_status_action_handles_models(): void
    {
        $action = UpdateStatusAction::activate();
        $users = User::factory()->count(2)->create(['is_active' => false]);
        $models = new Collection($users->all());
        $request = new Request();
        
        $result = $action->execute($models, $request);
        
        $this->assertEquals('success', $result['type']);
        $this->assertStringContains('updated to Active', $result['message']);
        
        foreach ($users as $user) {
            $user->refresh();
            $this->assertTrue($user->is_active);
        }
    }

    public function test_export_action_creation(): void
    {
        $action = new ExportAction();
        
        $this->assertEquals('Export', $action->name());
        $this->assertEquals('export', $action->uriKey());
        $this->assertEquals('ArrowDownTrayIcon', $action->icon());
        $this->assertFalse($action->destructive);
    }

    public function test_export_action_csv(): void
    {
        $action = ExportAction::csv();
        
        $this->assertEquals('Export CSV', $action->name());
        $this->assertEquals('export-csv', $action->uriKey());
    }

    public function test_export_action_handles_empty_collection(): void
    {
        $action = new ExportAction();
        $models = new Collection([]);
        $request = new Request();
        
        $result = $action->execute($models, $request);
        
        $this->assertEquals('error', $result['type']);
        $this->assertStringContains('No resources selected', $result['message']);
    }

    public function test_action_authorization(): void
    {
        $action = new DeleteAction();
        $request = new Request();
        
        $this->assertTrue($action->authorize($request));
    }

    public function test_action_with_confirmation(): void
    {
        $action = new DeleteAction();
        
        $this->assertNotNull($action->confirmationMessage());
        $this->assertStringContains('Are you sure', $action->confirmationMessage());
    }

    public function test_action_custom_configuration(): void
    {
        $action = (new DeleteAction())
            ->withName('Custom Delete')
            ->withUriKey('custom-delete')
            ->withIcon('CustomIcon')
            ->withConfirmation('Custom confirmation message')
            ->withSuccessMessage('Custom success message')
            ->withErrorMessage('Custom error message');
        
        $this->assertEquals('Custom Delete', $action->name());
        $this->assertEquals('custom-delete', $action->uriKey());
        $this->assertEquals('CustomIcon', $action->icon());
        $this->assertEquals('Custom confirmation message', $action->confirmationMessage());
        $this->assertEquals('Custom success message', $action->successMessage);
        $this->assertEquals('Custom error message', $action->errorMessage);
    }

    public function test_action_json_serialization(): void
    {
        $action = new DeleteAction();
        
        $json = $action->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('Delete', $json['name']);
        $this->assertEquals('delete', $json['uriKey']);
        $this->assertEquals('TrashIcon', $json['icon']);
        $this->assertTrue($json['destructive']);
        $this->assertNotNull($json['confirmationMessage']);
    }

    public function test_action_response_helpers(): void
    {
        $action = new DeleteAction();
        
        // Use reflection to test protected methods
        $reflection = new \ReflectionClass($action);
        
        $successMethod = $reflection->getMethod('success');
        $successMethod->setAccessible(true);
        $successResponse = $successMethod->invoke($action, 'Success message');
        
        $this->assertEquals([
            'type' => 'success',
            'message' => 'Success message',
            'redirect' => null,
        ], $successResponse);
        
        $errorMethod = $reflection->getMethod('error');
        $errorMethod->setAccessible(true);
        $errorResponse = $errorMethod->invoke($action, 'Error message');
        
        $this->assertEquals([
            'type' => 'error',
            'message' => 'Error message',
        ], $errorResponse);
    }

    public function test_action_transaction_handling(): void
    {
        $action = new DeleteAction();
        
        // Test that action uses transactions by default
        $this->assertTrue($action->withTransaction);
        
        // Test disabling transactions
        $action->withTransaction(false);
        $this->assertFalse($action->withTransaction);
    }

    public function test_action_name_generation(): void
    {
        $action = new DeleteAction();
        
        // Test that name is generated from class name
        $this->assertEquals('Delete', $action->name());
        
        // Test URI key generation
        $this->assertEquals('delete', $action->uriKey());
    }
}
