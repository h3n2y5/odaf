<?php

declare(strict_types=1);

use App\Auth\OdafUser;

function makeUser(): OdafUser
{
    return new OdafUser(
        objectId: '00000000000000000000000000000001',
        username: 'admin',
        name: 'System Administrator',
        email: 'admin@odaf.local',
        passwordHash: '$2y$12$abc',
        roleIds: ['00000000000000000000000000000002'],
    );
}

it('mengekspos identitas auth sesuai konvensi RAW(16) hex', function (): void {
    $user = makeUser();

    expect($user->getAuthIdentifierName())->toBe('OBJECT_ID')
        ->and($user->getAuthIdentifier())->toBe('00000000000000000000000000000001')
        ->and($user->getAuthPasswordName())->toBe('PASSWORD_HASH')
        ->and($user->getAuthPassword())->toBe('$2y$12$abc');
});

it('membawa OBJECT_ID role untuk RBAC', function (): void {
    expect(makeUser()->roleIds())->toBe(['00000000000000000000000000000002']);
});

it('menonaktifkan remember-me (tanpa token)', function (): void {
    $user = makeUser();
    $user->setRememberToken('x');

    expect($user->getRememberToken())->toBe('')
        ->and($user->getRememberTokenName())->toBe('');
});
