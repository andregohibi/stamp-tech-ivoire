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
        Schema::create('qr_stamps', function (Blueprint $table) {
            $table->id();
            
            // Unique identifier for QR code
            $table->string('unique_code')->unique();
            
            // Foreign keys
            $table->string('company_id');
            $table->string('signatory_id');
            
            // Encryption and security
            $table->text('payload_encrypted');
            $table->string('signature_hash');
            $table->string('encryption_key_version')->default('v1.0');
            $table->string('qr_image_path')->nullable();
            
            // Status and lifecycle
            $table->enum('status', ['active', 'expired', 'revoked', 'invalid', 'inactive'])->default('active');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            
            // Verification tracking
            $table->integer('verification_count')->default(0);
            $table->timestamp('last_verified_at')->nullable();
            
            // Metadata (JSON field)
            $table->json('metadata')->nullable();
            
            // User tracking
            $table->string('created_by');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys (string type to match UUID)
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('signatory_id')->references('id')->on('signatories')->onDelete('cascade');
            
            // Indexes for better query performance
            $table->index('unique_code');
            $table->index('company_id');
            $table->index('signatory_id');
            $table->index('status');
            $table->index('issued_at');
            $table->index('expires_at');
            $table->index(['company_id', 'status']);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_stamps');
    }
};
