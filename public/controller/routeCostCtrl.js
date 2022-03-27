app.controller('settingRouteCost', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Rate Cost";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/setting/route_cost').then(function(data) {
    $scope.data=data.data;
  });

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
      filename: 'Biaya Ritase',
      sheetName: 'Data',
      title: 'Biaya Ritase',
      exportOptions: {
        rows: {
          selected: true
        }
      },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/route_cost_datatable',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"company_name",name:"companies.name"},
      {data:"trayek.name",name:"trayek.name"},
      {data:"commodity.name",name:"commodity.name"},
      {data:"vehicle_type.name",name:"vehicle_type.name"},
      {data:"cost",name:"cost",className:"text-right"},
      {data:"description",name:"description"},
      {data:"action",name:"action", order:false,className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.delivery.route_cost.detail')) {
          $(row).find('td').attr('ui-sref', 'setting.route_cost.show({id:' + data.id + '})')
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
      $http.delete(baseUrl+'/setting/route_cost/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }
  $scope.formData={};
  $scope.url="";
  $scope.create=function() {
    $scope.modalTitle="Tambah Biaya Ritase";
    $scope.formData={};
    $scope.formData.is_container=0;
    $scope.url=baseUrl+'/setting/route_cost?_token='+csrfToken;
    $('#modal').modal('show');
  }

  $scope.saveAs=function(id) {
    var cofs=confirm("Apakah anda ingin melakukan Save As ?");
    if (!cofs) {
      return null;
    }
    $http.post(baseUrl+'/setting/route_cost/save_as',$scope.formData).then(function(data) {
      toastr.success("Data Berhasil Disimpan");
      oTable.ajax.reload();
    });
  }

  $scope.edit=function(ids) {
    $scope.modalTitle="Edit Biaya Ritase";
    $http.get(baseUrl+'/setting/route_cost/'+ids+'/edit').then(function(data) {
      $scope.item=data.data;
      // startdata
      $scope.formData={};
      $scope.formData.id=$scope.item.id;
      $scope.formData.commodity_id=$scope.item.commodity_id;
      $scope.formData.route_id=$scope.item.route_id;
      $scope.formData.vehicle_type_id=$scope.item.vehicle_type_id;
      $scope.formData.container_type_id=$scope.item.container_type_id;
      $scope.formData.cost=$scope.item.cost;
      $scope.formData.description=$scope.item.description;
      // endata
      $('#modal').modal('show');
    });
    $scope.url=baseUrl+'/setting/route_cost/'+ids+'?_method=PUT&_token='+csrfToken;
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: $scope.url,
      data: $scope.formData,
      dataType: 'json',
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $('#modal').modal('hide');
        $timeout(function() {
          $state.go('setting.route_cost.show',{id:data.id});
        },1000)
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

});
app.controller('settingContainerCost', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Biaya";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/setting/route_cost').then(function(data) {
    $scope.data=data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    dom: 'Blfrtip',
    buttons: [
      {
        'extend' : 'excel',
        'enabled' : true,
        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
        'className' : 'btn btn-default btn-sm',
        'filename' : 'Biaya kontainer - '+new Date(),
        'sheetName' : 'Data',
        'title' : 'Biaya kontainer'
      },
    ],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/route_cost_datatable',
      data: function(d) {
        d.is_container=true;
      },
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"trayek.name",name:"trayek.name"},
      {data:"commodity.name",name:"commodity.name"},
      {data:"container_type.full_name",name:"container_type.name"},
      {data:"cost",name:"cost",className:"text-right"},
      {data:"description",name:"description"},
      {data:"action",name:"action", order:false,className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.delivery.route_cost.detail')) {
        $(row).find('td').attr('ui-sref', 'setting.container_cost.show({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo( '.ibox-tools' );

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/setting/route_cost/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }
  $scope.formData={};
  $scope.url="";
  $scope.create=function() {
    $scope.modalTitle="Tambah Biaya Ritase";
    $scope.formData={};
    $scope.formData.is_container=1;
    $scope.url=baseUrl+'/setting/route_cost?_token='+csrfToken;
    $('#modal').modal('show');
  }

  $scope.saveAs=function(id) {
    var cofs=confirm("Apakah anda ingin melakukan Save As ?");
    if (!cofs) {
      return null;
    }
    $http.post(baseUrl+'/setting/route_cost/save_as',$scope.formData).then(function(data) {
      toastr.success("Data Berhasil Disimpan");
      oTable.ajax.reload();
    });
  }

  $scope.edit=function(ids) {
    $scope.modalTitle="Edit Biaya Ritase";
    $http.get(baseUrl+'/setting/route_cost/'+ids+'/edit').then(function(data) {
      $scope.item=data.data;
      // startdata
      $scope.formData.id=$scope.item.id;
      $scope.formData.commodity_id=$scope.item.commodity_id;
      $scope.formData.route_id=$scope.item.route_id;
      $scope.formData.container_type_id=$scope.item.container_type_id;
      $scope.formData.is_container=1;
      $scope.formData.cost=$scope.item.cost;
      $scope.formData.description=$scope.item.description;
      // endata
      $('#modal').modal('show');
    });
    $scope.url=baseUrl+'/setting/route_cost/'+ids+'?_method=PUT&_token='+csrfToken;
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
        $timeout(function() {
          $state.go('setting.container_cost.show',{id:data.id});
        },1000)
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

});
app.controller('settingRouteCostShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, routesService) {
  $rootScope.pageTitle="Rate Cost Detail";
  $('.ibox-content').addClass('sk-loading');
  $scope.loadCounter = 0;

  // console.log($stateParams);
  $scope.state=$state;
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/detail_cost_datatable/'+$stateParams.id,
      dataSrc: function(d) {
        $scope.loadCounter++;  
        if($scope.loadCounter == 3)
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

  $http.get(baseUrl+'/setting/route_cost/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $scope.loadCounter++;  
    if($scope.loadCounter == 3)
        $('.ibox-content').removeClass('sk-loading');
  });
  $http.get(routesService.url.show_cost($stateParams.id)).then(function(data) {
    $scope.cost=data.data;
    $scope.loadCounter++;  
    if($scope.loadCounter == 3)
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
      url: routesService.url.store_detail_cost($stateParams.id),
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
