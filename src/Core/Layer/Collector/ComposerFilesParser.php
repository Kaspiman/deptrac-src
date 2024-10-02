<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Layer\Collector;

use JsonException;
use RuntimeException;

/**
 * @psalm-type Autoload = array{'psr-0'?: array<string, string>, 'psr-4'?: array<string, string>}
 * @psalm-type Package = array{name: string, autoload?: Autoload, autoload-dev?: Autoload}
 * @psalm-type GroupedPackages = array{packages: array<string, Package>, packages-dev: array<string, Package>}
 */
class ComposerFilesParser
{
    /**
     * @var GroupedPackages
     */
    private array $lockedPackages;

    public function __construct(string $lockFile)
    {
        $this->lockedPackages = $this->getPackagesFromLockFile($lockFile);
    }

    /**
     * Resolves an array of package names to an array of namespaces declared by those packages.
     *
     * @param string[] $requirements
     *
     * @return string[]
     */
    public function autoloadableNamespacesForRequirements(array $requirements, bool $include, bool $includeDev): array
    {
        /**
         * @var array<string, array<string>> $result
         */
        $result = ['' => ['']];

        if ($include) {
            $result += $this->iterate($this->lockedPackages['packages'], false);
        }

        if ($includeDev) {
            $result += $this->iterate($this->lockedPackages['packages-dev'], true);
        }

        if ($requirements !== []) {
            $filtered = [];
            foreach ($requirements as $packageName) {
                if (isset($result[$packageName])) {
                    $filtered[] = $result[$packageName];
                }
            }

            $result = $filtered;
        }

        return array_merge(...array_values($result));
    }

    /**
     * @param array<Package> $packages
     * @return array<string, array<string>>
     */
    private function iterate(array $packages, bool $includeDev): array
    {
        $result = [];

        foreach ($packages as $package) {
            foreach ($this->extractNamespaces($package, $includeDev) as $packageName => $namespace) {
                $result[$packageName][] = $namespace;
            }
        }

        return $result;
    }

    /**
     * @throws RuntimeException
     * @return GroupedPackages
     */
    private function getPackagesFromLockFile(string $lockFile): array
    {
        $contents = file_get_contents($lockFile);
        if (false === $contents) {
            throw new RuntimeException('Could not load composer.lock file');
        }
        try {
            /**
             * @var array{packages: array<string, Package>, packages-dev: array<string, Package>} $lockPackages
             */
            $lockPackages = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Could not parse composer.lock file', 0, $exception);
        }

        // Group packages by dev and non-dev principle
        return [
            'packages' => array_column($lockPackages['packages'], null, 'name'),
            'packages-dev' => array_column($lockPackages['packages-dev'], null, 'name'),
        ];
    }

    /**
     * @param Package $package
     * @return array<string, string>
     */
    private function extractNamespaces(array $package, bool $includeDev): array
    {
        $namespaces = [];

        foreach (array_keys($package['autoload']['psr-0'] ?? []) as $namespace) {
            $namespaces[$package['name']] = $namespace;
        }

        foreach (array_keys($package['autoload']['psr-4'] ?? []) as $namespace) {
            $namespaces[$package['name']] = $namespace;
        }

        if ($includeDev) {
            foreach (array_keys($package['autoload-dev']['psr-0'] ?? []) as $namespace) {
                $namespaces[$package['name']] = $namespace;
            }
            foreach (array_keys($package['autoload-dev']['psr-4'] ?? []) as $namespace) {
                $namespaces[$package['name']] = $namespace;
            }
        }

        return $namespaces;
    }
}
