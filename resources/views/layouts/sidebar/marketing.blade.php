<li ng-show="roleList.includes('marketing')">
    <a><i class="fa fa-briefcase"></i> <span class="nav-label">Marketing</span><span class="fa arrow"></span></a>

    <ul class="nav nav-second-level collapse" ui-sref-active="active">
        <li ng-show='settings.general.is_use_sales' ui-sref-active="active">
            <a> <span class="nav-label">Item Price</span><span class="fa arrow"></span></a>
            <ul class="nav nav-third-level collapse">
                <li ui-sref-active="active"><a ui-sref="marketing.sales_price"><span class="nav-label">&nbsp;&nbsp; Price Lists</span></a></li>
                <li ui-sref-active="active"><a ui-sref="marketing.sales_contract"><span class="nav-label">&nbsp;&nbsp; Contract Price</span></a></li>
            </ul>
        </li>

        <li ui-sref-active="active" ng-show="roleList.includes('marketing.price')">
            <a> <span class="nav-label">Logistics Price</span><span class="fa arrow"></span></a>
            <ul class="nav nav-third-level collapse">
                <li ui-sref-active="active" ng-show="roleList.includes('marketing.price.price_list')"><a ui-sref="marketing.price_list"><span class="nav-label">&nbsp;&nbsp; Price Lists</span></a></li>
                <li ui-sref-active="active" ng-show="roleList.includes('marketing.price.contract_price')"><a ui-sref="marketing.contract_price"><span class="nav-label">&nbsp;&nbsp; Contract Price</span></a></li>
                <li ui-sref-active="active" ng-show="roleList.includes('marketing.price.vendor_price')"><a ui-sref="marketing.vendor_price"><span class="nav-label">&nbsp;&nbsp; Vendor Price</span></a></li>
            </ul>
        </li>

          <li ui-sref-active="active" ng-show="roleList.includes('marketing.leads')"><a ui-sref="marketing.lead"><span class="nav-label">Leads</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('marketing.opportunity')"><a ui-sref="marketing.opportunity"><span class="nav-label">Opportunity</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('marketing.Inquery')"><a ui-sref="marketing.inquery_qt"><span class="nav-label">Inquiry</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('marketing.quotation')"><a ui-sref="marketing.inquery"><span class="nav-label">Quotation</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('marketing.contract')"><a ui-sref="marketing.contract"><span class="nav-label">Contracts</span></a></li>
          <li ui-sref-active="active" ng-show="roleList.includes('marketing.report')"><a ui-sref="marketing.report"><span class="nav-label">Report</span></a></li>
          <li ui-sref-active="active"><a ui-sref="marketing.inquery_customer"><span class="nav-label">Customer Inquiry</span></a></li>
        </ul>
</li>