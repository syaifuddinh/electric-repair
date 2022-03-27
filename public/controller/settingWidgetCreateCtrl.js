app.controller('settingWidgetCreate', function($scope, $http, $rootScope,$state,$timeout,$compile, $stateParams) {
    $scope.formData = {}
    $scope.data = {}
    $rootScope.disBtn = false

    $scope.show = function() {
        $http.get(baseUrl+'/setting/widget/' + $stateParams.id).then(function success(data) {
          $scope.formData = data.data
        }, function error(data) {
          $scope.show()
        });
    }
    if($stateParams.id != null) {
        $scope.show()
    }
    
    $scope.showQuery = function() {
        $http.get(baseUrl+'/setting/query').then(function success(data) {
          $scope.data.queries = data.data
        }, function error(data) {
          $scope.showQuery()
        });
    }
    $scope.showQuery()  

    $scope.submitForm=function() {
      $rootScope.disBtn=true;
      var url = baseUrl + '/setting/widget';
      var method = 'post';
      if($scope.formData.id) {
          var url = baseUrl + '/setting/widget/' + $stateParams.id;
          var method = 'put';
      } 
      $http[method](url, $scope.formData).then(function(data) {
        $rootScope.disBtn = false
        toastr.success("Data Berhasil Disimpan !");
        $state.go('setting.widget')
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
});
