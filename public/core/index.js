(function(orig) {
    angular.modules = [];
    angular.module = function() {
        if (arguments.length > 1) {
            angular.modules.push(arguments[0]);
        }
        return orig.apply(null, arguments);
    }
})(angular.module);

function getModule(str) {
    if(str) {
        var exist = angular.modules.findIndex(x => x == str)
        if(exist > -1) {
            return true
        }
    }

    return false
}

var solog = angular.module('solog', [], () => {})
.service('solog', function(){
    var label = {}
    label.general = {}
    label.general.is_dangerous_good = 'Is Dangerous Good'
    label.general.is_fast_moving = 'Is Fast Moving'
    label.general.stock_by_warehouse = 'Stock By Warehouses'
    label.general.stock_by_item = 'Stock By Item'
    label.general.stocklist = 'Stocklist'
    label.general.generate = 'Generate'
    label.general.row = 'Row'
    label.general.column = 'Column'
    label.general.level = 'Level'
    label.general.latitude = 'Latitude'
    label.general.longitude = 'Longitude'
    label.general.map = 'Map'
    label.general.map_location = 'Map Location'
    label.general.duration = 'Duration'
    label.general.checker = 'Checker'
    label.general.day = 'Day'
    label.general.free_storage_day = 'Free Storage Day'
    label.general.over_storage_day = 'Over Storage Day'
    label.general.insurance = 'Insurance'
    label.general.operational = 'Operational'
    label.general.invoice = 'Invoice'
    label.general.origin = 'Origin'
    label.general.cost = 'Cost'
    label.general.revenue = 'Revenue'
    label.general.profit = 'Profit'
    label.general.employee = 'Employee'
    label.general.destination = 'Destination'
    label.general.qty_on_system = 'Qty On System'
    label.general.qty_on_real = 'Qty On Real'
    label.general.imposition = 'Charge in'
    label.general.storage_type = 'Storage Type'
    label.general.service = 'Service'
    label.general.service_type = 'Service Type'
    label.general.add = 'Add'
    label.general.print = 'Print'
    label.general.delete = 'Delete'
    label.general.reset = 'Reset'
    label.general.edit = 'Edit'
    label.general.approve = 'Approve'
    label.general.reject = 'Reject'
    label.general.code = 'Code'
    label.general.name = 'Name'
    label.general.address = 'Address'
    label.general.description = 'Description'
    label.general.date = 'Date'
    label.general.receive_date = 'Receive Date'
    label.general.load_date = 'Load Date'
    label.general.due_date = 'Due Date'
    label.general.type = 'Type'
    label.general.create = 'Create'
    label.general.save = 'Save'
    label.general.cancel = 'Cancel'
    label.general.save_as = 'Save As'
    label.general.back = 'Back'
    label.general.choose = 'Choose'
    label.general.feature = 'Feature'
    label.general.status = 'Status'
    label.general.user = 'User'
    label.general.contact = 'Contact'
    label.general.warehouse = 'Warehouse'
    label.general.container = 'Container'
    label.general.container_part = 'Container Part'
    label.general.container_yard = 'Container Yard'
    label.general.container_yard_destination = 'Container Yard Destination'
    label.general.container_inspection = 'Container Inspection'
    label.general.container_type = 'Container Type'
    label.general.origin_warehouse = 'Origin Warehouse'
    label.general.destination_warehouse = 'Destination Warehouse'
    label.general.branch = 'Branch'
    label.general.category = 'Category'
    label.general.owner = 'Owner'
    label.general.customer = 'Customer'
    label.general.operator = 'Operator'
    label.general.driver = 'Driver'
    label.general.vendor = 'Vendor'
    label.general.supplier = 'Supplier'
    label.general.shipper = 'Shipper'
    label.general.consignee = 'Consignee'
    label.general.created_by = 'Created By'
    label.general.created_at = 'Created At'
    label.general.destination = 'Destination'
    label.general.vehicle_type = 'Vehicle Type'
    label.general.sales = 'Sales'
    label.general.city = 'City'
    label.general.capacity = 'Capacity'
    label.general.pallet = 'Pallet'
    label.general.vehicle = 'Vehicle'
    label.general.vehicle_type = 'Vehicle Type'
    label.general.volume_capacity = 'Volume Capacity (m3)'
    label.general.weight_capacity = 'Weight Capacity (kg)'
    label.general.total = 'Total'
    label.general.used = 'Used'
    label.general.filter = 'Filter'
    label.general.purpose = 'Purpose'
    label.general.purchase_request = 'Purchase Request'
    label.general.purchase_price = 'Purchase Price'
    label.general.sale_price = 'Sale Price'
    label.general.price_total = 'Price Total'
    label.general.price = 'Price'
    label.general.base_price = 'Base Price'
    label.general.percentage = 'Percentage'
    label.general.item = 'Item'
    label.general.no_data = 'No Data'
    label.general.period = 'Period'
    label.general.contract = 'Contract'
    label.general.files = 'Files'
    label.general.file = 'File'
    label.general.filename = 'File Name'
    label.general.transaction_date = 'Transaction Date'
    label.general.date_end = 'Date End'
    label.general.job_order = 'Job Order'
    label.general.unit = 'Unit'
    label.general.source = 'Source'
    label.general.no_reff = 'No. Reff'
    label.general.weight_total = 'Weight Total (kg)'
    label.general.actual_weight_total = 'Actual Weight Total (kg)'
    label.general.weight_total = 'Weight Total (kg)'
    label.general.volume_total = 'Volume Total (m3)'
    label.general.qty = 'Qty'
    label.general.received_qty = 'Received Qty'
    label.general.onhand_qty = 'Onhand Qty'
    label.general.in_progress_qty = 'In Progress Qty'
    label.general.available_qty = 'Available Qty'
    label.general.stock = 'Stock'
    label.general.sales_order = 'Sales Order'
    label.general.warehouse = 'Warehouse'
    label.general.rack = 'Bin Location'
    label.general.default_rack = 'Default Bin Location'
    label.general.destination_rack = 'Bin Location Destination'
    label.general.detail = 'Detail'
    label.general.info = 'Info'
    label.general.are_you_sure = 'Are you sure ?'
    label.general.luas = 'Luas (m2)'
    label.general.is_floor_stake = 'Is Floor Stake ?'
    label.general.no = 'No'
    label.general.yes = 'Yes'
    label.general.barcode = 'Barcode'
    label.general.max = 'Max'
    label.general.pallet = 'Pallet'
    label.general.total_item = 'Total Item'
    label.general.long = 'Long'
    label.general.wide = 'Wide'
    label.general.high = 'High'
    label.general.weight = 'Weight'
    label.general.package = 'Package'
    label.general.dimension = 'Dimension'
    label.general.receipt_type = 'Receipt Type'
    label.general.draft = 'Draft'
    label.general.kg = 'kg'
    label.general.m3 = 'm3'
    label.general.attachment = 'Attachment'
    label.general.handphone = 'Handphone'
    label.general.ingoing_qty = 'Ingoing Qty'
    label.general.outgoing_qty = 'Outgoing Qty'
    label.general.periode_pengiriman = 'Periode Pengiriman'
    label.general.requested_date = 'Requested Date'
    label.general.approved_date = 'Approved Date'
    label.general.realization_date = 'Realization Date'
    label.general.partial = 'Partial'
    label.general.full = 'Full'
    label.general.clear = 'Clear'
    label.general.no_aju = 'No. AJU'
    label.general.no_bl = 'No. BL'
    label.general.posting = 'Posting'
    label.general.route = 'Route'
    label.general.commodity = 'Commodity'
    label.general.main_commodity = 'Main Commodity'
    label.general.kpi_status = 'KPI Status'
    label.general.updated_by = 'Updated By'
    label.general.minimum = 'Minimum'
    label.general.item_condition = 'Item Condition'
    label.general.default_item_category = 'Default Item Category'
    label.general.default_item = 'Default Item'
    label.general.item_category = 'Item Category'
    label.general.condition = 'Condition'
    label.general.requested_by = 'Requested By'
    label.general.approved_by = 'Approved By'
    label.general.shipment = 'Shipment'
    label.general.subject = 'Subject'
    label.general.body = 'Body'
    label.general.vendor_price = 'Vendor Price'
    label.general.unit_price = 'Unit Price'
    label.general.quotation = 'Quotation'
    label.general.document = 'Document'
    label.general.activity = 'Activity'
    label.general.activity_date = 'Activity Date'
    label.general.activities = 'Activities'
    label.general.shipping_address = 'Shipping address'
    label.general.destination_address = 'Destination address'
    label.general.transported_qty = 'Transported Qty'
    label.general.requested_qty = 'Requested Qty'
    label.general.discharged_qty = 'Discharged Qty'
    label.general.status_log = 'Status Log'
    label.general.proof_of_delivery = 'Proof Of Delivery'

    label.additional_fields = {}
    label.additional_fields.title = 'Additional Fields'
    label.additional_fields.for_feature = 'For Feature'
    label.warehouses = {}
    label.warehouses.title = 'Warehouses'
    label.warehouses.add = label.general.add + ' ' + label.general.warehouse
    label.warehouses.name = label.general.warehouse + ' ' + label.general.name

    label.sales_contract = {}
    label.sales_contract.title = "Sales Contract"

    label.vendor_prices = {}
    label.vendor_prices.title = "Vendor Prices"

    label.inquery = {}
    label.inquery.date = 'Inquery Date'
    label.inquery.code = 'No. Inquery'
    label.inquery.description = 'Inquery Description'

    label.opportunity = {}
    label.opportunity.code = 'No. Opportunity'
    label.opportunity.date = 'Opportunity Date'

    label.quotation = {}
    label.quotation.name = 'Title / Quotation Name'
    label.quotation.code = 'No. Quotation'
    label.quotation.detail = 'Detail Quotation'

    label.lead = {}
    label.lead.title = "Leads"

    label.contract = {}
    label.contract.title = 'Contracts'
    label.contract.new = 'New Contracts ?'
    label.contract.code = 'No Contract'
    label.contract.name = 'Contract Name'
    label.contract.date_end = 'Date End'
    label.contract.amandemen = 'Amandemen Contract'
    label.contract.amandemen_description = 'Amandemen Description'
    label.contract.stop = 'Stop Contract'
    label.contract.date = 'Contract Date'
    label.contract.status = 'Contract Status'

    label.container = {}
    label.container.code = 'No Container'

    label.gate_in_container = {}
    label.gate_in_container.title = 'Gate In Containers'
    label.gate_in_container.code = 'Gate In Container Code'

    label.work_order = {}
    label.work_order.title = 'Work Orders'
    label.work_order.code = 'No. Work Order'
    label.work_order.name = 'Job Name'

    label.item = {}
    label.item.title = 'Items'
    label.item.name = 'Item Name'
    label.item.code = 'Item Code'

    label.service = {}
    label.service.title = 'Services'

    label.vehicle_type = {}
    label.vehicle_type.title = 'Vehicle Types'

    label.cost = {}
    label.cost.title = 'Costs'
    label.cost.name = 'Cost Name'

    label.operational_progress = {}
    label.operational_progress.title = 'Operational Progress'

    label.job_order = {}
    label.job_order.code = 'No. Job Order'
    label.job_order.date = 'Job Order Date'
    label.job_order.history = 'Job Order History'
    label.job_order.title = 'Job Orders'
    label.job_order.qty = 'Qty Item'
    label.job_order.item_name = 'Item'

    label.manifest = {}
    label.manifest.code = 'No. Manifest'
    label.manifest.date = 'Packing List Date'
    label.manifest.eta = 'ETA (Estimated Time Arrival)'
    label.manifest.etd = 'ETD (Estimated Time Departure)'

    label.delivery_order_driver = {}
    label.delivery_order_driver.title = 'Delivery Order Driver'

    label.sales_order = {}
    label.sales_order.title = 'Sales Orders'
    label.sales_order.code = 'Sales Order Code'
    label.sales_order.qty_in_sales = 'Qty in Sales'

    label.claim = {}
    label.claim.title = 'Klaim'
    label.claim.date = 'Claim Date'
    label.claim.code = 'JO/SO Date'
    label.claim.jo_so_date = 'JO/SO Date'
    label.claim.collectible = 'Customer Tagih'

    label.claim_category = {}
    label.claim_category.title = 'Kategori Klaim'
    
    label.customer_order = {}
    label.customer_order.title = 'Customer Orders'
    label.customer_order.code = 'Customer Order Code'
    label.customer_order.no_po_customer = 'No. PO Customer'
    label.customer_order.approve = 'Setujui Permintaan'
    label.customer_order.reject = 'Tolak Permintaan'

    label.warehouse_receipt = {}
    label.warehouse_receipt.title = 'Good Receipt'
    label.warehouse_receipt.code = 'No. BSTB'

    label.moving_item_report = {}
    label.moving_item_report.title = 'Moving Item Report'

    label.stock_opname = {}
    label.stock_opname.title = 'Stock Opname'

    label.incoming_quality_check = {}
    label.incoming_quality_check.title = 'Incoming Quality Check'
    label.incoming_quality_check.status = 'Quality Status'

    label.warehouse = {}
    label.warehouse.luas_lahan = 'Luas Lahan (m2)'
    label.warehouse.luas_gudang = 'Luas Gudang (m2)'
    label.warehouse.luas_bangunan = 'Luas Bangunan (m2)'

    label.stocklist = {}
    label.stocklist.title = 'Stocklist'

    label.purchase_request = {}
    label.purchase_request.title = 'Purchase Request'
    label.purchase_request.code = 'Purchase Request Code'
    label.purchase_request.create_po = 'Create Purchase Order'

    label.purchase_order = {}
    label.purchase_order.title = 'Purchase Orders'
    label.purchase_order.requested_by = 'Requested By'
    label.purchase_order.requested_date = 'Requested Date'
    label.purchase_order.requirement_date = 'Requirement Date'
    label.purchase_order.code = 'Purchase Order Code'
    label.purchase_order.date = 'Purchase Order Date'

    label.purchase_order_return = {}
    label.purchase_order_return.title = 'Purchase Order Returns'

    label.picking_order = {}
    label.picking_order.title = 'Picking Orders'
    label.picking_order.code = 'Picking Order Code'

    label.invoice = {}
    label.invoice.title = 'Invoices'
    label.invoice.code = 'No. Invoice'

    label.initial_inventory = {}
    label.initial_inventory.title = 'Initial Inventory'
    label.initial_inventory.price = 'Price per Item'

    label.voyage_schedule = {}
    label.voyage_schedule.title = 'Voyage Schedule'

    label.sales_order_return = {}
    label.sales_order_return.title = 'Sales Order Return'

    label.item_usage = {}
    label.item_usage.title = 'Item Usage'
    label.item_usage.code = 'No. Item Usage'

    label.pallet_usage = {}
    label.pallet_usage.title = 'Pallet Usage'
    label.pallet_usage.code = 'No. Pallet Usage'

    label.transfer_mutation = {}
    label.transfer_mutation.title = 'Transfer Mutation'
    label.transfer_mutation.code = 'No. Transfer Mutation'

    label.vehicle = {}
    label.vehicle.title = 'Vehicles'

    label.receivable_payment = {}
    label.receivable_payment.title = 'Receivable Payment'

    label.sales_price = {}
    label.sales_price.title = 'Sales Price'

    this.label = label
    this.getModule = getModule
})