<li ng-show='settings.general.is_use_sales && roleList.includes("sales")'>
    <a><i class="fa fa-truck"></i> <span class="nav-label">Sales Order</span><span class="fa arrow"></span></a>
    <ul class="nav nav-second-level collapse" ui-sref-active="active">
        <li ng-show="roleList.includes('sales.customer_order')" ui-sref-active="active"><a ui-sref="sales_order.customer_order"><span class="nav-label"><% solog.label.customer_order.title %></span></a></li>

        <li ui-sref-active="active"><a ui-sref="sales_order.sales_order"><span class="nav-label"><% solog.label.sales_order.title %></span></a></li>

        <li ng-show="roleList.includes('sales.shipment')" ui-sref-active="active"><a ui-sref="sales_order.shipment"><span class="nav-label"><% solog.label.general.shipment %></span></a></li>

        <li ng-show="roleList.includes('sales.invoice')" ui-sref-active="active"><a ui-sref="sales_order.invoice"><span class="nav-label"><% solog.label.invoice.title %></span></a></li>

        <li ng-show="roleList.includes('sales.payment')" ui-sref-active="active"><a ui-sref="sales_order.bill_payment"><span class="nav-label">Payment</span></a></li>

        <li ng-show="roleList.includes('sales.purchase_request')" ui-sref-active="active"><a ui-sref="sales_order.purchase_request"><span class="nav-label">Purchase Request</span></a></li>

        <li ng-show="roleList.includes('sales.purchase_order')" ui-sref-active="active"><a ui-sref="sales_order.purchase_order"><span class="nav-label">Purchase Order</span></a></li>

        <li ng-show="roleList.includes('sales.receipt')" ui-sref-active="active"><a ui-sref="sales_order.receipt"><span class="nav-label">Good Receipt</span></a></li>

        <li ng-show="roleList.includes('sales.purchase_order_return')" ui-sref-active="active"><a ui-sref="sales_order.purchase_order_return">
            <span class="nav-label">PO Return</span></a>
        </li>


        <li ng-show="roleList.includes('sales.sales_order_return')" ui-sref-active="active">
            <a ui-sref="sales_order.sales_order_return">
                <span class="nav-label">Sales Order Return</span>
            </a>
        </li>

        <li ng-show="roleList.includes('sales.sales_stock')" ui-sref-active="active">
            <a> <span class="nav-label">Sales Stock</span><span class="fa arrow"></span></a>

            <ul class="nav nav-third-level collapse">

                <li ng-show="roleList.includes('sales.sales_stock.stocklist')" ui-sref-active="active">
                    <a ui-sref="sales_order.stocklist"><span class="nav-label">&nbsp;&nbsp; Stocklist</span></a>
                </li>

                <li ng-show="roleList.includes('sales.sales_stock.stock_by_item')" ui-sref-active="active">
                    <a ui-sref="sales_order.stock_by_item"><span class="nav-label">&nbsp;&nbsp; Stock By Item</span></a>
                </li>

                <li ng-show="roleList.includes('sales.sales_stock.stock_by_warehouse')" ui-sref-active="active">
                    <a ui-sref="sales_order.stock_by_warehouse"><span class="nav-label">&nbsp;&nbsp; Stock By Warehouse</span></a>
                </li>
                
            </ul>
        </li>
    </ul>
</li>