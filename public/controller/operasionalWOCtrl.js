app.controller('operasionalWO', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Work Order";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData = {};
  $http.get(baseUrl+'/marketing/work_order').then(function(data) {
    $scope.data=data.data;
  });
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[7,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [
      {
        extend : 'excel',
        enabled : true,
        text : '<span class="fa fa-file-excel-o"></span> Export Excel',
        className : 'btn btn-default btn-sm',
        filename : 'Work Order - '+new Date(),
        messageTop : 'Work Order ',
        sheetName : 'Data',
        title : 'Work Order '
      }
    ],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/work_order_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['company_id'] = $scope.formData.company_id;
        request['status'] = $scope.formData.status;

        return request;
      },
      dataSrc: function(d) {
          $('.ibox-content').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"company.name",name:"company.name"},
      {data:"code",name:"code"},
      {data:"customer.name",name:"customer.name",className:"font-bold"},
      {
        data:null,
        name:"created_at",
        render:resp => $filter('fullDate')(resp.created_at)
      },
      {data:"quotation.no_contract",name:"quotation.no_contract",className:"font-bold"},
      {data:"total_job_order",name:"total_job_order",className:"text-right"},
      {data:"status",name:"status",className:""},
      {data:"action_operasional",name:"action_operasional",className:"text-center"},
      // {
      //     data: null,
      //     render : function(resp) {
      //       var action = $('<div>' + resp.action_operasional + '</div>');
      //       action.find('a:eq(1)').remove();
      //       var outp = action.html();
      //       outp = outp.replace(/marketing/g, 'operational');
      //       return outp;
      //     },
      //     name:"created_at",
      //     className:"text-center"
      // },
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo( '#export_button' );

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/work_order_export?';
    url += params;
    location.href = url; 
  }

  $scope.showCustomer = function() {
      $http.get(baseUrl+'/contact/contact/customer').then(function(data) {
        $scope.customer=data.data;
      }, function(){
          $scope.showCustomer()
      });
  }
  $scope.showCustomer()

  $http.get(baseUrl+'/marketing/work_order').then(function(data) {
    $scope.data=data.data
  })

  $scope.requestWO=function() {
    $scope.woData={}
    $scope.woData.company_id=authUser.company_id
    $scope.woData.create_by=authUser.id
    $scope.woData.date=dateNow
    $('#requestWOmodal').modal();
  }

  $scope.submitRequest=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/work_order/store_draft',$scope.woData).then(function(data) {
      // $state.go('operational.job_order');
      $('#requestWOmodal').modal('hide');
      $timeout(function() {
        $state.reload();
      },1000)
      toastr.success("Permintaan Pembuatan Work Order telah diajukan !");
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

  $scope.searchData = function() {
    oTable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }
});
app.controller('operasionalWOShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Work Order";
  $('.ibox-content').addClass('sk-loading');

  $scope.states=$state;
  $scope.stateParams=$stateParams;
  $scope.status=[
    {id:1,name:'Proses'},
    {id:2,name:'Selesai'},
  ];

  $scope.imposition=[
    {id:1,name:'Kubikasi'},
    {id:2,name:'Tonase'},
    {id:3,name:'Item'},
    {id:4,name:'Borongan'},
  ]
  $scope.qtyData={}
  $scope.editQty=function() {
    $scope.qtyData.qty=$scope.item.qty
    $('#modalEditQty').modal()
  }

  $scope.submitQty=function() {
    $http.post(baseUrl+'/marketing/work_order/store_qty/'+$stateParams.id,$scope.qtyData).then(function(data) {
      $('#modalEditQty').modal('hide')
      toastr.success("Qty Borongan berhasil dirubah","Selamat !")
      $timeout(function() {
        $state.reload()
      },1000)
    })
  }

  $http.get(baseUrl+'/marketing/work_order/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;
    $scope.detail_jo=data.data.detail_jo;

    $scope.progress_name=[]
    angular.forEach($scope.detail_jo, function(val,i) {
      var sp=val.kpi_status;
      if (sp.is_done && sp.type==1) {
        $scope.progress_name.push('Selesai')
      } else {
        $scope.progress_name.push('Proses')
      }
    })

    $scope.imposition_name_arr=[]
    angular.forEach(data.data.detail,function(jsn,i) {
      if (jsn.quotation_detail_id) {
        var val=jsn.quotation_detail
        if ($rootScope.in_array(val.service_type_id,[6,7])) {
          $scope.imposition_name_arr.push(val.piece.name);
        } else if (val.service_type_id==2) {
          $scope.imposition_name_arr.push("Kontainer");
        } else if (val.service_type_id==3) {
          $scope.imposition_name_arr.push("Unit");
        } else {
          $scope.imposition_name_arr.push($rootScope.findJsonId(val.imposition,$scope.imposition).name);
        }
      } else {
        var val=jsn.price_list
        if ($rootScope.in_array(val.service_type_id,[6,7])) {
          $scope.imposition_name_arr.push(val.piece.name);
        } else if (val.service_type_id==2) {
          $scope.imposition_name_arr.push("Kontainer");
        } else if (val.service_type_id==3) {
          $scope.imposition_name_arr.push("Unit");
        } else {
          $scope.imposition_name_arr.push("Tonase/Kubikasi/Item");
        }
      }
    })
    $('.ibox-content').removeClass('sk-loading');
  })

  // $timeout(function() {
  //   if ($state.current.name=="marketing.work_order.show") {
  //     $state.go('marketing.work_order.show.detail',{id:$stateParams.id},{location:false});
  //   }
  // },2000)
});
app.controller('operasionalWOShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Work Order";
  $scope.imposition=$scope.$parent.imposition;
  $scope.formData={}
  $scope.item={}
  $scope.baseUrl=baseUrl;
  $scope.stateParams=$stateParams;

  $http.get(baseUrl+'/marketing/work_order/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;

    $scope.status_proses=[]
    angular.forEach($scope.detail,function(val,i) {
      if (val.is_done) {
        $scope.status_proses.push('<span class="badge badge-success">Selesai</span>')
      } else {
        $scope.status_proses.push('<span class="badge badge-warning">Proses</span>')
      }
    })
  })

  $scope.quotationAdd=function() {
    $http.get(baseUrl+'/marketing/work_order/add_detail/'+$stateParams.id).then(function(data) {
      $scope.quotationData=data.data;
      $scope.formData.detail=[]

      angular.forEach(data.data, function(val,i) {
        if (val.service_type_id==6) {
          var imp=val.piece_name;
        } else if (val.service_type_id==2||val.service_type_id==3) {
          var imp='Unit'
        } else {
          var imp=$rootScope.findJsonId(val.imposition,$scope.imposition).name
        }
        $scope.formData.detail.push({
          include:1,
          imposition_name:imp
        });

      })
    });
  }

  $scope.editDetail=function(jsn) {
    $scope.editData={}
    $scope.editData.description=jsn.description;
    $scope.editData.wod_id=jsn.id;
    $scope.editData.qty=jsn.qty;
    $('#editModal').modal('show');
  }

  $scope.approveDetail=function(id) {
    var cofs=confirm("Apakah anda yakin ingin merubah status menjadi Selesai ?");
    if(cofs) {
      
      $http.post(baseUrl+'/marketing/work_order/approve_detail/'+id).then(function(data) {
        $state.reload()
        toastr.success("Work Order telah diselesaikan","Selamat !")
      });
    }
  }

  $scope.submitEdit=function() {
    $http.post(baseUrl+'/marketing/work_order/store_edit_detail/',$scope.editData).then(function(data) {
      $('#editModal').modal('hide');
      $timeout(function() {
        $state.reload()
      },500)
      toastr.success("Data Berhasil Disimpan","Selamat !")
    });
  }

  var qTable = $('#quotation_datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/quotation_detail_datatable',
      data: function(d) {
        d.quotation_id=$scope.item.quotation_id;
        d.no_service_4=1;
      }
    },
    columns:[
      {data:"action_choose",name:"id"},
      {data:"service",name:"service",className:"font-bold"},
      {data:"route_name",name:"route_name"},
      {data:"commodity_name",name:"commodity_name"},
      {data:"vehicle_type_name",name:"vehicle_type_name",className:""},
      {data:"container_type_name",name:"container_type_name",className:""},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  var pTable = $('#price_list_datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/price_list_datatable',
      data:function(d) {
        d.disable4=true;
      }
    },
    columns:[
      {data:"action_choose2",name:"id",className:"text-center"},
      {data:"code",name:"code"},
      {data:"route.name",name:"route.name"},
      {data:"name",name:"name"},
      {data:"commodity.name",name:"commodity.name"},
      {data:"service.name",name:"service.name"},
      {data:"moda.name",name:"moda.name"},
      {data:"vehicle_type.name",name:"vehicle_type.name"},
      {data:"container_type.full_name",name:"container_type.name"}
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });


  $scope.chooseKontrak=function(id,code) {
    var cofs=confirm("Apakah anda ingin menambahkan list ini ?");
    if (cofs) {
      $http.post(baseUrl+'/marketing/work_order/store_detail/'+$stateParams.id,{quotation_detail_id:id}).then(function(data) {
        $('#quotationDataModal').modal('hide');
        $timeout(function() {
          $state.reload();
        },1000)
      })
    }
  }
  $scope.choosePriceList=function(jsn) {
    var cofs=confirm("Apakah anda ingin menambahkan list ini ?");
    if (cofs) {
      $http.post(baseUrl+'/marketing/work_order/store_detail/'+$stateParams.id,{price_list_id:jsn.id}).then(function(data) {
        $('#priceDataModal').modal('hide');
        $timeout(function() {
          $state.reload();
        },1000)
      })
    }
  }

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/work_order/delete_detail/'+ids,{_token:csrfToken}).then(function success(data) {
        $state.reload();
        // oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.addFromQuotation=function() {
    qTable.ajax.reload();
    $('#quotationDataModal').modal('show');
  }
  $scope.addFromPrice=function() {
    pTable.ajax.reload();
    $('#priceDataModal').modal('show');
  }

  $scope.submitQuotationData=function() {
    $http.post(baseUrl+'/marketing/work_order')
  }

});
app.controller('operasionalWOShowJobOrder', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Work Order";
  $scope.detail_jo=$scope.$parent.detail_jo;
  $scope.imposition=$scope.$parent.imposition;
  $scope.status_approve=$scope.$parent.progress_name;
});
app.controller('operasionalWOCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Work Order";
  $('.ibox-content').addClass('sk-loading');

  $scope.imposition=[
    {id:1,name:'Kubikasi'},
    {id:2,name:'Tonase'},
    {id:3,name:'Item'},
    {id:4,name:'Borongan'},
  ]

  $scope.formData={}
  $scope.formData.detail=[];
  $scope.formData.date=dateNow;
  $scope.formData.company_id=compId;
  $scope.formData.type_tarif=1;
  $scope.disable4=false;
  $scope.showPL=true;

  $http.get(baseUrl+'/marketing/work_order/create').then(function(data) {
    $scope.data=data.data;
    $scope.companyChange(compId);
    $('.ibox-content').removeClass('sk-loading');
  })

  // $scope.companyChange=function(id) {
  //   $scope.customers=[];
  //   angular.forEach($scope.data.customer,function(val,i) {
  //     if (val.company_id==id) {
  //       $scope.customers.push({
  //         id:val.id, name:val.name
  //       })
  //     }
  //   })
  // }

  $scope.resetTable=function() {
    $scope.formData.detail=[];
    $scope.counter=0
    $('#appendTable tbody').empty()
    $scope.disable4=false;
    $scope.showPL=true;
  }

  $scope.changeType=function(id,formData) {
    $scope.formData={};
    $scope.formData.company_id=formData.company_id;
    $scope.formData.customer_id=formData.customer_id;
    $scope.formData.type_tarif=id;
    $scope.formData.date=formData.date;
    $scope.formData.name=formData.name;
    $scope.qtyDiv=false;
    $scope.formData.qty=0;
    if (id==1) {
      $scope.div_item_kontrak=true;
      $scope.div_type_layanan=false;
    } else {
      $scope.div_item_kontrak=false;
      $scope.div_type_layanan=true;
    }
    $scope.resetTable()
  }

  var contractTable = $('#contract_datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[4,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/inquery_datatable',
      data: function(d) {
        d.status_approve=4;
        d.customer_id=$scope.formData.customer_id;
        d.is_active=1;
      }
    },
    columns:[
      {data:"action_choose",name:"id",className:"text-center"},
      {data:"name",name:"name"},
      {data:"code",name:"code"},
      {data:"no_contract",name:"no_contract"},
      {data:"date_end_contract",name:"date_end_contract"},
      {data:"customer.name",name:"customer.name"},
      {data:"customer_stage.name",name:"customer_stage.name"},
      {data:"sales.name",name:"sales.name"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  var priceListTable = $('#price_list_datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[6,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/price_list_datatable',
      data:function(d) {
        d.disable4=$scope.disable4;
      }
    },
    columns:[
      {data:"action_choose2",name:"id",className:"text-center"},
      {data:"code",name:"code"},
      {data:"route.name",name:"route.name"},
      {data:"name",name:"name"},
      {data:"commodity.name",name:"commodity.name"},
      {data:"piece.name",name:"piece.name"},
      {data:"service.name",name:"service.name"},
      {data:"service_type.name",name:"service_type.name"},
      {data:"moda.name",name:"moda.name"},
      {data:"vehicle_type.name",name:"vehicle_type.name"},
      {data:"price_full",name:"price_full"}
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.cariKontrak=function() {
    if (!$scope.formData.customer_id) {
      return toastr.error("Anda Harus Memilih Customer");
    }
    contractTable.ajax.reload(function() {
      $('#modalContract').modal('show');
    });
  }
  $scope.cariTarif=function() {
    if (!$scope.formData.customer_id) {
      return toastr.error("Anda Harus Memilih Customer");
    }
    priceListTable.ajax.reload(function() {
      $('#modalPriceList').modal('show');
    });
  }
  $scope.checking=function(jsn) {
    var adaNo4=false;
    angular.forEach($scope.formData.detail,function(vls,x) {
      if (adaNo4) {
        return;
      }
      if (vls.service_type_id!=4 && vls.include) {
        adaNo4=true
      }
    })
    // console.log(adaNo4)

    angular.forEach($scope.formData.detail, function(val,i) {
      if (val.quotation_detail_id==jsn.quotation_detail_id) {
        return;
      }
      if (jsn.include) {
        // jika centang
        if (jsn.service_type_id==4) {
          val.disabled=1
          val.include=0
        } else {
          if (val.service_type_id==4) {
            val.disabled=1
            val.include=0
          }
        }
      } else {
        // jika tidak centang
        if (jsn.service_type_id==4) {
          val.disabled=0
          val.include=0
        } else {
          if (val.service_type_id==4 && !adaNo4) {
            val.disabled=0
            val.include=0
            return null;
          }
        }

      }
    })
  }

  $scope.chooseKontrak=function(value) {
    var html="";
    $http.get(baseUrl+'/marketing/work_order/cari_detail_kontrak/'+value.id).then(function(data) {
      dt=data.data.item;
      $scope.formData.qty=0;
      if (dt.bill_type==2) {
        $scope.qtyDiv=true;
        $scope.formData.qty=1;
        if (dt.imposition==1) {
          $scope.qtyTitle="Jumlah Kubikasi";
        } else if (dt.imposition==2) {
          $scope.qtyTitle="Jumlah Tonase";
        } else if (dt.imposition==3){
          $scope.qtyTitle="Jumlah Item";
        } else {
          $scope.qtyDiv=false;
        }
      } else {
        $scope.qtyDiv=false;
      }
      angular.forEach(data.data.detail, function(val,i) {
        if (val.service_type_id==6) {
          var imp=val.piece_name;
        } else if (val.service_type_id==2||val.service_type_id==3) {
          var imp='Unit'
        } else {
          if (dt.bill_type==2) {
            var imp=$rootScope.findJsonId(dt.imposition,$scope.imposition).name
          } else {
            var imp=$rootScope.findJsonId(val.imposition,$scope.imposition).name
          }
        }
        var price=val.price_contract_tonase+val.price_contract_volume+val.price_contract_item+val.price_contract_full;

        html+='<tr>'
        html+='<td class="text-center">'
        html+='<div class="checkbox checkbox-inline checkbox-primary">'
        html+='<input ng-change="checking(formData.detail['+i+'])" ng-disabled="formData.detail['+i+'].disabled" type="checkbox" id="tr'+i+'" ng-model="formData.detail['+i+'].include" ng-true-value="1" ng-false-value="0">'
        html+='<label id="tr'+i+'"></label>'
        html+='</div>'
        html+='</td>'
        html+='<td>'+val.service.name+'</td>'
        html+='<td>'+(val.route?val.route.name:"")+'</td>'
        html+='<td>'+(val.commodity?val.commodity.name:"")+'</td>'
        html+='<td>'+(val.vehicle_type?val.vehicle_type.name:"")+'</td>'
        html+='<td>'+(val.container_type?val.container_type.full_name:"")+'</td>'
        html+='<td>'+imp+'</td>'
        html+='<td class="text-right">'+$filter('number')(price)+'</td>'
        html+='</tr>'

        $scope.formData.detail.push({
          quotation_detail_id:val.id,
          include:0,
          service_type_id:val.service_type_id,
          disabled:0
        })
      });
      $scope.formData.contract_code=value.no_contract;
      $scope.formData.contract_id=value.id;
      $('#appendTable tbody').html($compile(html)($scope))
    })
    $('#modalContract').modal('hide');
  }

  $scope.counter=0
  $scope.choosePriceList=function(val) {
    // console.log(value)
    if (val.service_type_id==6) {
      var imp=val.piece_name;
    } else if (val.service_type_id==2||val.service_type_id==3) {
      var imp='Unit'
    } else {
      var imp='-'
    }
    if (val.service_type_id==1) {
      var price="";
      price+=$filter('number')(val.price_tonase)+' (Kg)<br>'
      price+=$filter('number')(val.price_volume)+' (m3)<br>'
      price+=$filter('number')(val.price_item)+' (item)<br>'
    } else {
      var price=$filter('number')(val.price_full);
    }

    var html=""
    html+='<tr id="row-'+$scope.counter+'">'
    html+='<td class="text-center">'
    html+='<a ng-click="deleteAppend('+$scope.counter+')"><i class="fa fa-trash"></i></a>'
    html+='</td>'
    html+='<td>'+ val.name + ' / ' + val.service.name + '</td>'
    html+='<td>'+(val.route?val.route.name:"")+'</td>'
    html+='<td>'+(val.commodity?val.commodity.name:"")+'</td>'
    html+='<td>'+(val.vehicle_type?val.vehicle_type.name:"")+'</td>'
    html+='<td>'+(val.container_type?val.container_type.full_name:"")+'</td>'
    html+='<td>'+imp+'</td>'
    html+='<td class="text-right">'+price+'</td>'
    html+='</tr>'

    $scope.formData.detail.push({
      price_list_id:val.id,
      include:1,
      service_type_id:val.service_type_id
    })

    // disable pencarian tipe 4
    if (val.service_type_id==4) {
      $scope.disable4=true;
      $scope.showPL=false;
    } else {
      $scope.disable4=true;
    }
    $scope.formData.price_list_code=val.code+' '+val.name;
    $scope.formData.price_list_id=val.id;
    $('#appendTable tbody').append($compile(html)($scope))
    $('#modalPriceList').modal('hide');
    $scope.counter++
  }

  $scope.deleteAppend=function(id) {
    delete $scope.formData.detail[id];
    $('#row-'+id).remove()
    delete $scope.formData.price_list_id
    delete $scope.formData.price_list_code

    angular.forEach($scope.formData.detail, function(val,i) {
      if (!val) {
        return;
      }
      if (val.service_type_id==4) {
        $scope.disable4=true;
        $scope.showPL=false;
        return null;
      }
    })
    $scope.disable4=false;
    $scope.showPL=true;
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/work_order',$scope.formData).then(function(data) {
      $state.go('marketing.work_order');
      toastr.success("Data Berhasil Disimpan!");
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
