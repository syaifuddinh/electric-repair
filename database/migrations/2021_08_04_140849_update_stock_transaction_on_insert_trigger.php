<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateStockTransactionOnInsertTrigger extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $user = env('DB_USERNAME', 'root');
        $strStock = "
        DROP TRIGGER IF EXISTS `on_insert_stock_transaction`;

        CREATE
            DEFINER = '$user'@'localhost' 
            TRIGGER `on_insert_stock_transaction` AFTER INSERT ON `stock_transactions` 
            FOR EACH ROW BEGIN
            
            DECLARE stok_terakhir INT;
            
            SET @idd:=(SELECT COUNT(id) 
                                FROM warehouse_stocks WHERE warehouse_receipt_detail_id=new.warehouse_receipt_detail_id 
                                AND warehouse_id=new.warehouse_id LIMIT 1);
            SET @warehouse_receipt_id = (SELECT header_id FROM warehouse_receipt_details 
                                                        WHERE id = NEW.warehouse_receipt_detail_id);
            SET @id_stock_exists = (SELECT COUNT(id) FROM warehouse_stock_details 
                                                WHERE item_id = NEW.item_id 
                                                AND rack_id = NEW.rack_id 
                                                AND warehouse_receipt_id = @warehouse_receipt_id 
                                                AND warehouse_receipt_detail_id = new.warehouse_receipt_detail_id);
            
            SET @id_stock = (SELECT id FROM warehouse_stock_details 
                                        WHERE item_id = NEW.item_id 
                                        AND rack_id = NEW.rack_id 
                                        AND warehouse_receipt_id = @warehouse_receipt_id 
                                        AND warehouse_receipt_detail_id = new.warehouse_receipt_detail_id 
                                        LIMIT 0, 1);
            SET @current_qty = (SELECT qty FROM warehouse_stock_details WHERE id = @id_stock);
            
            /** if IS_APPROVE == 1 **/
            IF new.is_approve = 1 THEN
                
                SET stok_terakhir := (SELECT IFNULL(SUM(qty), 0) FROM `warehouse_stocks` WHERE item_id = new.item_id);
                SET stok_terakhir := stok_terakhir + (new.qty_masuk - new.qty_keluar);
                /** memasukkan stok_trans_report **/
                INSERT INTO `stock_transactions_report` 
                            (`header_id`, `warehouse_id`,`rack_id`,`item_id`,`type_transaction_id`,`code`,`date_transaction`,`qty_masuk`,`qty_keluar`,`harga_masuk`,`harga_keluar`,`jumlah_stok`) 
                            VALUES (new.id, new.`warehouse_id`, new.`rack_id`, new.`item_id`, new.`type_transaction_id`, new.`code`, new.`date_transaction`, new.`qty_masuk`, new.`qty_keluar`, new.`harga_masuk`, new.`harga_keluar`, stok_terakhir);
            
                    /** memasukkan warehouse_stocks **/
                INSERT INTO warehouse_stocks ( id,warehouse_id, item_id, warehouse_receipt_detail_id, qty )
                    VALUES (@idd,new.warehouse_id,
                    new.item_id, new.warehouse_receipt_detail_id,
                    ( SELECT SUM( qty_masuk - qty_keluar ) AS wr 
                        FROM stock_transactions WHERE warehouse_id = new.warehouse_id AND warehouse_receipt_detail_id = new.warehouse_receipt_detail_id ) )
                ON DUPLICATE KEY UPDATE
                    qty = ( SELECT SUM( qty_masuk - qty_keluar ) AS wr FROM stock_transactions WHERE warehouse_id = new.warehouse_id AND warehouse_receipt_detail_id = new.warehouse_receipt_detail_id ) ;
                            
                /** update/insert warehouse_stock_detail **/
                IF @id_stock_exists = 0 THEN
                    INSERT INTO warehouse_stock_details(rack_id, item_id, qty, customer_id, warehouse_receipt_id, warehouse_receipt_detail_id) 
                            VALUES(NEW.rack_id, NEW.item_id, NEW.qty_masuk, new.customer_id, @warehouse_receipt_id, new.warehouse_receipt_detail_id);
                ELSE 
                    UPDATE warehouse_stock_details SET qty = @current_qty + NEW.qty_masuk WHERE id = @id_stock;
                    SET @outbond = NEW.qty_keluar - NEW.qty_masuk;
                    IF @outbond > 0 THEN
                        SET @onhand_qty = (SELECT onhand_qty FROM warehouse_stock_details WHERE warehouse_receipt_detail_id = NEW.warehouse_receipt_detail_id AND rack_id = NEW.rack_id LIMIT 0, 1) + @outbond;
                        UPDATE warehouse_stock_details SET onhand_qty = @onhand_qty WHERE id = @id_stock;
                    END IF;
                END IF;
                                
                SET @volume = (SELECT ROUND(`long` * `wide` * `high`, 3) AS volume  FROM warehouse_receipt_details WHERE id = new.warehouse_receipt_detail_id) / 1000000 * ( NEW.qty_masuk - NEW.qty_keluar );
                SET @tonase = (SELECT ROUND(weight, 3) AS weight FROM warehouse_receipt_details WHERE id = new.warehouse_receipt_detail_id) * ( NEW.qty_masuk - NEW.qty_keluar );
                
                UPDATE racks SET `capacity_volume_used` = ROUND(`capacity_volume_used` + @volume, 3), `capacity_tonase_used` = ROUND(`capacity_tonase_used` + @tonase, 3) WHERE id = NEW.rack_id;
            
            ELSE 
                IF @id_stock_exists = 1 THEN                       
                    SET @requested_qty = (SELECT COALESCE(SUM(qty_keluar - qty_masuk), 0) FROM stock_transactions WHERE is_approve = 0 
                                                        AND warehouse_receipt_detail_id = new.warehouse_receipt_detail_id 
                                                        AND rack_id = new.rack_id);
                    UPDATE warehouse_stock_details SET onhand_qty = COALESCE(@requested_qty, 0) WHERE id = @id_stock;
                END IF;
            
            END IF;
            
            UPDATE warehouse_stock_details SET available_qty = qty - onhand_qty 
                    WHERE rack_id = NEW.rack_id 
                    AND item_id = NEW.item_id 
                    AND warehouse_receipt_detail_id = NEW.warehouse_receipt_detail_id;
        END;
        ";
        DB::unprepared($strStock);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $user = env('DB_USERNAME', 'root');
        $strStock = "
            DROP TRIGGER IF EXISTS `on_insert_stock_transaction`;

            CREATE
                DEFINER = '$user'@'localhost' 
                TRIGGER `on_insert_stock_transaction` AFTER INSERT ON `stock_transactions` 
                FOR EACH ROW BEGIN
                
                DECLARE stok_terakhir INT;

                SET @idd:=(SELECT COUNT(id) FROM warehouse_stocks WHERE warehouse_receipt_detail_id=new.warehouse_receipt_detail_id AND warehouse_id=new.warehouse_id LIMIT 1);
                SET @warehouse_receipt_id = (SELECT header_id FROM warehouse_receipt_details WHERE id = NEW.warehouse_receipt_detail_id);
                SET @id_stock_exists = (SELECT COUNT(id) FROM warehouse_stock_details WHERE item_id = NEW.item_id AND rack_id = NEW.rack_id AND warehouse_receipt_id = @warehouse_receipt_id AND warehouse_receipt_detail_id = new.warehouse_receipt_detail_id);

                SET @id_stock = (SELECT id FROM warehouse_stock_details WHERE item_id = NEW.item_id AND rack_id = NEW.rack_id AND warehouse_receipt_id = @warehouse_receipt_id AND warehouse_receipt_detail_id = new.warehouse_receipt_detail_id LIMIT 0, 1);
                SET @current_qty = (SELECT qty FROM warehouse_stock_details WHERE id = @id_stock);

                IF new.is_approve = 1 THEN
                    
                    SET stok_terakhir := (SELECT IFNULL(SUM(qty), 0) FROM `warehouse_stocks` WHERE item_id = new.item_id);
                    SET stok_terakhir := stok_terakhir + (new.qty_masuk - new.qty_keluar);
                    INSERT INTO `stock_transactions_report` (`header_id`, `warehouse_id`,`rack_id`,`item_id`,`type_transaction_id`,`code`,`date_transaction`,`qty_masuk`,`qty_keluar`,`harga_masuk`,`harga_keluar`,`jumlah_stok`) VALUES (new.id, new.`warehouse_id`, new.`rack_id`, new.`item_id`, new.`type_transaction_id`, new.`code`, new.`date_transaction`, new.`qty_masuk`, new.`qty_keluar`, new.`harga_masuk`, new.`harga_keluar`, stok_terakhir);

                    INSERT INTO warehouse_stocks ( id,warehouse_id, item_id, warehouse_receipt_detail_id, qty )
                        VALUES (@idd,new.warehouse_id,
                        new.item_id, new.warehouse_receipt_detail_id,
                        ( SELECT SUM( qty_masuk - qty_keluar ) AS wr FROM stock_transactions WHERE warehouse_id = new.warehouse_id AND warehouse_receipt_detail_id = new.warehouse_receipt_detail_id ) )
                    ON DUPLICATE KEY UPDATE
                        qty = ( SELECT SUM( qty_masuk - qty_keluar ) AS wr FROM stock_transactions WHERE warehouse_id = new.warehouse_id AND warehouse_receipt_detail_id = new.warehouse_receipt_detail_id ) ;
                    
                    
                    IF @id_stock_exists = 0 THEN
                    INSERT INTO warehouse_stock_details(rack_id, item_id, qty, customer_id, warehouse_receipt_id, warehouse_receipt_detail_id) VALUES(NEW.rack_id, NEW.item_id, NEW.qty_masuk, new.customer_id, @warehouse_receipt_id, new.warehouse_receipt_detail_id);
                    ELSE 
                    UPDATE warehouse_stock_details SET qty = @current_qty + NEW.qty_masuk - NEW.qty_keluar WHERE id = @id_stock;
                    SET @outbond = NEW.qty_keluar - NEW.qty_masuk;
                    IF @outbond > 0 THEN
                            SET @onhand_qty = (SELECT onhand_qty FROM warehouse_stock_details WHERE warehouse_receipt_detail_id = NEW.warehouse_receipt_detail_id AND rack_id = NEW.rack_id LIMIT 0, 1) - @outbond;
                            UPDATE warehouse_stock_details SET onhand_qty = @onhand_qty WHERE id = @id_stock;
                    END IF;
                    END IF;
                    
                    SET @volume = (SELECT ROUND(`long` * `wide` * `high`, 3) AS volume  FROM warehouse_receipt_details WHERE id = new.warehouse_receipt_detail_id) / 1000000 * ( NEW.qty_masuk - NEW.qty_keluar );
                    SET @tonase = (SELECT ROUND(weight, 3) AS weight FROM warehouse_receipt_details WHERE id = new.warehouse_receipt_detail_id) * ( NEW.qty_masuk - NEW.qty_keluar );
                    
                    UPDATE racks SET `capacity_volume_used` = ROUND(`capacity_volume_used` + @volume, 3), `capacity_tonase_used` = ROUND(`capacity_tonase_used` + @tonase, 3) WHERE id = NEW.rack_id;
                ELSE
                    IF @id_stock_exists = 1 THEN                       
                    SET @requested_qty = (SELECT SUM(qty_keluar - qty_masuk) FROM stock_transactions WHERE is_approve = 0 AND warehouse_receipt_detail_id = new.warehouse_receipt_detail_id AND rack_id = new.rack_id);
                    UPDATE warehouse_stock_details SET onhand_qty = @requested_qty WHERE id = @id_stock;
                    END IF;

                END IF;

                UPDATE warehouse_stock_details SET available_qty = qty - onhand_qty WHERE rack_id = NEW.rack_id AND item_id = NEW.item_id AND warehouse_receipt_detail_id = NEW.warehouse_receipt_detail_id;
            END;
        ";
        DB::unprepared($strStock);
    }
}
