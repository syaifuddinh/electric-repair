<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOnDeleteStockTransactionTrigger extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $user = env('DB_USERNAME', 'root');
        $str = "
            DROP TRIGGER IF EXISTS `on_delete_stock_transaction`;

            CREATE
                DEFINER = '$user'@'localhost' 
                TRIGGER `on_delete_stock_transaction` AFTER DELETE ON `stock_transactions` 
                FOR EACH ROW BEGIN
                
                DECLARE stok_terakhir INT;

                SET @id_stock = (SELECT id FROM warehouse_stock_details WHERE item_id = OLD.item_id AND rack_id = OLD.rack_id AND warehouse_receipt_detail_id = OLD.warehouse_receipt_detail_id LIMIT 0, 1);
                SET @current_qty = (SELECT SUM(qty_masuk - qty_keluar) FROM stock_transactions WHERE is_approve = 1 AND item_id = OLD.item_id AND rack_id = OLD.rack_id AND warehouse_receipt_detail_id = OLD.warehouse_receipt_detail_id);
                SET @current_qty_in_wh = (SELECT SUM(qty_masuk - qty_keluar) FROM stock_transactions WHERE is_approve = 1 AND warehouse_id = OLD.warehouse_id AND warehouse_receipt_detail_id = OLD.warehouse_receipt_detail_id);

                IF OLD.is_approve = 1 THEN

                   UPDATE warehouse_stocks SET qty = @current_qty_in_wh WHERE warehouse_receipt_detail_id = OLD.warehouse_receipt_detail_id AND warehouse_id = OLD.warehouse_id; 
                    
                   UPDATE warehouse_stock_details SET qty = @current_qty WHERE warehouse_receipt_detail_id = OLD.warehouse_receipt_detail_id AND rack_id = OLD.rack_id;
                   SET @outbond = OLD.qty_keluar - OLD.qty_masuk;
                   IF @outbond > 0 THEN
                        SET @onhand_qty = (SELECT onhand_qty FROM warehouse_stock_details WHERE warehouse_receipt_detail_id = OLD.warehouse_receipt_detail_id AND rack_id = OLD.rack_id LIMIT 0, 1) + @outbond;
                        UPDATE warehouse_stock_details SET onhand_qty = @onhand_qty + OLD.qty_masuk - OLD.qty_keluar WHERE id = @id_stock;
                   END IF;
                    
                    SET @volume = (SELECT ROUND(`long` * `wide` * `high`, 3) AS volume  FROM warehouse_receipt_details WHERE id = OLD.warehouse_receipt_detail_id) / 1000000 * ( OLD.qty_masuk - OLD.qty_keluar );
                    SET @tonase = (SELECT ROUND(weight, 3) AS weight FROM warehouse_receipt_details WHERE id = OLD.warehouse_receipt_detail_id) * ( OLD.qty_masuk - OLD.qty_keluar );
                    
                    UPDATE racks SET `capacity_volume_used` = ROUND(`capacity_volume_used` - @volume, 3), `capacity_tonase_used` = ROUND(`capacity_tonase_used` - @tonase, 3) WHERE id = OLD.rack_id;
                    
                ELSE
                    SET @requested_qty = (SELECT SUM(qty_keluar - qty_masuk) FROM stock_transactions WHERE is_approve = 0 AND warehouse_receipt_detail_id = OLD.warehouse_receipt_detail_id AND rack_id = OLD.rack_id);
                    
                    UPDATE warehouse_stock_details SET onhand_qty = @requested_qty WHERE warehouse_receipt_detail_id = OLD.warehouse_receipt_detail_id AND rack_id = OLD.rack_id AND item_id = OLD.item_id;

                END IF;

                UPDATE warehouse_stock_details SET available_qty = qty - onhand_qty WHERE rack_id = OLD.rack_id AND item_id = OLD.item_id AND warehouse_receipt_detail_id = OLD.warehouse_receipt_detail_id;
            END;
        ";

        DB::unprepared($str);        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
