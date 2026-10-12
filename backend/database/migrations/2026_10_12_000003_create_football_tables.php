<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Los ids son los de football-data, así no se duplica nada al reimportar
return new class extends Migration {
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('code', 10)->nullable()->index();
            $table->string('name');
            $table->string('type', 30)->nullable();
            $table->string('emblem')->nullable();
            $table->string('area_name')->nullable();
            $table->string('plan', 30)->nullable();
            $table->date('season_start')->nullable();
            $table->date('season_end')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->index();
            $table->string('short_name')->nullable();
            $table->string('tla', 10)->nullable();
            $table->string('crest')->nullable();
            $table->string('venue')->nullable();
            $table->unsignedSmallInteger('founded')->nullable();
            $table->string('club_colors')->nullable();
            $table->string('website')->nullable();
            $table->string('area_name')->nullable();
            $table->string('coach_name')->nullable();
            $table->timestamps();
        });

        Schema::create('competition_team', function (Blueprint $table) {
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['competition_id', 'team_id']);
        });

        Schema::create('players', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->index();
            $table->string('position')->nullable()->index();
            $table->unsignedSmallInteger('shirt_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
        Schema::dropIfExists('competition_team');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('competitions');
    }
};
