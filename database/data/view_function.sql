DROP VIEW IF EXISTS `notif_type_10`;
CREATE TABLE `notif_type_10` (
    `id` INT(10) UNSIGNED NOT NULL,
    `notif_type` INT(2) NOT NULL,
    `title` VARCHAR(126) NULL COLLATE 'utf8_general_ci',
    `des` VARCHAR(205) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(29) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(2) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` DATETIME NULL,
    `company_id` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `notif_type_11`;
CREATE TABLE `notif_type_11` (
    `id` INT(10) UNSIGNED NOT NULL,
    `title` VARCHAR(29) NOT NULL COLLATE 'utf8mb4_general_ci',
    `des` VARCHAR(401) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(25) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(2) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` TIMESTAMP NULL,
    `company_id` INT(10) UNSIGNED NULL,
    `joid` BIGINT(21) NOT NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `notif_type_12`;
CREATE TABLE `notif_type_12` (
    `id` INT(10) UNSIGNED NULL,
    `title` VARCHAR(47) NOT NULL COLLATE 'utf8mb4_general_ci',
    `des` VARCHAR(407) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(29) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(2) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` TIMESTAMP NULL,
    `company_id` INT(10) UNSIGNED NOT NULL,
    `status_approve` INT(11) NOT NULL COMMENT 'Lead, Opportunity, Inquery, Quotation, Kontrak'
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `notif_type_13`;
CREATE TABLE `notif_type_13` (
    `id` INT(10) UNSIGNED NULL,
    `title` VARCHAR(42) NOT NULL COLLATE 'utf8mb4_general_ci',
    `des` VARCHAR(397) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(28) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(2) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` TIMESTAMP NULL,
    `company_id` INT(10) UNSIGNED NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `notif_type_14`;
CREATE TABLE `notif_type_14` (
    `id` INT(10) UNSIGNED NOT NULL,
    `title` VARCHAR(31) NOT NULL COLLATE 'utf8mb4_general_ci',
    `des` VARCHAR(398) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(29) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(2) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` TIMESTAMP NULL,
    `company_id` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `notif_type_16`;
CREATE TABLE `notif_type_16` (
    `id` INT(10) UNSIGNED NULL,
    `title` VARCHAR(41) NOT NULL COLLATE 'utf8mb4_general_ci',
    `des` VARCHAR(397) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(26) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(2) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` TIMESTAMP NULL,
    `company_id` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `notif_type_17`;
CREATE TABLE `notif_type_17` (
    `id` INT(10) UNSIGNED NOT NULL,
    `title` VARCHAR(72) NOT NULL COLLATE 'utf8mb4_general_ci',
    `des` VARCHAR(413) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(29) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(2) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` TIMESTAMP NULL,
    `company_id` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `notif_type_5`;
CREATE TABLE `notif_type_5` (
    `id` INT(10) UNSIGNED NOT NULL,
    `title` VARCHAR(52) NOT NULL COLLATE 'utf8mb4_general_ci',
    `des` VARCHAR(207) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(29) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` TIMESTAMP NULL,
    `company_id` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `notif_type_6`;
CREATE TABLE `notif_type_6` (
    `id` INT(10) UNSIGNED NULL,
    `title` VARCHAR(63) NOT NULL COLLATE 'utf8mb4_general_ci',
    `des` VARCHAR(397) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(26) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` TIMESTAMP NULL,
    `company_id` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `notif_type_7`;
CREATE TABLE `notif_type_7` (
    `id` INT(10) UNSIGNED NULL,
    `title` VARCHAR(61) NOT NULL COLLATE 'utf8mb4_general_ci',
    `des` VARCHAR(397) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(26) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` TIMESTAMP NULL,
    `company_id` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `notif_type_8`;
CREATE TABLE `notif_type_8` (
    `id` INT(10) UNSIGNED NULL,
    `title` VARCHAR(61) NOT NULL COLLATE 'utf8mb4_general_ci',
    `des` VARCHAR(397) NULL COLLATE 'utf8mb4_unicode_ci',
    `url` VARCHAR(26) NOT NULL COLLATE 'utf8mb4_general_ci',
    `params` VARCHAR(17) NOT NULL COLLATE 'utf8mb4_general_ci',
    `type` VARCHAR(1) NOT NULL COLLATE 'utf8mb4_general_ci',
    `date` TIMESTAMP NULL,
    `company_id` INT(10) UNSIGNED NOT NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `view_cash_ammounts`;
CREATE TABLE `view_cash_ammounts` (
    `company_id` INT(10) UNSIGNED NOT NULL,
    `date_transaction` DATE NOT NULL,
    `saldo_awal` DOUBLE NULL,
    `debet` DOUBLE NOT NULL,
    `credit` DOUBLE NOT NULL
) ENGINE=MyISAM;

DROP VIEW IF EXISTS `view_work_order_costs`;
CREATE TABLE `view_work_order_costs` (
    `id` INT(10) UNSIGNED NOT NULL,
    `code` VARCHAR(191) NULL COLLATE 'utf8mb4_unicode_ci',
    `operasional` DOUBLE NOT NULL,
    `reimburse` DOUBLE NOT NULL,
    `pendapatan` DOUBLE NOT NULL,
    `qty_jo` BIGINT(21) NOT NULL
) ENGINE=MyISAM;

DROP FUNCTION IF EXISTS `cekQtyWo`;
DELIMITER //
CREATE DEFINER=`root`@`localhost` FUNCTION `cekQtyWo`(`wod_id` integer) RETURNS double
BEGIN

    DECLARE qtys double default 0;

    DECLARE stype int;

    DECLARE qty_wo double;

    DECLARE imps int default null;

    SELECT if(qd.service_type_id is not null,qd.service_type_id,pl.service_type_id),ifnull(wod.qty,0),qd.imposition INTO stype, qty_wo, imps FROM work_order_details as wod left join quotation_details as qd on qd.id = wod.quotation_detail_id left join price_lists as pl on pl.id = wod.price_list_id WHERE wod.id = wod_id;

    IF stype IN (2,3,4) THEN

    (SELECT ifnull(sum(total_unit),0) INTO qtys from job_orders WHERE work_order_detail_id = wod_id);

    ELSEIF stype IN (5,7,10) THEN

        (SELECT ifnull(sum(jod.qty),0) INTO qtys from job_order_details as jod left join job_orders as jo on jo.id = jod.header_id WHERE jo.work_order_detail_id = wod_id);

    ELSEIF stype = 6 THEN

        (SELECT count(id) INTO qtys from job_orders where work_order_detail_id = wod_id);

    ELSE

        IF imps is not null THEN

        (SELECT ifnull(SUM(IF(imps=1,jod.volume,IF(imps=2,jod.weight,jod.qty))),0) INTO qtys FROM job_order_details AS jod left join job_orders as jo on jo.id = jod.header_id where jo.work_order_detail_id = wod_id);

        ELSE

        (SELECT ifnull(SUM(IF(jod.imposition=1,jod.volume,IF(jod.imposition=2,jod.weight,jod.qty))),0) INTO qtys FROM job_order_details AS jod left join job_orders as jo on jo.id = jod.header_id where jo.work_order_detail_id = wod_id);

        END IF;

    END IF;

    RETURN qtys;

END//
DELIMITER ;

DROP FUNCTION IF EXISTS `f_invoiceReminder`;
DELIMITER //
CREATE DEFINER=`root`@`localhost` FUNCTION `f_invoiceReminder`() RETURNS int(11)
BEGIN

  DECLARE hari integer;

  DECLARE hr integer;

  SET hari = (SELECT reminder_types.interval FROM reminder_types where id = 1);

  IF hari is not null THEN

  SET hr = hari;

  ELSE

  SET hr = 0;

  END IF;

  RETURN hr;

END//
DELIMITER ;

DROP FUNCTION IF EXISTS `f_satuanKontrak`;
DELIMITER //
CREATE DEFINER=`root`@`localhost` FUNCTION `f_satuanKontrak`(`qd_id` integer) RETURNS char(255) CHARSET latin1
BEGIN

    DECLARE satuan char(255) default "";

    DECLARE stype int;

    SELECT service_type_id INTO stype FROM quotation_details where id = qd_id;

    IF stype IN (1) THEN

    SELECT impositions.name INTO satuan FROM quotation_details LEFT JOIN impositions on impositions.id = quotation_details.imposition where quotation_details.id = qd_id;

    ELSEIF stype IN (3,4) THEN

    SET satuan = "Unit";

    ELSEIF stype IN (2) THEN

    SET satuan = "Kontainer";

    ELSE

    SELECT pieces.name INTO satuan FROM quotation_details LEFT JOIN pieces on pieces.id = quotation_details.piece_id where quotation_details.id = qd_id;

    END IF;

RETURN satuan;

END//
DELIMITER ;

DROP FUNCTION IF EXISTS `f_satuanTarif`;
DELIMITER //
CREATE DEFINER=`root`@`localhost` FUNCTION `f_satuanTarif`(`qd_id` integer) RETURNS char(255) CHARSET latin1
BEGIN

    DECLARE satuan char(255) default "";

    DECLARE stype int;

    SELECT service_type_id INTO stype FROM price_lists where id = qd_id;

    IF stype IN (1) THEN

    SET satuan = "Kubikasi/Tonase/Item";

    ELSEIF stype IN (2) THEN

    SET satuan = "Unit";

    ELSEIF stype IN (3) THEN

    SET satuan = "Kontainer";

    ELSEIF stype IN (4) THEN

    SELECT vehicle_types.name INTO satuan FROM price_lists LEFT JOIN vehicle_types on vehicle_types.id = price_lists.vehicle_type_id where price_lists.id = qd_id;

    ELSE

    SELECT pieces.name INTO satuan FROM price_lists LEFT JOIN pieces on pieces.id = price_lists.piece_id where price_lists.id = qd_id;

    END IF;

RETURN satuan;

END//
DELIMITER ;

DROP TRIGGER IF EXISTS `bill_payments_on_insert`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `bill_payments_on_insert` AFTER INSERT ON `bill_payments` FOR EACH ROW BEGIN
    UPDATE bills 
    SET paid = ( SELECT sum( total ) FROM bill_payments WHERE header_id = NEW.header_id )
WHERE
    id = NEW.header_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `bill_payments_on_update`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `bill_payments_on_update` AFTER UPDATE ON `bill_payments` FOR EACH ROW BEGIN
    UPDATE bills 
    SET paid = ( SELECT sum( total ) FROM bill_payments WHERE header_id = NEW.header_id )
WHERE
    id = NEW.header_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `containers_on_update`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `containers_on_update` AFTER UPDATE ON `containers` FOR EACH ROW BEGIN
    UPDATE voyage_schedules SET total_container = (select count(*) as tot from containers where voyage_schedule_id = new.voyage_schedule_id)
    WHERE id = new.voyage_schedule_id;
    UPDATE voyage_schedules SET total_container = (select count(*) as tot from containers where voyage_schedule_id = old.voyage_schedule_id)
    WHERE id = old.voyage_schedule_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `handling_on_done`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `handling_on_done` AFTER UPDATE ON `handlings` FOR EACH ROW BEGIN
    DECLARE v_id INTEGER ;
    DECLARE v_finished INTEGER DEFAULT 0;
    DECLARE item_cursor CURSOR FOR SELECT id FROM job_order_details WHERE header_id = NEW.job_order_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_finished = 1;
    
    
    IF new.status = 1 THEN
        OPEN item_cursor;
        
        SET @data_transaction_id = (SELECT id FROM type_transactions WHERE `name` = "Handling");
        get_item: LOOP
        FETCH item_cursor INTO v_id;
        IF v_finished = 1 THEN 
            LEAVE get_item;
        END IF;
        
        SET @data_item_id = (SELECT item_id FROM job_order_details WHERE id = v_id);
        SET @data_qty = (SELECT qty FROM job_order_details WHERE id = v_id);
        SET @data_rack_id = (SELECT rack_id FROM job_order_details WHERE id = v_id);
        SET @data_no_surat_jalan = (SELECT no_surat_jalan FROM job_order_details WHERE id = v_id);
        SET @data_warehouse_receipt_detail_id = (SELECT warehouse_receipt_detail_id FROM job_order_details WHERE id = v_id);
        INSERT INTO `stock_transactions`(`no_surat_jalan`, `warehouse_id`, `rack_id`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(@data_no_surat_jalan, NEW.warehouse_id, @data_rack_id, @data_item_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Pengeluaran barang dari handling area", 0, @data_qty, @data_warehouse_receipt_detail_id);
    
        END LOOP get_item;
    END IF;
    END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `job_order_details_on_delete`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `job_order_details_on_delete` AFTER DELETE ON `job_order_details` FOR EACH ROW BEGIN
    SET @is_packaging = (SELECT is_packaging FROM job_orders WHERE id = old.header_id);
    IF @is_packaging != 1 THEN
        UPDATE job_orders SET
        price = (select IFNULL(sum(price),0) from job_order_details where header_id = old.header_id),
        total_price = (select IFNULL(sum(total_price),0) from job_order_details where header_id = old.header_id)
        WHERE id = old.header_id;
    end if;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `job_order_details_on_insert`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `job_order_details_on_insert` AFTER INSERT ON `job_order_details` FOR EACH ROW BEGIN
    SET @is_packaging = (SELECT is_packaging FROM job_orders WHERE id = new.header_id);
    if @is_packaging != 1 THEN
        UPDATE job_orders SET
        price = (select sum(price) from job_order_details where header_id = new.header_id),
        total_price = (select sum(total_price) from job_order_details where header_id = new.header_id)
        WHERE id = new.header_id;
    end if;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `job_order_details_on_update`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `job_order_details_on_update` AFTER UPDATE ON `job_order_details` FOR EACH ROW BEGIN
    SET @is_packaging = (SELECT is_packaging FROM job_orders WHERE id = new.header_id);
    IF @is_packaging != 1 THEN
        UPDATE job_orders SET
        price = (select sum(price) from job_order_details where header_id = new.header_id),
        total_price = (select sum(total_price) from job_order_details where header_id = new.header_id)
        WHERE id = new.header_id;
    end if;
    
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `jurnal_header_on_delete`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `jurnal_header_on_delete` AFTER DELETE ON `journal_details` FOR EACH ROW BEGIN
    UPDATE journals 
    SET debet = ( SELECT sum( debet ) FROM journal_details WHERE header_id = OLD.header_id ),
    credit = ( SELECT sum( credit ) FROM journal_details WHERE header_id = OLD.header_id ) 
WHERE
    id = OLD.header_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `jurnal_header_on_insert`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `jurnal_header_on_insert` AFTER INSERT ON `journal_details` FOR EACH ROW BEGIN
    UPDATE journals 
    SET debet = ( SELECT sum( debet ) FROM journal_details WHERE header_id = NEW.header_id ),
    credit = ( SELECT sum( credit ) FROM journal_details WHERE header_id = NEW.header_id ) 
WHERE
    id = NEW.header_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `jurnal_header_on_update`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `jurnal_header_on_update` AFTER UPDATE ON `journal_details` FOR EACH ROW BEGIN
    UPDATE journals 
    SET debet = ( SELECT sum( debet ) FROM journal_details WHERE header_id = NEW.header_id ),
    credit = ( SELECT sum( credit ) FROM journal_details WHERE header_id = NEW.header_id ) 
WHERE
    id = NEW.header_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `on_delete_route_cost_details`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `on_delete_route_cost_details` AFTER DELETE ON `route_cost_details` FOR EACH ROW BEGIN
    UPDATE route_costs 
    SET cost = ( SELECT IFNULL( sum( cost ), 0 ) FROM route_cost_details WHERE header_id = OLD.header_id )
    WHERE id = OLD.header_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `on_insert_payable_details`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `on_insert_payable_details` AFTER INSERT ON `payable_details` FOR EACH ROW BEGIN
    UPDATE payables 
    SET debet = ( SELECT IFNULL( sum( debet ), 0 ) FROM payable_details WHERE header_id = new.header_id ),
    credit = ( SELECT IFNULL( sum( credit ), 0 ) FROM payable_details WHERE header_id = new.header_id )
    WHERE id = NEW.header_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `on_insert_receivable_details`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `on_insert_receivable_details` AFTER INSERT ON `receivable_details` FOR EACH ROW BEGIN
    UPDATE receivables 
    SET debet = ( SELECT IFNULL( sum( debet ), 0 ) FROM receivable_details WHERE header_id = new.header_id ),
    credit = ( SELECT IFNULL( sum( credit ), 0 ) FROM receivable_details WHERE header_id = new.header_id )
    WHERE id = NEW.header_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `on_insert_route_cost_details`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `on_insert_route_cost_details` AFTER INSERT ON `route_cost_details` FOR EACH ROW BEGIN
    UPDATE route_costs 
    SET cost = ( SELECT IFNULL( sum( cost ), 0 ) FROM route_cost_details WHERE header_id = new.header_id )
    WHERE id = NEW.header_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `on_insert_stock_transaction`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `on_insert_stock_transaction` AFTER INSERT ON `stock_transactions` FOR EACH ROW BEGIN
    
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
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `on_update_receivable_details`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `on_update_receivable_details` AFTER UPDATE ON `receivable_details` FOR EACH ROW BEGIN
    UPDATE receivables 
    SET debet = ( SELECT IFNULL( sum( debet ), 0 ) FROM receivable_details WHERE header_id = new.header_id ),
    credit = ( SELECT IFNULL( sum( credit ), 0 ) FROM receivable_details WHERE header_id = new.header_id )
    WHERE id = NEW.header_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `packaging_on_done`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `packaging_on_done` AFTER UPDATE ON `packagings` FOR EACH ROW BEGIN
    DECLARE v_id INTEGER ;
    DECLARE v_finished INTEGER DEFAULT 0;
    DECLARE item_cursor CURSOR FOR SELECT id FROM job_order_details WHERE header_id = NEW.job_order_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_finished = 1;
    
    
    IF new.status = 1 THEN
        OPEN item_cursor;
        
        SET @data_transaction_id = (SELECT id FROM type_transactions WHERE `name` = "Packaging");
        get_item: LOOP
        FETCH item_cursor INTO v_id;
        IF v_finished = 1 THEN 
            LEAVE get_item;
        END IF;
        
        SET @data_item_id = (SELECT item_id FROM job_order_details WHERE id = v_id);
        SET @data_sender_id = (SELECT sender_id FROM items WHERE id = @data_item_id);
        SET @data_receiver_id = (SELECT receiver_id FROM items WHERE id = @data_item_id);
        SET @data_qty = (SELECT qty FROM job_order_details WHERE id = v_id) * NEW.qty;
        SET @data_rack_id = (SELECT rack_id FROM job_order_details WHERE id = v_id);
        
        SET @data_warehouse_receipt_detail_id = (SELECT warehouse_receipt_detail_id FROM job_order_details WHERE id = v_id);
        
        INSERT INTO `stock_transactions`(`warehouse_id`, `rack_id`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES( NEW.warehouse_id, @data_rack_id, @data_item_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Pengeluaran barang dari untuk pengemasan", 0, @data_qty, @data_warehouse_receipt_detail_id);
    
        END LOOP get_item;
        
        SET @data_warehouse_id = NEW.warehouse_id;
        SET @data_customer_id = (SELECT customer_id FROM job_orders WHERE id = NEW.job_order_id);
        SET @item_order = (SELECT COUNT(id) + 1 FROM items WHERE customer_id = @data_customer_id);
        SET @item_code = (SELECT CONCAT('BRG', LPAD(@data_customer_id, 4, 0), LPAD(@item_order, 3, 0)));
        
        SET @item_piece_id = (SELECT id FROM pieces WHERE `name` = 'Item');
        INSERT INTO items(`code`, `name`, `barcode`, `is_stock`, `customer_id`, `sender_id`, `receiver_id`, `inbound_date`, `piece_id`, `long`, wide, height, tonase, volume, is_package) VALUES (@item_code, NEW.item_name, NEW.barcode, 0, @data_customer_id, @data_sender_id, @data_receiver_id, @data_inbound_date, @item_piece_id, 10, 10, 10, 10, 10, 1);
    
        SET @data_item_id = (SELECT id FROM items WHERE `code` = @item_code);
        INSERT INTO `stock_transactions`(`warehouse_id`, `rack_id`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(NEW.warehouse_id, NEW.rack_id, @data_item_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Pemasukan barang dari hasil pengemasan", new.qty, 0, @data_warehouse_receipt_detail_id);
    END IF;
    END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `picking_on_approve`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `picking_on_approve` AFTER UPDATE ON `pickings` FOR EACH ROW BEGIN
    DECLARE v_id INTEGER ;
    DECLARE v_finished INTEGER DEFAULT 0;
    DECLARE item_cursor CURSOR FOR SELECT id FROM picking_details WHERE header_id = NEW.id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_finished = 1;
    
    
    IF new.status = 2 THEN
        OPEN item_cursor;
        SET @data_rack_target_id = (SELECT racks.id FROM racks JOIN storage_types ON storage_type_id = storage_types.id WHERE is_picking_area = 1 AND warehouse_id = NEW.warehouse_id);
        SET @data_transaction_id = (SELECT id FROM type_transactions WHERE `slug` = "picking");
        get_item: LOOP
        FETCH item_cursor INTO v_id;
        IF v_finished = 1 THEN 
            LEAVE get_item;
        END IF;
        
        SET @data_item_id = (SELECT item_id FROM picking_details WHERE id = v_id);
        SET @data_qty = (SELECT qty FROM picking_details WHERE id = v_id);
        SET @data_rack_id = (SELECT rack_id FROM picking_details WHERE id = v_id);
        
        SET @data_warehouse_receipt_detail_id = (SELECT warehouse_receipt_detail_id FROM picking_details WHERE id = v_id);
        INSERT INTO `stock_transactions`(`warehouse_id`, `rack_id`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(NEW.warehouse_id, @data_rack_id, @data_item_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Pengeluaran barang dari rak penyimpanan", 0, @data_qty, @data_warehouse_receipt_detail_id);
        INSERT INTO `stock_transactions`(`warehouse_id`, `rack_id`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(NEW.warehouse_id, @data_rack_target_id, @data_item_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Penerimaan barang ke picking area", @data_qty, 0, @data_warehouse_receipt_detail_id);
        END LOOP get_item;
    END IF;
    END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `quotation_cost_on_delete`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `quotation_cost_on_delete` AFTER DELETE ON `quotation_costs` FOR EACH ROW BEGIN
    UPDATE quotation_details 
    SET cost = ( SELECT IFNULL(sum( total*cost ),0) AS ttl FROM quotation_costs WHERE quotation_detail_id = OLD.quotation_detail_id)
WHERE
    id = OLD.quotation_detail_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `quotation_cost_on_insert`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `quotation_cost_on_insert` AFTER INSERT ON `quotation_costs` FOR EACH ROW BEGIN
    UPDATE quotation_details 
    SET cost = ( SELECT IFNULL(sum( total*cost ),0) AS ttl FROM quotation_costs WHERE quotation_detail_id = new.quotation_detail_id)
WHERE
    id = new.quotation_detail_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `quotation_cost_on_update`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
DELIMITER //
CREATE TRIGGER `quotation_cost_on_update` AFTER UPDATE ON `quotation_costs` FOR EACH ROW BEGIN
    UPDATE quotation_details 
    SET cost = ( SELECT IFNULL(sum( total*cost ),0) AS ttl FROM quotation_costs WHERE quotation_detail_id = new.quotation_detail_id)
WHERE
    id = new.quotation_detail_id;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `stuffing_on_done`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `stuffing_on_done` AFTER UPDATE ON `stuffings` FOR EACH ROW BEGIN
    DECLARE v_id INTEGER ;
    DECLARE v_finished INTEGER DEFAULT 0;
    DECLARE item_cursor CURSOR FOR SELECT id FROM job_order_details WHERE header_id = NEW.job_order_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_finished = 1;
    
    
    IF new.status = 1 THEN
        OPEN item_cursor;
        
        SET @data_transaction_id = (SELECT id FROM type_transactions WHERE `name` = "Stuffing");
        get_item: LOOP
        FETCH item_cursor INTO v_id;
        IF v_finished = 1 THEN 
            LEAVE get_item;
        END IF;
        
        SET @data_item_id = (SELECT item_id FROM job_order_details WHERE id = v_id);
        SET @data_qty = (SELECT qty FROM job_order_details WHERE id = v_id);
        SET @data_rack_id = (SELECT rack_id FROM job_order_details WHERE id = v_id);
        SET @data_no_surat_jalan = (SELECT no_surat_jalan FROM job_order_details WHERE id = v_id);
        SET @data_warehouse_receipt_detail_id = (SELECT warehouse_receipt_detail_id FROM job_order_details WHERE id = v_id);
        INSERT INTO `stock_transactions`(no_surat_jalan, `warehouse_id`, `rack_id`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(@data_no_surat_jalan, NEW.warehouse_id, @data_rack_id, @data_item_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Pengeluaran barang dari picking area", 0, @data_qty, @data_warehouse_receipt_detail_id);
    
        END LOOP get_item;
    END IF;
    END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `warehouse_receipts_on_update`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `warehouse_receipts_on_update` BEFORE UPDATE ON `warehouse_receipts` FOR EACH ROW BEGIN
    DECLARE v_id INTEGER ;
    DECLARE v_finished INTEGER DEFAULT 0;
        DECLARE item_cursor CURSOR FOR SELECT id FROM warehouse_receipt_details WHERE header_id = NEW.id;
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_finished = 1;
        
        IF NEW.status = 1 AND OLD.status != 1 THEN
        
            OPEN item_cursor;
            get_item: LOOP
        
                FETCH item_cursor INTO v_id;
                IF v_finished = 1 THEN 
                    LEAVE get_item;
                END IF;
                
                SET @data_inbound_date = (SELECT DATE_FORMAT(stripping_done, '%Y-%m-%d') FROM warehouse_receipts WHERE id = NEW.id);
                SET @data_warehouse_id = NEW.id;
                SET @data_sender_id = NEW.sender_id;
                SET @data_receiver_id = NEW.receiver_id;
                SET @data_transaction_id = (SELECT id FROM type_transactions WHERE slug = 'warehouseReceipt');
                SET @data_rack_id = (SELECT rack_id FROM warehouse_receipt_details WHERE id = v_id);
                
                SET @data_qty = (SELECT qty FROM warehouse_receipt_details WHERE id = v_id);
                SET @data_pallet_id = (SELECT pallet_id FROM warehouse_receipt_details WHERE id = v_id);
                
                SET @data_item_id = (SELECT item_id FROM warehouse_receipt_details WHERE `id` = v_id);
                
                INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(NEW.customer_id, NEW.warehouse_id, @data_rack_id, NEW.code, @data_item_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Penerimaan barang dari customer", @data_qty, 0, v_id);
                
                IF @data_pallet_id IS NOT NULL THEN
                SET @data_pallet_qty = (SELECT pallet_qty FROM warehouse_receipt_details WHERE id = v_id);
                INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(NEW.customer_id, NEW.warehouse_id, @data_rack_id, NEW.code, @data_pallet_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Penerimaan barang dari customer", 0, @data_pallet_qty, v_id);
                END IF;
        END LOOP get_item;
        CLOSE item_cursor;
    END IF;
    END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP TRIGGER IF EXISTS `warehouse_receipt_details_on_insert`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `warehouse_receipt_details_on_insert` AFTER INSERT ON `warehouse_receipt_details` FOR EACH ROW BEGIN
    SET @data_status = (SELECT STATUS FROM warehouse_receipts WHERE id = NEW.header_id);
    if @data_status = 1 then
    
    SET @data_inbound_date = (SELECT DATE_FORMAT(stripping_done, '%Y-%m-%d') FROM warehouse_receipts WHERE id = NEW.header_id);
    SET @data_warehouse_id = (SELECT warehouse_id FROM warehouse_receipts WHERE id = NEW.header_id);
    SET @data_customer_id = (SELECT customer_id FROM warehouse_receipts WHERE id = NEW.header_id);
    SET @data_sender_id = (SELECT sender_id FROM warehouse_receipts WHERE id = NEW.header_id);
    SET @data_receiver_id = (SELECT receiver_id FROM warehouse_receipts WHERE id = NEW.header_id);
    SET @data_no_surat_jalan = (SELECT `code` FROM warehouse_receipts WHERE id = new.header_id);
    IF new.is_exists = 0 OR new.is_exists IS NULL THEN
    
    SET @data_transaction_id = (SELECT id FROM type_transactions WHERE slug = 'warehouseReceipt');
    
    
    INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan, NEW.item_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Penerimaan barang dari customer", NEW.qty, 0, NEW.id);
    
    if NEW.pallet_id IS not null then
    INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`, warehouse_receipt_detail_id) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan, NEW.pallet_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Penerimaan barang dari customer", 0, NEW.pallet_qty, NEW.id);
    end if;
    
    else
    
    INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan,new.item_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Penerimaan barang dari customer", NEW.qty, 0);
    IF NEW.pallet_id IS NOT NULL THEN
    INSERT INTO `stock_transactions`(`customer_id`, `warehouse_id`, `rack_id`, `no_surat_jalan`, `item_id`, `type_transaction_id`, `date_transaction`, `description`, `qty_masuk`, `qty_keluar`) VALUES(@data_customer_id, @data_warehouse_id, NEW.rack_id, @data_no_surat_jalan, NEW.pallet_id, @data_transaction_id, DATE_FORMAT(NOW(), "%Y-%m-%d"), "Penerimaan barang dari customer", 0, NEW.pallet_qty);
    END IF;
    
    end if;
    
    end if;
    
    END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

DROP VIEW IF EXISTS `notif_type_10`;
DROP TABLE IF EXISTS `notif_type_10`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_10` AS select `invoices`.`id` AS `id`,10 AS `notif_type`,convert((case when ((to_days(`invoices`.`due_date`) - to_days(now())) = 0) then convert(concat('Ada Invoice Jual yang jatuh tempo hari ini !') using utf8mb4) when ((to_days(`invoices`.`due_date`) - to_days(now())) = 1) then convert(concat('Ada Invoice Jual yang akan jatuh tempo besok !') using utf8mb4) else concat('Ada Invoice yang akan jatuh tempo dalam ',(to_days(`invoices`.`due_date`) - to_days(now())),' hari pada ',convert(date_format(`invoices`.`due_date`,'%d %M') using utf8mb4),'!') end) using utf8) AS `title`,concat('No. Invoice : ',`invoices`.`code`) AS `des`,concat('operational.invoice_jual.show') AS `url`,concat('{"id":',`invoices`.`id`,'}') AS `params`,concat(10) AS `type`,cast((`invoices`.`due_date` - interval (to_days(`invoices`.`due_date`) - to_days(now())) day) as datetime) AS `date`,`invoices`.`company_id` AS `company_id` from `invoices` where (`invoices`.`due_date` between cast(now() as date) and cast((now() + interval `f_invoiceReminder`() day) as date)) ;

DROP VIEW IF EXISTS `notif_type_11`;
DROP TABLE IF EXISTS `notif_type_11`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_11` AS select `wo`.`id` AS `id`,concat('Work Order Baru telah Dibuat!') AS `title`,concat('No. WO : ',`wo`.`code`,' Customer ',`ct`.`name`) AS `des`,concat('marketing.work_order.show') AS `url`,concat('{"id":',`wo`.`id`,'}') AS `params`,concat(11) AS `type`,`wo`.`created_at` AS `date`,`wo`.`company_id` AS `company_id`,count(`jo`.`id`) AS `joid` from ((`work_orders` `wo` left join `contacts` `ct` on((`ct`.`id` = `wo`.`customer_id`))) left join `job_orders` `jo` on((`jo`.`work_order_id` = `wo`.`id`))) group by `wo`.`id` having (`joid` < 1) ;

DROP VIEW IF EXISTS `notif_type_12`;
DROP TABLE IF EXISTS `notif_type_12`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_12` AS select `quotation_details`.`id` AS `id`,concat('Ada Item Quotation yang memerlukan persetujuan!') AS `title`,concat('No. Quotation : ',`quotations`.`code`,' Layanan ',`services`.`name`) AS `des`,concat('marketing.inquery.show.detail') AS `url`,concat('{"id":',`quotations`.`id`,'}') AS `params`,concat(12) AS `type`,`quotation_details`.`created_at` AS `date`,`quotations`.`company_id` AS `company_id`,`quotations`.`status_approve` AS `status_approve` from ((`quotations` left join `quotation_details` on((`quotations`.`id` = `quotation_details`.`header_id`))) left join `services` on((`services`.`id` = `quotation_details`.`service_id`))) where (`quotations`.`status_approve` = 1) ;

DROP VIEW IF EXISTS `notif_type_13`;
DROP TABLE IF EXISTS `notif_type_13`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_13` AS select `joc`.`id` AS `id`,concat('Ada Pengajuan Biaya Job Order ke Keuangan!') AS `title`,concat('No. JO : ',`jo`.`code`,' pada ',`ct`.`name`) AS `des`,concat('finance.submission_cost.show') AS `url`,concat('{"id":',`sc`.`id`,'}') AS `params`,concat(13) AS `type`,`sc`.`updated_at` AS `date`,`jo`.`company_id` AS `company_id` from (`submission_costs` `sc` left join ((`job_orders` `jo` left join `job_order_costs` `joc` on((`jo`.`id` = `joc`.`header_id`))) left join `cost_types` `ct` on((`ct`.`id` = `joc`.`cost_type_id`))) on((`sc`.`relation_cost_id` = `joc`.`id`))) where (`joc`.`status` = 2) ;

DROP VIEW IF EXISTS `notif_type_14`;
DROP TABLE IF EXISTS `notif_type_14`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_14` AS select `inv`.`id` AS `id`,concat('Invoice Jual Baru telah Dibuat!') AS `title`,concat('No. Invoice : ',`inv`.`code`,', ',`ct`.`name`) AS `des`,concat('operational.invoice_jual.show') AS `url`,concat('{"id":',`inv`.`id`,'}') AS `params`,concat(14) AS `type`,`inv`.`created_at` AS `date`,`inv`.`company_id` AS `company_id` from (`invoices` `inv` left join `contacts` `ct` on((`ct`.`id` = `inv`.`customer_id`))) where (`inv`.`status` = 1) ;

DROP VIEW IF EXISTS `notif_type_16`;
DROP TABLE IF EXISTS `notif_type_16`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_16` AS select `joc`.`id` AS `id`,concat('Ada Biaya Job Order memerlukan Pengajuan!') AS `title`,concat('No. JO : ',`jo`.`code`,' pada ',`ct`.`name`) AS `des`,concat('operational.job_order.show') AS `url`,concat('{"id":',`jo`.`id`,'}') AS `params`,concat(16) AS `type`,`joc`.`updated_at` AS `date`,`jo`.`company_id` AS `company_id` from ((`job_orders` `jo` left join `job_order_costs` `joc` on((`jo`.`id` = `joc`.`header_id`))) left join `cost_types` `ct` on((`ct`.`id` = `joc`.`cost_type_id`))) where (`joc`.`status` = 1) ;

DROP VIEW IF EXISTS `notif_type_17`;
DROP TABLE IF EXISTS `notif_type_17`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_17` AS select `J`.`id` AS `id`,concat('Ada manifest yang kapalnya sudah berangkat tapi belum mempunyai invoice!') AS `title`,concat('No. Manifest : ',`M`.`code`,' pada Job Order ',`JO`.`code`) AS `des`,concat('operational.manifest_fcl.show') AS `url`,concat('{"id":',`M`.`id`,'}') AS `params`,concat(17) AS `type`,`M`.`date_manifest` AS `date`,`M`.`company_id` AS `company_id` from (((((`job_order_details` `J` join `manifest_details` `MD` on((`J`.`id` = `MD`.`job_order_detail_id`))) join `manifests` `M` on((`M`.`id` = `MD`.`header_id`))) join `containers` `C` on((`C`.`id` = `M`.`container_id`))) join `voyage_schedules` `V` on((`V`.`id` = `C`.`id`))) join `job_orders` `JO` on((`JO`.`id` = `J`.`header_id`))) where (((to_days(`V`.`departure`) - to_days(now())) < -(1)) and isnull(`JO`.`invoice_id`)) ;

DROP VIEW IF EXISTS `notif_type_5`;
DROP TABLE IF EXISTS `notif_type_5`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_5` AS select `quotations`.`id` AS `id`,concat('Ada Quotation yang memerlukan persetujuan penawaran!') AS `title`,concat('No. Quotation : ',`quotations`.`code`) AS `des`,concat('marketing.inquery.show.detail') AS `url`,concat('{"id":',`quotations`.`id`,'}') AS `params`,concat(5) AS `type`,`quotations`.`created_at` AS `date`,`quotations`.`company_id` AS `company_id` from (`quotations` left join `quotation_details` on((`quotation_details`.`header_id` = `quotations`.`id`))) where (`quotations`.`status_approve` = 2) group by `quotations`.`id` having ((count(`quotation_details`.`id`) = sum(`quotation_details`.`is_approve`)) and (count(`quotation_details`.`id`) > 0)) ;

DROP VIEW IF EXISTS `notif_type_6`;
DROP TABLE IF EXISTS `notif_type_6`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_6` AS select `joc`.`id` AS `id`,concat('Ada Biaya Job Order Baru yang memerlukan persetujuan Supervisi!') AS `title`,concat('No. JO : ',`jo`.`code`,' pada ',`ct`.`name`) AS `des`,concat('operational.job_order.show') AS `url`,concat('{"id":',`jo`.`id`,'}') AS `params`,concat(6) AS `type`,`joc`.`updated_at` AS `date`,`jo`.`company_id` AS `company_id` from ((`job_orders` `jo` left join `job_order_costs` `joc` on((`jo`.`id` = `joc`.`header_id`))) left join `cost_types` `ct` on((`ct`.`id` = `joc`.`cost_type_id`))) where ((`joc`.`status` = 7) and (`joc`.`total_price` < 50000000) and isnull(`jo`.`invoice_id`)) ;

DROP VIEW IF EXISTS `notif_type_7`;
DROP TABLE IF EXISTS `notif_type_7`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_7` AS select `joc`.`id` AS `id`,concat('Ada Biaya Job Order Baru yang memerlukan persetujuan Manajer!') AS `title`,concat('No. JO : ',`jo`.`code`,' pada ',`ct`.`name`) AS `des`,concat('operational.job_order.show') AS `url`,concat('{"id":',`jo`.`id`,'}') AS `params`,concat(7) AS `type`,`joc`.`updated_at` AS `date`,`jo`.`company_id` AS `company_id` from ((`job_orders` `jo` left join `job_order_costs` `joc` on((`jo`.`id` = `joc`.`header_id`))) left join `cost_types` `ct` on((`ct`.`id` = `joc`.`cost_type_id`))) where ((`joc`.`status` = 7) and (`joc`.`total_price` between 50000001 and 100000000) and isnull(`jo`.`invoice_id`)) ;

DROP VIEW IF EXISTS `notif_type_8`;
DROP TABLE IF EXISTS `notif_type_8`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notif_type_8` AS select `joc`.`id` AS `id`,concat('Ada Biaya Job Order Baru yang memerlukan persetujuan Direksi!') AS `title`,concat('No. JO : ',`jo`.`code`,' pada ',`ct`.`name`) AS `des`,concat('operational.job_order.show') AS `url`,concat('{"id":',`jo`.`id`,'}') AS `params`,concat(8) AS `type`,`joc`.`updated_at` AS `date`,`jo`.`company_id` AS `company_id` from ((`job_orders` `jo` left join `job_order_costs` `joc` on((`jo`.`id` = `joc`.`header_id`))) left join `cost_types` `ct` on((`ct`.`id` = `joc`.`cost_type_id`))) where ((`joc`.`status` = 7) and (`joc`.`total_price` > 100000000) and isnull(`jo`.`invoice_id`)) ;

DROP VIEW IF EXISTS `view_cash_ammounts`;
DROP TABLE IF EXISTS `view_cash_ammounts`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_cash_ammounts` AS (select `j`.`company_id` AS `company_id`,`j`.`date_transaction` AS `date_transaction`,sum((`jd`.`debet` - `jd`.`credit`)) AS `saldo_awal`,ifnull(sum(`jd`.`debet`),0) AS `debet`,ifnull(sum(`jd`.`credit`),0) AS `credit` from ((`journal_details` `jd` join `journals` `j` on((`jd`.`header_id` = `j`.`id`))) join `accounts` `a` on((`jd`.`account_id` = `a`.`id`))) where ((`a`.`no_cash_bank` = 1) and (`j`.`status` = 3)) group by `j`.`date_transaction`,`j`.`company_id`) ;

DROP VIEW IF EXISTS `view_work_order_costs`;
DROP TABLE IF EXISTS `view_work_order_costs`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_work_order_costs` AS select `wo`.`id` AS `id`,`wo`.`code` AS `code`,ifnull((select sum(`job_order_costs`.`total_price`) from (`job_order_costs` left join `job_orders` on((`job_orders`.`id` = `job_order_costs`.`header_id`))) where ((`job_orders`.`work_order_id` = `wo`.`id`) and (`job_order_costs`.`type` = 1) and (`job_order_costs`.`status` in (3,5)))),0) AS `operasional`,ifnull((select sum(`job_order_costs`.`total_price`) from (`job_order_costs` left join `job_orders` on((`job_orders`.`id` = `job_order_costs`.`header_id`))) where ((`job_orders`.`work_order_id` = `wo`.`id`) and (`job_order_costs`.`type` = 2) and (`job_order_costs`.`status` in (3,5)))),0) AS `reimburse`,ifnull((select sum(if((`job_orders`.`service_type_id` in (2,3,4)),(`job_orders`.`duration` * `job_orders`.`total_unit` * `job_orders`.`price`),`job_orders`.`duration` * `job_orders`.`total_price`)) from `job_orders` where (`job_orders`.`work_order_id` = `wo`.`id`)),0) AS `pendapatan`,ifnull((select count(`job_orders`.`id`) from `job_orders` where (`job_orders`.`work_order_id` = `wo`.`id`)),0) AS `qty_jo` from `work_orders` `wo` ;
