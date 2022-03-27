var warehouses = angular.module('warehouses', ['ui.router', 'branchs'], () => {})

warehouses.service('warehousesService', function($http, $rootScope) {
    var api = {}
    var url = {}
    url.index = () => baseUrl + '/inventory/warehouse'
    url.show = (id) => baseUrl + '/inventory/warehouse/' + id
    url.update = (id = null) => baseUrl + '/operational_warehouse/setting/store_warehouse',
    url.store = () => baseUrl + '/operational_warehouse/setting/store_warehouse'
    url.destroy = (ids) => baseUrl + '/operational_warehouse/setting/delete_warehouse/' + ids
    url.datatable = () => baseUrl + '/api/operational_warehouse/warehouse_datatable'
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

    api.update = function(payload, id, fn) {
        if(!fn)
            fn = (dt) => {}
        payload.id = id
        $http.post(url.update(id), payload).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp)
        }, function(error){
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
            fn(resp.data)
        }, function(){
        });
    }

    api.index = function(payload = {}, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.index(), {params : payload}).then(function(resp) {
            fn(resp.data.data)
        }, function(){
            api.index(payload, fn)
        });
    }

    this.api = api
})