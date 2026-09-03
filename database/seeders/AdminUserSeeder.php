<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessPlan = Plan::where('name', 'business')->first()
            ?? Plan::where('name', 'pro')->first();

        User::updateOrCreate(
            ['email' => 'admin@pdf2word.app'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('Admin@123456'),
                'is_admin' => true,
                'email_verified_at' => now(),
                'locale' => 'th',
                'provider' => 'email',
                'plan_id' => $businessPlan?->id,
            ]
        );
    }
}
