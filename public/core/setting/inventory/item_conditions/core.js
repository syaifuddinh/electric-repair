var itemConditions = angular.module('itemConditions', ['ui.router', 'fieldTypes'], () => {})

itemConditions.config(($stateProvider, $urlRouterProvider) => {
    $stateProvider.state('inventory.item_condition',{url:'/item_condition',data:{label:'Item Condition'},views:{'@':{templateUrl:'core/setting/inventory/item_conditions/view/index.html',controller:'itemConditions'}}})
    $stateProvider.state('inventory.item_condition.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'core/setting/inventory/item_conditions/view/create.html',controller:'itemConditionsCreate'}}})
    $stateProvider.state('inventory.item_condition.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'core/setting/inventory/item_conditions/view/create.html',controller:'itemConditionsCreate'}}})
})

itemConditions.service('itemConditionsService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.index = () => baseUrl + '/inventory/item_condition'
    url.store = () => baseUrl + '/inventory/item_condition'
    url.update = (id) => baseUrl + '/inventory/item_condition/' + id
    url.destroy = (id) => baseUrl + '/inventory/item_condition/' + id
    url.show = (id) => baseUrl + '/inventory/item_condition/' + id
    url.datatable = () => baseUrl + '/api/inventory/item_condition_datatable'
    this.url = url

    api.index = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.index() ).then(function(resp) {
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
        $http.put(url.update(id), payload).then(function(resp) {
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
        $http.delete(url.destroy(id)).then(function(resp) {
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