var salesOrders = angular.module('salesOrders', ['ui.router'], () => {})

salesOrders.config(($stateProvider, $urlRouterProvider) => {
    $stateProvider.state('sales_order.sales_order',{url:'/sales_order',data:{label:'Sales Order'},views:{'@':{templateUrl:'core/sales/sales_orders/view/index.html',controller:'salesOrders'}}})
    $stateProvider.state('sales_order.sales_order.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'core/sales/sales_orders/view/create.html',controller:'salesOrdersCreate'}}})
    $stateProvider.state('sales_order.sales_order.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'core/sales/sales_orders/view/show.html',controller:'salesOrdersShow'}}})
    $stateProvider.state('sales_order.sales_order.show.create_shipment',{url:'/create_shipment',data:{label:'Create Shipment'},views:{'@':{templateUrl:'core/sales/sales_orders/view/create_shipment.html',controller:'salesOrdersCreateShipment'}}})
    $stateProvider.state('sales_order.sales_order.show.show_shipment',{url:'/shipment/:id_shipment',data:{label:'Detail Shipment'},views:{'@':{templateUrl:'core/sales/sales_orders/view/show_shipment.html',controller:'salesOrdersShowShipment'}}})
    $stateProvider.state('sales_order.sales_order.show.show_shipment.set_vehicle',{url:'/set_vehicle',data:{label:'Set Kendaraan'},views:{'@':{templateUrl:'view/operational/manifest_ftl/create_pickup.html',controller:'salesOrdersShowShipmentSetVehicle'}}})
})

salesOrders.service('salesOrdersService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.show = (id) => baseUrl + '/sales/sales_order/' + id
    url.showDetail = (id) => baseUrl + '/sales/sales_order/' + id + '/detail'
    url.showDetailInfo = (id, sales_order_detail_id) => baseUrl + '/sales/sales_order/' + id + '/detail/' + sales_order_detail_id
    url.destroy = (id) => baseUrl + '/sales/sales_order/' + id
    url.store = () => baseUrl + '/sales/sales_order'
    url.detailDatatable = () => baseUrl + '/sales/sales_order/detail/datatable'
    url.datatable = () => baseUrl + '/api/sales/sales_order_datatable'
    url.approve = (id) => baseUrl + '/sales/sales_order/' + id + '/approve'
    url.reject = (id) => baseUrl + '/sales/sales_order/' + id + '/reject'
    this.url = url

  
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

    api.showDetailInfo = function(id, sales_order_detail_id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showDetailInfo(id, sales_order_detail_id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }
    
    api.approve = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.approve(id)).then(function(resp) {
            fn(resp.data)
        }, function(){
        });
    }

    api.reject = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.reject(id)).then(function(resp) {
            fn(resp.data)
        }, function(){
        });
    }

    this.api = api
})