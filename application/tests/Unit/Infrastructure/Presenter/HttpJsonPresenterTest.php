<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Presenter;

use App\Infrastructure\Presenter\HttpJsonPresenter;
use App\Signature\PresenterInterface;
use PHPUnit\Framework\TestCase;

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
}
