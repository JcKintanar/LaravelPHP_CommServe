<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmergencyHotlinesTable extends Migration
{
    public function up()
    {
        Schema::create('emergency_hotlines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('number');
            $table->text('description')->nullable();
            $table->string('barangay')->nullable();
            $table->string('cityMunicipality')->nullable();
            $table->timestamp('createdAt')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('emergency_hotlines');
    }
}
