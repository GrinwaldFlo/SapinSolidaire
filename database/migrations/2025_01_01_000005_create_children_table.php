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
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_request_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->smallInteger('birth_year')->unsigned();
            $table->smallInteger('height')->unsigned()->nullable();
            $table->string('gift');
            $table->string('shoe_size', 10)->nullable();
            $table->char('code', 4)->unique();
            $table->enum('status', ['pending', 'validated', 'rejected', 'rejected_final', 'printed', 'received', 'given'])->default('pending');
            $table->timestamp('status_changed_at')->nullable();
            $table->text('rejection_comment')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('confirmation_email_sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
