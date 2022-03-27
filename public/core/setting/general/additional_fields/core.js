var additionalFields = angular.module('additionalFields', ['ui.router', 'fieldTypes'], () => {})

additionalFields.config(($stateProvider, $urlRouterProvider) => {
    $stateProvider.state('setting.general.additional_fields',{url:'/setting',data:{label:'Additional Fields'},views:{'':{templateUrl:'core/setting/general/additional_fields/view/index.html',controller:'additionalFields'}}})
})

additionalFields.service('additionalFieldsService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.indexInJobOrderSummary = (slug) => baseUrl + '/setting/additional_field/field/job_order_summary'
    url.indexInOperationalProgress = (slug) => baseUrl + '/setting/additional_field/field/operational_progress'
    url.indexInManifest = () => baseUrl + '/setting/additional_field/field/in_manifest'
    url.indexInIndex = (type_transaction) => baseUrl + '/setting/additional_field/field/in_index/' + type_transaction
    url.indexByTransaction = (slug) => baseUrl + '/setting/additional_field/field/' + slug
    url.store = () => baseUrl + '/setting/additional_field'
    url.show = (id) => baseUrl + '/setting/additional_field/' + id
    url.datatable = () => baseUrl + '/api/setting/additional_field_datatable'
    this.url = url

    api.indexGroup = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/setting/additional_field/group').then(function(resp) {
            var data = resp.data.data;
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
        $http.put(baseUrl + '/setting/additional_field/' + id, payload).then(function(resp) {
            $rootScope.disBtn=false;
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
    
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    api.destroy = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.delete(baseUrl+'/setting/additional_field/' + id).then(function(resp) {
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

    var dom = {}
    dom.get = function(type_transaction, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/setting/additional_field/field/' + type_transaction).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }
    dom.getJobOrderSummaryKey = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/setting/additional_field/field/job_order_summary').then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    dom.getOperationalProgressKey = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/setting/additional_field/field/operational_progress').then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    dom.getInManifestKey = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.indexInManifest()).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    dom.getInIndexKey = function(type_transaction, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.indexInIndex(type_transaction)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    this.dom = dom
})