var racks = angular.module('racks', ['ui.router', 'branchs'], () => {})

racks.service('racksService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.suggestionDescending = () => baseUrl + '/inventory/rack/suggestion/descending' 
    url.index = () => baseUrl + '/inventory/rack'
    url.show = (id) => baseUrl + '/inventory/rack/' + id
    url.update = (id) => baseUrl + '/inventory/rack/' + id
    url.store = () => baseUrl + '/inventory/rack'
    url.destroy = (ids) => baseUrl + '/operational_rack/setting/delete_rack/' + ids
    url.setMap = (id, warehouse_map_id) => baseUrl + '/inventory/rack/' + id + '/map/' + warehouse_map_id
    url.datatable = () => baseUrl + '/api/operational_rack/rack_datatable'
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
        $http.put(url.update(id), payload).then(function(resp) {
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

    api.setMap = function(id, warehouse_map_id, fn, onError) {
        if(!fn)
            fn = (dt) => {}
        if(!onError)
            fn = (dt) => {}
        $http.put(url.setMap(id, warehouse_map_id)).then(function(resp) {
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
            onError()
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

    api.suggestionDescending = function(warehouse_id, items = [], fn) {
        if(!fn)
            fn = (dt) => {}
        var payload = {}
        payload.warehouse_id = warehouse_id
        payload.items = items
        var params = {params : payload}
        $http.get(url.suggestionDescending(), params).then(function(resp) {
            fn(resp.data.data.rack_id)
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