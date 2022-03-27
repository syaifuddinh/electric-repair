<li ui-sref-active="active" ng-show="roleList.includes('inventory.pallet')">
    <a> <span class="nav-label">Pallet</span><span class="fa arrow"></span></a>
    <ul class="nav nav-third-level collapse">
        <li ng-show="roleList.includes('inventory.pallet.master')" ui-sref-active="active" ><a ui-sref="operational_warehouse.master_pallet"><span class="nav-label">&nbsp;&nbsp; Pallet Master</span></a></li>
        <li ng-show="roleList.includes('inventory.pallet.stock')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_stock"><span class="nav-label">&nbsp;&nbsp; Stock Pallet</span></a></li>
        <li ng-show="roleList.includes('inventory.pallet.purchase_request')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_purchase_request"><span class="nav-label">&nbsp;&nbsp; Purchase Request</span></a></li>

        <li ng-show="roleList.includes('inventory.pallet.purchase_order')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_purchase_order"><span class="nav-label">&nbsp;&nbsp; Purchase Order</span></a></li>

        <li ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet.receipt"><span class="nav-label">&nbsp;&nbsp; Good Receipt</span></a></li>
        
        <li ng-show="roleList.includes('inventory.pallet.usage')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_using"><span class="nav-label">&nbsp;&nbsp; Pallet Usages</span></a></li>
        <li ng-show="roleList.includes('inventory.pallet.po_return')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_po_return"><span class="nav-label">&nbsp;&nbsp; Purchase Order Return</span></a></li>
        <li ng-show="roleList.includes('inventory.pallet.sales_order')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_sales_order"><span class="nav-label">&nbsp;&nbsp; Sales Order</span></a></li>
        <li ng-show="roleList.includes('inventory.pallet.sales_order_return')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_sales_order_return"><span class="nav-label">&nbsp;&nbsp; Sales Order Return</span></a></li>
        <li ng-show="roleList.includes('inventory.pallet.migration')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_migration"><span class="nav-label">&nbsp;&nbsp; Migration</span></a></li>
        <li ng-show="roleList.includes('inventory.pallet.deletion')" ui-sref-active="active" ><a ui-sref="operational_warehouse.pallet_deletion"><span class="nav-label">&nbsp;&nbsp; Deletion</span></a></li>
    </ul>
</li>