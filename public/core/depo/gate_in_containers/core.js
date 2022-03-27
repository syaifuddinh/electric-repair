var gateInContainers = angular.module('gateInContainers', ['ui.router'], () => {})

gateInContainers.config(($stateProvider, $urlRouterProvider) => {
    $stateProvider.state('depo.gate_in_container',{url:'/gate_in_container',data:{label:'Gate In Container'},views:{'@':{templateUrl:'core/depo/gate_in_containers/view/index.html',controller:'gateInContainers'}}})
    $stateProvider.state('depo.gate_in_container.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'core/depo/gate_in_containers/view/create.html',controller:'gateInContainersCreate'}}})
    $stateProvider.state('depo.gate_in_container.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'core/depo/gate_in_containers/view/create.html',controller:'gateInContainersCreate'}}})
    $stateProvider.state('depo.gate_in_container.show',{url:'/:id',data:{label:'Edit'},views:{'@':{templateUrl:'core/depo/gate_in_containers/view/show.html',controller:'gateInContainersShow'}}})
})

gateInContainers.service('gateInContainersService', function($http, $rootScope, $filter) {
    var api = {}
    var url = {}

    url.store = () => baseUrl + '/depo/gate_in_container'
    url.update = (id) => baseUrl + '/depo/gate_in_container/' + id
    url.show = (id) => baseUrl + '/depo/gate_in_container/' + id
    url.approve = (id) => baseUrl + '/depo/gate_in_container/' + id + '/approve'
    url.showDetail = (id) => baseUrl + '/depo/gate_in_container/' + id + '/detail'
    url.destroy = (id) => baseUrl + '/depo/gate_in_container/' + id
    url.datatable = () => baseUrl + '/api/depo/gate_in_container_datatable'
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
            api.show(id, fn)
        });
    }

    api.approve = function(id, fn) {
        if(!fn)
            fn = (dt) => {}

        $rootScope.disBtn=true;
        $http.put(url.approve(id)).then(function(resp) {
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