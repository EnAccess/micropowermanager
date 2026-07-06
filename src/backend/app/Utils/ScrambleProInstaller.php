<?php

declare(strict_types=1);

namespace App\Utils;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositorySet;
use Composer\Script\Event;

/**
 * Composer script (wired to pre-autoload-dump in composer.json) that installs
 * dedoc/scramble-pro (license-gated, private satis repository) into vendor/ whenever
 * credentials for satis.dedoc.co are available — developer machines with a license key
 * in auth.json as well as CI with a COMPOSER_AUTH secret. The package is deliberately
 * never written to composer.lock so machines without credentials can install the
 * project unaffected; without credentials this script silently skips.
 *
 * pre-autoload-dump fires before Composer builds the autoloader package map, so the
 * package installed here ends up in the dumped autoloader and in
 * vendor/composer/installed.json before Laravel's package:discover runs.
 *
 * The Composer\* classes are provided by the running composer binary, not by vendor/,
 * which is why this file is excluded from PHPStan analysis.
 */
class ScrambleProInstaller {
    private const PACKAGE = 'dedoc/scramble-pro';
    private const DEFAULT_CONSTRAINT = '*';
    private const REPOSITORY_URL = 'https://satis.dedoc.co';
    private const AUTH_HOST = 'satis.dedoc.co';

    private static bool $running = false;

    public static function preAutoloadDump(Event $event): void {
        if (self::$running) {
            return;
        }

        $composer = $event->getComposer();
        $io = $event->getIO();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        // The version constraint is configured in composer.json under
        // extra.scramble-pro-installer.constraint.
        $constraint = $composer->getPackage()->getExtra()['scramble-pro-installer']['constraint'] ?? self::DEFAULT_CONSTRAINT;

        if ($localRepo->findPackage(self::PACKAGE, $constraint) !== null) {
            return;
        }
        if (!$io->hasAuthentication(self::AUTH_HOST)) {
            $io->write(
                sprintf('scramble-pro-installer: no credentials for %s configured, skipping %s', self::AUTH_HOST, self::PACKAGE),
                true,
                IOInterface::VERBOSE
            );

            return;
        }

        self::$running = true;
        try {
            self::install($composer, $io, $localRepo, $constraint, $event->isDevMode());
        } catch (\Throwable $exception) {
            // Network/auth failures must not break the main composer command.
            $io->writeError(sprintf(
                '<warning>scramble-pro-installer: %s — %s was not installed. '
                .'Run "composer dump-autoload" once the issue is resolved.</warning>',
                $exception->getMessage(),
                self::PACKAGE
            ));
        } finally {
            self::$running = false;
        }
    }

    private static function install(Composer $composer, IOInterface $io, InstalledRepositoryInterface $localRepo, string $constraint, bool $devMode): void {
        $rootPackage = $composer->getPackage();
        $repositorySet = new RepositorySet($rootPackage->getMinimumStability(), $rootPackage->getStabilityFlags());
        // Created through the RepositoryManager so the root HttpDownloader/IO is
        // reused and the satis credentials from auth.json/COMPOSER_AUTH apply.
        $repositorySet->addRepository(
            $composer->getRepositoryManager()->createRepository('composer', ['type' => 'composer', 'url' => self::REPOSITORY_URL])
        );

        $platformRepo = new PlatformRepository([], $composer->getConfig()->get('platform') ?: []);
        $target = (new VersionSelector($repositorySet, $platformRepo))
            ->findBestCandidate(self::PACKAGE, $constraint, 'stable', null, 0, $io);
        if ($target === false) {
            throw new \RuntimeException(sprintf('could not resolve %s (%s) from %s', self::PACKAGE, $constraint, self::REPOSITORY_URL));
        }
        while ($target instanceof AliasPackage) {
            $target = $target->getAliasOf();
        }

        $installed = $localRepo->findPackage(self::PACKAGE, '*');
        $operation = $installed !== null
            ? new UpdateOperation($installed, $target)
            : new InstallOperation($target);

        $io->write(sprintf(
            '<info>scramble-pro-installer: installing %s (%s) — not tracked in composer.lock</info>',
            $target->getName(),
            $target->getFullPrettyVersion()
        ));

        // runScripts=false suppresses package events during this nested install.
        // execute() downloads, installs into vendor/ and writes installed.json plus
        // installed.php itself.
        $composer->getInstallationManager()->execute($localRepo, [$operation], $devMode, false);
    }
}
