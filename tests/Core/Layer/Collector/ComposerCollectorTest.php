<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Core\Layer\Collector;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Contract\Layer\InvalidCollectorDefinitionException;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeReference;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeToken;
use Qossmic\Deptrac\Core\Ast\AstMap\ClassLike\ClassLikeType;
use Qossmic\Deptrac\Core\Layer\Collector\ComposerCollector;

final class ComposerCollectorTest extends TestCase
{
    private ComposerCollector $sut;

    public function setUp(): void
    {
        $this->sut = new ComposerCollector();
    }

    public static function dataProviderSatisfy(): iterable
    {
        yield [
            [
                'composerPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.json',
                'composerLockPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.lock',
                'packages' => ['phpstan/phpdoc-parser'],
                'include' => true,
                'includeDev' => true,
            ],
            'PHPStan\\PhpDocParser\\Ast\\Attribute',
            true,
        ];
        yield [
            [
                'composerPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.json',
                'composerLockPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.lock',
                'packages' => ['phpstan/phpdoc-parser'],
                'include' => true,
                'includeDev' => true,
            ],
            'Completely\\Wrong\\Namespace\\Attribute',
            false,
        ];
        yield [
            [
                'composerPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.json',
                'composerLockPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.lock',
                'packages' => ['friendsofphp/php-cs-fixer'],
                'include' => true,
                'includeDev' => true,
            ],
            'PhpCsFixer\\FileReader',
            true,
        ];
        yield [
            [
                'composerPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.json',
                'composerLockPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.lock',
                'packages' => [],
                'include' => false,
                'includeDev' => true,
            ],
            'PhpCsFixer\\Config',
            true,
        ];
        yield [
            [
                'composerPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.json',
                'composerLockPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.lock',
                'packages' => [],
                'include' => true,
                'includeDev' => false,
            ],
            'PhpCsFixer\\Config',
            false,
        ];
    }

    /**
     * @dataProvider dataProviderSatisfy
     */
    public function testSatisfy(array $configuration, string $className, bool $expected): void
    {
        $stat = $this->sut->satisfy(
            $configuration,
            new ClassLikeReference(ClassLikeToken::fromFQCN($className), ClassLikeType::TYPE_CLASS),
        );

        self::assertSame($expected, $stat);
    }

    public function testComposerPackageDoesNotExist(): void
    {
        $this->expectException(InvalidCollectorDefinitionException::class);

        $this->sut->satisfy(
            [
                'composerPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.json',
                'composerLockPath' => __DIR__.DIRECTORY_SEPARATOR.'data/composer.lock',
                'packages' => ['fake_package'],
                'includeDev' => true
            ],
            new ClassLikeReference(ClassLikeToken::fromFQCN(''), ClassLikeType::TYPE_CLASS),
        );
    }
}
