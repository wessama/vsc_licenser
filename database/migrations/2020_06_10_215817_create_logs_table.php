<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ticket_id')->nullable();
            $table->foreign('ticket_id', 'ticket_fk_583778')->references('id')->on('tickets');
            $table->integer('exit_code');
            $table->longText('message')->nullable();
            $table->string('last_run_cmd');
            $table->string('started_at');
            $table->string('ended_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
}
