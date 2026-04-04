<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Tests;

use MulerTech\FileBundle\MulerTechFileBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class MulerTechFileBundleTest extends TestCase
{
    public function testBundleExtendsAbstractBundle(): void
    {
        $bundle = new MulerTechFileBundle();

        self::assertInstanceOf(AbstractBundle::class, $bundle);
    }

    public function testBundleHasCorrectAlias(): void
    {
        $bundle = new MulerTechFileBundle();

        self::assertSame('mulertech_file', $bundle->getContainerExtension()->getAlias());
    }
}
