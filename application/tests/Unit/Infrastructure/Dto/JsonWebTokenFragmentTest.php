<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Dto;

use App\Infrastructure\Dto\JsonWebTokenFragment;
use PHPUnit\Framework\TestCase;

class JsonWebTokenFragmentTest extends TestCase
{
    public function test_cria_fragmento_jwt_com_dados_validos(): void
    {
        $fragment = new JsonWebTokenFragment(
            sub: 'user-uuid-123',
            iss: 'https://app.test',
            aud: 'https://app.test',
            iat: 1700000000,
            exp: 1700086400,
            nbf: 1700000000,
        );

        $this->assertEquals('user-uuid-123', $fragment->sub);
        $this->assertEquals('https://app.test', $fragment->iss);
        $this->assertEquals('https://app.test', $fragment->aud);
        $this->assertEquals(1700000000, $fragment->iat);
        $this->assertEquals(1700086400, $fragment->exp);
        $this->assertEquals(1700000000, $fragment->nbf);
    }

    public function test_to_associative_array(): void
    {
        $fragment = new JsonWebTokenFragment(
            sub: 'user-uuid-123',
            iss: 'https://app.test',
            aud: 'https://app.test',
            iat: 1700000000,
            exp: 1700086400,
            nbf: 1700000000,
        );

        $array = $fragment->toAssociativeArray();

        $this->assertIsArray($array);
        $this->assertEquals('user-uuid-123', $array['sub']);
        $this->assertEquals('https://app.test', $array['iss']);
        $this->assertEquals('https://app.test', $array['aud']);
        $this->assertEquals(1700000000, $array['iat']);
        $this->assertEquals(1700086400, $array['exp']);
        $this->assertEquals(1700000000, $array['nbf']);
        $this->assertCount(6, $array);
    }

    public function test_propriedades_sao_readonly(): void
    {
        $fragment = new JsonWebTokenFragment(
            sub: 'user-uuid-123',
            iss: 'https://app.test',
            aud: 'https://app.test',
            iat: 1700000000,
            exp: 1700086400,
            nbf: 1700000000,
        );

        $reflection = new \ReflectionClass($fragment);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }
    }
}
