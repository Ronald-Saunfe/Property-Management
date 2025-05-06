<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test forgot password endpoint sends a reset link.
     *
     * @return void
     */
    public function test_forgot_password_sends_reset_link()
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        
        // Laravel should have created a password reset token
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        // Laravel should have sent a notification
        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Test forgot password validation errors.
     *
     * @return void
     */
    public function test_forgot_password_validation_errors()
    {
        $response = $this->postJson('/api/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test forgot password with non-existent email.
     *
     * @return void
     */
    public function test_forgot_password_with_nonexistent_email()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(400);
    }

    /**
     * Test reset password with valid token.
     *
     * @return void
     */
    public function test_reset_password_with_valid_token()
    {
        $user = User::factory()->create();
        
        // Create a password reset token
        $token = app('auth.password.broker')->createToken($user);
        
        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(200);
        
        // Verify the password was updated
        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
        
        // Verify the token was deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    /**
     * Test reset password validation errors.
     *
     * @return void
     */
    public function test_reset_password_validation_errors()
    {
        $response = $this->postJson('/api/reset-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'email', 'password']);
    }

    /**
     * Test reset password with invalid token.
     *
     * @return void
     */
    public function test_reset_password_with_invalid_token()
    {
        $user = User::factory()->create();
        
        $response = $this->postJson('/api/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(400);
        
        // Verify the password was not updated
        $this->assertFalse(Hash::check('newpassword', $user->fresh()->password));
    }

    /**
     * Test reset password with mismatched password confirmation.
     *
     * @return void
     */
    public function test_reset_password_with_mismatched_confirmation()
    {
        $user = User::factory()->create();
        $token = app('auth.password.broker')->createToken($user);
        
        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}