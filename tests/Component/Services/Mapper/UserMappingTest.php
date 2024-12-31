<?php

namespace Tests\Component\Services\Mapper;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Nivseb\LaraMonitor\Services\Mapper;
use Nivseb\LaraMonitor\Struct\User;

test(
    'build user from class that only implements authenticatable contract',
    function (): void {
        $authUser = new class implements Authenticatable {
            /** @phpstan-ignore-next-line */
            public function getAuthIdentifierName(): void
            {
                throw new Exception('Method should not be called');
            }

            public function getAuthIdentifier()
            {
                return 'testUserName';
            }

            /** @phpstan-ignore-next-line */
            public function getAuthPassword(): void
            {
                throw new Exception('Method should not be called');
            }

            /** @phpstan-ignore-next-line */
            public function getRememberToken(): void
            {
                throw new Exception('Method should not be called');
            }

            public function setRememberToken($value): void
            {
                throw new Exception('Method should not be called');
            }

            /** @phpstan-ignore-next-line */
            public function getRememberTokenName(): void
            {
                throw new Exception('Method should not be called');
            }

            public function getAuthPasswordName()
            {
                return 'password';
            }
        };

        $expectedGuard = fake()->word();

        $mapper = new Mapper();

        /** @var User $user */
        $user = $mapper->buildUserFromAuthenticated($expectedGuard, $authUser);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($user->domain)->toBe($expectedGuard)
            ->and($user->id)->toBe('testUserName')
            ->and($user->username)->toBeNull()
            ->and($user->email)->toBeNull();
    }
);

test(
    'build user from class that implements authenticatable contract and has public email property',
    function (): void {
        $authUser = new class implements Authenticatable {
            public string $email = 'test-email@github.com';

            /** @phpstan-ignore-next-line */
            public function getAuthIdentifierName(): void
            {
                throw new Exception('Method should not be called');
            }

            public function getAuthIdentifier()
            {
                return 'testUserName';
            }

            /** @phpstan-ignore-next-line */
            public function getAuthPassword(): void
            {
                throw new Exception('Method should not be called');
            }

            /** @phpstan-ignore-next-line */
            public function getRememberToken(): void
            {
                throw new Exception('Method should not be called');
            }

            public function setRememberToken($value): void
            {
                throw new Exception('Method should not be called');
            }

            /** @phpstan-ignore-next-line */
            public function getRememberTokenName(): void
            {
                throw new Exception('Method should not be called');
            }

            public function getAuthPasswordName()
            {
                return 'password';
            }
        };

        $expectedGuard = fake()->word();

        $mapper = new Mapper();

        /** @var User $user */
        $user = $mapper->buildUserFromAuthenticated($expectedGuard, $authUser);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($user->domain)->toBe($expectedGuard)
            ->and($user->id)->toBe('testUserName')
            ->and($user->username)->toBe('test-email@github.com')
            ->and($user->email)->toBe('test-email@github.com');
    }
);

test(
    'build user from class that implements authenticatable contract and has protected email property',
    function (): void {
        $authUser = new class implements Authenticatable {
            protected string $email = 'test-email@github.com';

            /** @phpstan-ignore-next-line */
            public function getAuthIdentifierName(): void
            {
                throw new Exception('Method should not be called');
            }

            public function getAuthIdentifier()
            {
                return 'testUserName';
            }

            /** @phpstan-ignore-next-line */
            public function getAuthPassword(): void
            {
                throw new Exception('Method should not be called');
            }

            /** @phpstan-ignore-next-line */
            public function getRememberToken(): void
            {
                throw new Exception('Method should not be called');
            }

            public function setRememberToken($value): void
            {
                throw new Exception('Method should not be called');
            }

            /** @phpstan-ignore-next-line */
            public function getRememberTokenName(): void
            {
                throw new Exception('Method should not be called');
            }

            public function getAuthPasswordName()
            {
                return 'password';
            }
        };

        $expectedGuard = fake()->word();

        $mapper = new Mapper();

        /** @var User $user */
        $user = $mapper->buildUserFromAuthenticated($expectedGuard, $authUser);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($user->domain)->toBe($expectedGuard)
            ->and($user->id)->toBe('testUserName')
            ->and($user->username)->toBeNull()
            ->and($user->email)->toBeNull();
    }
);

test(
    'build user from class illuminate user and doesnt have email attribute',
    function (): void {
        $authUser = new class extends \Illuminate\Foundation\Auth\User {
            protected $attributes = ['id' => 186];
        };

        $expectedGuard = fake()->word();

        $mapper = new Mapper();

        /** @var User $user */
        $user = $mapper->buildUserFromAuthenticated($expectedGuard, $authUser);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($user->domain)->toBe($expectedGuard)
            ->and($user->id)->toBe(186)
            ->and($user->username)->toBeNull()
            ->and($user->email)->toBeNull();
    }
);

test(
    'build user from class illuminate user and have email attribute',
    function (): void {
        $authUser = new class extends \Illuminate\Foundation\Auth\User {
            protected $attributes = [
                'id'    => 186,
                'email' => 'test-email@github.com',
            ];
        };

        $expectedGuard = fake()->word();

        $mapper = new Mapper();

        /** @var User $user */
        $user = $mapper->buildUserFromAuthenticated($expectedGuard, $authUser);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($user->domain)->toBe($expectedGuard)
            ->and($user->id)->toBe(186)
            ->and($user->username)->toBe('test-email@github.com')
            ->and($user->email)->toBe('test-email@github.com');
    }
);
