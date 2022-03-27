app.controller('opWarehousePutaway', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Put Away";
  $scope.formData = {};

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/putaway_datatable',
      data : function(request) {
        request['warehouse_from'] = $scope.formData.warehouse_from;
        request['status'] = $scope.formData.status;
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;

        return request;
      }
    },

    dom: 'Blfrtip',
    buttons: [{
      extend: 'excel',
      enabled: true,
      action: newExportAction,
      text: '<span class="fa fa-file-excel-o"></span> Export Excel',
      className: 'btn btn-default btn-sm pull-right',
      filename: 'Putaway',
      sheetName: 'Data',
      title: 'Putaway',
      exportOptions: {
        rows: {
          selected: true
        }
      },
    }],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    columns:[
      {data:"code",name:"im.code",className:"font-bold", 'width' : '20%'},
      {data:"warehouse_from",name:"wfrom.name", 'width' : '25%'},
      {
        data:null,
        orderable:false, 
        searchable:false,
        'width' : '25%',
        render : resp => $filter('fullDate')(resp.date_transaction)
      },
      {data:"status_label",name:"im.status",className:"text-center"},
      {
        data:null,
        orderable:false,
        searchable:false,
        render : function(item) {
            var html = ''
            html += "<a ng-show=\"roleList.includes('inventory.putaway.detail')\" ui-sref='operational_warehouse.putaway.show({id:" + item.id + "})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            if (item.status==1) {
                html +='<a ng-show="roleList.includes(\'inventory.putaway.delete\')" ng-click="deletes(' + item.id + ')"><i class="fa fa-trash"></i></a>';
            }
            return html;
        },
        className:"text-center"
      },
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('operational_warehouse.putaway.detail')) {
          $(row).find('td').attr('ui-sref', 'operational_warehouse.putaway.show({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo('.ibox-tools')
  $compile($('thead'))($scope)

  $scope.searchData = function() {
    oTable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/warehouse_putaway_export?';
    url += params;
    location.href = url; 
  }

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/operational_warehouse/putaway/' + ids).then(function(data) {
        toastr.success("Putaway berhasil dihapus","Selamat !")
        oTable.ajax.reload();
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
  }
});
app.controller('opWarehousePutawayCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Add Put Away";
    $scope.formData={}
    $scope.formData.date_transaction=dateNow
    $scope.formData.detail=[]
    $scope.detailData={}
    $scope.detailData.qty=1
    $scope.detailData.stock=0

    $scope.deletes = function(id) {
        $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id) 
    }

  $scope.getSuratJalan = function() {
    var request = {
      warehouse_id : $scope.formData.warehouse_from_id
    };
    request = $.param(request);
    $http.get(baseUrl+'/inventory/item/surat_jalan?' + request).then(function(data) {
      $scope.data.warehouse_receipt=data.data.warehouse_receipt;
    });
  }

  $scope.is_allow_insert = function() {
    if(!$scope.detailData.item_id || $scope.detailData.stock==0 || parseInt($scope.detailData.qty) > parseInt($scope.detailData.stock)) {
      return true;
    }

    return false;
  }

  oTable = $('#pallet_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:false,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational_warehouse/item_warehouse_datatable',
      data: function(d) {
        d.warehouse_id = $scope.formData.warehouse_from_id
        d.rack_id = $scope.formData.rack_from_id
        d.warehouse_receipt_id = $scope.formData.warehouse_receipt_id
      }
    },
    columns:[
      {
        data:null,
        orderable:false,
        searchable:false,
        className:"text-center",
        render : function(e) {
            var r = e.action_choose.replace('job_order.', '', '')
            return r
        }
      },
      {data:"code",name:"code"},
      {data:"name",name:"name"},
      {data:"barcode",name:"barcode", className : 'hidden'},
      {data:"category",name:"categories.name"},
      {data:"piece.name",name:"piece.name"},
      {data:"description",name:"description"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.appendItemWarehouse = function(v) {
      $scope.detailData = {}
      $scope.detailData.id = Math.round(Math.random() * 9999999)
      $scope.detailData.code = v.code
      $scope.detailData.item_id = v.id
      $scope.detailData.name = v.name
      $scope.detailData.rack_id = v.rack_id
      $scope.detailData.qty = v.qty
      $scope.detailData.warehouse_receipt_id = v.warehouse_receipt_id
      $scope.detailData.warehouse_receipt_detail_id = v.warehouse_receipt_detail_id
      $scope.detailData.warehouse_receipt_code = v.warehouse_receipt_code
      $scope.detailData.rack_code = v.rack_code
      $scope.formData.detail.push($scope.detailData)
  }

  $scope.$on('getItemWarehouse', function(e, v){
      $scope.appendItemWarehouse(v)
  })

  $scope.$on('getItemWarehouses', function(e, items){
      var i
      for(i in items) {
          $scope.appendItemWarehouse(items[i])
      }
  })

  $scope.cariPallet=function() {
    if (!$scope.formData.warehouse_from_id) {
      toastr.error("Anda harus memilih gudang telebih dahulu!","Maaf!")
      return null;
    }
    if (!$scope.formData.rack_from_id) {
      toastr.error("Anda harus memilih rak asal telebih dahulu!","Maaf!")
      return null;
    }
    if (!$scope.formData.warehouse_receipt_id) {
      toastr.error("Anda harus memilih No TTB telebih dahulu!","Maaf!")
      return null;
    }
    $('#modalItem').modal()
    oTable.ajax.reload();
  }

  $scope.choosePallet=function(json) {
    $('#modalItem').modal('hide')
    $scope.detailData.item_id=json.id
    $scope.detailData.warehouse_receipt_detail_id=json.warehouse_receipt_detail_id
    $scope.detailData.item_name=json.name+' ('+json.code+')'
    
    $scope.detailData.stock=json.qty
    
  }

  $scope.changeWarehouseFrom=function(id) {
    $scope.formData.warehouse_to_id = $scope.formData.warehouse_from_id 
    $scope.formData.detail=[]
    $scope.detailData={}
    $scope.detailData.qty=1
    $scope.detailData.stock=0
    $scope.counter=0
    $scope.formData.company_id=$rootScope.findJsonId(id,$scope.data.warehouse).company_id

    $scope.rack_from=[]
    $scope.rack_to=[]
    angular.forEach($scope.data.rack,function(val,i) {
      if (val.warehouse_id==id) {
        $scope.rack_from.push({id:val.id,name:val.code})
      }
    })
  }
  $scope.changeWarehouseTo=function(id) {
    $scope.rack_to=[]
    angular.forEach($scope.data.rack,function(val,i) {
      if (val.warehouse_id==id) {
        $scope.rack_to.push({id:val.id,name:val.code})
      }
    })
  }

  $scope.create =  function() {
      $http.get(baseUrl+'/operational_warehouse/putaway/create').then(function(data){
          $scope.data=data.data
      },function(error){
          $scope.create()
          console.log(error)
      })
  }
  $scope.create()


  $scope.counter=0
  $scope.appendTable=function() {
    $scope.isItemExists = true;
    var dt=$scope.detailData
    var fail=0
    angular.forEach($scope.formData.detail,function(val,i) {
      if (!val) {
        return;
      }
      if (val.item_id==dt.item_id) {
        toastr.error("Item sudah ditambahkan","Maaf");
        fail=1;
      }
    })
    if (fail) {
      return null
    }
    var html=""
    var item=$rootScope.findJsonId(dt.item_id,$scope.data.item)

    $scope.formData.detail.push({
      item_id:dt.item_id,
      warehouse_receipt_detail_id:dt.warehouse_receipt_detail_id,
      qty:dt.qty,
      price:dt.price
    })

    html+="<tr id='row-"+$scope.counter+"'>"
    html+="<td>"+item.code+"</td>"
    html+="<td>"+$scope.detailData.item_name+"</td>"
    html+="<td class='text-right'>"+$filter('number')(dt.qty)+"</td>"
    html+="<td><a ng-click='deleteAppend("+$scope.counter+")'><span class='fa fa-trash'></span></a></td>"
    html+="</tr>"

    $('#appendTable').append($compile(html)($scope))
    $scope.counter++
    $scope.resetDetail()
    $scope.hitungAppend()
  }

  $scope.resetDetail=function() {
    $scope.detailData={}
    $scope.detailData.qty=1
    $scope.detailData.stock=0
  }

  $scope.deleteAppend=function(id) {
    $('#row-'+id).remove()
    delete $scope.formData.detail[id]
   
    $scope.hitungAppend()
  }

  $scope.hitungAppend=function() {
    var count=0
    angular.forEach($scope.formData.detail,function(val,i) {
      if (!val) {
        return;
      }
      count++
    })
    if (count==0) {
      $scope.disBtn=true
      $scope.isItemExists=false
    } else {
      $scope.disBtn=false
      $scope.isItemExists=true
    }
  }

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $http.post(baseUrl+'/operational_warehouse/putaway',$scope.formData).then(function(data) {
          // $('#revisiModal').modal('hide');
          $timeout(function() {
            $state.go('operational_warehouse.putaway');
          },1000)
          toastr.success("Data Berhasil Disimpan !");
          $scope.disBtn=false;
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
app.controller('opWarehousePutawayShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail";

    $scope.show = function() {
        $http.get(baseUrl+'/operational_warehouse/putaway/'+$stateParams.id).then(function(data){
            $scope.item=data.data.item
            $scope.detail=data.data.detail
        },function(error){
            console.log(error)
        })
    }
    $scope.show()

    $scope.status=[
        {id:1,name:'<span class="badge badge-success">Pengajuan</span>'},
        {id:2,name:'<span class="badge badge-primary">Item Out (On Transit)</span>'},
        {id:3,name:'<span class="badge badge-info">Item Receipt (Done)</span>'},
    ]

  $scope.editDetail=function(json) {
    console.log(json)
    $('#editModal').modal()
    $scope.editData={}
    $scope.editData.id=json.id
    $scope.editData.qty=json.qty
  }

  $scope.deleteDetail=function(id) {
    var cofs=confirm("Apakah anda yakin ?")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/putaway/delete_detail/'+id).then(function(data) {
      // $('#revisiModal').modal('hide');
      $timeout(function() {
        $state.reload();
      },1000)
      toastr.success("Data Berhasil Dihapus !");
      $scope.disBtn=false;
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

  $scope.submitEdit=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/operational_warehouse/putaway/store_detail',$scope.editData).then(function(data) {
      $('#editModal').modal('hide');
      $scope.show()
      toastr.success("Data Berhasil Disimpan !");
      $scope.disBtn=false;
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

  $scope.approve=function() {
    var cofs=confirm("Apakah anda yakin ? barang akan dikeluarkan dalam gudang!")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/putaway/item_out/'+$stateParams.id).then(function(data) {
      // $('#revisiModal').modal('hide');
      $scope.show()
      toastr.success("Data Berhasil Disimpan !");
      $scope.disBtn=false;
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
  $scope.item_in=function() {
    var cofs=confirm("Apakah anda yakin ? barang akan dimasukkan dalam gudang!")
    if (!cofs) {
      return false;
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational_warehouse/putaway/item_in/'+$stateParams.id).then(function(data) {
      $scope.show() 
      toastr.success("Data Berhasil Disimpan !");
      $scope.disBtn=false;
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
})
