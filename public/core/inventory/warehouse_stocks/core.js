var warehouseStocks = angular.module('warehouseStocks', ['ui.router', 'branchs'], () => {})

warehouseStocks.service('warehouseStocksService', function($http, $rootScope) {
    var api = {}
    var url = {}
    url.datatable = () => baseUrl + '/api/inventory/warehouse_stock_datatable'
    url.itemDatatable = () => baseUrl + '/api/inventory/stock_by_item_datatable'
    this.url = url

    this.api = api
})