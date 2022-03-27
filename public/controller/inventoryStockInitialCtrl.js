app.controller('inventoryStockInitial', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.initial_inventory.title;
    $scope.filterData={}
    $scope.warehouses=[]
    $scope.is_filter=false;

    $('.ibox-content').addClass('sk-loading');

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [
            {
                extend : 'excel',
                enabled : true,
                action : newExportAction,
                text : '<span class="fa fa-file-excel-o"></span> Export Excel',
                className : 'btn btn-default btn-sm',
                filename : 'Inventory - Stock Initial | '+new Date(),
                sheetName : 'Data',
                title: 'Inventory - Stock Initial',
                exportOptions: {
                    rows: {
                        selected: true
                    }
                },
            },
        ],
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/inventory/stock_initial_datatable',
            data: function(d) {
                d.company_id=$scope.filterData.company_id;
                d.warehouse_id=$scope.filterData.warehouse_id;
            },
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },
        columns:[
            {data:"company.name",name:"company.name"},
            {data:"warehouse.name",name:"warehouse.name"},
            {
            data:null,
            orderable:false,
            searchable:false,
            render:resp => $filter('fullDate')(resp.date_transaction)
            },
            {data:"code",name:"code"},
            {data:"item.name",name:"item.name"},
            {data:"item.category.name",name:"item.category.name"},
            {data:"qty",name:"qty",className:"text-right"},
            {data:"price",name:"price",className:"text-right"},
            {data:"total",name:"total",className:"text-right"},
            {data:"description",name:"description"},
            {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            var data = oTable.row(row).data() 
            if($rootScope.roleList.includes('inventory.first_stock.edit') && parseInt(data.journal_status) == 1) {
                $(row).find('td').attr('ui-sref', 'inventory.stock_initial.edit({id:' + data.id + '})')
                $(row).find('td:last-child').removeAttr('ui-sref')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
    });

    oTable.buttons().container().appendTo( '#export_button' );

    $compile($('table'))($scope);



  $scope.companyChange=function(compId) {
    $scope.warehouses=[]
    angular.forEach($scope.data.warehouse, function(val,i) {
      if (val.company_id==compId) {
        $scope.warehouses.push({
          id:val.id,
          name:val.name
        })
      }
    })
    if ($scope.warehouses.length>0) {
      $scope.filterData.warehouse_id=$scope.warehouses[0].id
    }
    $scope.refreshTable();
  }
  $scope.refreshTable=function() {
    oTable.ajax.reload();
  }

  $scope.reset_filter=function() {
    $scope.filterData={};
    $scope.refreshTable();
  }

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/inventory/stock_initial/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});

app.controller('inventoryStockInitialCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Tambah Persediaan Awal Item";
    $scope.formData={};
    $scope.formData.date_transaction=dateNow;
    $scope.formData.qty=0;
    $scope.formData.price=0;
    $('.ibox-content').toggleClass('sk-loading');

    $http.get(baseUrl+'/inventory/stock_initial/create').then(function(data) {
        $scope.data=data.data;
        $('.ibox-content').toggleClass('sk-loading');
    });

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        var payload = $scope.formData
        $http.post(baseUrl+'/inventory/stock_initial', payload).then(function(resp) {
            $scope.disBtn=false;
            toastr.success(resp.data.message)
            $state.go('inventory.stock_initial');
            fn(resp)
        }, function(error) {
            $scope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

});
app.controller('inventoryStockInitialEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Persediaan Awal Item";
  $scope.formData={};
  $('.ibox-content').toggleClass('sk-loading');

  $http.get(baseUrl+'/inventory/stock_initial/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.journal_id=dt.journal_id;
    $scope.formData.warehouse_id=dt.warehouse_id;
    $scope.formData.item_id=dt.item_id;
    $scope.formData.date_transaction=$filter('minDate')(dt.date_transaction);
    $scope.formData.qty=dt.qty;
    $scope.formData.price=dt.price;
    $scope.formData.description=dt.description;
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/inventory/stock_initial/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('inventory.stock_initial');
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
