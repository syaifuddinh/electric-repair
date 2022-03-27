<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('sales_orders', 'customer_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn(['customer_id']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'warehouse_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn(['warehouse_id']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'company_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn(['company_id']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'create_by')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropForeign(['create_by']);
                $table->dropColumn(['create_by']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'approve_by')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropForeign(['approve_by']);
                $table->dropColumn(['approve_by']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'cancel_by')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropForeign(['cancel_by']);
                $table->dropColumn(['cancel_by']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'date_approve')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn(['date_approve']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'date_cancel')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn(['date_cancel']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'cancel_reason')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn(['cancel_reason']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'description')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn(['description']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'status')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn(['status']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'date_transaction')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn(['date_transaction']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'created_at')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn(['created_at']);
            });
        }
        
        if(Schema::hasColumn('sales_orders', 'updated_at')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn(['updated_at']);
            });
        }
        
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->unsignedInteger('job_order_id')->nullable(true)->index();

            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['job_order_id']);
            $table->dropColumn(['job_order_id']);
        });
    }
}
