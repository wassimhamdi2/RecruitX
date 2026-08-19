<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_documents', function (Blueprint $table) {
            $table->string('parse_status')->nullable()->after('is_primary');
            $table->json('parsed_data')->nullable()->after('parse_status');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_documents', function (Blueprint $table) {
            $table->dropColumn(['parse_status', 'parsed_data']);
        });
    }
};