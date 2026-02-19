<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Usuario;

use App\Domain\Entity\Usuario\Entidade;
use App\Domain\Entity\Usuario\Perfil;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EntidadeTest extends TestCase
{
    private function criarEntidade(array $overrides = []): Entidade
    {
        $dados = array_merge([
            'uuid'         => 'uuid-usuario-123',
            'nome'         => 'Ana Souza',
            'email'        => 'ana@email.com',
            'senha'        => password_hash('senha123', PASSWORD_BCRYPT),
            'ativo'        => true,
            'perfil'       => 'atendente',
            'criadoEm'     => new DateTimeImmutable('2024-01-01 10:00:00'),
            'atualizadoEm' => new DateTimeImmutable('2024-01-01 10:00:00'),
        ], $overrides);

        return new Entidade(
            uuid: $dados['uuid'],
            nome: $dados['nome'],
            email: $dados['email'],
            senha: $dados['senha'],
            ativo: $dados['ativo'],
            perfil: $dados['perfil'],
            criadoEm: $dados['criadoEm'],
            atualizadoEm: $dados['atualizadoEm'],
        );
    }

    public function test_criacao_com_dados_validos(): void
    {
        $entidade = $this->criarEntidade();

        $this->assertEquals('uuid-usuario-123', $entidade->uuid);
        $this->assertEquals('Ana Souza', $entidade->nome);
        $this->assertEquals('ana@email.com', $entidade->email);
        $this->assertTrue($entidade->ativo);
        $this->assertEquals('atendente', $entidade->perfil);
        $this->assertNull($entidade->deletadoEm);
    }

    public function test_todos_os_perfis_validos_sao_aceitos(): void
    {
        foreach (Perfil::casesAsArray() as $perfil) {
            $entidade = $this->criarEntidade(['perfil' => $perfil]);
            $this->assertEquals($perfil, $entidade->perfil);
        }
    }

    public function test_email_invalido_lanca_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email inválido');

        $this->criarEntidade(['email' => 'email-invalido']);
    }

    public function test_nome_com_menos_de_3_caracteres_lanca_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nome deve ter pelo menos 3 caracteres');

        $this->criarEntidade(['nome' => 'AB']);
    }

    public function test_perfil_invalido_lanca_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Perfil inválido');

        $this->criarEntidade(['perfil' => 'perfil_inexistente']);
    }

    public function test_ativar_define_ativo_como_true(): void
    {
        $entidade = $this->criarEntidade(['ativo' => false]);
        $this->assertFalse($entidade->ativo);

        $entidade->ativar();

        $this->assertTrue($entidade->ativo);
    }

    public function test_ativar_atualiza_atualizado_em(): void
    {
        $data = new DateTimeImmutable('2024-01-01 00:00:00');
        $entidade = $this->criarEntidade(['ativo' => false, 'atualizadoEm' => $data, 'criadoEm' => $data]);

        $entidade->ativar();

        $this->assertGreaterThanOrEqual($data, $entidade->atualizadoEm);
    }

    public function test_desativar_define_ativo_como_false(): void
    {
        $entidade = $this->criarEntidade(['ativo' => true]);
        $this->assertTrue($entidade->ativo);

        $entidade->desativar();

        $this->assertFalse($entidade->ativo);
    }

    public function test_desativar_atualiza_atualizado_em(): void
    {
        $data = new DateTimeImmutable('2024-01-01 00:00:00');
        $entidade = $this->criarEntidade(['atualizadoEm' => $data, 'criadoEm' => $data]);

        $entidade->desativar();

        $this->assertGreaterThanOrEqual($data, $entidade->atualizadoEm);
    }

    public function test_excluir_define_deletado_em_e_desativa(): void
    {
        $entidade = $this->criarEntidade(['ativo' => true]);

        $this->assertNull($entidade->deletadoEm);
        $this->assertFalse($entidade->estaExcluido());

        $entidade->excluir();

        $this->assertNotNull($entidade->deletadoEm);
        $this->assertFalse($entidade->ativo);
        $this->assertTrue($entidade->estaExcluido());
    }

    public function test_excluir_atualiza_atualizado_em(): void
    {
        $data = new DateTimeImmutable('2024-01-01 00:00:00');
        $entidade = $this->criarEntidade(['atualizadoEm' => $data, 'criadoEm' => $data]);

        $entidade->excluir();

        $this->assertGreaterThanOrEqual($data, $entidade->atualizadoEm);
    }

    public function test_esta_excluido_retorna_false_quando_nao_excluido(): void
    {
        $entidade = $this->criarEntidade();
        $this->assertFalse($entidade->estaExcluido());
    }

    public function test_to_http_response_retorna_campos_corretos(): void
    {
        $entidade = $this->criarEntidade();
        $res = $entidade->toHttpResponse();

        $this->assertArrayHasKey('uuid', $res);
        $this->assertArrayHasKey('nome', $res);
        $this->assertArrayHasKey('email', $res);
        $this->assertArrayHasKey('ativo', $res);
        $this->assertArrayHasKey('perfil', $res);
        $this->assertArrayHasKey('criado_em', $res);
        $this->assertArrayHasKey('atualizado_em', $res);
        $this->assertArrayHasKey('deletado_em', $res);
        $this->assertArrayNotHasKey('senha', $res);
    }

    public function test_to_create_data_array_inclui_senha(): void
    {
        $entidade = $this->criarEntidade();
        $res = $entidade->toCreateDataArray();

        $this->assertArrayHasKey('nome', $res);
        $this->assertArrayHasKey('email', $res);
        $this->assertArrayHasKey('senha', $res);
        $this->assertArrayHasKey('perfil', $res);
        $this->assertArrayNotHasKey('uuid', $res);
    }

    public function test_to_token_payload_retorna_sub_e_perfil(): void
    {
        $entidade = $this->criarEntidade();
        $payload = $entidade->toTokenPayload();

        $this->assertArrayHasKey('sub', $payload);
        $this->assertArrayHasKey('perf', $payload);
        $this->assertEquals('uuid-usuario-123', $payload['sub']);
        $this->assertEquals('atendente', $payload['perf']);
    }

    public function test_verify_password_correto(): void
    {
        $senha = 'minha-senha-123';
        $entidade = $this->criarEntidade(['senha' => password_hash($senha, PASSWORD_BCRYPT)]);

        $this->assertTrue($entidade->verifyPassword($senha));
    }

    public function test_verify_password_incorreto(): void
    {
        $entidade = $this->criarEntidade(['senha' => password_hash('senha-correta', PASSWORD_BCRYPT)]);

        $this->assertFalse($entidade->verifyPassword('senha-errada'));
    }

    public function test_constantes_de_status(): void
    {
        $this->assertTrue(Entidade::STATUS_ATIVO);
        $this->assertFalse(Entidade::STATUS_INATIVO);
    }
}
