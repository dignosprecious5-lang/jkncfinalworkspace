<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE users 
                MODIFY role ENUM('SuperAdmin', 'Admin', 'Employee', 'Client') 
                NOT NULL DEFAULT 'Employee'
            ");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE users 
                MODIFY role ENUM('SuperAdmin', 'Admin', 'Employee') 
                NOT NULL DEFAULT 'Employee'
            ");
        }
    }
};
