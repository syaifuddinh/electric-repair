var deliveryOrders = angular.module('deliveryOrders', ['ui.router'], () => {})

deliveryOrders.config(($stateProvider, $urlRouterProvider) => {
})

deliveryOrders.service('deliveryOrdersService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.store = () => baseUrl + '/operational/delivery_order'
    url.add_item = (id) => baseUrl + '/operational/delivery_order_ftl/add_item/' + id
    url.datatable = () => baseUrl + '/api/operational/delivery_order_datatable'
    this.url = url

    api.indexGroup = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/operational/delivery_order/group').then(function(resp) {
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
    api.add_item = function(payload, id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(url.add_item(id), payload).then(function(resp) {
            console.log(payload)
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
        $http.put(baseUrl+'/operational/delivery_order', payload).then(function(resp) {
            toastr.success(data.data.message)
            fn(data)
        }, function(){
        });
    }
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(baseUrl+'/operational/delivery_order', payload).then(function(resp) {
            fn(data)
        }, function(){
        });
    }

    this.api = api
})