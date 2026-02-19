<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Cliente;

use App\Domain\Entity\Cliente\Entidade;
use App\Exception\DomainHttpException;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EntidadeTest extends TestCase
{
    private function criarEntidade(array $overrides = []): Entidade
    {
        $dados = array_merge([
            'uuid'         => 'uuid-teste-123',
            'nome'         => 'João Silva',
            'documento'    => '12345678901',
            'email'        => 'joao@email.com',
            'fone'         => '11999999999',
            'criadoEm'     => new DateTimeImmutable('2024-01-01 10:00:00'),
            'atualizadoEm' => new DateTimeImmutable('2024-01-01 10:00:00'),
        ], $overrides);

        return new Entidade(
            uuid: $dados['uuid'],
            nome: $dados['nome'],
            documento: $dados['documento'],
            email: $dados['email'],
            fone: $dados['fone'],
            criadoEm: $dados['criadoEm'],
            atualizadoEm: $dados['atualizadoEm'],
        );
    }

    public function test_criacao_com_dados_validos(): void
    {
        $entidade = $this->criarEntidade();

        $this->assertEquals('uuid-teste-123', $entidade->uuid);
        $this->assertEquals('João Silva', $entidade->nome);
        $this->assertEquals('12345678901', $entidade->documento);
        $this->assertEquals('joao@email.com', $entidade->email);
        $this->assertEquals('11999999999', $entidade->fone);
        $this->assertNull($entidade->deletadoEm);
    }

    public function test_nome_com_menos_de_3_caracteres_lanca_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nome deve ter pelo menos 3 caracteres');

        $this->criarEntidade(['nome' => 'AB']);
    }

    public function test_nome_somente_espacos_lanca_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nome deve ter pelo menos 3 caracteres');

        $this->criarEntidade(['nome' => '   ']);
    }

    public function test_nome_exatamente_3_caracteres_e_valido(): void
    {
        $entidade = $this->criarEntidade(['nome' => 'Ana']);
        $this->assertEquals('Ana', $entidade->nome);
    }

    public function test_excluir_define_deletado_em(): void
    {
        $entidade = $this->criarEntidade();

        $this->assertNull($entidade->deletadoEm);
        $this->assertFalse($entidade->estaExcluido());

        $entidade->excluir();

        $this->assertNotNull($entidade->deletadoEm);
        $this->assertInstanceOf(DateTimeImmutable::class, $entidade->deletadoEm);
        $this->assertTrue($entidade->estaExcluido());
    }

    public function test_excluir_atualiza_atualizado_em(): void
    {
        $criadoEm = new DateTimeImmutable('2024-01-01 00:00:00');
        $entidade = $this->criarEntidade([
            'criadoEm'     => $criadoEm,
            'atualizadoEm' => $criadoEm,
        ]);

        $entidade->excluir();

        $this->assertGreaterThanOrEqual($criadoEm, $entidade->atualizadoEm);
    }

    public function test_esta_excluido_retorna_false_quando_nao_excluido(): void
    {
        $entidade = $this->criarEntidade();
        $this->assertFalse($entidade->estaExcluido());
    }

    public function test_esta_excluido_retorna_true_com_deletado_em_definido(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->excluir();
        $this->assertTrue($entidade->estaExcluido());
    }

    public function test_validar_documento_cpf_11_digitos(): void
    {
        $entidade = $this->criarEntidade(['documento' => '12345678901']);
        $entidade->validarDocumento();
        $this->assertTrue(true);
    }

    public function test_validar_documento_cnpj_14_digitos(): void
    {
        $entidade = $this->criarEntidade(['documento' => '12345678000195']);
        $entidade->validarDocumento();
        $this->assertTrue(true);
    }

    public function test_validar_documento_invalido_lanca_excecao(): void
    {
        $entidade = $this->criarEntidade(['documento' => '12345']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Documento inválido');

        $entidade->validarDocumento();
    }

    public function test_validar_email_valido(): void
    {
        $entidade = $this->criarEntidade(['email' => 'teste@dominio.com.br']);
        $entidade->validarEmail();
        $this->assertTrue(true);
    }

    public function test_validar_email_invalido_lanca_excecao(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->email = 'email-sem-arroba';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email inválido');

        $entidade->validarEmail();
    }

    public function test_cpf_valido_aceito(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->cpfValido('12345678901');
        $this->assertTrue(true);
    }

    public function test_cpf_vazio_lanca_excecao(): void
    {
        $entidade = $this->criarEntidade();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CPF não pode ser vazio');

        $entidade->cpfValido('');
    }

    public function test_cpf_menos_de_11_digitos_lanca_excecao(): void
    {
        $entidade = $this->criarEntidade();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CPF deve ter 11 dígitos');

        $entidade->cpfValido('1234567890');
    }

    public function test_cpf_mais_de_11_digitos_lanca_excecao(): void
    {
        $entidade = $this->criarEntidade();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CPF deve ter 11 dígitos');

        $entidade->cpfValido('123456789012');
    }

    public function test_cnpj_valido_aceito(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->cnpjValido('12345678000195');
        $this->assertTrue(true);
    }

    public function test_cnpj_vazio_lanca_excecao(): void
    {
        $entidade = $this->criarEntidade();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CNPJ não pode ser vazio');

        $entidade->cnpjValido('');
    }

    public function test_cnpj_menos_de_14_digitos_lanca_excecao(): void
    {
        $entidade = $this->criarEntidade();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CNPJ deve ter 14 dígitos');

        $entidade->cnpjValido('1234567800019');
    }

    public function test_documento_limpo_remove_pontuacao_cpf(): void
    {
        $entidade = $this->criarEntidade(['documento' => '123.456.789-01']);
        $this->assertEquals('12345678901', $entidade->documentoLimpo());
    }

    public function test_documento_limpo_remove_pontuacao_cnpj(): void
    {
        $entidade = $this->criarEntidade(['documento' => '12.345.678/0001-95']);
        $this->assertEquals('12345678000195', $entidade->documentoLimpo());
    }

    public function test_to_http_response_retorna_campos_corretos(): void
    {
        $entidade = $this->criarEntidade();
        $res = $entidade->toHttpResponse();

        $this->assertArrayHasKey('uuid', $res);
        $this->assertArrayHasKey('nome', $res);
        $this->assertArrayHasKey('documento', $res);
        $this->assertArrayHasKey('email', $res);
        $this->assertArrayHasKey('fone', $res);
        $this->assertArrayHasKey('criado_em', $res);
        $this->assertArrayHasKey('atualizado_em', $res);
        $this->assertEquals('uuid-teste-123', $res['uuid']);
        $this->assertEquals('João Silva', $res['nome']);
    }

    public function test_to_external_retorna_campos_corretos(): void
    {
        $entidade = $this->criarEntidade();
        $res = $entidade->toExternal();

        $this->assertArrayHasKey('uuid', $res);
        $this->assertArrayHasKey('nome', $res);
        $this->assertArrayHasKey('documento', $res);
        $this->assertArrayHasKey('email', $res);
        $this->assertArrayHasKey('fone', $res);
    }

    public function test_to_create_data_array_retorna_campos_sem_uuid(): void
    {
        $entidade = $this->criarEntidade();
        $res = $entidade->toCreateDataArray();

        $this->assertArrayHasKey('nome', $res);
        $this->assertArrayHasKey('documento', $res);
        $this->assertArrayHasKey('email', $res);
        $this->assertArrayHasKey('fone', $res);
        $this->assertArrayNotHasKey('uuid', $res);
        $this->assertEquals('12345678901', $res['documento']); // documentoLimpo()
    }

    public function test_atualizar_nome(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->atualizar(['nome' => 'Maria Oliveira']);
        $this->assertEquals('Maria Oliveira', $entidade->nome);
    }

    public function test_atualizar_email(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->atualizar(['email' => 'maria@email.com']);
        $this->assertEquals('maria@email.com', $entidade->email);
    }

    public function test_atualizar_documento(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->atualizar(['documento' => '98765432100']);
        $this->assertEquals('98765432100', $entidade->documento);
    }

    public function test_atualizar_fone(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->atualizar(['fone' => '11888888888']);
        $this->assertEquals('11888888888', $entidade->fone);
    }

    public function test_atualizar_nome_invalido_lanca_excecao(): void
    {
        $entidade = $this->criarEntidade();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nome deve ter pelo menos 3 caracteres');

        $entidade->atualizar(['nome' => 'AB']);
    }

    public function test_atualizar_atualiza_atualizado_em(): void
    {
        $data = new DateTimeImmutable('2024-01-01 00:00:00');
        $entidade = $this->criarEntidade(['criadoEm' => $data, 'atualizadoEm' => $data]);

        $entidade->atualizar(['nome' => 'Novo Nome']);

        $this->assertGreaterThanOrEqual($data, $entidade->atualizadoEm);
    }

    public function test_to_update_data_array_retorna_campos_corretos(): void
    {
        $entidade = $this->criarEntidade();
        $res = $entidade->toUpdateDataArray();

        $this->assertArrayHasKey('nome', $res);
        $this->assertArrayHasKey('documento', $res);
        $this->assertArrayHasKey('email', $res);
        $this->assertArrayHasKey('fone', $res);
    }

    public function test_validar_fone_nao_lanca_excecao(): void
    {
        $entidade = $this->criarEntidade();
        $entidade->validarFone();
        $this->assertTrue(true);
    }
}
