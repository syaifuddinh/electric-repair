<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixingTriggerWarehouseReceiptDetailOnInsert extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('warehouse_stocks', 'warehouse_receipt_detail_id')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(false)->index();

                $table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onDelete('RESTRICT');
            });
        }

        if(!Schema::hasColumn('stock_transactions', 'is_approve')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                $table->smallInteger('is_approve')->nullable(false)->default(1)->index();
            });
        }

        if(Schema::hasColumn('warehouse_stock_details', 'qty')) {
            Schema::table('warehouse_stock_details', function (Blueprint $table) {
                $table->dropColumn(['qty']);
            });
        }

        if(!Schema::hasColumn('warehouse_stock_details', 'warehouse_receipt_detail_id')) {
            Schema::table('warehouse_stock_details', function (Blueprint $table) {
                $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(true)->index();
            });
        }
        Schema::table('warehouse_stock_details', function (Blueprint $table) {
            $table->double('qty')->nullable(false)->default(0);
            $table->double('onhand_qty')->nullable(false)->default(0);
            $table->double('available_qty')->nullable(false)->default(0);
        });

        $user = env('DB_USERNAME', 'root');
        $str = "

        DROP TRIGGER IF EXISTS  `warehouse_receipt_details_on_insert`;

        CREATE
            DEFINER = '$user'@'localhost'
            TRIGGER `warehouse_receipt_details_on_insert` AFTER INSERT ON `warehouse_receipt_details` 
            FOR EACH ROW BEGIN
            SET @data_status = (SELECT STATUS FROM warehouse_receipts WHERE id = NEW.header_id);
            IF @data_status = 1 THEN
            
            SET @data_inbound_date = (SELECT DATE_FORMAT(stripping_done, '%Y-%m-%d') FROM warehouse_receipts WHERE id = NEW.header_id);
            SET @data_warehouse_id = (SELECT warehouse_id FROM warehouse_receipts WHERE id = NEW.header_id);
            SET @data_customer_id = (SELECT customer_id FROM warehouse_receipts WHERE id = NEW.header_id);
            SET @data_sender_id = (SELECT sender_id FROM warehouse_receipts WHERE id = NEW.header_id);
            SET @data_receiver_id = (SELECT receiver_id FROM warehouse_receipts WHERE id = NEW.header_id);
            SET @data_no_surat_jalan = (SELECT `code` FROM warehouse_receipts WHERE id = new.header_id);
            IF new.is_exists = 0 OR new.is_exists IS NULL THEN
            
            SET @data_transaction_id = (SELECT id FROM type_transactions WHERE slug = 'warehouseReceipt');
            
            
            INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan, NEW.item_id, @data_transaction_id, DATE_FORMAT(NOW(), \"%Y-%m-%d\"), \"Penerimaan barang dari customer\", NEW.qty, 0, NEW.id);
            
            IF NEW.pallet_id IS NOT NULL THEN
            INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan, NEW.pallet_id, @data_transaction_id, DATE_FORMAT(NOW(), \"%Y-%m-%d\"), \"Penerimaan barang dari customer\", 0, NEW.pallet_qty, NEW.id);
            END IF;
            
            ELSE
            
            INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan,new.item_id, @data_transaction_id, DATE_FORMAT(NOW(), \"%Y-%m-%d\"), \"Penerimaan barang dari customer\", NEW.qty, 0, NEW.id);
            IF NEW.pallet_id IS NOT NULL THEN
            INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan, NEW.pallet_id, @data_transaction_id, DATE_FORMAT(NOW(), \"%Y-%m-%d\"), \"Penerimaan barang dari customer\", 0, NEW.pallet_qty);
            END IF;
            
            END IF;
            
            END IF;
            
            END;

        ";

        DB::unprepared($str);

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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('item_migrations')->delete();
        DB::table('item_migration_details')->delete();
        DB::table('invoices')->delete();
        DB::table('invoice_details')->delete();
        DB::table('job_order_details')->delete();
        DB::table('job_orders')->delete();
        DB::table('picking_details')->delete();
        DB::table('pickings')->delete();
        DB::table('stok_opname_warehouse_details')->delete();
        DB::table('stok_opname_warehouses')->delete();

        DB::table('stock_transactions_report')
        ->delete();
        DB::table('warehouse_stock_details')
        ->delete();
        DB::table('warehouse_stocks')
        ->delete();
        DB::table('stock_transactions')
        ->delete();
        DB::table('warehouse_receipt_details')
        ->delete();
        DB::table('warehouse_receipts')
        ->delete();

        DB::table('racks')
        ->update([
            'capacity_tonase_used' => 0,
            'capacity_volume_used' => 0,
        ]);

        if(Schema::hasColumn('warehouse_stocks', 'warehouse_receipt_detail_id')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->dropForeign(['warehouse_receipt_detail_id']);
                $table->dropColumn(['warehouse_receipt_detail_id']);
            });
        }

        if(Schema::hasColumn('warehouse_stock_details', 'warehouse_receipt_detail_id')) {
            Schema::table('warehouse_stock_details', function (Blueprint $table) {
                $table->dropColumn(['warehouse_receipt_detail_id']);
            });
        }

        if(Schema::hasColumn('stock_transactions', 'is_approve')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                $table->dropColumn(['is_approve']);
            });
        }
        if(Schema::hasColumn('warehouse_stock_details', 'onhand_qty')) {
            Schema::table('warehouse_stock_details', function (Blueprint $table) {
                $table->integer('qty')->nullable(false)->default(0)->change();
                $table->dropColumn(['available_qty']);
                $table->dropColumn(['onhand_qty']);
            });
        }

        $user = env('DB_USERNAME', 'root');
        $str = "

        DROP TRIGGER /*!50032 IF EXISTS */ `warehouse_receipt_details_on_insert`;

        CREATE
            /*!50017 DEFINER = '$user'@'localhost' */
            TRIGGER `warehouse_receipt_details_on_insert` AFTER INSERT ON `warehouse_receipt_details` 
            FOR EACH ROW BEGIN
            SET @data_status = (SELECT STATUS FROM warehouse_receipts WHERE id = NEW.header_id);
            IF @data_status = 1 THEN
            
            SET @data_inbound_date = (SELECT DATE_FORMAT(stripping_done, '%Y-%m-%d') FROM warehouse_receipts WHERE id = NEW.header_id);
            SET @data_warehouse_id = (SELECT warehouse_id FROM warehouse_receipts WHERE id = NEW.header_id);
            SET @data_customer_id = (SELECT customer_id FROM warehouse_receipts WHERE id = NEW.header_id);
            SET @data_sender_id = (SELECT sender_id FROM warehouse_receipts WHERE id = NEW.header_id);
            SET @data_receiver_id = (SELECT receiver_id FROM warehouse_receipts WHERE id = NEW.header_id);
            SET @data_no_surat_jalan = (SELECT `code` FROM warehouse_receipts WHERE id = new.header_id);
            IF new.is_exists = 0 OR new.is_exists IS NULL THEN
            
            SET @data_transaction_id = (SELECT id FROM type_transactions WHERE slug = 'warehouseReceipt');
            
            
            INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan, NEW.item_id, @data_transaction_id, DATE_FORMAT(NOW(), \"%Y-%m-%d\"), \"Penerimaan barang dari customer\", NEW.qty, 0, NEW.id);
            
            IF NEW.pallet_id IS NOT NULL THEN
            INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan, NEW.pallet_id, @data_transaction_id, DATE_FORMAT(NOW(), \"%Y-%m-%d\"), \"Penerimaan barang dari customer\", 0, NEW.pallet_qty, NEW.id);
            END IF;
            
            ELSE
            
            INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan,new.item_id, @data_transaction_id, DATE_FORMAT(NOW(), \"%Y-%m-%d\"), \"Penerimaan barang dari customer\", NEW.qty, 0);
            IF NEW.pallet_id IS NOT NULL THEN
            INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan, NEW.pallet_id, @data_transaction_id, DATE_FORMAT(NOW(), \"%Y-%m-%d\"), \"Penerimaan barang dari customer\", 0, NEW.pallet_qty);
            END IF;
            
            END IF;
            
            END IF;
            
            END;
        ";
        DB::unprepared($str);

        $strStock = "
            DROP TRIGGER IF EXISTS `on_insert_stock_transaction`;

            CREATE
                DEFINER = '$user'@'localhost' 
                TRIGGER `on_insert_stock_transaction` AFTER INSERT ON `stock_transactions` 
                FOR EACH ROW BEGIN
                
                DECLARE stok_terakhir INT;
                SET @idd:=(SELECT id FROM warehouse_stocks WHERE item_id=new.item_id AND warehouse_id=new.warehouse_id LIMIT 1);
                SET stok_terakhir := (SELECT IFNULL(SUM(qty), 0) FROM `warehouse_stocks` WHERE item_id = new.item_id);
                SET stok_terakhir := stok_terakhir + (new.qty_masuk - new.qty_keluar);
                INSERT INTO `stock_transactions_report` (`header_id`, `warehouse_id`,`rack_id`,`item_id`,`type_transaction_id`,`code`,`date_transaction`,`qty_masuk`,`qty_keluar`,`harga_masuk`,`harga_keluar`,`jumlah_stok`) VALUES (new.id, new.`warehouse_id`, new.`rack_id`, new.`item_id`, new.`type_transaction_id`, new.`code`, new.`date_transaction`, new.`qty_masuk`, new.`qty_keluar`, new.`harga_masuk`, new.`harga_keluar`, stok_terakhir);
                
                INSERT INTO warehouse_stocks ( id,warehouse_id, item_id, qty )
                    VALUES (@idd,new.warehouse_id,
                    new.item_id,
                    ( SELECT SUM( qty_masuk - qty_keluar ) AS wr FROM stock_transactions WHERE item_id = new.item_id AND warehouse_id = new.warehouse_id ) )
                ON DUPLICATE KEY UPDATE
                    qty = VALUES(qty);
                
                SET @warehouse_receipt_id = (SELECT header_id FROM warehouse_receipt_details WHERE id = NEW.warehouse_receipt_detail_id);
                SET @id_stock_exists = (SELECT COUNT(id) FROM warehouse_stock_details WHERE item_id = NEW.item_id AND rack_id = NEW.rack_id AND warehouse_receipt_id = @warehouse_receipt_id);
                IF @id_stock_exists = 0 THEN
                   INSERT INTO warehouse_stock_details(rack_id, item_id, qty, customer_id, warehouse_receipt_id) VALUES(NEW.rack_id, NEW.item_id, NEW.qty_masuk, new.customer_id, @warehouse_receipt_id);
                ELSE 
                   SET @id_stock = (SELECT id FROM warehouse_stock_details WHERE item_id = NEW.item_id AND rack_id = NEW.rack_id AND warehouse_receipt_id = @warehouse_receipt_id LIMIT 0, 1);
                   SET @current_qty = (SELECT qty FROM warehouse_stock_details WHERE id = @id_stock);
                   UPDATE warehouse_stock_details SET qty = @current_qty + NEW.qty_masuk - NEW.qty_keluar WHERE id = @id_stock;
                END IF;
                
                SET @volume = (SELECT `long` * `wide` * `height`  FROM items WHERE id = new.item_id) / 1000000 * ( NEW.qty_masuk - NEW.qty_keluar );
                SET @tonase = (SELECT tonase FROM items WHERE id = new.item_id) * ( NEW.qty_masuk - NEW.qty_keluar );
                
                UPDATE racks SET `capacity_volume_used` = `capacity_volume_used` + @volume, `capacity_tonase_used` = `capacity_tonase_used` + @tonase WHERE id = NEW.rack_id;
            END;
        ";
        DB::unprepared($strStock);
    }
}
