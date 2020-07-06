<?php

declare(strict_types=1);

namespace MeshTest;

use Mesh\Mesh;
use Mesh\Sequence;
use PHPUnit\Framework\TestCase;

/**
 * Class MeshTest
 * @package Mesh
 */
final class MeshTest extends TestCase
{
    public function testMeshHasErrors(): void
    {
        $mesh = new Mesh();

        $this->assertFalse($mesh->hasErrors());
        $this->assertEmpty($mesh->getErrors());
        $this->assertEquals([], $mesh->getErrors());
    }

    public function testValidOutcome()
    {
        $mesh = (new Mesh(['foo' => 'bar']))->add('foo',
            (new Sequence())
                ->rule('required')
        );

        $this->assertTrue($mesh->validate());
    }
}
