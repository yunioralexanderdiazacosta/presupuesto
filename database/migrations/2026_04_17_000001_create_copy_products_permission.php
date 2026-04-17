<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::create(['name' => 'copy-products', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Permission::where('name', 'copy-products')->delete();
    }
};
