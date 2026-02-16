<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('senha');
            $table->boolean('ativo')->default(true);
            $table->string('perfil');

            $defaultsTimestampsColumns = require __DIR__ . '/../defaults/ColumnsTimestamps.php';
            $defaultsTimestampsColumns->addDefaultColumnsTimestamps($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
