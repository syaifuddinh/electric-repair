<li ng-show="roleList.includes('operational')">
        <a><i class="fa fa-tachometer"></i> <span class="nav-label">Operational</span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level collapse" ui-sref-active="active">
          <!-- <li ui-sref-active="active" ng-show="roleList.includes('operational.work_order')"><a ui-sref="operational.work_order"><span class="nav-label">Work Order</span></a></li> -->
          <li ui-sref-active="active" ng-show="roleList.includes('operational.voyage_schedule')"><a ui-sref="operational.voyage_schedule"><span class="nav-label">Voyage Schedule</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('operational.container')"><a ui-sref="operational.container"><span class="nav-label">Container</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('marketing.work_order')"><a ui-sref="marketing.work_order"><span class="nav-label">Work Order</span> </a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('operational.job_order')"><a ui-sref="operational.job_order"><span class="nav-label">Job Order</span></a></li>
          <!-- <li ui-sref-active="active" ng-show="roleList.includes('operational.job_order_archive')"><a ui-sref="operational.job_order_archive"><span class="nav-label">Job Order Archives</span></a></li> -->
          <li ui-sref-active="active"><a ui-sref="operational.vendor_job"><span class="nav-label">Vendor Job</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('operational.manifest')">
            <a> <span class="nav-label">Packing List</span><span class="fa arrow"></span></a>
            <ul class="nav nav-third-level collapse">
              <li ui-sref-active="active" ng-show="roleList.includes('operational.manifest.vehicle')"><a ui-sref="operational.manifest_ftl"><span class="nav-label">&nbsp;&nbsp; FTL / LTL</span></a></li>
              <li ui-sref-active="active" ng-show="roleList.includes('operational.manifest.container')"><a ui-sref="operational.manifest_fcl"><span class="nav-label">&nbsp;&nbsp; FCL / LCL</span></a></li>
            </ul>
          </li>
          <li ui-sref-active="active" ng-show="roleList.includes('operational.delivery_order')"><a ui-sref="operational.delivery_order_driver"><span class="nav-label">Delivery Order</span></a></li>
          <li ui-sref-active="active"><a ui-sref="operational.shipment_status"><span class="nav-label">Shipment Status</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('operational.invoice_customer')"><a ui-sref="operational.invoice_jual"><span class="nav-label">Invoice</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('operational.claim')">
            <a> <span class="nav-label">Klaim</span><span class="fa arrow"></span></a>
            <ul class="nav nav-third-level collapse">
              <li ui-sref-active="active" ng-show="roleList.includes('operational.claim.claim_categories')"><a ui-sref="operational.claim_categories"><span class="nav-label">&nbsp;&nbsp; Kategori Klaim</span></a></li>
              <li ui-sref-active="active" ng-show="roleList.includes('operational.claim.claim')"><a ui-sref="operational.claims"><span class="nav-label">&nbsp;&nbsp; Klaim</span></a></li>
              <li ui-sref-active="active"><a ui-sref="operational.claim.claim_sale"><span class="nav-label">&nbsp;&nbsp; Penjualan Klaim</span></a></li>
            </ul>
          </li>
          <li ui-sref-active="active" ng-show="roleList.includes('operational.invoice_vendor')"><a ui-sref="operational.invoice_vendor"><span class="nav-label">Vendor Bills</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('operational.progress')"><a ui-sref="operational.progress"><span class="nav-label">Operational Progress</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('marketing.activity_work_order')"><a ui-sref="marketing.activity_work_order"><span class="nav-label">WO Summary</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('marketing.activity_job_order')"><a ui-sref="marketing.activity_job_order"><span class="nav-label">JO Summary</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('operational.report')"><a ui-sref="operational.report"><span class="nav-label">Report</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('operational.print_shipment')"><a ui-sref="operational.print_shipment"><span class="nav-label">Shipping Instruction</span></a></li>
    </ul>
</li>