<?php

declare(strict_types=1);

use Jb\Database\Blueprint;
use Jb\Database\Connection;
use Jb\Database\Migration;

return new class (Connection::getInstance()) extends Migration {
    public function up(): void
    {
        $this->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->unsignedBigInteger('role_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        $this->drop('users');
    }
};