var costTypes = angular.module('costTypes', ['ui.router', 'fieldTypes'], () => {})

costTypes.service('costTypesService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.index = () => baseUrl + '/setting/cost_type'
    url.show = (id) => baseUrl + '/setting/cost_type/' + id
    url.store = () => baseUrl + '/setting/cost_type'
    url.datatable = () => baseUrl + '/api/setting/cost_type_datatable'
    this.url = url

    api.index = function(payload = {}, fn) {
        if(!fn)
            fn = (dt) => {}
        var params = {params : payload}
        $http.get( url.index(), params ).then(function(resp) {
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
            fn(resp.data.item)
        }, function(){
        });
    }

    this.api = api
})