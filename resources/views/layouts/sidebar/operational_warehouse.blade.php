
<a><i class="fa fa-industry"></i> <span class="nav-label">Warehouse</span><span class="fa arrow"></span></a>
<ul class="nav nav-second-level collapse" ui-sref-active="active">
    <!-- <li ui-sref-active="active" ng-show="roleList.includes('operational_warehouse.setting')"><a ui-sref="operational_warehouse.setting"><span class="nav-label">Setting</span></a></li>
    <li ui-sref-active="active" ng-show="roleList.includes('operational_warehouse.receive')"><a ui-sref="operational_warehouse.receipt"><span class="nav-label">Good Receipt</span></a></li> -->
    <!-- <li ui-sref-active="active"><a ui-sref="operational_warehouse.packaging"><span class="nav-label">Packaging</span></a></li>

    <li>
        <a> <span class="nav-label">Stock</span><span class="fa arrow"></span></a>
        <ul class="nav nav-third-level collapse" ui-sref-active="active">
            <li ui-sref-active="active" ng-show="roleList.includes('operational_warehouse.stocklist')"><a ui-sref="operational_warehouse.stocklist"><span class="nav-label">Stock List</span></a></li>

            <li ui-sref-active="active" ng-show="roleList.includes('operational_warehouse.stocklist')"><a ui-sref="operational_warehouse.stok_opname"><span class="nav-label">Stock Opname</span></a></li>


            <li ui-sref-active="active" ng-show="roleList.includes('operational_warehouse.receipt_report')"><a ui-sref="operational_warehouse.receipt_report"><span class="nav-label">Moving Item Report</span></a></li>
        </ul>
    </li> -->


    

    <li ui-sref-active="active" ng-show="roleList.includes('operational_warehouse.pallet')">
        <a> <span class="nav-label">Pallet</span><span class="fa arrow"></span></a>
        <ul class="nav nav-third-level collapse">
            <li ng-show="roleList.includes('operational_warehouse.pallet.master')" ui-sref-active="active" ><a ui-sref="operational_warehouse.master_pallet"><span class="nav-label">&nbsp;&nbsp; Pallet Master</span></a></li>
            <li ng-show="roleList.includes('operational_warehouse.pallet.stock')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_stock"><span class="nav-label">&nbsp;&nbsp; Stock Pallet</span></a></li>
            <li ng-show="roleList.includes('operational_warehouse.pallet.purchase_request')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_purchase_request"><span class="nav-label">&nbsp;&nbsp; Purchase Request</span></a></li>
            <li ng-show="roleList.includes('operational_warehouse.pallet.purchase_order')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_purchase_order"><span class="nav-label">&nbsp;&nbsp; Purchase Order</span></a></li>
            <li ng-show="roleList.includes('operational_warehouse.pallet.receipt')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_receipt"><span class="nav-label">&nbsp;&nbsp; Receive</span></a></li>
            <li ng-show="roleList.includes('operational_warehouse.pallet.using')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_using"><span class="nav-label">&nbsp;&nbsp; Pallet Usages</span></a></li>
            <li ng-show="roleList.includes('operational_warehouse.pallet.po_return')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_po_return"><span class="nav-label">&nbsp;&nbsp; Purchase Order Return</span></a></li>
            <li ng-show="roleList.includes('operational_warehouse.pallet.sales_order')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_sales_order"><span class="nav-label">&nbsp;&nbsp; Sales Order</span></a></li>
            <li ng-show="roleList.includes('operational_warehouse.pallet.sales_order_return')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_sales_order_return"><span class="nav-label">&nbsp;&nbsp; Sales Order Return</span></a></li>
            <li ng-show="roleList.includes('operational_warehouse.pallet.migration')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_migration"><span class="nav-label">&nbsp;&nbsp; Migration</span></a></li>
            <li ng-show="roleList.includes('operational_warehouse.pallet.deletion')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_deletion"><span class="nav-label">&nbsp;&nbsp; Deletion</span></a></li>
        </ul>
    </li>

</ul>

