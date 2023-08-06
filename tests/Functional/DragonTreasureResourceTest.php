<?php

namespace Functional;

use App\Entity\ApiToken;
use App\Factory\ApiTokenFactory;
use App\Factory\UserFactory;
use Zenstruck\Browser\HttpOptions;
use App\Tests\Functional\ApiTestCase;
use App\Factory\DragonTreasureFactory;
use Zenstruck\Foundry\Test\ResetDatabase;

class DragonTreasureResourceTest extends ApiTestCase
{
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

    public function testPostToCreateTreasure(): void
    {
        $user = UserFactory::createOne();
        $this->browser()
            ->actingAs($user)
            ->post('/api/treasures', [
                'json' => [],
            ])
            ->assertStatus(422)
            ->post('/api/treasures', HttpOptions::json([
                'name' => 'A shiny thing',
                'description' => 'It sparkles when I wave it in the air.',
                'value' => 1000,
                'coolFactor' => 5,
                'owner' => '/api/users/'.$user->getId(),
            ]))
            ->assertStatus(201)
            ->dump()
            ->assertJsonMatches('name', 'A shiny thing')
        ;
    }

    public function testPostToCreateTreasureDeniedWithoutScope(): void
    {
        $token = ApiTokenFactory::createOne([
            'scopes' => [ApiToken::SCOPE_TREASURE_EDIT]
        ]);
        $this->browser()
            ->post('/api/treasures', [
                'json' => [],
                'headers' => [
                    'Authorization' => 'Bearer '.$token->getToken()
                ]
            ])
            ->assertStatus(403)
        ;
    }

    public function testPatchToUpdateTreasure()
    {
        $user = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne(['owner' => $user]);
        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'.$treasure->getId(), [
                'json' => [
                    'value' => 12345,
                ],
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 12345)
        ;
        $user2 = UserFactory::createOne();
        $this->browser()
            ->actingAs($user2)
            ->patch('/api/treasures/'.$treasure->getId(), [
                'json' => [
                    'value' => 6789,
                    // be tricky and try to change the owner
                    'owner' => '/api/users/'.$user2->getId(),
                ],
            ])
            ->assertStatus(403)
        ;
        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'.$treasure->getId(), [
                'json' => [
                    // change the owner to someone else
                    'owner' => '/api/users/'.$user2->getId(),
                ],
            ])
            ->assertStatus(403)
        ;
    }
}
