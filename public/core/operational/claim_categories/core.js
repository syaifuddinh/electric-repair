var operationalClaimCategories = angular.module('operationalClaimCategories', ['ui.router'], () => {})

operationalClaimCategories.config(($stateProvider, $urlRouterProvider) => {
    $stateProvider.state('operational.claim_categories',{url:'/claim_categories',data:{label:'Kategori Klaim'},views:{'@':{templateUrl:'core/operational/claim_categories/view/index.html',controller:'operationalClaimCategories'}}})
})

operationalClaimCategories.service('operationalClaimCategoriesService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.show = (id) => baseUrl + '/operational/claim_categories/' + id
    url.destroy = (id) => baseUrl + '/operational/claim_categories/' + id
    url.store = () => baseUrl + '/operational/claim_categories'
    url.update = (id) => baseUrl + '/operational/claim_categories/' + id
    url.datatable = () => baseUrl + '/api/operational/claim_categories_datatable'
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

    api.destroy = function(id, fn) {
        if(!fn)
            fn = (dt) => {}

        $http.delete(url.destroy(id)).then(function(resp) {
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
        $http.put(baseUrl+'/operational/claim_categories/'+id, payload).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp)
        }, function(){
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

    this.api = api
})