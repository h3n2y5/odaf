<?php

declare(strict_types=1);

use Odaf\Compiler\Contracts\CompilerDiagnosticInterface;
use Odaf\Compiler\MetadataCompiler;
use Odaf\Support\Identity\UlidIdentityGenerator;
use Tests\Support\GraphFactory;

function wfCompiler(): MetadataCompiler
{
    return new MetadataCompiler(new UlidIdentityGenerator);
}

/**
 * @param  array<int, CompilerDiagnosticInterface>  $diagnostics
 * @return array<int, string>
 */
function diagnosticCodes(array $diagnostics): array
{
    return array_map(static fn (CompilerDiagnosticInterface $d): string => $d->code(), $diagnostics);
}

it('mengompilasi graph workflow ke dalam package', function (): void {
    $payload = wfCompiler()->compile(GraphFactory::withWorkflow())->toArray();

    $wfId = 'WF00000000000000000000000000050';
    expect($payload['workflows'])->toHaveKey($wfId)
        ->and($payload['workflows'][$wfId]['code'])->toBe('WF_CUSTOMER_APPROVAL')
        ->and($payload['workflows'][$wfId]['initialActivityId'])->toBe('AC00000000000000000000000000051')
        ->and($payload['workflows'][$wfId]['activities'])->toHaveCount(3)
        ->and($payload['workflows'][$wfId]['transitions'])->toHaveCount(2);
});

it('mengindeks workflow berdasarkan dataset', function (): void {
    $payload = wfCompiler()->compile(GraphFactory::withWorkflow())->toArray();

    expect($payload['index']['workflowByDataset'])
        ->toHaveKey('DS00000000000000000000000000020')
        ->and($payload['index']['workflowByDataset']['DS00000000000000000000000000020'])
        ->toBe('WF00000000000000000000000000050');
});

it('tetap deterministik dengan workflow', function (): void {
    $a = wfCompiler()->compile(GraphFactory::withWorkflow());
    $b = wfCompiler()->compile(GraphFactory::withWorkflow());

    expect($a->checksum())->toBe($b->checksum());
});

it('gagal validasi ketika workflow tanpa activity akhir (WF-004)', function (): void {
    $diagnostics = wfCompiler()->validate(GraphFactory::withWorkflow(['finalOnApproved' => false]));

    expect(diagnosticCodes($diagnostics))->toContain('ODAF-CMP-1604');
});

it('workflow valid tidak menghasilkan error', function (): void {
    $errors = array_filter(
        wfCompiler()->validate(GraphFactory::withWorkflow()),
        static fn (CompilerDiagnosticInterface $d): bool => $d->severity() === CompilerDiagnosticInterface::SEVERITY_ERROR,
    );

    expect($errors)->toBeEmpty();
});
