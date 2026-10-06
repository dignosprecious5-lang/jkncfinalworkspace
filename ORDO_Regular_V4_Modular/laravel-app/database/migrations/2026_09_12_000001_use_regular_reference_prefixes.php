<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('projects')->orderBy('id')->chunkById(100,function($records){
            foreach($records as $record){
                $updated=preg_replace('/\bPROJ-(?=[A-Z0-9])/', 'REG-', $record->data);
                if($updated!==$record->data) DB::table('projects')->where('id',$record->id)->update(['data'=>$updated]);
            }
        });
    }

    public function down(): void
    {
        // Deliberately retain issued Regular references; a rollback must not rename them.
    }
};
