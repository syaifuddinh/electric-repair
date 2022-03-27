var operationalClaims = angular.module('operationalClaims', ['ui.router'], () => {})

operationalClaims.config(($stateProvider, $urlRouterProvider) => {
    $stateProvider.state('operational.claims',{url:'/claims',data:{label:'Klaim'},views:{'@':{templateUrl:'core/operational/claims/view/index.html',controller:'operationalClaims'}}})
    $stateProvider.state('operational.claims.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'core/operational/claims/view/create.html',controller:'operationalClaimsCreate'}}})
    $stateProvider.state('operational.claims.show',{url:'/:id',data:{label:'Detail'},views:{'@':{templateUrl:'core/operational/claims/view/show.html',controller:'operationalClaimsShow'}}})
    $stateProvider.state('operational.claims.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'core/operational/claims/view/edit.html',controller:'operationalClaimsEdit'}}})
})

operationalClaims.service('operationalClaimsService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.show = (id) => baseUrl + '/operational/claims/' + id
    url.showDetail = (id) => baseUrl + '/operational/claims/' + id + '/detail'
    url.destroy = (id) => baseUrl + '/operational/claims/' + id
    url.store = () => baseUrl + '/operational/claims'
    url.update = (id) => baseUrl + '/operational/claims/' + id
    url.detailDatatable = () => baseUrl + '/operational/claims/detail/datatable'
    url.datatable = () => baseUrl + '/api/operational/claims_datatable'
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
    
    api.deleteFile = function(id, fn) {
        if(!fn)
            fn = (dt) => {}

        $http.delete(url.deleteFile(id)).then(function(resp) {
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
        $http.put(baseUrl+'/sales/claims/'+id, payload).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp)
        }, function(){
        });
    }
    
    api.approve = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.approve(id)).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp)
        }, function(err){
            toastr.error(err.data.message)
        });
    }

    api.reject = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.reject(id)).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp)
        }, function(err){
            toastr.error(err.data.message)
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

    api.showFile = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showFile(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    api.showDetail = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showDetail(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    api.showDetailInfo = function(id, operational_detail_id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showDetailInfo(id, operational_detail_id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    this.api = api
})