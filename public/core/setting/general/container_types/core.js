var containerTypes = angular.module('containerTypes', ['ui.router', 'fieldTypes'], () => {})

containerTypes.service('containerTypesService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.index = () => baseUrl + '/setting/container_type'
    url.store = () => baseUrl + '/setting/container_type'
    url.update = (id) => baseUrl + '/setting/container_type/' + id
    url.delete = (id) => baseUrl + '/setting/container_type/' + id
    url.datatable = () => baseUrl + '/api/setting/container_type_datatable'
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
        $http.put(url.update(id), payload).then(function(resp) {
            toastr.success(data.data.message)
            fn(data)
        }, function(){
        });
    }
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(url.show(id), payload).then(function(resp) {
            fn(data)
        }, function(){
        });
    }

    api.delete = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.delete(url.delete(id)).then(function(resp) {
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