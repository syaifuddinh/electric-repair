var vehicleTypes = angular.module('vehicleTypes', ['ui.router', 'fieldTypes'], () => {})

vehicleTypes.service('vehicleTypesService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.index = () => baseUrl + '/api/v4/setting/vehicle_type'
    url.store = () => baseUrl + '/api/v4/setting/vehicle_type'
    url.update = (id) => baseUrl + '/api/v4/setting/vehicle_type/' + id + '?_method=PUT'
    url.show = (id) => baseUrl + '/api/v4/setting/vehicle_type/' + id + '/edit'
    url.datatable = () => baseUrl + '/api/v4/setting/vehicle_type_datatable'
    this.url = url

    api.index = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.index() ).then(function(resp) {
            var data = resp.data.data;
            fn(data)
        }, function(){
            api.index(fn)
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
        $http.put(baseUrl+'/setting/vehicle_type', payload).then(function(resp) {
            toastr.success(data.data.message)
            fn(data)
        }, function(){
        });
    }
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(baseUrl+'/setting/vehicle_type', payload).then(function(resp) {
            fn(data)
        }, function(){
        });
    }

    this.api = api
})