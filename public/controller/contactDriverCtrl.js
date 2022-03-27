app.controller('contactDriver', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Driver";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/driver/driver_datatable'
    },
    columns:[
      {data:"name",name:"name"},
      {data:"address",name:"address"},
      {data:"phone",name:"phone"},
      {data:"company.name",name:"company.name"},
      {data:"is_internal",name:"is_internal"},
      {data:"is_active",name:"is_active"},
      {data:"action_fr_contact",name:"action_fr_contact",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/driver/driver/'+ids,{_token:csrfToken}).then(function(res) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function(err) {
        toastr.error("Tidak bisa menghapus driver karena sudah terdapat transaksi!");
      });
    }
  }

});
app.controller('contactDriverCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Driver";
  $('.ibox-content').addClass('sk-loading');

  new google.maps.places.Autocomplete(
  (document.getElementById('place_search')), {
    types: []
  });

  $scope.formData={};
  $scope.driver_status=[
    {id:1,name:"Driver Utama"},
    {id:2,name:"Driver Cadangan"},
    {id:3,name:"Helper"},
    {id:4,name:"Driver Vendor"},
  ];
  $scope.isNotEdit=true;

  $http.get(baseUrl+'/contact/contact/create').then(function(data) {
    $scope.data=data.data;
    $scope.formData.company_id=compId;
    $scope.getVehicle(compId);

    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.getVehicle=function(cid) {
    $scope.vehicles=[];
    $http.get(baseUrl+'/api/vehicle/get_vehicle',{params:{company_id:cid}}).then(function(data) {
      angular.forEach(data.data,function(val,i) {
        $scope.vehicles.push({id:val.id,name:val.code+' - '+val.nopol});
      });
    });
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/driver/driver?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('contact.driver');
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
app.controller('contactDriverEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Driver";
  $('.ibox-content').addClass('sk-loading');

  new google.maps.places.Autocomplete(
  (document.getElementById('place_search')), {
    types: []
  });

  $scope.formData={};
  $scope.driver_status=[
    {id:1,name:"Driver Utama"},
    {id:2,name:"Driver Cadangan"},
    {id:3,name:"Helper"},
    {id:4,name:"Driver Vendor"},
  ];
  $scope.isNotEdit=false;

  $http.get(baseUrl+'/contact/contact/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.company_id=dt.company_id;
    $scope.formData.name=dt.name;
    $scope.formData.address=dt.address;
    $scope.formData.city_id=dt.city_id;
    $scope.formData.postal_code=dt.postal_code;
    $scope.formData.phone=dt.phone;
    $scope.formData.phone2=dt.phone2;
    $scope.formData.email=dt.email;
    $scope.formData.driver_status=dt.driver_status;
    $scope.formData.pegawai_no=dt.pegawai_no;
    $scope.formData.npwp=dt.npwp;
    $scope.formData.description=dt.description;
    $scope.formData.rek_bank_id=dt.rek_bank_id;
    $scope.formData.rek_milik=dt.rek_milik;
    $scope.formData.rek_cabang=dt.rek_cabang;
    $scope.formData.rek_no=dt.rek_no;

    $scope.getVehicle(dt.company_id);
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.getVehicle=function(cid) {
    $scope.vehicles=[];
    angular.forEach($scope.data.vehicle,function(val,i) {
      if (cid==$scope.formData.company_id) {
        $scope.vehicles.push({id:val.id,name:val.code+' - '+val.nopol});
      }
    });
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/driver/driver/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('contact.driver');
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
app.controller('contactDriverShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Driver";
  $scope.baseUrl=baseUrl;
  if ($state.current.name=="contact.driver.show") {
    $state.go('contact.driver.show.info');
  }

});
app.controller('contactDriverShowInfo', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Driver | Info";
  $http.get(baseUrl+'/driver/driver/'+$stateParams.id).then(function(data) {
    $scope.data=data.data.item;
  });
});
app.controller('contactDriverShowVehicle', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Driver | Kendaraan";
  $http.get(baseUrl+'/driver/driver/vehicle_list/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
  });
  $scope.formData={};
  $scope.driver_status=[
    {id:1,name:"Driver Utama"},
    {id:2,name:"Driver Cadangan"},
    {id:3,name:"Helper"},
    {id:4,name:"Driver Vendor"},
  ];
  // console.log($scope.$parent);
  $scope.getVehicle=function(cid) {
    $scope.vehicles=[];
    $http.get(baseUrl+'/api/vehicle/get_vehicle',{params:{company_id:cid}}).then(function(data) {
      angular.forEach(data.data,function(val,i) {
        $scope.vehicles.push({id:val.id,name:val.code+' - '+val.nopol});
      });
    });
  }

  $scope.delete_vehicle=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/driver/driver/delete_vehicle/'+ids,{_token:csrfToken}).then(function(res) {
        $state.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function(err) {
        toastr.error("Error Deleting Data!");
      });
    }
  }

  $http.get(baseUrl+'/driver/driver/'+$stateParams.id).then(function(data) {
    $scope.vehicles=[];
    angular.forEach(data.data.vehicle,function(val,i) {
      if (data.data.item.company_id==val.company_id) {
        $scope.vehicles.push({id:val.id,name:val.code+' - '+val.nopol});
      }
    })
  });

  $scope.addVehicle=function() {
    $scope.formData={};
    $('#modal_vehicle').modal('show');
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/driver/driver/store_vehicle/'+$stateParams.id+'?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $('#modal_vehicle').modal('hide');
        $timeout(function() {
          $state.reload();
        },1000);
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
