app.controller('operationalProgress', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, additionalFieldsService) {
    $rootScope.pageTitle = $rootScope.solog.label.operational_progress.title;
    $('.ibox-content').addClass('sk-loading');
    $scope.filterData={};
    $scope.is_filter=false;

    $scope.initDatatable = function() {
        oTable = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
            // dom:'Blfrtip',
            ajax: {
              headers : {'Authorization' : 'Bearer '+authUser.api_token},
              url : baseUrl+'/api/operational/kpi_log_datatable',
              data: function(d) {
                    d.params=$scope.filterData;
              },
              dataSrc: function(d) {
                  $('.ibox-content').removeClass('sk-loading');
                  return d.data;
              }
            },
            buttons: [
                {
                    'extend' : 'excel',
                    'action' : newExportAction,
                    'enabled' : true,
                    // 'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                    // 'className' : 'btn btn-default btn-sm',
                    // 'filename' : 'Job Order - '+new Date(),
                    // 'sheetName' : 'Data',
                    // 'title' : 'Job Order'
                },
            ],
            columns:columns,
            columnDefs:columnDefs,
            createdRow: function(row, data, dataIndex) {
                if($rootScope.roleList.includes('operational.progress.detail')) {
                    $(row).find('td').attr('ui-sref', 'operational.job_order.show({id:' + data.job_order_id + '})')
                    $(row).find('td:last-child').removeAttr('ui-sref')
                } else {
                    $(oTable.table().node()).removeClass('table-hover')
                }
                $compile(angular.element(row).contents())($scope);
            }
        });
        oTable.buttons().container().appendTo( '#export_button' );
    }

    additionalFieldsService.dom.getOperationalProgressKey(function(list){
        columnDefs = [
            {title : $rootScope.solog.label.general.date},
            {title : $rootScope.solog.label.general.service},
            {title : $rootScope.solog.label.job_order.code},
            {title : $rootScope.solog.label.general.customer},
            {title : $rootScope.solog.label.general.updated_by},
            {title : $rootScope.solog.label.general.description},
            {title : $rootScope.solog.label.general.status}
        ]
        columns = [
            {data:"date_update",name:"date_update"},
            {data:"job_order.service.name",name:"job_order.service.name"},
            {data:"job_order.code",name:"job_order.code",className:"font-bold"},
            {data:"job_order.customer.name",name:"job_order.customer.name"},
            {data:"creates.name",name:"creates.name",className:""},
            {data:"description",name:"description",className:""},
            {data:"kpi_status.name",name:"kpi_status.name"}
        ]

        for(x in list) {
            columns.push({
                data : list[x].slug,
                orderable:false,
                searchable:false
            })
            
            columnDefs.push({title : list[x].name})
        }

        columnDefs.push({title : ''})
        columns.push({data:"action",name:"created_at",className:"text-center"})

        columnDefs = columnDefs.map((c, i) => {
            c.targets = i
            return c
        })
        console.log(columnDefs)
        console.log(columns)
        $scope.initDatatable()
    })
    
  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/work_order/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $http({method: 'GET', url: baseUrl+'/operational/progress_operasional'})
  .then(function successCallback(data, status, headers, config) {
    $scope.data=data.data;
  },
  function errorCallback(data, status, headers, config) {

  });

  $scope.refreshTable=function() {
    oTable.ajax.reload();
  }

  $scope.reset_filter=function() {
    $scope.filterData={};
    $scope.refreshTable()
  }

  $scope.edit=function(jsn) {
    // console.log(jsn);
    $scope.formData={}
    $scope.formData.date_update=$filter('minDate')(jsn.date_update)
    // $scope.formData
    $('#modalEdit').modal()
  }

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/progress_operasional_export?';
    url += params;
    location.href = url;
  }
});
app.controller('operationalProgressCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Progress Operasional";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={}
  $scope.job_order={}

  $http.get(baseUrl+'/operational/progress_operasional/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.changeCustomer=function(value) {
    $scope.job_order={}
    $scope.formData.job_order_id=null;
    $scope.formData.kpi_status_id=null;
    joTable.ajax.reload()
    $('#job_order_id').val('');
  }

  $scope.cariJO=function() {
    $('#modalJO').modal('show');
  }

  $scope.selectJO=function(id,code) {
    $scope.formData.job_order_id=id;
    $('#job_order_id').val(id);
    $scope.job_order.code=code;
    $('#modalJO').modal('hide');
    $http.get(baseUrl+'/operational/progress_operasional/cari_status_by_jo/'+id).then(function(data) {
      $scope.kpi_status=data.data.kpi_status
      $scope.formData.kpi_status_id=data.data.jo.kpi_id;
    });
  }

  var joTable = $('#jo_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX : false,
    order:[[0,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/job_order_datatable',
      data: function(d) {
        d.customer_id=$scope.formData.customer_id;
      }
    },
    columns:[
      {data:"action_choose",name:"created_at",className:"text-center",orderable:'false'},
      {data:"code",name:"job_orders.code"},
      {data:"service_name",name:"services.name"},
      {data:"service_type_name",name:"service_types.name",className:"font-bold"},
      {data:"shipment_date",name:"job_orders.shipment_date"},
      // {data:"status",name:"status",className:""},
      {data:"route_name",name:"routes.name",className:""},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  $compile($('thead'))($scope);

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/operational/progress_operasional?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        // $state.reload();
        $timeout(function() {
          $state.go('operational.progress');
        },1000)
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
