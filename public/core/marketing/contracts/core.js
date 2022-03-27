var contracts = angular.module('contracts', ['ui.router'], () => {})

contracts.config(($stateProvider, $urlRouterProvider) => {
})

contracts.service('contractsService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.store = () => baseUrl + '/marketing/contract'
    url.show = (id) => baseUrl + '/marketing/contract/' + id
    url.datatable = () => baseUrl + '/api/marketing/contract_datatable'
    this.url = url

    api.indexGroup = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/setting/contract/group').then(function(resp) {
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

    api.update = function(payload, id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.put(baseUrl+'/setting/contract', payload).then(function(resp) {
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

    this.api = api
})