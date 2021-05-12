<?php

namespace CupNoodles\SquareByWeight\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Schema;

/**
 * 
 */
class AddPaymentOrderId extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('payment_profiles', 'order_id')) {
            Schema::table('payment_profiles', function (Blueprint $table) {
                $table->integer('order_id');
            });
        }
        
    }


    public function down()
    {
        Schema::table('payment_profiles', function (Blueprint $table) {
            $table->dropColumn(['payment_profiles', 'order_id']);
        });
    }


}
