var manifests = angular.module('manifests', ['ui.router'], () => {})

manifests.config(($stateProvider, $urlRouterProvider) => {
})

manifests.service('manifestsService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.store = () => baseUrl + '/operational/manifest'
    url.add_item = (id) => baseUrl + '/operational/manifest_ftl/add_item/' + id
    url.datatable = () => baseUrl + '/api/operational/manifest_datatable'
    this.url = url

    api.indexGroup = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/operational/manifest/group').then(function(resp) {
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
        $http.put(baseUrl+'/operational/manifest', payload).then(function(resp) {
            toastr.success(data.data.message)
            fn(data)
        }, function(){
        });
    }
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(baseUrl+'/operational/manifest', payload).then(function(resp) {
            fn(data)
        }, function(){
        });
    }

    this.api = api
})