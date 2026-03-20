<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // IDENTITAS LOGIN
            $table->string('username')->unique();
            $table->string('name');
            $table->string('email')->unique()->nullable();

            // AUTH
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();

            // ROLE & AKSES
            $table->enum('role', ['admin','admin_pangan','admin_hartibun', 'uptd'])
                ->default('uptd')
                ->comment('admin = super admin, admin_pangan = verifikator bidang pangan, admin_hartibun = verifikator hartibun, uptd = tim uptd');

            $table->enum('status', ['aktif', 'nonaktif'])
                ->default('aktif');

            // AUDIT & OBSERVER SUPPORT
            $table->timestamp('last_login_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
