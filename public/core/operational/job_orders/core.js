var jobOrders = angular.module('jobOrders', ['ui.router'], () => {})

jobOrders.config(($stateProvider, $urlRouterProvider) => {
})

jobOrders.service('jobOrdersService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.downloadImportItem = () => baseUrl + '/operational/job_order/download_import_item'
    url.importItemWarehouse = () => baseUrl + '/operational/job_order/import_item_warehouse'
    url.store = () => baseUrl + '/operational/job_order'
    url.showCost = (id) => baseUrl + '/operational/job_order/' + id + '/cost'
    url.datatable = () => baseUrl + '/api/operational/job_order_datatable'
    this.url = url

    api.importItemWarehouse = function(payload, fn) {
        if(!fn)
            fn = (dt) => {}
        $rootScope.disBtn = true
        $.ajax({
            url: url.importItemWarehouse() + '?_token=' + csrfToken,
            contentType: false,
            processData: false,
            type: 'POST',
            data: payload,
            beforeSend: function (request) {
                request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
            },
            success: function (resp) {
                $rootScope.disBtn = false;
                toastr.success(resp.message)
                fn(resp.data)
            },
            error: function (resp) {
                toastr.error(resp.responseJSON.message, "Error Has Found !");
            }
        });
    }
    
    api.indexGroup = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/operational/job_order/group').then(function(resp) {
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
        $http.put(baseUrl+'/operational/job_order', payload).then(function(resp) {
            toastr.success(resp.data.message)
            fn(data)
        }, function(){
        });
    }
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(baseUrl+'/operational/job_order', payload).then(function(resp) {
            fn(data)
        }, function(){
        });
    }

    api.showCost = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showCost(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    this.api = api
})