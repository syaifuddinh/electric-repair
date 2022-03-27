<a><i class="fa fa-money"></i> <span class="nav-label">Finance & Accounting</span><span class="fa arrow"></span></a>
<ul class="nav nav-second-level collapse" ui-sref-active="active">
<li ui-sref-active="active" ng-show="roleList.includes('finance.asset')">
<a> <span class="nav-label">Fixed Asset</span><span class="fa arrow"></span></a>
<ul class="nav nav-third-level collapse">
<li ui-sref-active="active" ng-show="roleList.includes('finance.asset.group')"><a ui-sref="finance.kelompok_asset"><span class="nav-label">&nbsp;&nbsp; Asset Category</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.asset.first_saldo_asset')"><a ui-sref="finance.saldoawal_asset"><span class="nav-label">&nbsp;&nbsp; Asset Opening Balance</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.asset.purchase')"><a ui-sref="finance.pembelian_asset"><span class="nav-label">&nbsp;&nbsp; Asset Purchases</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.asset.list_asset')"><a ui-sref="finance.daftar_asset"><span class="nav-label">&nbsp;&nbsp; All Assets</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.asset.depreciation')"><a ui-sref="finance.depresiasi_asset"><span class="nav-label">&nbsp;&nbsp; Asset Depreciation</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.asset.rejected')"><a ui-sref="finance.pengafkiran_asset"><span class="nav-label">&nbsp;&nbsp; Asset Write-Off</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.asset.sell')"><a ui-sref="finance.penjualan_asset"><span class="nav-label">&nbsp;&nbsp; Asset Sale</span></a></li>
</ul>
</li>
<li ui-sref-active="active"><a ui-sref="finance.pajak"><span class="nav-label">Tax Invoice / Faktur</span></a></li>
{{-- Date: 06-03-2020; Description: Menambah menu baru; Developer: rizal; Status: Edit --}}
<li ui-sref-active="active" ng-show="roleList.includes('finance.deposite')">
    <a> <span class="nav-label">Deposit / DP</span><span class="fa arrow"></span></a>
    <ul class="nav nav-third-level collapse">
        <li ui-sref-active="active" ng-show="roleList.includes('finance.deposite.supplier')"><a ui-sref="finance.um_supplier"><span class="nav-label">&nbsp;&nbsp; Deposit Vendor</span></a></li>
        <li ui-sref-active="active" ng-show="roleList.includes('finance.deposite.customer')"><a ui-sref="finance.um_customer"><span class="nav-label">&nbsp;&nbsp; Deposit Customer</span></a></li>
    </ul>
</li>

<li ui-sref-active="active" ng-show="roleList.includes('finance.credit')">
    <a> <span class="nav-label">Payables</span><span class="fa arrow"></span></a>
    <ul class="nav nav-third-level collapse">
        <li ui-sref-active="active" ng-show="roleList.includes('finance.credit.draft_list_hutang')"><a ui-sref="finance.draft_list_hutang"><span class="nav-label">&nbsp;&nbsp; All Payables</span></a></li>
        <li ui-sref-active="active" ng-show="roleList.includes('finance.credit.draft')"><a ui-sref="finance.debt_payable"><span class="nav-label">&nbsp;&nbsp; Payable Payment</span></a></li>
        <!-- <li ui-sref-active="active" ng-show="roleList.includes('finance.credit.payment')"><a ui-sref="finance.debt_payment"><span class="nav-label">&nbsp;&nbsp; Pelunasan Hutang</span></a></li> -->
    </ul>
</li>

<li ui-sref-active="active" ng-show="roleList.includes('finance.debt')">
<a> <span class="nav-label">Receivables</span><span class="fa arrow"></span></a>
<ul class="nav nav-third-level collapse">
<li ui-sref-active="active" ng-show="roleList.includes('finance.credit.draft_list_piutang')"><a ui-sref="finance.draft_list_piutang"><span class="nav-label">&nbsp;&nbsp; All Receivables</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.debt.draft')"><a ui-sref="finance.bill_receivable"><span class="nav-label">&nbsp;&nbsp; Receivable Payments</span></a></li>
</ul>
</li>
<!--
<li ui-sref-active="active" ng-show="roleList.includes('finance.noted')">
<a> <span class="nav-label">Nota Potong</span><span class="fa arrow"></span></a>
<ul class="nav nav-third-level collapse">
<li ui-sref-active="active" ng-show="roleList.includes('finance.noted.sell')"><a ui-sref="finance.nota_credit"><span class="nav-label">&nbsp;&nbsp; Nota Potong Penjualan</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.noted.purchase')"><a ui-sref="finance.nota_debet"><span class="nav-label">&nbsp;&nbsp; Nota Potong Pembelian</span></a></li>
</ul>
</li>
-->
<li ui-sref-active="active" ng-show="roleList.includes('finance.giro')"><a ui-sref="finance.cek_giro"><span class="nav-label">Cheque</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.mutasi_kas')">
<a><span class="nav-label">Cash Transfer / Mutation</span><span class="fa arrow"></span></a>
<ul class="nav nav-third-level collapse">
<li ui-sref-active="active" ng-show="roleList.includes('finance.mutasi_kas.request')"><a ui-sref="finance.permintaan_mutasi"><span class="nav-label">&nbsp;&nbsp; Mutation Request</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.mutasi_kas.realisasi')"><a ui-sref="finance.realisasi_mutasi"><span class="nav-label">&nbsp;&nbsp; Realization</span></a></li>
</ul>
</li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.transaction_cash')"><a ui-sref="finance.cash_transaction"><span class="nav-label">Cash/Bank Transactions </span></a></li>
<!-- <li ui-sref-active="active" ng-show="roleList.includes('finance.submission_cost')"><a ui-sref="finance.submission_cost"><span class="nav-label">Pengajuan Biaya</span></a></li> -->
<li ui-sref-active="active" ng-show="roleList.includes('finance.cash_bon')"><a ui-sref="finance.kas_bon"><span class="nav-label">Cash Advances</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.cash_count')"><a ui-sref="finance.cash_count"><span class="nav-label">Cash Count</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.journal')"><a ui-sref="finance.journal"><span class="nav-label">Journals</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.report')"><a ui-sref="finance.report"><span class="nav-label">Report</span></a></li>
<li ui-sref-active="active" ng-show="roleList.includes('finance.closing')"><a ui-sref="finance.closing"><span class="nav-label">Closing</span></a></li>
</ul>
