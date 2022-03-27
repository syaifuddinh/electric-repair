var invoices = angular.module('invoices', ['ui.router'], () => {})

invoices.config(($stateProvider, $urlRouterProvider) => {
})

invoices.service('invoicesService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.store = () => baseUrl + '/operational/invoice'
    url.datatable = () => baseUrl + '/api/operational/invoice_jual_datatable'
    this.url = url

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
        $http.put(baseUrl+'/operational/invoice', payload).then(function(resp) {
            toastr.success(data.data.message)
            fn(data)
        }, function(){
        });
    }
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(baseUrl+'/operational/invoice', payload).then(function(resp) {
            fn(data)
        }, function(){
        });
    }

    this.api = api
})