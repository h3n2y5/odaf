<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\OdafUserProvider;
use App\Console\Commands\CompileApplicationCommand;
use App\Console\Commands\ScaffoldTableCommand;
use App\Console\Commands\SetUserPasswordCommand;
use App\Console\Commands\ExportMetadataCommand;
use App\Console\Commands\ImportMetadataCommand;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\ServiceProvider;
use Odaf\Compiler\Contracts\MetadataCompilerInterface;
use Odaf\Compiler\MetadataCompiler;
use Odaf\Engine\Audit\Contracts\AuditEngineInterface;
use Odaf\Engine\Audit\OracleAuditEngine;
use Odaf\Engine\Dataset\Contracts\DatasetEngineInterface;
use Odaf\Engine\Dataset\OracleDatasetEngine;
use Odaf\Engine\Lov\Contracts\LovEngineInterface;
use Odaf\Engine\Lov\OracleLovEngine;
use Odaf\Engine\Notification\Channel\InAppChannel;
use Odaf\Engine\Notification\Channel\LogChannel;
use Odaf\Engine\Notification\Contracts\NotificationEngineInterface;
use Odaf\Engine\Notification\OracleNotificationEngine;
use Odaf\Engine\Render\Contracts\RendererInterface;
use Odaf\Engine\Render\HtmlRenderer;
use Odaf\Engine\Security\Contracts\SecurityEngineInterface;
use Odaf\Engine\Security\RbacSecurityEngine;
use Odaf\Engine\Validation\Contracts\ValidationEngineInterface;
use Odaf\Engine\Validation\MetadataValidationEngine;
use Odaf\Engine\Workflow\Contracts\WorkflowEngineInterface;
use Odaf\Engine\Workflow\OracleWorkflowEngine;
use Odaf\Metadata\Contracts\MetadataRepositoryInterface;
use Odaf\Metadata\Oracle\OracleMetadataRepository;
use Odaf\Runtime\Contracts\RuntimeKernelInterface;
use Odaf\Runtime\RuntimePackageRepository;
use Odaf\Runtime\ServiceRegistry;
use Odaf\Runtime\UnifiedRuntimeKernel;
use Odaf\Studio\MetadataScaffolder;
use Odaf\Studio\TableDataManager;
use Odaf\Studio\TableIntrospector;
use Odaf\Support\Identity\IdentityGeneratorInterface;
use Odaf\Support\Identity\UlidIdentityGenerator;
use Odaf\Support\Identity\UuidV7IdentityGenerator;

/**
 * Mendaftarkan binding building block ODAF ke service container.
 *
 * Aturan dependensi (Vol.1 Bab 10) ditegakkan lewat koneksi terpisah:
 *  - 'oracle'          : metadata design-time (BB-01) + data bisnis (BB-05).
 *  - 'oracle_runtime'  : Runtime Repository RT_* (isolasi CORE-004).
 */
final class OdafServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- Identity (OBJECT_ID) -------------------------------------------
        $this->app->singleton(IdentityGeneratorInterface::class, function (): IdentityGeneratorInterface {
            return match (env('ODAF_ID_STRATEGY', 'ulid')) {
                'uuid7' => new UuidV7IdentityGenerator,
                default => new UlidIdentityGenerator,
            };
        });

        // --- BB-01 Metadata Repository (design-time) ------------------------
        $this->app->singleton(MetadataRepositoryInterface::class, function (Application $app): MetadataRepositoryInterface {
            return new OracleMetadataRepository(DB::connection($this->designConnection()));
        });

        // --- Studio: Metadata Scaffolder (generator dari tabel Oracle) ------
        $this->app->singleton(MetadataScaffolder::class, function (Application $app): MetadataScaffolder {
            return new MetadataScaffolder(
                DB::connection($this->designConnection()),
                $app->make(IdentityGeneratorInterface::class),
            );
        });

        // --- Studio: Data Manager (CRUD generik berbasis introspeksi) -------
        $this->app->singleton(TableIntrospector::class, function (Application $app): TableIntrospector {
            return new TableIntrospector(DB::connection($this->designConnection()));
        });
        $this->app->singleton(TableDataManager::class, function (Application $app): TableDataManager {
            return new TableDataManager(
                DB::connection($this->designConnection()),
                $app->make(TableIntrospector::class),
                $app->make(IdentityGeneratorInterface::class),
            );
        });

        // --- BB-02 Metadata Compiler ----------------------------------------
        $this->app->singleton(MetadataCompilerInterface::class, function (Application $app): MetadataCompilerInterface {
            return new MetadataCompiler($app->make(IdentityGeneratorInterface::class));
        });

        // --- Runtime Package Repository (RT_*) ------------------------------
        $this->app->singleton(RuntimePackageRepository::class, function (Application $app): RuntimePackageRepository {
            return new RuntimePackageRepository(
                DB::connection($this->runtimeConnection()),
                $app->make(IdentityGeneratorInterface::class),
            );
        });

        // --- Service Registry (Vol.3 Bab 09) --------------------------------
        $this->app->singleton(ServiceRegistry::class, function (Application $app): ServiceRegistry {
            $registry = new ServiceRegistry;
            $registry->register(DatasetEngineInterface::class, fn (): object => $app->make(DatasetEngineInterface::class));
            $registry->register(SecurityEngineInterface::class, fn (): object => $app->make(SecurityEngineInterface::class));
            $registry->register(ValidationEngineInterface::class, fn (): object => $app->make(ValidationEngineInterface::class));
            $registry->register(LovEngineInterface::class, fn (): object => $app->make(LovEngineInterface::class));
            $registry->register(RendererInterface::class, fn (): object => $app->make(RendererInterface::class));
            $registry->register(AuditEngineInterface::class, fn (): object => $app->make(AuditEngineInterface::class));
            $registry->register(WorkflowEngineInterface::class, fn (): object => $app->make(WorkflowEngineInterface::class));
            $registry->register(NotificationEngineInterface::class, fn (): object => $app->make(NotificationEngineInterface::class));

            return $registry;
        });

        // --- BB-03 Unified Runtime Kernel -----------------------------------
        $this->app->singleton(RuntimeKernelInterface::class, function (Application $app): RuntimeKernelInterface {
            return new UnifiedRuntimeKernel($app->make(ServiceRegistry::class));
        });
        $this->app->alias(RuntimeKernelInterface::class, UnifiedRuntimeKernel::class);

        // --- Engines --------------------------------------------------------
        $this->app->singleton(DatasetEngineInterface::class, function (Application $app): DatasetEngineInterface {
            return new OracleDatasetEngine(
                DB::connection($this->designConnection()),
                $app->make(UnifiedRuntimeKernel::class),
                $app->make(IdentityGeneratorInterface::class),
            );
        });

        $this->app->singleton(ValidationEngineInterface::class, function (Application $app): ValidationEngineInterface {
            return new MetadataValidationEngine(
                $app->make(UnifiedRuntimeKernel::class),
                DB::connection($this->designConnection()),
            );
        });

        $this->app->singleton(LovEngineInterface::class, function (Application $app): LovEngineInterface {
            return new OracleLovEngine(
                DB::connection($this->designConnection()),
                $app->make(UnifiedRuntimeKernel::class),
            );
        });

        $this->app->singleton(SecurityEngineInterface::class, function (Application $app): SecurityEngineInterface {
            return new RbacSecurityEngine(DB::connection($this->designConnection()));
        });

        $this->app->singleton(AuditEngineInterface::class, function (Application $app): AuditEngineInterface {
            return new OracleAuditEngine(
                DB::connection($this->designConnection()),
                $app->make(IdentityGeneratorInterface::class),
            );
        });

        $this->app->singleton(RendererInterface::class, fn (): RendererInterface => new HtmlRenderer);

        $this->app->singleton(NotificationEngineInterface::class, function (Application $app): NotificationEngineInterface {
            $connection = DB::connection($this->designConnection());

            return new OracleNotificationEngine(
                $connection,
                $app->make(UnifiedRuntimeKernel::class),
                [
                    new InAppChannel($connection, $app->make(IdentityGeneratorInterface::class)),
                    new LogChannel,
                ],
            );
        });

        $this->app->singleton(WorkflowEngineInterface::class, function (Application $app): WorkflowEngineInterface {
            return new OracleWorkflowEngine(
                DB::connection($this->designConnection()),
                $app->make(UnifiedRuntimeKernel::class),
                $app->make(IdentityGeneratorInterface::class),
                $app->make(AuditEngineInterface::class),
                $app->make(NotificationEngineInterface::class),
            );
        });
    }

    public function boot(): void
    {
        // Provider autentikasi kustom terhadap SEC_USER.
        Auth::provider('odaf', function (Application $app): OdafUserProvider {
            return new OdafUserProvider(
                DB::connection($this->designConnection()),
                $app->make(Hasher::class),
            );
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                CompileApplicationCommand::class,
                SetUserPasswordCommand::class,
                ScaffoldTableCommand::class,
                ExportMetadataCommand::class,
                ImportMetadataCommand::class,
            ]);
        }
        
        Event::listen(function (Login $event) {
            $this->auditAuth('USER_LOGIN', $event->user);
        });

        Event::listen(function (Logout $event) {
            $this->auditAuth('USER_LOGOUT', $event->user);
        });
    }

    private function auditAuth(string $eventType, mixed $user): void
    {
        try {
            $engine = $this->app->make(AuditEngineInterface::class);
            $userId = $user->getAuthIdentifier();
            // If the user is an array or object, try to get a name
            $username = is_object($user) ? ($user->username ?? $user->email ?? $userId) : $userId;
            
            $context = new class($userId) implements \Odaf\Runtime\Contracts\ExecutionContextInterface {
                public function __construct(private ?string $u) {}
                public function userId(): ?string { return $this->u; }
                public function applicationId(): string { return ''; }
                public function locale(): string { return 'id'; }
                public function roleIds(): array { return []; }
                public function attributes(): array { return ['datasetCode' => 'SEC_USER']; }
            };
            
            $ip = function_exists('request') && request() ? request()->ip() : null;
            $agent = function_exists('request') && request() ? request()->userAgent() : null;
            
            $engine->record($context, $eventType, (string)$userId, (string)$username, [], ['ip' => $ip, 'agent' => $agent]);
        } catch (\Throwable $e) {
            // Ignore audit errors
        }
    }

    /**
     * Koneksi metadata design-time & data bisnis.
     */
    private function designConnection(): string
    {
        return (string) config('database.default', 'oracle');
    }

    /**
     * Koneksi Runtime Repository (RT_*). Untuk dev sama dengan design-time.
     */
    private function runtimeConnection(): string
    {
        $default = (string) config('database.default', 'oracle');

        return config()->has('database.connections.oracle_runtime') && $default === 'oracle'
            ? 'oracle_runtime'
            : $default;
    }
}
