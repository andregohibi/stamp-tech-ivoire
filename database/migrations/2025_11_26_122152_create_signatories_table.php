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
        Schema::create('signatories', function (Blueprint $table) {
           // Primary key as string (UUID)
            $table->string('id')->primary();
            
            // Foreign key to company
            $table->string('company_id');
            
            // Personal information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            
            // Signature and permissions
            $table->string('signature_image')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended','fired'])->default('active'); // active, inactive, suspended
            $table->boolean('can_generate_qr')->default(false);
            
            // Additional fields
            $table->text('notes')->nullable();
            
            // User tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key for company (string type to match UUID)
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            
            // Indexes for better query performance
            $table->index('company_id');
            $table->index('status');
            $table->index('email');
            $table->index(['company_id', 'status']);
            $table->index('can_generate_qr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatories');
    }
};
