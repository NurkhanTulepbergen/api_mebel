<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
class CreateApiUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API user with a strong random password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Enter name');
        $email = $this->ask('Enter email');

        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");
            return 0;
        }

        // Генерация сильного пароля
        $password = $this->generateStrongPassword();

        // Создание пользователя
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info('User created successfully!');
        $this->line("Email: {$email}");
        $this->line("Password: {$password}");

        return 1;
    }

     /**
     * Generate a strong random password.
     */
    private function generateStrongPassword($length = 16)
    {
        // Смешиваем буквы, цифры и символы
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}';
        return substr(str_shuffle(str_repeat($chars, $length)), 0, $length);
    }
}
