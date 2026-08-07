<?php

declare(strict_types=1);

use Jb\Database\Blueprint;
use Jb\Database\Connection;
use Jb\Database\Migration;

return new class (Connection::getInstance()) extends Migration {
    public function up(): void
    {
        $this->create('item_categorias', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->drop('item_categorias');
    }
};