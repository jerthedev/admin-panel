<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Create Admin User Command
 *
 * Creates an admin user for accessing the admin panel.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:user 
                            {--name= : The name of the admin user}
                            {--email= : The email of the admin user}
                            {--password= : The password for the admin user}';

    /**
     * The console command description.
     */
    protected $description = 'Create an admin user for the admin panel';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Creating Admin User...');
        $this->newLine();

        // Get user model
        $userModel = $this->getUserModel();
        if (!$userModel) {
            return self::FAILURE;
        }

        // Get user details
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('❌ Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("   • {$error}");
            }
            return self::FAILURE;
        }

        // Create user
        try {
            $user = new $userModel();
            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make($password);
            
            // Set admin flag if the field exists
            if ($this->hasAdminField($user)) {
                $user->is_admin = true;
            }
            
            $user->save();

            $this->info('✅ Admin user created successfully!');
            $this->newLine();
            $this->line("Name: {$name}");
            $this->line("Email: {$email}");
            
            if ($this->hasAdminField($user)) {
                $this->line("Admin: Yes");
            } else {
                $this->warn("⚠️  Note: User created without admin flag. You may need to manually set admin permissions.");
            }

            $this->newLine();
            $this->info("You can now login to the admin panel at: " . url(config('admin-panel.path', 'admin')));

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to create user: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Get the user model class.
     */
    protected function getUserModel(): ?string
    {
        $userModel = config('auth.providers.users.model');
        
        if (!$userModel) {
            $this->error('❌ User model not configured in auth.providers.users.model');
            return null;
        }

        if (!class_exists($userModel)) {
            $this->error("❌ User model class does not exist: {$userModel}");
            return null;
        }

        return $userModel;
    }

    /**
     * Check if the user model has an admin field.
     */
    protected function hasAdminField($user): bool
    {
        $fillable = $user->getFillable();
        $guarded = $user->getGuarded();
        
        // Check if is_admin is in fillable or not in guarded
        return in_array('is_admin', $fillable) || 
               (!in_array('is_admin', $guarded) && !in_array('*', $guarded));
    }
}
