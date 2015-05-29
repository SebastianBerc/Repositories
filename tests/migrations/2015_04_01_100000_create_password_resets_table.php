<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreatePasswordResetsTable
 *
 * @author Sebastian Berć <sebastian.berc@gmail.com>
 *
 */
class CreatePasswordResetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->string('token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('password_resets');
    }
}
