<?php

declare(strict_types=1);

/*
 * UserFrosting Core Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-core
 * @copyright Copyright (c) 2013-2024 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-core/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\Core;

use Lcharette\WebpackEncoreTwig\EntrypointsTwigExtension;
use Lcharette\WebpackEncoreTwig\VersionedAssetsTwigExtension;
use UserFrosting\Event\AppInitiatedEvent;
use UserFrosting\Event\BakeryInitiatedEvent;
use UserFrosting\Event\EventListenerRecipe;
use UserFrosting\Sprinkle\BakeryRecipe;
use UserFrosting\Sprinkle\Core\Bakery\AssetsBuildCommand;
use UserFrosting\Sprinkle\Core\Bakery\AssetsInstallCommand;
use UserFrosting\Sprinkle\Core\Bakery\AssetsUpdateCommand;
use UserFrosting\Sprinkle\Core\Bakery\AssetsWebpackCommand;
use UserFrosting\Sprinkle\Core\Bakery\BakeCommand;
use UserFrosting\Sprinkle\Core\Bakery\ClearCacheCommand;
use UserFrosting\Sprinkle\Core\Bakery\DebugCommand;
use UserFrosting\Sprinkle\Core\Bakery\DebugConfigCommand;
use UserFrosting\Sprinkle\Core\Bakery\DebugDbCommand;
use UserFrosting\Sprinkle\Core\Bakery\DebugEventsCommand;
use UserFrosting\Sprinkle\Core\Bakery\DebugLocatorCommand;
use UserFrosting\Sprinkle\Core\Bakery\DebugMailCommand;
use UserFrosting\Sprinkle\Core\Bakery\DebugTwigCommand;
use UserFrosting\Sprinkle\Core\Bakery\DebugVersionCommand;
use UserFrosting\Sprinkle\Core\Bakery\LocaleCompareCommand;
use UserFrosting\Sprinkle\Core\Bakery\LocaleDictionaryCommand;
use UserFrosting\Sprinkle\Core\Bakery\LocaleInfoCommand;
use UserFrosting\Sprinkle\Core\Bakery\MigrateCleanCommand;
use UserFrosting\Sprinkle\Core\Bakery\MigrateCommand;
use UserFrosting\Sprinkle\Core\Bakery\MigrateRefreshCommand;
use UserFrosting\Sprinkle\Core\Bakery\MigrateResetCommand;
use UserFrosting\Sprinkle\Core\Bakery\MigrateResetHardCommand;
use UserFrosting\Sprinkle\Core\Bakery\MigrateRollbackCommand;
use UserFrosting\Sprinkle\Core\Bakery\MigrateStatusCommand;
use UserFrosting\Sprinkle\Core\Bakery\RouteListCommand;
use UserFrosting\Sprinkle\Core\Bakery\SeedCommand;
use UserFrosting\Sprinkle\Core\Bakery\SeedListCommand;
use UserFrosting\Sprinkle\Core\Bakery\ServeCommand;
use UserFrosting\Sprinkle\Core\Bakery\SetupCommand;
use UserFrosting\Sprinkle\Core\Bakery\SetupDbCommand;
use UserFrosting\Sprinkle\Core\Bakery\SetupEnvCommand;
use UserFrosting\Sprinkle\Core\Bakery\SetupMailCommand;
use UserFrosting\Sprinkle\Core\Bakery\SprinkleListCommand;
use UserFrosting\Sprinkle\Core\Bakery\TestMailCommand;
use UserFrosting\Sprinkle\Core\Csrf\CsrfGuardMiddleware;
use UserFrosting\Sprinkle\Core\Database\Migrations\v400\SessionsTable;
use UserFrosting\Sprinkle\Core\Database\Migrations\v400\ThrottlesTable;
use UserFrosting\Sprinkle\Core\Error\ExceptionHandlerMiddleware;
use UserFrosting\Sprinkle\Core\Error\RegisterShutdownHandler;
use UserFrosting\Sprinkle\Core\Event\ResourceLocatorInitiatedEvent;
use UserFrosting\Sprinkle\Core\Listeners\ModelInitiated;
use UserFrosting\Sprinkle\Core\Listeners\ResourceLocatorInitiated;
use UserFrosting\Sprinkle\Core\Listeners\SetRouteCaching;
use UserFrosting\Sprinkle\Core\Middlewares\FilePermissionMiddleware;
use UserFrosting\Sprinkle\Core\Middlewares\ServerRequestMiddleware;
use UserFrosting\Sprinkle\Core\Middlewares\SessionMiddleware;
use UserFrosting\Sprinkle\Core\Middlewares\URIMiddleware;
use UserFrosting\Sprinkle\Core\Routes\AlertsRoutes;
use UserFrosting\Sprinkle\Core\ServicesProvider\AlertStreamService;
use UserFrosting\Sprinkle\Core\ServicesProvider\CacheService;
use UserFrosting\Sprinkle\Core\ServicesProvider\ConfigService;
use UserFrosting\Sprinkle\Core\ServicesProvider\DatabaseService;
use UserFrosting\Sprinkle\Core\ServicesProvider\ErrorHandlerService;
use UserFrosting\Sprinkle\Core\ServicesProvider\I18nService;
use UserFrosting\Sprinkle\Core\ServicesProvider\LocatorService;
use UserFrosting\Sprinkle\Core\ServicesProvider\LoggersService;
use UserFrosting\Sprinkle\Core\ServicesProvider\MailService;
use UserFrosting\Sprinkle\Core\ServicesProvider\MigratorService;
use UserFrosting\Sprinkle\Core\ServicesProvider\RoutingService;
use UserFrosting\Sprinkle\Core\ServicesProvider\SeedService;
use UserFrosting\Sprinkle\Core\ServicesProvider\SessionService;
use UserFrosting\Sprinkle\Core\ServicesProvider\ThrottlerService;
use UserFrosting\Sprinkle\Core\ServicesProvider\TwigService;
use UserFrosting\Sprinkle\Core\ServicesProvider\VersionsService;
use UserFrosting\Sprinkle\Core\ServicesProvider\WebpackService;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\MigrationRecipe;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\TwigExtensionRecipe;
use UserFrosting\Sprinkle\Core\Twig\Extensions\AlertsExtension;
use UserFrosting\Sprinkle\Core\Twig\Extensions\ConfigExtension;
use UserFrosting\Sprinkle\Core\Twig\Extensions\CoreExtension;
use UserFrosting\Sprinkle\Core\Twig\Extensions\CsrfExtension;
use UserFrosting\Sprinkle\Core\Twig\Extensions\I18nExtension;
use UserFrosting\Sprinkle\Core\Twig\Extensions\RoutesExtension;
use UserFrosting\Sprinkle\MiddlewareRecipe;
use UserFrosting\Sprinkle\SprinkleRecipe;

class Core implements
    SprinkleRecipe,
    TwigExtensionRecipe,
    MigrationRecipe,
    EventListenerRecipe,
    MiddlewareRecipe,
    BakeryRecipe
{
    /**
     * Return the Sprinkle name.
     *
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Core Sprinkle';
    }

    /**
     * Return the Sprinkle dir path.
     *
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return __DIR__ . '/../';
    }

    /**
     * Return an array of all registered Bakery Commands.
     *
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getBakeryCommands(): array
    {
        return [
            AssetsBuildCommand::class,
            AssetsUpdateCommand::class,
            AssetsInstallCommand::class,
            AssetsWebpackCommand::class,
            BakeCommand::class,
            ClearCacheCommand::class,
            DebugCommand::class,
            DebugConfigCommand::class,
            DebugDbCommand::class,
            DebugEventsCommand::class,
            DebugLocatorCommand::class,
            DebugMailCommand::class,
            DebugTwigCommand::class,
            DebugVersionCommand::class,
            LocaleCompareCommand::class,
            LocaleDictionaryCommand::class,
            LocaleInfoCommand::class,
            MigrateCommand::class,
            MigrateCleanCommand::class,
            MigrateRefreshCommand::class,
            MigrateResetCommand::class,
            MigrateResetHardCommand::class,
            MigrateRollbackCommand::class,
            MigrateStatusCommand::class,
            RouteListCommand::class,
            SeedCommand::class,
            SeedListCommand::class,
            ServeCommand::class,
            SetupCommand::class,
            SetupDbCommand::class,
            SetupEnvCommand::class,
            SetupMailCommand::class,
            SprinkleListCommand::class,
            TestMailCommand::class,
        ];
    }

    /**
     * Return dependent sprinkles.
     *
     * {@inheritdoc}
     */
    public function getSprinkles(): array
    {
        return [];
    }

    /**
     * Returns a list of routes definition in PHP files.
     *
     * {@inheritDoc}
     */
    public function getRoutes(): array
    {
        return [
            AlertsRoutes::class,
        ];
    }

    /**
     * Returns a list of all PHP-DI services/container definitions class.
     *
     * {@inheritDoc}
     */
    public function getServices(): array
    {
        return [
            AlertStreamService::class,
            CacheService::class,
            ConfigService::class,
            DatabaseService::class,
            ErrorHandlerService::class,
            I18nService::class,
            LocatorService::class,
            LoggersService::class,
            MailService::class,
            MigratorService::class,
            RoutingService::class,
            SeedService::class,
            SessionService::class,
            ThrottlerService::class,
            TwigService::class,
            VersionsService::class,
            WebpackService::class,
        ];
    }

    /**
     * Returns a list of all Middlewares classes.
     *
     * {@inheritDoc}
     */
    public function getMiddlewares(): array
    {
        return [
            ServerRequestMiddleware::class,
            CsrfGuardMiddleware::class,
            SessionMiddleware::class,
            URIMiddleware::class,
            FilePermissionMiddleware::class,
            ExceptionHandlerMiddleware::class,
        ];
    }

    /**
     * Return an array of all registered Twig Extensions.
     *
     * {@inheritDoc}
     */
    public function getTwigExtensions(): array
    {
        return [
            CoreExtension::class,
            ConfigExtension::class,
            CsrfExtension::class,
            I18nExtension::class,
            AlertsExtension::class,
            RoutesExtension::class,
            EntrypointsTwigExtension::class,
            VersionedAssetsTwigExtension::class,
        ];
    }

    /**
     * Return an array of all registered Migrations.
     *
     * {@inheritDoc}
     */
    public function getMigrations(): array
    {
        return [
            SessionsTable::class,
            ThrottlesTable::class,
        ];
    }

    /**
     * Return a map of all registered event listener.
     *
     * {@inheritdoc}
     */
    public function getEventListeners(): array
    {
        return [
            AppInitiatedEvent::class => [
                RegisterShutdownHandler::class,
                ModelInitiated::class,
                SetRouteCaching::class,
            ],
            BakeryInitiatedEvent::class => [
                ModelInitiated::class,
                SetRouteCaching::class,
            ],
            ResourceLocatorInitiatedEvent::class => [
                ResourceLocatorInitiated::class,
            ],
        ];
    }
}
