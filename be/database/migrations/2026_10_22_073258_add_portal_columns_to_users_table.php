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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('mahasiswa')->after('email')->index();
            $table->string('nim', 30)->nullable()->unique()->after('role');
            $table->string('prodi', 100)->nullable()->after('nim');
            $table->string('no_hp', 20)->nullable()->after('prodi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropUnique(['nim']);
            $table->dropColumn(['role', 'nim', 'prodi', 'no_hp']);
        });
    }
};
