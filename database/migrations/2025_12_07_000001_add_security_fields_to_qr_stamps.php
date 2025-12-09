<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_stamps', function (Blueprint $table) {
            // Token hash pour vérification sécurisée (index unique)
            $table->string('token_hash')->nullable()->after('unique_code');
            $table->unique('token_hash');

            // Audit fields pour sécurité et rate limiting
            $table->integer('verification_attempts')->default(0)->after('last_verified_at');
            $table->timestamp('last_suspicious_attempt')->nullable()->after('verification_attempts');
            $table->string('last_suspicious_ip')->nullable()->after('last_suspicious_attempt');
            $table->text('last_suspicious_user_agent')->nullable()->after('last_suspicious_ip');

            // Index pour les queries rapides
            $table->index('token_hash');
            $table->index('verification_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('qr_stamps', function (Blueprint $table) {
            $table->dropUnique(['token_hash']);
            $table->dropIndex(['token_hash']);
            $table->dropIndex(['verification_attempts']);
            $table->dropColumn([
                'token_hash',
                'verification_attempts',
                'last_suspicious_attempt',
                'last_suspicious_ip',
                'last_suspicious_user_agent',
            ]);
        });
    }
};
