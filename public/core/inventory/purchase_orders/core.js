var purchaseOrders = angular.module('purchaseOrders', ['ui.router', 'branchs'], () => {})

purchaseOrders.service('purchaseOrdersService', function($http, $rootScope) {
    var api = {}
    var url = {}
    url.index = () => baseUrl + '/inventory/purchase_order'
    url.indexStatus = () => baseUrl + '/inventory/purchase_order/status'
    url.showDetail = (id) => baseUrl + '/inventory/purchase_order/' + id
    url.show = (id) => baseUrl + '/inventory/purchase_order/' + id
    url.approve = (id) => baseUrl + '/inventory/purchase_order/' + id + '/approve',
    url.store = () => baseUrl + '/inventory/purchase_order'
    url.update = (id) => baseUrl + '/inventory/purchase_order/' + id,
    url.destroy = (id) => baseUrl + '/inventory/purchase_order/' + id,
    url.datatable = () => baseUrl + '/api/inventory/purchase_order_datatable'
    url.palletDatatable = () => baseUrl + '/api/inventory/pallet_purchase_order_datatable'
    this.url = url

    api.store = function(payload, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(url.store(), payload).then(function(resp) {
            $rootScope.disBtn=false;
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

    api.approve = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.put(url.approve(id)).then(function(resp) {
            $rootScope.disBtn=false;
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
            $rootScope.disBtn=false;
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
        $rootScope.disBtn=true;
        $http.put(url.update(id), payload).then(function(resp) {
            $rootScope.disBtn=false;
            toastr.success(resp.data.message)
            fn(resp)
        }, function(){
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

    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data.item)
        }, function(){
        });
    }
    
    api.indexStatus = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.indexStatus()).then( (resp) => {
            fn(resp.data.data)
        }, () => {

        });
    }

    api.showDetail = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showDetail(id)).then(function(resp) {
            fn(resp.data.detail)
        }, function(){
        });
    }

    api.index = function(payload = {}, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.index()).then(function(resp) {
            fn(resp.data.data)
        }, function(){
            api.index(fn)
        });
    }

    this.api = api
})