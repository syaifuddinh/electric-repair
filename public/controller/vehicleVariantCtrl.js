app.controller('settingVehicleVariant', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Varian Kendaraan";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right m-l-sm',
        filename: 'Vehicle Variant - ' + new Date,
        sheetName: 'Data',
        title: 'Vehicle Variant',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/vehicle_variant_datatable'
    },
    columns:[
      {data:"code",name:"code"},
      {data:"name",name:"name"},
      {data:"vehicle_manufacturer.name",name:"vehicle_manufacturer.name"},
      {data:"vehicle_type.name",name:"vehicle_type.name"},
      {data:"year_manufacture",name:"year_manufacture"},
      {data:"vehicle_joint.name",name:"vehicle_joint.name"},
      {data:"action",name:"action", sorting:false,className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/vehicle_variant/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karena sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});
app.controller('settingVehicleVariantCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Varian";

  $http.get(baseUrl+'/setting/vehicle_variant/create').then(function(data) {
    $scope.data=data.data;
  });

  $scope.transmission=[
    {id:1,name:"Manual"},
    {id:2,name:"Otomatis"},
  ];

  $scope.disBtn=false;

  $scope.backward = function() {
    if($rootScope.hasBuffer()) {
        $rootScope.accessBuffer()
    } else {
      $scope.emptyBuffer()
      $state.go('setting.vehicle_variant')
    }
  }

  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/vehicle_variant?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $state.go('setting.vehicle_variant');
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
app.controller('settingVehicleVariantEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Varian";
  $scope.formData={};
  $http.get(baseUrl+'/setting/vehicle_variant/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.code=dt.code;
    $scope.formData.name=dt.name;
    $scope.formData.vehicle_type_id=dt.vehicle_type_id;
    $scope.formData.vehicle_manufacturer_id=dt.vehicle_manufacturer_id;
    $scope.formData.year_manufacture=dt.year_manufacture;
    $scope.formData.cost=dt.cost;
    $scope.formData.cylinder=dt.cylinder;
    $scope.formData.cc_capacity=dt.cc_capacity;
    $scope.formData.bbm_capacity=dt.bbm_capacity;
    $scope.formData.bbm_type_id=dt.bbm_type_id;
    $scope.formData.transmission=dt.transmission;
    $scope.formData.joints=dt.joints;
    $scope.formData.vehicle_joint_id=dt.vehicle_joint_id;
    $scope.formData.tire_size_id=dt.tire_size_id;
    $scope.formData.seat=dt.seat;
    $scope.formData.first_km_initial=dt.first_km_initial;
    $scope.formData.next_km_initial=dt.next_km_initial;
    $scope.formData.description=dt.description;
  });

  $scope.transmission=[
    {id:1,name:"Manual"},
    {id:2,name:"Otomatis"},
  ];

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/vehicle_variant/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.vehicle_variant');
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
