var customerOrders = angular.module('customerOrders', ['ui.router'], () => {})

customerOrders.config(($stateProvider, $urlRouterProvider) => {
    $stateProvider.state('sales_order.customer_order',{url:'/customer_order',data:{label:'Customer Order'},views:{'@':{templateUrl:'core/sales/customer_orders/view/index.html',controller:'customerOrders'}}})
    $stateProvider.state('sales_order.customer_order.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'core/sales/customer_orders/view/create.html',controller:'customerOrdersCreate'}}})
    $stateProvider.state('sales_order.customer_order.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'core/sales/customer_orders/view/show.html',controller:'customerOrdersShow'}}})
})

customerOrders.service('customerOrdersService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.show = (id) => baseUrl + '/sales/customer_order/' + id
    url.showDetail = (id) => baseUrl + '/sales/customer_order/' + id + '/detail'
    url.showDetailInfo = (id, customer_order_detail_id) => baseUrl + '/sales/customer_order/' + id + '/detail/' + customer_order_detail_id
    url.showFile = (id) => baseUrl + '/sales/customer_order/' + id + '/file'
    url.deleteFile = (id) => baseUrl + '/sales/customer_order/' + id + '/file'
    url.destroy = (id) => baseUrl + '/sales/customer_order/' + id
    url.approve = (id) => baseUrl + '/sales/customer_order/' + id + '/approve'
    url.reject = (id) => baseUrl + '/sales/customer_order/' + id + '/reject'
    url.store = () => baseUrl + '/sales/customer_order'
    url.detailDatatable = () => baseUrl + '/sales/customer_order/detail/datatable'
    url.datatable = () => baseUrl + '/api/sales/customer_order_datatable'
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
    
    api.deleteFile = function(id, fn) {
        if(!fn)
            fn = (dt) => {}

        $http.delete(url.deleteFile(id)).then(function(resp) {
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
        $http.put(baseUrl+'/sales/customer_order/'+id, payload).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp)
        }, function(){
        });
    }
    
    api.approve = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.approve(id)).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp)
        }, function(err){
            toastr.error(err.data.message)
        });
    }

    api.reject = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.reject(id)).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp)
        }, function(err){
            toastr.error(err.data.message)
        });
    }

    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
            api.show(id, fn)
        });
    }

    api.showFile = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showFile(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
            api.showFile(id, fn)
        });
    }

    api.showDetail = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showDetail(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
            api.showDetail(id, fn)
        });
    }

    api.showDetailInfo = function(id, sales_order_detail_id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showDetailInfo(id, sales_order_detail_id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
            api.showDetailInfo(id, sales_order_detail_id, fn)
        });
    }

    this.api = api
})