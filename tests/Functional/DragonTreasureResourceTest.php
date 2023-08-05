<?php

namespace Functional;

use App\Factory\DragonTreasureFactory;
use Monolog\Test\TestCase;
use Zenstruck\Browser\Json;
use Zenstruck\Browser\Test\HasBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class DragonTreasureResourceTest extends KernelTestCase
{

    use HasBrowser;
    use ResetDatabase;

    public function testGetCollectionOfTreasure(): void
    {
        DragonTreasureFactory::createMany(5);

        $json = $this->browser()
            ->get('/api/treasures')
            ->assertJson()
            ->assertJsonMatches('"hydra:totalItems"', 5)
            ->json()
            ;

            $this->assertSame(array_keys($json->decoded()['hydra:member'][0]), [
                '@id',
                '@type',
                'name',
                'description',
                'value',
                'coolFactor',
                'owner',
                'shortDescription',
                'plunderedAtAgo',
            ]);
    }
}
