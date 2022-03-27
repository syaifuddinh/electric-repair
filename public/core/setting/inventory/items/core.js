var items = angular.module('items', ['ui.router', 'fieldTypes'], () => {})

items.service('itemsService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.inWarehouseDatatable = () => baseUrl + '/api/operational_warehouse/item_warehouse_datatable'
    url.indexCategory = () => baseUrl + '/inventory/category'
    url.destroy = (id) => baseUrl + '/inventory/item/' + id
    url.show = (id) => baseUrl + '/inventory/item/' + id
    url.index = () => baseUrl + '/inventory/item'
    url.store = () => baseUrl + '/inventory/item'
    url.datatable = () => baseUrl + '/inventory/item/datatable'
    this.url = url

    api.indexCategory = function(payload = {}, fn) {
        if(!fn)
            fn = (dt) => {}

        var arg = {}
        arg.params = payload
        $http.get( url.indexCategory(), arg ).then(function(resp) {
            var data = resp.data;
            fn(data)
        }, function(){
            api.indexCategory(payload, fn)
        });
    }

    api.index = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.index() ).then(function(resp) {
            var data = resp.data;
            fn(data)
        }, function(){
        });
    }

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
        $http.put(baseUrl+'/setting/company', payload).then(function(resp) {
            toastr.success(data.data.message)
            fn(data)
        }, function(){
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

    api.destroy = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.delete(url.destroy(id)).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp.data)
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

    this.api = api
})