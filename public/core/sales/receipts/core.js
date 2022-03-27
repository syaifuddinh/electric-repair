var receiptSales = angular.module('receiptSales', ['ui.router'], () => {})

receiptSales.config(($stateProvider, $urlRouterProvider) => {
    $stateProvider.state('sales_order.receipt',{url:'/receipt',data:{label:'Sales Order'},views:{'@':{templateUrl:'core/sales/receipts/view/index.html',controller:'receiptSales'}}})
    $stateProvider.state('sales_order.receipt.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'core/sales/receipts/view/create.html',controller:'receiptSalesCreate'}}})
    $stateProvider.state('sales_order.receipt.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'core/sales/receipts/view/show.html',controller:'receiptSalesShow'}}})
    $stateProvider.state('sales_order.receipt.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'core/sales/receipts/view/create.html',controller:'receiptSalesCreate'}}})
})

receiptSales.service('receiptSalesService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.show = (id) => baseUrl + '/sales/sales_order/' + id
    url.showDetail = (id) => baseUrl + '/sales/sales_order/' + id + '/detail'
    url.destroy = (id) => baseUrl + '/sales/sales_order/' + id
    url.store = () => baseUrl + '/sales/sales_order'
    url.datatable = () => baseUrl + '/api/sales/sales_order_datatable'
    this.url = url

    api.indexGroup = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/setting/sales_order/group').then(function(resp) {
            var data = resp.data.data;
            fn(data)
        }, function(){
        });
    }

    api.store = function(payload, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(url.store(), payload).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp)
        }, function(error) {
            $rootScope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

    api.destroy = function(id, fn) {
        if(!fn)
            fn = (dt) => {}

        $http.delete(url.destroy(id)).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp)
        }, function(error) {
            $rootScope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

    api.update = function(payload, id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.put(baseUrl+'/setting/sales_order', payload).then(function(resp) {
            toastr.success(data.data.message)
            fn(data)
        }, function(){
        });
    }

    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    api.showDetail = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showDetail(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    this.api = api
})