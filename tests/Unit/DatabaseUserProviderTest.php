<?php

namespace Imanghafoori\MasterPass\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Imanghafoori\MasterPass\MasterPassDatabaseUserProvider;
use Imanghafoori\MasterPass\Models\User;

class DatabaseUserProviderTest extends TestCase
{
    protected $user;
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->make(['password' => Hash::make('user_passowrd')]);
        $this->provider = new MasterPassDatabaseUserProvider($this->app->db->connection(), $this->app->hash, 'users');
    }

    /** @test */
    public function validates_user_credentials_without_master_pass()
    {
        $isValid = $this->provider->validateCredentials($this->user, ['password' => 'user_passowrd']);
        $this->assertTrue($isValid);

        $isValid = $this->provider->validateCredentials($this->user, ['password' => 'wrong_password']);
        $this->assertFalse($isValid);
    }

    /** @test */
    public function validates_user_credentials_by_hashed_master_pass()
    {
        config()->set('master_password.MASTER_PASSWORD', Hash::make('masterpass'));
        $this->provider = new MasterPassDatabaseUserProvider($this->app->db->connection(), $this->app->hash, 'users');

        $isValid = $this->provider->validateCredentials($this->user, ['password' => 'masterpass']);
        $this->assertTrue($isValid);
    }

    /** @test */
    public function validates_user_credentials_by_plain_text_master_pass()
    {
        config()->set('master_password.MASTER_PASSWORD', 'masterpass');
        $this->provider = new MasterPassDatabaseUserProvider($this->app->db->connection(), $this->app->hash, 'users');

        $isValid = $this->provider->validateCredentials($this->user, ['password' => 'masterpass']);
        $this->assertTrue($isValid);
    }

    /** @test */
    public function validates_user_credentials_by_dynamic_master_pass()
    {
        $password = 'masterpass';
        $credentials = ['password' => $password];

        $this->provider = new MasterPassDatabaseUserProvider($this->app->db->connection(), $this->app->hash, 'users');

        Event::partialMock()->shouldReceive('dispatch')
            ->with('masterPass.whatIsIt?', [$this->user, $credentials], true)
            ->once()
            ->andReturn($password);

        $isValid = $this->provider->validateCredentials($this->user, $credentials);

        $this->assertTrue($isValid);
    }
}
