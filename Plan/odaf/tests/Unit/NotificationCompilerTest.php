<?php

declare(strict_types=1);

use Odaf\Compiler\Contracts\CompilerDiagnosticInterface;
use Odaf\Compiler\MetadataCompiler;
use Odaf\Support\Identity\UlidIdentityGenerator;
use Tests\Support\GraphFactory;

function ntfCompiler(): MetadataCompiler
{
    return new MetadataCompiler(new UlidIdentityGenerator);
}

it('mengompilasi notifikasi & subscription ke dalam package', function (): void {
    $payload = ntfCompiler()->compile(GraphFactory::withNotifications())->toArray();

    expect($payload['notifications'])->toHaveKey('NT00000000000000000000000000060')
        ->and($payload['notifications']['NT00000000000000000000000000060']['channel'])->toBe('IN_APP')
        ->and($payload['subscriptions'])->toHaveCount(1)
        ->and($payload['subscriptions'][0]['recipientType'])->toBe('ROLE');
});

it('mengindeks subscription berdasarkan event code', function (): void {
    $payload = ntfCompiler()->compile(GraphFactory::withNotifications())->toArray();

    expect($payload['index']['subscriptionsByEvent'])
        ->toHaveKey('WF.WF_CUSTOMER_APPROVAL.SUBMIT')
        ->and($payload['index']['subscriptionsByEvent']['WF.WF_CUSTOMER_APPROVAL.SUBMIT'])
        ->toBe([0]);
});

it('tetap deterministik dengan notifikasi', function (): void {
    $a = ntfCompiler()->compile(GraphFactory::withNotifications());
    $b = ntfCompiler()->compile(GraphFactory::withNotifications());

    expect($a->checksum())->toBe($b->checksum());
});

it('notifikasi valid tidak menghasilkan error', function (): void {
    $errors = array_filter(
        ntfCompiler()->validate(GraphFactory::withNotifications()),
        static fn (CompilerDiagnosticInterface $d): bool => $d->severity() === CompilerDiagnosticInterface::SEVERITY_ERROR,
    );

    expect($errors)->toBeEmpty();
});
