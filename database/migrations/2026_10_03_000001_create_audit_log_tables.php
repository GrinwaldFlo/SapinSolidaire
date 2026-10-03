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
        Schema::create('sent_mail_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('recipient_email');
            $table->string('purpose');
            $table->string('mailable_class');
            $table->foreignUuid('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sent_by_label')->nullable();
            $table->timestamps();

            $table->index('recipient_email');
            $table->index('purpose');
        });

        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_label')->nullable();
            $table->string('action_type');
            $table->text('description');
            $table->foreignUuid('family_id')->nullable()->constrained('families')->nullOnDelete();
            $table->foreignUuid('gift_request_id')->nullable()->constrained('gift_requests')->nullOnDelete();
            $table->foreignUuid('child_id')->nullable()->constrained('children')->nullOnDelete();
            $table->timestamps();

            $table->index('action_type');
        });

        Schema::create('family_submission_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email');
            $table->string('action_type');
            $table->foreignUuid('family_id')->nullable()->constrained('families')->nullOnDelete();
            $table->foreignUuid('gift_request_id')->nullable()->constrained('gift_requests')->nullOnDelete();
            $table->timestamps();

            $table->index('email');
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_submission_logs');
        Schema::dropIfExists('admin_action_logs');
        Schema::dropIfExists('sent_mail_logs');
    }
};
