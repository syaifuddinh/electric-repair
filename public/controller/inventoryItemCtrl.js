app.controller('inventoryItem', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle=  $rootScope.solog.label.item.title;
    $scope.formData = {};

    $rootScope.emptyBuffer()

    $scope.searchData = function() {
        $scope.$broadcast("reloadItem", $scope.formData)
    }
});

app.controller('inventoryItemCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Master Item";
  $scope.formData={};
  $scope.formData.initial_cost=0;
  $scope.formData.is_stock=0;
  $scope.formData.minimal_stock=0;
  $scope.formData.is_active=1;
  $scope.formData.is_merchandise=1;
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/inventory/item/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/inventory/item?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('inventory.item');
        // oTable.ajax.reload();
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        // console.log(xhr);
        if (xhr.status==422) {
          var msgs="";
          $.each(xhr.responseJSON.errors, function(i, val) {
            msgs+=val+'<br>';
          });
          toastr.warning(msgs,"Validation Error!");
        } else {
          toastr.error(xhr.responseJSON.message,"Error has Found!");
        }
      }
    });
  }

});
app.controller('inventoryItemEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Master Item";
  $scope.formData={};
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/inventory/item/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData = dt
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/inventory/item/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('inventory.item');
        // oTable.ajax.reload();
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        // console.log(xhr);
        if (xhr.status==422) {
          var msgs="";
          $.each(xhr.responseJSON.errors, function(i, val) {
            msgs+=val+'<br>';
          });
          toastr.warning(msgs,"Validation Error!");
        } else {
          toastr.error(xhr.responseJSON.message,"Error has Found!");
        }
      }
    });
  }

});

app.controller('inventoryItemCreateNew',function($scope, $http, $rootScope, $state, $stateParams) {
})

app.controller('inventoryItemShow',function($scope,$http,$rootScope,$state,$stateParams) {
    $rootScope.pageTitle = "Detail"
})
