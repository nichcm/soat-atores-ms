<?php

declare(strict_types=1);

namespace Tests\Unit\Exception;

use App\Exception\DomainHttpException;
use DomainException;
use PHPUnit\Framework\TestCase;

class DomainHttpExceptionTest extends TestCase
{
    public function test_cria_excecao_com_mensagem_e_codigo(): void
    {
        $exception = new DomainHttpException('Erro de dominio', 400);

        $this->assertEquals('Erro de dominio', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
    }

    public function test_cria_excecao_com_codigo_padrao_zero(): void
    {
        $exception = new DomainHttpException('Erro sem codigo');

        $this->assertEquals('Erro sem codigo', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function test_herda_de_domain_exception(): void
    {
        $exception = new DomainHttpException('Erro', 500);

        $this->assertInstanceOf(DomainException::class, $exception);
    }

    public function test_excecao_com_codigos_http_comuns(): void
    {
        $bad_request = new DomainHttpException('Bad Request', 400);
        $not_found = new DomainHttpException('Not Found', 404);
        $internal_error = new DomainHttpException('Internal Error', 500);

        $this->assertEquals(400, $bad_request->getCode());
        $this->assertEquals(404, $not_found->getCode());
        $this->assertEquals(500, $internal_error->getCode());
    }
}
