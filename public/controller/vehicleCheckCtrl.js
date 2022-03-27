app.controller('vehicleVehicleCheck', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Pengecekan Kendaraan";
  $scope.formData = {
    items : [],
    body : []
  };
  $scope.vehicles=[];

  $http.get(baseUrl+'/vehicle/vehicle_check/create').then(function(data) {
    $scope.data=data.data;
    $scope.data.active_vehicle = data.data.vehicle;
    
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/vehicle/vehicle_check_datatable',
      data: function(d) {
        d.company_id = $scope.formData.company_id;
        d.vehicle_id = $scope.formData.vehicle_id;
        d.start_date = $scope.formData.start_date;
        d.end_date = $scope.formData.end_date;
      }
    },
    columns:[
      {data:"company.name",name:"company.name"},
      {data:"vehicle.nopol",name:"vehicle.nopol"},
      {
          data:null,
          search:false,
          name:"date_transaction",
          render : resp => $filter('fullDate')(resp.date_transaction)
      },
      {data:"officer",name:"officer"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('vehicle.checklist.create')) {
          $(row).find('td').attr('ui-sref', 'vehicle.vehicle_check.edit({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.change_cabang = function() {
    $scope.vehicles=[];
    var id = $scope.formData.company_id;
    $http.get(baseUrl+'/api/vehicle/get_vehicle', {params:{company_id:id}}).then(function(data) {
      // console.log(data.data);
      angular.forEach(data.data, function(val,i) {
        $scope.vehicles.push(val);
      });
    });
  }

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/vehicle/vehicle_check/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.refreshTable=function() {
    oTable.ajax.reload();
  }

  $scope.reset_filter=function() {
    $scope.formData={};
    $scope.refreshTable()
  }

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/kendaraan_pengecekan_export?';
    url += params;
    location.href = url; 
  }
});

app.controller('vehicleVehicleCheckCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Pengecekan Kendaraan";

  $scope.formData={};
  $scope.formData.date_transaction=dateNow;
  $http.get(baseUrl+'/vehicle/vehicle_check/create').then(function(data) {
    $scope.data=data.data;
  });

  $scope.cariKendaraan=function(id) {
    $scope.vehicles=[];
    $http.get(baseUrl+'/api/vehicle/get_vehicle', {params:{company_id:id}}).then(function(data) {
      // console.log(data.data);
      angular.forEach(data.data, function(val,i) {
        $scope.vehicles.push(val);
      });
    });
  }

  $scope.changeDefaultValueItem = function(index) {
    $scope.formData.items[index].is_function = 0;
    $scope.formData.items[index].condition = 0;
  }

  $scope.changeDefaultValueBody = function(index) {
    $scope.formData.body[index].is_function = 0;
    $scope.formData.body[index].condition = 0;
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/vehicle/vehicle_check?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('vehicle.vehicle_check');
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

app.controller('vehicleVehicleCheckEdit', function($scope, $http, $rootScope,$state,$stateParams,$filter) {
    $rootScope.pageTitle="Edit Pengecekan Kendaraan";
  
    $scope.formData={};
    $scope.vehicles=[];
    $http.get(baseUrl+'/vehicle/vehicle_check/create').then(function(data) {
        $scope.data=data.data;

        $http.get(baseUrl + '/vehicle/vehicle_check/' + $stateParams.id).then(function(data) {
            $scope.formData = data.data.item;
            var body_details = data.data.body;
            var checklist_details = data.data.checklist;
            $scope.formData.date_transaction = $filter('minDate')(data.data.item.date_transaction);
            $scope.cariKendaraan($scope.formData.company_id);
            var body
            for(x in $scope.data.body) {
              body = $scope.data.body[x]
              if( body_details.filter(i => i.vehicle_body_id == body.id).length == 0 ) {
                  body_details.push({
                      'vehicle_body_id' : body.id
                  })
              }
            }
            $scope.formData.body = body_details

            for(x in $scope.data.checklist) {
              checklist = $scope.data.checklist[x]
              if( checklist_details.filter(i => i.vehicle_checklist_id == checklist.id).length == 0 ) {
                  checklist_details.push({
                      'vehicle_checklist_id' : checklist.id
                  })
              }
            }
            $scope.formData.items = checklist_details
        });
    });
    
    $scope.cariKendaraan=function(id) {
        $scope.vehicles=[];
      $http.get(baseUrl+'/api/vehicle/get_vehicle', {params:{company_id:id}}).then(function(data) {
        angular.forEach(data.data, function(val,i) {
          $scope.vehicles.push(val);
        });
      });
    }
  
    $scope.changeDefaultValueItem = function(index) {
      $scope.formData.items[index].is_function = 0;
      $scope.formData.items[index].condition = 0;
    }

    $scope.changeDefaultValueBody = function(index) {
      $scope.formData.body[index].is_function = 0;
      $scope.formData.body[index].condition = 0;
    }

    $scope.disBtn=false;
    $scope.submitForm=function() {
      $scope.disBtn=true;
      $.ajax({
        type: "put",
        url: baseUrl+'/vehicle/vehicle_check/'+ $stateParams.id +'?_token='+ csrfToken,
        data: $scope.formData,
        success: function(data){
          $scope.$apply(function() {
            $scope.disBtn=false;
          });
          toastr.success("Data Berhasil Disimpan");
          $state.go('vehicle.vehicle_check');
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
