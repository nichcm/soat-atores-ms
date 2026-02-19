<?php

declare(strict_types=1);

namespace Tests\Unit\Exception;

use App\Exception\DomainHttpException;
use DomainException;
use PHPUnit\Framework\TestCase;

class DomainHttpExceptionTest extends TestCase
{
    public function test_e_instancia_de_domain_exception(): void
    {
        $e = new DomainHttpException('Erro de domínio', 400);
        $this->assertInstanceOf(DomainException::class, $e);
    }

    public function test_mensagem_e_codigo_sao_passados_corretamente(): void
    {
        $e = new DomainHttpException('Recurso não encontrado', 404);

        $this->assertEquals('Recurso não encontrado', $e->getMessage());
        $this->assertEquals(404, $e->getCode());
    }

    public function test_codigo_padrao_e_zero(): void
    {
        $e = new DomainHttpException('Erro genérico');
        $this->assertEquals(0, $e->getCode());
    }

    public function test_pode_ser_lancada_e_capturada(): void
    {
        $this->expectException(DomainHttpException::class);
        $this->expectExceptionMessage('Operação inválida');
        $this->expectExceptionCode(400);

        throw new DomainHttpException('Operação inválida', 400);
    }

    public function test_pode_ser_capturada_como_domain_exception(): void
    {
        $this->expectException(DomainException::class);

        throw new DomainHttpException('Erro de domínio', 500);
    }
}
