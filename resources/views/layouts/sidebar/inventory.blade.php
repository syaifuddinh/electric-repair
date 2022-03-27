<li ng-show="roleList.includes('inventory')">
    <a><i class="fa fa-cubes"></i> <span class="nav-label">Inventory</span><span class="fa arrow"></span></a>
    <ul class="nav nav-second-level collapse" ui-sref-active="active">
        <!-- <li ui-sref-active="active"><a ui-sref="inventory.dashboard"><span class="nav-label">Dashboard</span></a></li> -->
        <li ui-sref-active="active"><a ui-sref="operational_warehouse.setting"><span class="nav-label">Setting</span></a></li>
        <li ui-sref-active="active" ng-show="roleList.includes('inventory.category')"><a ui-sref="inventory.category"><span class="nav-label">Item Category</span></a></li>
        <li ui-sref-active="active" ng-show="roleList.includes('inventory.item')"><a ui-sref="inventory.item"><span class="nav-label">Item Master</span></a></li>
        <li ui-sref-active="active" ng-show="roleList.includes('inventory.first_stock')"><a ui-sref="inventory.stock_initial"><span class="nav-label">Initial Inventory</span></a></li>
        <li ui-sref-active="active" ng-show="roleList.includes('inventory.purchase_request')"><a ui-sref="inventory.purchase_request"><span class="nav-label">Purchase Request</span></a></li>
        <li ui-sref-active="active" ng-show="roleList.includes('inventory.purchase_order')"><a ui-sref="inventory.purchase_order"><span class="nav-label">Purchase Order</span></a></li>

        <li ui-sref-active="active" ng-show="roleList.includes('inventory.retur')"><a ui-sref="inventory.retur"><span class="nav-label">Purchase Order Returns</span></a></li>

        <!-- <li ui-sref-active="active" ng-show="roleList.includes('inventory.receipt')"><a ui-sref="inventory.receipt"><span class="nav-label">Inventory Receipt</span></a></li> -->
        <li ui-sref-active="active" ng-show="roleList.includes('inventory.receipt')"><a ui-sref="operational_warehouse.receipt"><span class="nav-label">Good Receipt</span></a></li>
        <li ui-sref-active="active"><a ui-sref="inventory.quality_check"><span class="nav-label">Incoming Quality Check</span></a></li>

        <li ui-sref-active="active" ng-show="roleList.includes('inventory.putaway')"><a ui-sref="operational_warehouse.putaway"><span class="nav-label">Put Away</span></a></li>
        <li ui-sref-active="active" ng-show="roleList.includes('inventory.transfer')"><a ui-sref="operational_warehouse.mutasi_transfer"><span class="nav-label">Transfer Mutations</span></a></li>


        <li ui-sref-active="active" ><a ui-sref="operational_warehouse.picking"><span class="nav-label">Picking</span></a></li>

        <!-- <li ui-sref-active="active"><a ui-sref="inventory.picking_order"><span class="nav-label">Picking Order</span></a></li> -->
        <li ui-sref-active="active" ng-show="roleList.includes('inventory.good_issue')"><a ui-sref="inventory.using_item"><span class="nav-label">Item Usages</span></a></li>
        <!-- <li ui-sref-active="active" ng-show="roleList.includes('inventory.adjustment')"><a ui-sref="inventory.adjustment"><span class="nav-label">Stock Adjustment</span></a></li> -->

        <li ui-sref-active="active"><a ui-sref="operational_warehouse.packaging"><span class="nav-label">Packaging / Dispatch</span></a></li>

        <li>
        <a> <span class="nav-label">Stock</span><span class="fa arrow"></span></a>
        <ul class="nav nav-third-level collapse" ui-sref-active="active">
            <li ui-sref-active="active" ng-show="roleList.includes('inventory.stock.stocklist')"><a ui-sref="operational_warehouse.stocklist"><span class="nav-label">Stock List</span></a></li>

            <li ui-sref-active="active" ng-show="roleList.includes('inventory.stock.stock_by_warehouse')"><a ui-sref="inventory.warehouse_stock"><span class="nav-label">Stock By Warehouse</span></a></li>

            <li ui-sref-active="active" ng-show="roleList.includes('inventory.stock.stock_by_item')">
                <a ui-sref="inventory.stock_by_item"><span class="nav-label">Stock By Item</span></a>
            </li>

            <li ui-sref-active="active" ng-show="roleList.includes('inventory.stock.opname')"><a ui-sref="operational_warehouse.stok_opname"><span class="nav-label">Stock Opname</span></a></li>


            <li ui-sref-active="active" ng-show="roleList.includes('inventory.receipt_report')"><a ui-sref="operational_warehouse.receipt_report"><span class="nav-label">Moving Item Report</span></a></li>
        </ul>
        </li>

        @include('layouts.sidebar.pallet')
        <!-- <li ui-sref-active="active"><a ui-sref="inventory.mutasi_transfer"><span class="nav-label">Item Transfer</span></a></li> -->
        <!-- <li ui-sref-active="active" ng-show="roleList.includes('inventory.stock_card')"><a ui-sref="inventory.stock_transaction"><span class="nav-label">Stock Card</span></a></li> -->
        
        <li ui-sref-active="active" ng-show="roleList.includes('inventory.report')"><a ui-sref="inventory.report"><span class="nav-label">Report</span></a></li>
    </ul>
</li>