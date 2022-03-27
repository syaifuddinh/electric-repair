var containerInspections = angular.module('containerInspections', ['ui.router'], () => {})

containerInspections.config(($stateProvider, $urlRouterProvider) => {
        $stateProvider.state('depo.container_inspection',{url:'/container_inspection',data:{label:'Item Condition'},views:{'@':{templateUrl:'core/depo/container_inspections/view/index.html',controller:'containerInspections'}}})
        $stateProvider.state('depo.container_inspection.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'core/depo/container_inspections/view/create.html',controller:'containerInspectionsCreate'}}})
        $stateProvider.state('depo.container_inspection.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'core/depo/container_inspections/view/create.html',controller:'containerInspectionsCreate'}}})
        $stateProvider.state('depo.container_inspection.show',{url:'/:id',data:{label:'Edit'},views:{'@':{templateUrl:'core/depo/container_inspections/view/show.html',controller:'containerInspectionsShow'}}})

})

containerInspections.service('containerInspectionsService', function($http, $rootScope, $filter) {
    var api = {}
    var url = {}

    url.store = () => baseUrl + '/depo/container_inspection'
    url.update = (id) => baseUrl + '/depo/container_inspection/' + id
    url.show = (id) => baseUrl + '/depo/container_inspection/' + id
    url.showDetail = (id) => baseUrl + '/depo/container_inspection/' + id + '/detail'
    url.destroy = (id) => baseUrl + '/depo/container_inspection/' + id
    url.datatable = () => baseUrl + '/api/depo/container_inspection_datatable'
    this.url = url


    api.store = function(payload, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(url.store(), payload).then(function(resp) {
            $rootScope.disBtn=false;
            
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
            if(resp.data.data.date) {
                resp.data.data.date = $filter('minDate')(resp.data.data.date)
            }
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