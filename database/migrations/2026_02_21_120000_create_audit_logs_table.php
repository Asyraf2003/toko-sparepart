<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_role', 50)->nullable();

            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->string('action', 100);

            // Kontrak docs: reason nullable tapi wajib pada aksi tertentu (enforced di UseCase/Controller)
            $table->string('reason', 255)->nullable();

            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();

            $table->string('ip', 45)->nullable(); // IPv4/IPv6
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['action', 'created_at']);
            $table->index(['entity_type', 'entity_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
