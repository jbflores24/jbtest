<?php

declare(strict_types=1);

use Jb\Database\Blueprint;
use Jb\Database\Connection;
use Jb\Database\Migration;

return new class (Connection::getInstance()) extends Migration {
    public function up(): void
    {
        $this->create('items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('categoria_id');
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('uuid', 36)->unique();
            $table->timestamps();
            $table->foreign('categoria_id')->references('id')->on('item_categorias')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $this->drop('items');
    }
};