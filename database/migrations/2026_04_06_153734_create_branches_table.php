<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branches')) {
            $this->syncExistingOrganizationalTables();

            return;
        }

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name');
            $table->foreignId('address_id')->constrained('organizational_addresses')->cascadeOnDelete();
            $table->string('branch_head');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }

    private function syncExistingOrganizationalTables(): void
    {
        $this->ensureOrganizationalAddressColumns();

        $fallbackAddressId = $this->fallbackAddressId();

        if (!Schema::hasColumn('branches', 'address_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->foreignId('address_id')->nullable()->after('branch_name')->constrained('organizational_addresses')->nullOnDelete();
            });
        }

        DB::table('branches')->whereNull('address_id')->update(['address_id' => $fallbackAddressId]);

        if (Schema::hasTable('offices')) {
            Schema::table('offices', function (Blueprint $table) {
                if (!Schema::hasColumn('offices', 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->after('office_name')->constrained('branches')->nullOnDelete();
                }

                if (!Schema::hasColumn('offices', 'address_id')) {
                    $table->foreignId('address_id')->nullable()->after(Schema::hasColumn('offices', 'branch_id') ? 'branch_id' : 'office_name')->constrained('organizational_addresses')->nullOnDelete();
                }
            });

            DB::table('offices')->whereNull('address_id')->update(['address_id' => $fallbackAddressId]);
        }

        foreach ([
            'departments' => 'department_name',
            'divisions' => 'division_name',
            'units' => 'unit_name',
        ] as $tableName => $afterColumn) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'address_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $afterColumn) {
                $table->foreignId('address_id')->nullable()->after($afterColumn)->constrained('organizational_addresses')->nullOnDelete();
            });

            DB::table($tableName)->whereNull('address_id')->update(['address_id' => $fallbackAddressId]);
        }
    }

    private function ensureOrganizationalAddressColumns(): void
    {
        if (!Schema::hasTable('organizational_addresses')) {
            return;
        }

        Schema::table('organizational_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('organizational_addresses', 'country')) {
                $table->string('country')->default('Philippines')->after('id');
            }

            foreach ([
                'region_code' => ['string', 20],
                'region_name' => ['string', null],
                'province_code' => ['string', 20],
                'province_name' => ['string', null],
                'province_type' => ['string', 50],
                'city_code' => ['string', 20],
                'city_name' => ['string', null],
                'barangay_code' => ['string', 20],
                'barangay_name' => ['string', null],
                'postal_code' => ['string', 20],
            ] as $column => [$type, $length]) {
                if (!Schema::hasColumn('organizational_addresses', $column)) {
                    $definition = $length ? $table->{$type}($column, $length) : $table->{$type}($column);
                    $definition->nullable();
                }
            }

            foreach (['street_address', 'full_address'] as $column) {
                if (!Schema::hasColumn('organizational_addresses', $column)) {
                    $table->text($column)->nullable();
                }
            }

            foreach (['subdivision_building', 'unit_no'] as $column) {
                if (!Schema::hasColumn('organizational_addresses', $column)) {
                    $table->string($column)->nullable();
                }
            }
        });

        if (Schema::hasColumn('organizational_addresses', 'business_address')) {
            DB::table('organizational_addresses')
                ->whereNull('full_address')
                ->update([
                    'street_address' => DB::raw('business_address'),
                    'full_address' => DB::raw('business_address'),
                ]);
        }
    }

    private function fallbackAddressId(): int
    {
        $existingId = DB::table('organizational_addresses')->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        $address = [
            'country' => 'Philippines',
            'region_code' => 'N/A',
            'region_name' => 'N/A',
            'city_code' => 'N/A',
            'city_name' => 'N/A',
            'barangay_code' => 'N/A',
            'barangay_name' => 'N/A',
            'street_address' => 'Existing organizational address',
            'full_address' => 'Existing organizational address',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('organizational_addresses', 'business_address')) {
            $address['business_address'] = 'Existing organizational address';
        }

        return (int) DB::table('organizational_addresses')->insertGetId($address);
    }
};
