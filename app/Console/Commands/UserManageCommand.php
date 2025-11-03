<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManageCommand extends Command
{
    protected $signature = 'user:manage {action} {--id=} {--email=} {--name=} {--new-email=} {--new-name=} {--password=}';
    protected $description = 'Manage users: create, list, remove, update';

    public function handle()
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'create':
                $this->createUser();
                break;
            case 'list':
                $this->listUsers();
                break;
            case 'remove':
                $this->removeUser();
                break;
            case 'update':
                $this->updateUser();
                break;
            default:
                $this->error('Unknown action. Use create, list, remove, update.');
        }
    }

    protected function createUser()
    {
        $name = $this->option('name') ?? $this->ask('Name');
        $email = $this->option('email') ?? $this->ask('Email');
        $password = $this->option('password') ?? $this->secret('Password');

        if (User::where('email', $email)->exists()) {
            $this->error('A user with this email already exists.');
            return;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $this->info("User created: ID {$user->id}, Name {$user->name}, Email {$user->email}");
    }

    protected function listUsers()
    {
        $users = User::all(['id', 'name', 'email']);
        if ($users->isEmpty()) {
            $this->info('No users found.');
            return;
        }
        $this->table(['ID', 'Name', 'Email'], $users->toArray());
    }

    protected function removeUser()
    {
        $id = $this->option('id');
        $email = $this->option('email');
        $user = null;
        if ($id) {
            $user = User::find($id);
        } elseif ($email) {
            $user = User::where('email', $email)->first();
        } else {
            $this->error('Specify --id or --email to remove a user.');
            return;
        }
        if (!$user) {
            $this->error('User not found.');
            return;
        }
        $user->delete();
        $this->info("User removed: ID {$user->id}, Email {$user->email}");
    }

    protected function updateUser()
    {
        $id = $this->option('id');
        $email = $this->option('email');
        $user = null;
        if ($id) {
            $user = User::find($id);
        } elseif ($email) {
            $user = User::where('email', $email)->first();
        } else {
            $this->error('Specify --id or --email to update a user.');
            return;
        }
        if (!$user) {
            $this->error('User not found.');
            return;
        }
        $newEmail = $this->option('new-email');
        $newName = $this->option('new-name');
        $newPassword = $this->option('new-password');
        $updated = false;
        if ($newEmail) {
            if (User::where('email', $newEmail)->exists()) {
                $this->error('A user with the new email already exists.');
                return;
            }
            $user->email = $newEmail;
            $updated = true;
        }
        if ($newName) {
            $user->name = $newName;
            $updated = true;
        }
        if ($newPassword !== null) {
            if (empty($newPassword)) {
                $this->error('New password cannot be empty.');
                return;
            }
            $user->password = Hash::make($newPassword);
            $updated = true;
            $this->info('Password updated.');
        }
        if ($updated) {
            $user->save();
            $this->info("User updated: ID {$user->id}, Name {$user->name}, Email {$user->email}");
        } else {
            $this->info('No changes made. Use --new-email, --new-name, or --new-password.');
        }
    }
}
