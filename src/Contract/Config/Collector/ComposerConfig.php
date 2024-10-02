<?php

namespace Qossmic\Deptrac\Contract\Config\Collector;

use Qossmic\Deptrac\Contract\Config\CollectorConfig;
use Qossmic\Deptrac\Contract\Config\CollectorType;

final class ComposerConfig extends CollectorConfig
{
    protected CollectorType $collectorType = CollectorType::TYPE_COMPOSER;

    /** @var list<string> */
    private array $packages = [];

    private function __construct(
        private readonly string $composerPath,
        private readonly string $composerLockPath,
        private readonly bool $include,
        private readonly bool $includeDev,
    ) {}

    /**
     * @param list<string> $packages
     */
    public static function create(string $composerPath = 'composer.json', string $composerLockPath = 'composer.lock', array $packages = [], bool $include = true, bool $includeDev = true): self
    {
        $result = new self($composerPath, $composerLockPath, $include, $includeDev);
        foreach ($packages as $package) {
            $result->addPackage($package);
        }

        return $result;
    }

    public function addPackage(string $package): self
    {
        $this->packages[] = $package;

        return $this;
    }

    /** @return array{
     *     composerPath: string,
     *     composerLockPath: string,
     *     packages: list<string>,
     *     include: bool,
     *     includeDev: bool,
     *     private: bool,
     *     type: string}
     */
    public function toArray(): array
    {
        return [
            'composerPath' => $this->composerPath,
            'composerLockPath' => $this->composerLockPath,
            'packages' => $this->packages,
            'include' => $this->include,
            'includeDev' => $this->includeDev,
            'private' => $this->private,
            'type' => $this->collectorType->value,
        ];
    }
}
