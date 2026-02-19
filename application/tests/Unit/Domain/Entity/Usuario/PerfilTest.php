<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Usuario;

use App\Domain\Entity\Usuario\Perfil;
use PHPUnit\Framework\TestCase;

class PerfilTest extends TestCase
{
    public function test_casos_existentes(): void
    {
        $this->assertEquals('atendente', Perfil::ATENDENTE->value);
        $this->assertEquals('comercial', Perfil::COMERCIAL->value);
        $this->assertEquals('mecanico', Perfil::MECANICO->value);
        $this->assertEquals('gestor_estoque', Perfil::GESTOR_ESTOQUE->value);
    }

    public function test_cases_as_array_retorna_todos_os_perfis(): void
    {
        $perfis = Perfil::casesAsArray();

        $this->assertIsArray($perfis);
        $this->assertCount(4, $perfis);
        $this->assertContains('atendente', $perfis);
        $this->assertContains('comercial', $perfis);
        $this->assertContains('mecanico', $perfis);
        $this->assertContains('gestor_estoque', $perfis);
    }

    public function test_cases_as_array_retorna_strings(): void
    {
        $perfis = Perfil::casesAsArray();

        foreach ($perfis as $perfil) {
            $this->assertIsString($perfil);
        }
    }

    public function test_from_string_valido(): void
    {
        $perfil = Perfil::from('atendente');
        $this->assertEquals(Perfil::ATENDENTE, $perfil);
    }

    public function test_try_from_invalido_retorna_null(): void
    {
        $perfil = Perfil::tryFrom('invalido');
        $this->assertNull($perfil);
    }
}
