app.controller('settingAccountDefault', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Setting Default Akun";
  $('.ibox-content').addClass('sk-loading');
  $scope.$state=$state;
  $http.get(baseUrl+'/setting/account_default').then(function(data) {
    
    $scope.data=data.data;
    $scope.formData=data.data.default;
    $('.ibox-content').removeClass('sk-loading');
  });
  $scope.submitForm=function() {
    // console.log($scope.formData);
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/account_default?_token='+csrfToken,
      data: $scope.formData,
      dataType: 'json',
      success: function(data){
        toastr.success("Data Berhasil Disimpan");
      },
      error: function(xhr, status, response) {
        toastr.error("Error Has Found");
      }
    });
  }
});
