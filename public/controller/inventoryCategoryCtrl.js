app.controller('inventoryCategory', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Item Category";
    $rootScope.emptyBuffer()
});

app.controller('inventoryCategoryCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Add New Category";
  $scope.formData={};
  $scope.formData.is_tire=0;
  $scope.formData.is_asset=0;
  $scope.formData.is_jasa=0;
  $scope.formData.is_container_part=0;
  $('.ibox-content').toggleClass('sk-loading');

  $scope.banChange=function(value) {
    if (value==1) {
      $scope.formData.is_asset=1;
      $scope.formData.type_ban=1;
    } else {
      delete $scope.formData.type_ban;
    }
  }

  $scope.backward = function() {
    if($rootScope.hasBuffer()) {
        $rootScope.accessBuffer()
    } else {
      $scope.emptyBuffer()
      $state.go('inventory.category')
    }
  }

  $http.get(baseUrl+'/inventory/category/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/inventory/category?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data has successfully stored");
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
          $scope.emptyBuffer()
          $state.go('inventory.category');
        }
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
app.controller('inventoryCategoryEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = "Category Edit";
    $scope.formData = {};
    $('.ibox-content').toggleClass('sk-loading');

    $scope.banChange = function(value) {
        if (value == 1) {
            $scope.formData.is_asset = 1;
            $scope.formData.type_ban = 1;
        } else {
            delete $scope.formData.type_ban;
        }
    }

    $http.get(baseUrl+'/inventory/category/'+$stateParams.id+'/edit')
        .then(function(data) {
            $scope.data = data.data;
            var dt = data.data.category;
            $scope.formData.parent_id = dt.parent_id;
            $scope.formData.code = dt.code;
            $scope.formData.name = dt.name;
            $scope.formData.is_asset = dt.is_asset;
            $scope.formData.is_jasa = dt.is_jasa;
            $scope.formData.is_tire = dt.is_tire;

            if(dt.is_tire == 1) {
                if(dt.is_ban_luar == 1)
                    $scope.formData.type_ban = 1;
                else if(dt.is_ban_dalam == 1)
                    $scope.formData.type_ban = 2;
                else
                    $scope.formData.type_ban = 3;
            }
            $('.ibox-content').toggleClass('sk-loading');
        });

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $.ajax({
        type: "post",
        url: baseUrl+'/inventory/category/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
        data: $scope.formData,
        success: function(data){
            $scope.$apply(function() {
            $scope.disBtn=false;
            });
            toastr.success("Data Berhasil Disimpan");
            $state.go('inventory.category');
        },
        error: function(xhr, response, status) {
            $scope.$apply(function() {
            $scope.disBtn=false;
            });
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
app.controller('inventoryCategoryShow', function($scope,$rootScope,$http,$stateParams,$state) {
    $rootScope.pageTitle = "Category Details"
    $scope.item={}
    $http.get(`${baseUrl}/inventory/category/${$stateParams.id}`).then(function(e) {
        $scope.item=e.data
    })

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $scope.emptyBuffer()
            $state.go('inventory.category')
        }
    }
    
  $scope.deletes=function(ids) {
    var cfs=confirm("Are You Sure ?");
    if (cfs) {
      $http.delete(baseUrl+'/inventory/category/'+ids,{_token:csrfToken}).then(function success(data) {
        toastr.success("Record has been deleted!");
        $state.go('inventory.category')
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

})
