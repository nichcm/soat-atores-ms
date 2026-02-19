<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Presenter;

use App\Infrastructure\Presenter\HttpJsonPresenter;
use App\Signature\PresenterInterface;
use Tests\TestCase;

class HttpJsonPresenterTest extends TestCase
{
    public function test_implementa_presenter_interface(): void
    {
        $presenter = new HttpJsonPresenter();

        $this->assertInstanceOf(PresenterInterface::class, $presenter);
    }

    public function test_set_status_code_retorna_instancia(): void
    {
        $presenter = new HttpJsonPresenter();
        $result = $presenter->setStatusCode(201);

        $this->assertInstanceOf(HttpJsonPresenter::class, $result);
    }

    public function test_to_present_retorna_json_response(): void
    {
        $presenter = new HttpJsonPresenter();
        $dados = ['chave' => 'valor', 'numero' => 42];

        $response = $presenter->toPresent($dados);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function test_to_present_com_status_code_customizado(): void
    {
        $presenter = new HttpJsonPresenter();
        $presenter->setStatusCode(201);

        $response = $presenter->toPresent(['criado' => true]);

        $this->assertEquals(201, $response->getStatusCode());
    }
}
