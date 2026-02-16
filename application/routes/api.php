<?php

use App\Http\Middleware\JsonWebTokenMiddleware;
use Illuminate\Support\Facades\Route;

require_once __DIR__ . "/cliente.php";
require_once __DIR__ . "/veiculo.php";
require_once __DIR__ . "/usuario.php";

Route::get(
    "ping",
    fn() => response()->json([
        "err" => false,
        "msg" => "pong",
    ]),
)->withoutMiddleware(JsonWebTokenMiddleware::class);

Route::fallback(
    fn() => response()->json([
        "err" => true,
        "msg" => "Recurso não encontrado",
    ]),
);
