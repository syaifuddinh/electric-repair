var movementContainers = angular.module('movementContainers', ['ui.router'], () => {})

movementContainers.config(($stateProvider, $urlRouterProvider) => {
    $stateProvider.state('depo.movement_container',{url:'/movement_container',data:{label:'Movement Container'},views:{'@':{templateUrl:'core/depo/movement_containers/view/index.html',controller:'movementContainers'}}})
    $stateProvider.state('depo.movement_container.create',{url:'/create',data:{label:'Create'},views:{'@':{templateUrl:'core/depo/movement_containers/view/create.html',controller:'movementContainersCreate'}}})
    $stateProvider.state('depo.movement_container.edit',{url:'/:id/edit',data:{label:'Edit'},views:{'@':{templateUrl:'core/depo/movement_containers/view/create.html',controller:'movementContainersCreate'}}})
    $stateProvider.state('depo.movement_container.show',{url:'/:id',data:{label:'Edit'},views:{'@':{templateUrl:'core/depo/movement_containers/view/show.html',controller:'movementContainersShow'}}})
})

movementContainers.service('movementContainersService', function($http, $rootScope, $filter) {
    var api = {}
    var url = {}

    url.store = () => baseUrl + '/depo/movement_container'
    url.update = (id) => baseUrl + '/depo/movement_container/' + id
    url.show = (id) => baseUrl + '/depo/movement_container/' + id
    url.approve = (id) => baseUrl + '/depo/movement_container/' + id + '/approve'
    url.showDetail = (id) => baseUrl + '/depo/movement_container/' + id + '/detail'
    url.destroy = (id) => baseUrl + '/depo/movement_container/' + id
    url.datatable = () => baseUrl + '/api/depo/movement_container_datatable'
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

    api.showDetail = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showDetail(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
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