var warehouseMaps = angular.module('warehouseMaps', ['ui.router', 'branchs'], () => {})

warehouseMaps.service('warehouseMapsService', function($http, $rootScope) {
    var api = {}
    var url = {}
    url.generateMap = (warehouse_id) => baseUrl + '/inventory/warehouse/' + warehouse_id + '/generate_map'
    url.indexMap = (warehouse_id) => baseUrl + '/inventory/warehouse/' + warehouse_id + '/map'
    url.index = (warehouse_id) => baseUrl + '/inventory/warehouse/' + warehouse_id + '/map_list'
    this.url = url

    api.generateMap = function(payload, warehouse_id, fn) {
        if(!fn)
            fn = (dt) => {}
        $rootScope.disBtn = true
        $http.patch(url.generateMap(warehouse_id), payload).then(function(resp) {
            $rootScope.disBtn = false
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
    api.indexMap = function(warehouse_id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.indexMap(warehouse_id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    api.index = function(warehouse_id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.index(warehouse_id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
            api.index(warehouse_id, fn)
        });
    }

    this.api = api
})