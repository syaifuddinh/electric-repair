app.controller('vehicleVehicleDistance', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Kilometer Kendaraan";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData = {};
  $scope.formFilter = {};
  $scope.is_filter = false;

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/vehicle/vehicle_distance_datatable',
      data: function(d) {
        d.company_id = $scope.formFilter.company_id;
        d.vehicle_id = $scope.formFilter.vehicle_id;
        d.start_date = $scope.formFilter.start_date;
        d.end_date = $scope.formFilter.end_date;
      },
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"vehicle.code",name:"vehicle.code"},
      {data:"vehicle.nopol",name:"vehicle.nopol"},
      {
        data:null,
        name:"date_distance",
        searchable:false,
        render:resp => $filter('fullDate')(resp.date_distance)
      },
      {
        data:"distance",
        className:'text-right',
        name:"distance"
      },
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $http.get(baseUrl+'/vehicle/vehicle_distance').then(function(data) {
    $scope.data=data.data;
    $scope.data.active_vehicles = $scope.data.vehicles;
  });

  $scope.formData={};
  $scope.url="";
  $scope.create=function() {
    $scope.modalTitle="Tambah Kilometer Kendaraan";
    $scope.formData={};
    $scope.formData.date_distance=dateNow;
    $scope.formData.distance=0;
    $scope.url=baseUrl+'/vehicle/vehicle_distance?_token='+csrfToken;
    $('#modal').modal('show');
  }

  $scope.edit=function(ids) {
    $scope.modalTitle="Edit Kilometer Kendaraan";
    $http.get(baseUrl+'/vehicle/vehicle_distance/'+ids+'/edit').then(function(data) {
      $scope.item=data.data;
      // startdata
      $scope.formData.name=$scope.item.name;
      $scope.formData.type=$scope.item.type;
      $scope.formData.is_repeat=$scope.item.is_repeat;
      $scope.formData.interval=$scope.item.interval;
      $scope.formData.cost=$scope.item.cost;
      $scope.formData.description=$scope.item.description;
      // endata
      $('#modal').modal('show');
    });
    $scope.url=baseUrl+'/vehicle/vehicle_distance/'+ids+'?_method=PUT&_token='+csrfToken;
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: $scope.url,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $('#modal').modal('hide');
        oTable.ajax.reload();
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

  $scope.company_changed = function() {
    $scope.data.active_vehicles = $scope.data.vehicles.filter( function(item) {
      return item.company_id == $scope.formFilter.company_id;
    });
    $scope.formFilter.vehicle_id = '';
  }

  $scope.refreshTable=function() {
    oTable.ajax.reload();
  }

  $scope.reset_filter=function() {
    $scope.formFilter = {};
    $scope.refreshTable()
  }

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/kilometer_kendaraan_export?';
    url += params;
    location.href = url; 
  }
  
});
