app.controller('settingRoute', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, routesService) {
  $rootScope.pageTitle="Route";
  $('.ibox-content').addClass('sk-loading');

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
        className: 'btn btn-default btn-sm pull-right',
        filename: 'Route Pengangkutan',
        sheetName: 'Data',
        title: 'Route Pengangkutan',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/route_datatable',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"company.name",name:"company.name"},
      {data:"code",name:"code"},
      {data:"name",name:"name"},
      {data:"from.name",name:"from.name"},
      {data:"to.name",name:"to.name"},
      {data:"distance",name:"distance"},
      {data:"duration",name:"duration"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.delivery.route.detail')) {
          $(row).find('td').attr('ui-sref', 'setting.route.show({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(routesService.url.destroy(ids), {_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});
app.controller('settingRouteCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, routesService) {
    $rootScope.pageTitle="Tambah";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData = {}
    $scope.formData.company_id = compId
    $scope.formData.type_satuan = 1


  $scope.type_satuan=[
    {id:1,name:'Jam'},
    {id:2,name:'Hari'},
    {id:3,name:'Menit'}
  ];
  $http.get(routesService.url.create()).then(function(data) {
    $scope.data=data.data;
    $scope.data.city_origin = $scope.data.city
    $scope.data.city_destination = $scope.data.city
    $('.ibox-content').removeClass('sk-loading');
  });

    $scope.changeCityFrom = function() {
        $scope.data.city_origin = $scope.data.city.filter(c => c.country_id == $scope.formData.country_from_id)
    }

    $scope.changeCityTo = function() {
        $scope.data.city_destination = $scope.data.city.filter(c => c.country_id == $scope.formData.country_to_id)
    }

    $scope.showCountry = function() {
        $http.get(baseUrl+'/setting/city/create').then(function(data) {
            $scope.country = data.data.country;
        });
    }
    $scope.showCountry()

    $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
            $rootScope.emptyBuffer()
            $state.go('setting.route')
        }
    }


  $scope.show = function() {
      if($stateParams.id) {
          $http.get(routesService.url.show($stateParams.id)).then(function(data) {
            $scope.formData=data.data.item;
            $scope.changeCityFrom()
            $scope.changeCityTo()
          });    
      }
  }
  $scope.show()

  $rootScope.disBtn=false;
  $scope.submitForm=function() {
    $rootScope.disBtn=true;
    if($stateParams.id) {
        routesService.api.update($scope.formData, $stateParams.id, function(){
            $scope.backward()
        })
    } else {
        routesService.api.store($scope.formData, function(){
            $scope.backward()
        })
    }
  }

});

app.controller('settingRouteShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, routesService) {
  $rootScope.pageTitle="Detail Route";
  $('.ibox-content').addClass('sk-loading');
  $http.get(routesService.url.show($stateParams.id)).then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });
  $scope.formData={}
  var urlPost="";
  $scope.openModal=function() {
    urlPost = routesService.url.store_cost() 
    $scope.modalTitle="Add Route Cost";
    $('#route_cost_modal').modal('show');
    $scope.formData={}
    $scope.formData.is_container=0
    $scope.formData.header_id=$stateParams.id
  }

  $scope.type_satuan=[
    {id:1,name:'Jam'},
    {id:2,name:'Hari'},
  ];

  $scope.costTypeData={}
  $scope.changeCostType=function(id) {
    $http.get(baseUrl+'/setting/cost_type/'+id).then(function(data) {
      $scope.costTypeData=data.data.item;
    });
  }

  $scope.edit=function(ids) {
    urlPost = routesService.url.edit_cost(ids)
    $scope.modalTitle="Edit Route Costs";
    $http.get(routesService.url.show_cost(ids)).then(function(data) {
      var dt=data.data.item;
      $scope.formData={}
      $scope.formData.commodity_id=dt.commodity_id;
      $scope.formData.vehicle_type_id=dt.vehicle_type_id;
      $scope.formData.container_type_id=dt.container_type_id;
      $scope.formData.description=dt.description;
      $scope.formData.is_container=dt.is_container;
      $scope.formData.header_id=$stateParams.id;
      $('#route_cost_modal').modal('show');
    });
  }

  $scope.submitForm=function() {
    $http.post(urlPost,$scope.formData).then(function(data) {
      $('#route_cost_modal').modal('hide');
      toastr.success("Data Berhasil Disimpan");
      $timeout(function() {
            $state.reload();
      },1000);
    }, function(err) {
      if (err.status==422) {
        var msgs="";
        angular.forEach(err.data.errors,function(val,i) {
          msgs+=val+'<br>';
        });
        toastr.warning(msgs,"Validation Error!");
      }
    });
  }

  $scope.saveAs=function(jsn) {
    // console.log(jsn)
    $scope.saveData={}
    $scope.saveData.code=jsn.code;
    $scope.saveData.name=jsn.name;
    $scope.saveData.company_id=jsn.company_id;
    $scope.saveData.city_from=jsn.city_from;
    $scope.saveData.city_to=jsn.city_to;
    $scope.saveData.type_satuan=jsn.type_satuan;
    $scope.saveData.distance=jsn.distance;
    $scope.saveData.duration=jsn.duration;
    $scope.saveData.description=jsn.description;
    $('#saveAsModal').modal('show');
  }

  $scope.disBtn=false;
  $scope.submitSave=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/route?_token='+csrfToken,
      data: $scope.saveData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $('#saveAsModal').modal('hide');
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

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(routesService.url.delete_cost(ids), {_token:csrfToken}).then(function success(data) {
        $state.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});
app.controller('settingRouteShowCost', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, routesService) {
  $rootScope.pageTitle="Detail Biaya";
  $('.ibox-content').addClass('sk-loading');
  $scope.loadFinished = 0;

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/detail_cost_datatable/'+$stateParams.idcost,
      dataSrc: function(d) {
        $scope.loadFinished++;
        if($scope.loadFinished ==3)
            $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"cost_type.code",name:"cost_type.code"},
      {data:"cost_type.name",name:"cost_type.name"},
      {data:"is_internal",name:"is_internal"},
      {data:"cost",name:"cost"},
      {data:"total_liter",name:"total_liter"},
      {data:"description",name:"description"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $http.get(routesService.url.show($stateParams.id)).then(function(data) {
    $scope.data=data.data;
    $scope.loadFinished++;
    if($scope.loadFinished ==3)
        $('.ibox-content').removeClass('sk-loading');
  });
  $http.get(routesService.url.show_cost($stateParams.idcost)).then(function(data) {
    $scope.cost=data.data;
    $scope.loadFinished++;
    if($scope.loadFinished ==3)
        $('.ibox-content').removeClass('sk-loading');
  });

  $scope.hitungCost=function() {
    // console.log($scope);
    $scope.formData1.total_liter=0;
    $scope.formData1.harga_satuan=0;
    $scope.formData1.cost=$scope.formData1.cost_type_id.initial_cost;
  }

  $scope.submitForm=function() {
    $http.post(urlPost,$scope.formData).then(function(data) {
      $('#route_cost_modal').modal('hide');
      toastr.success("Data Berhasil Disimpan");
      $timeout(function() {
        $state.reload();
      },1000);
    }, function(err) {
      if (err.status==422) {
        var msgs="";
        angular.forEach(err.data.errors,function(val,i) {
          msgs+=val+'<br>';
        });
        toastr.warning(msgs,"Validation Error!");
      }
    });
  }

  $scope.disBtn=false;
  $scope.simpanBaru=function() {
    // console.log($scope.formData1);
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: routesService.url.store_detail_cost($stateParams.idcost),
      data: $scope.formData1,
      beforeSend: function (request) {
            request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
      },
      success: function(data){
        oTable.ajax.reload();
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        // $state.go('setting.route');
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

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(routesService.url.delete_detail_cost(ids), {_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});
