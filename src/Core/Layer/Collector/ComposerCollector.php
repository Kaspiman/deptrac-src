<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Core\Layer\Collector;

use Qossmic\Deptrac\Contract\Ast\CouldNotParseFileException;
use Qossmic\Deptrac\Contract\Ast\TokenReferenceInterface;
use Qossmic\Deptrac\Contract\Layer\CollectorInterface;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;
use RuntimeException;

final class ComposerCollector implements CollectorInterface
{
    /**
     * @var array<string, ComposerFilesParser>
     */
    private array $parser = [];

    public function satisfy(array $config, TokenReferenceInterface $reference): bool
    {
        if (!isset($config['composerPath']) || !is_string($config['composerPath'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('ComposerCollector needs the path to the composer.json file as string.');
        }

        if (!isset($config['composerLockPath']) || !is_string($config['composerLockPath'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('ComposerCollector needs the path to the composer.lock file as string.');
        }

        // packages is optional
        if (isset($config['packages']) && !is_array($config['packages'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('ComposerCollector needs the list of packages as string.');
        }

        if (!isset($config['include']) || !\is_bool($config['include'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('ComposerCollector needs the include for packages as bool.');
        }

        if (!isset($config['includeDev']) || !\is_bool($config['includeDev'])) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('ComposerCollector needs the includeDev for dev-packages as bool.');
        }

        if (!$config['include'] && !$config['includeDev']) {
            throw InvalidCollectorDefinitionException::invalidCollectorConfiguration('ComposerCollector needs at least one true value from the include and includeDev');
        }

        try {
            $parser = $this->parser[$config['composerLockPath']] ??= new ComposerFilesParser($config['composerLockPath']);
        } catch (RuntimeException $exception) {
            throw new CouldNotParseFileException('Could not parse composer files.', 0, $exception);
        }

        /**
         * @var array<string> $packages
         */
        $packages = $config['packages'] !== [] ? $config['packages'] : [];

        $namespaces = $parser->autoloadableNamespacesForRequirements($packages, $config['include'], $config['includeDev']);

        $token = $reference->getToken()->toString();

        foreach ($namespaces as $namespace) {
            if (str_starts_with($token, $namespace)) {
                return true;
            }
        }

        return false;
    }
}
