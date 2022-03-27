app.controller('invPickingOrder',function($http,$rootScope,$scope,$state,$stateParams,$compile,$filter) {
  $rootScope.pageTitle="Picking Order"
  $scope.status=[
    {id:1,name:`<span class="label label-warning">Draft</span>`},
    {id:2,name:`<span class="label label-primary">On Picking</span>`},
    {id:3,name:`<span class="label label-success">Posted</span>`}
  ]

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[7,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/inventory/picking_order_datatable',
      // data : function(request) {
      //   request['company_id'] = $scope.formData.company_id;
      //   request['warehouse_id'] = $scope.formData.warehouse_id;
      //   request['status'] = $scope.formData.status;
      //   request['start_date'] = $scope.formData.start_date;
      //   request['end_date'] = $scope.formData.end_date;
      //
      //   return request;
      // }
    },
    columns:[
      {data:"code",name:"p.code"},
      {data:"customer",name:"cu.name"},
      {data:"warehouse",name:"w.name"},
      {data:"company",name:"c.name"},
      {
          data:null,
          orderable:false,
          searchable:false,
          render : resp => $filter('fullDate')(resp.date_transaction)
      },
      {data:"staff",name:"st.staff"},
      {data:null,name:"p.status",render:function(e) {
        return $scope.status.find(d => d.id==e.status).name
      }},
      {data:null,name:"p.id", className : 'text-center',render: function(e) {
        return `<a ui-sref="inventory.picking_order.show({id:${e.id}})"><span class="fa fa-folder-o"></span></a>`
      }}
    ],
    createdRow: function(row, data, dataIndex) {
      $(row).find('td').attr('ui-sref', 'inventory.picking_order.show({id:' + data.id + '})')
      $(row).find('td:last-child').removeAttr('ui-sref')
      $compile(angular.element(row).contents())($scope);
    }
  });

})
app.controller('invPickingOrderCreate',function($http,$rootScope,$scope,$state,$stateParams,$compile,$filter) {
  $rootScope.pageTitle="Picking Order Create"
  $scope.data={}
  $scope.warehouses=[]
  $scope.formData={}
  $scope.formData.company_id=null
  $http.get(`${baseUrl}/inventory/picking_order/create`).then(function(e) {
    $scope.data=e.data
  })
  $scope.$watch('formData.company_id',function(val) {
    $scope.warehouses = $scope.data.warehouse.filter(e => e.company_id==val)
  })
  $scope.submitForm=function() {
    $http.post(`${baseUrl}/inventory/picking_order`,$scope.formData).then(function(e) {
      $state.go('inventory.picking_order.show',{id: e.data.id})
    }).catch(function(e) {
      toastr.warning(e.data.message,`Oops!`);
    })
  }
})
app.controller('invPickingOrderShow',function($http,$rootScope,$scope,$state,$stateParams,$compile,$filter) {
  $rootScope.pageTitle="Detail Picking";
  $scope.formData={}
  $scope.formData.detail=[]
  $scope.show=function() {
    $http.get(baseUrl+'/inventory/picking_order/'+$stateParams.id).then(function(data){
      $scope.item=data.data.item
      $scope.detail=data.data.detail
      $scope.formData.detail=[]
      for (var i = 0; i < data.data.detail.length; i++) {
        let vv = data.data.detail[i];
        $scope.formData.detail.push( Object.assign({},{...vv,...{qty_realisation:vv.qty}}) )
      }
    },function(error){
      console.log(error)
    })
  }
  $scope.show();
  $scope.importModal=function() {
    $('#importModal').modal()
  }
  $scope.isRealisation=false
  $scope.status=[
    {id:1,name:`<span class="label label-warning">Draft</span>`},
    {id:2,name:`<span class="label label-primary">On Picking</span>`},
    {id:3,name:`<span class="label label-success">Posted</span>`}
  ]
  $scope.realisation=function() {
    if ($scope.formData.detail.length<1) {
      return toastr.error(`Item list is not available`);
    }
    $scope.isRealisation=true
  }
  $scope.cancelRealisation=function() {
    $scope.isRealisation=false
  }
  $scope.importSubmit=function() {
    $.ajax({
      type: `post`,
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url: `${baseUrl}/api/inventory/picking_order/${$stateParams.id}/import`,
      contentType: false,
      cache: false,
      processData: false,
      data: new FormData($('#forms')[0]),
      dataType:`json`,
      success: function(e) {
        $('#importModal').modal('hide')
        let errorhtml = ``
        if(e.errors.length>0) {
          for (var i = 0; i < e.errors.length; i++) {
            let val = e.errors[i]
            errorhtml+=`- ${val}`
          }
          toastr.warning(errorhtml)
        }
        if (e.total_imported>0) {
          toastr.success(`${e.total_imported} Data was successfully imported`)
        }
        $scope.$apply(function() {
          $scope.show()
        })
      },
      error:function(xhr) {
        toastr.error(`${xhr.data.message}`)
      }
    })
  }
  $scope.changeQtyRealisation=function(row) {
    let val = Object.assign({},$scope.formData.detail[row])
    if (val.qty_realisation>val.qty) {
      toastr.warning("Realisation value must be not higher than quantity")
      Object.assign($scope.formData.detail[row],{qty_realisation:val.qty})
    }
  }
  $scope.storeRealisation=function() {
    const cof = confirm(`Are You Sure ?`);
    if (cof) {
      $http.post(`${baseUrl}/inventory/picking_order/realisation/${$stateParams.id}`,$scope.formData).then(function(e) {
        toastr.success("Data saved successfully")
        $scope.show()
        $scope.isRealisation=false
      })
    }
  }
  $scope.postCancelRealisation=function() {
    const cof = confirm(`Are You Sure ?`);
    if (cof) {
      $http.post(`${baseUrl}/inventory/picking_order/cancel_realisation/${$stateParams.id}`).then(function(e) {
        toastr.success("Done")
        $scope.show()
        $scope.isRealisation=false
      })
    }
  }
  $scope.posting=function() {
    const cof = confirm(`Are You Sure ?`);
    if (cof) {
      $http.post(`${baseUrl}/inventory/picking_order/posting/${$stateParams.id}`).then(function(e) {
        toastr.success("Picking Posted Successfully")
        $scope.show()
        $scope.isRealisation=false
      })
    }
  }
  $scope.row=null
  $scope.formEdit={}
  $scope.editQty=function(row) {
    let fd = $scope.formData.detail[row]
    $scope.row = row
    $scope.formEdit = {
      id: fd.id,
      qty_delivered: fd.qty_delivered
    }
    $('#modalQtyRealisation').modal()
  }
  $scope.saveUpdateQty=function() {
    $http.post(`${baseUrl}/inventory/picking_order/update_qty`,$scope.formEdit).then(function(e) {
      $('#modalQtyRealisation').modal('hide')
      $scope.show()
    })
  }
})
