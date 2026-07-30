<?php
namespace Tests\Support\Traits;

trait AuthTrait
{
    protected function loginAsHead(): void
    {
        $this->loginAs(1); // user id 1 = jayaputra = head
    }

    protected function loginAsAdmin(): void
    {
        $this->loginAs(2); // user id 2 = tamanindah = admin
    }

    protected function loginAsEmployee(): void
    {
        $this->loginAs(3); // user id 3 = choland = employee
    }

    protected function loginAs(int $userId): void
    {
        $userModel = new \Myth\Auth\Models\UserModel();
        $user = $userModel->find($userId);
        $this->assertNotNull($user, "User with id {$userId} not found");

        $auth = service('authentication');
        $auth->login($user);

        $this->assertTrue($auth->isLoggedIn(), "Failed to login as user {$userId}");
    }

    protected function logout(): void
    {
        $auth = service('authentication');
        $auth->logout();
        $this->assertFalse($auth->isLoggedIn());
    }
}
