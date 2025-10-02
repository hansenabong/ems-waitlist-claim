<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register(): void
    {
        $res = $this->post('/register', [
            'name' => 'A',
            'email' => 'a@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'agree' => 'on', // ensure this is required by your controller/validator
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'a@example.com']);
    }
    public function test_user_cannot_register_without_agreeing_to_privacy_policy(): void
    {
        $res = $this->post('/register', [
            'name' => 'B',
            'email' => 'b@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            // no 'agree'
        ]);

        $res->assertSessionHasErrors('agree');
        $this->assertDatabaseMissing('users', ['email' => 'b@example.com']);
    }
}
