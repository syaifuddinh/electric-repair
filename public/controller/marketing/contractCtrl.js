app.controller('marketingContract', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle = $rootScope.solog.label.contract.title;
  $('.ibox-content').addClass('sk-loading');
  $scope.formData = {};
  $scope.contact_id = $stateParams.id

  $scope.searchData = function() {
    $scope.options.datatable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    $scope.options.datatable.ajax.reload();
  }

});
app.controller('marketingContractShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail";
  $('.ibox-content').addClass('sk-loading');
  $scope.totalCost=[]
  $http.get(baseUrl+'/marketing/contract/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;

    angular.forEach($scope.data.details,function(val,i) {
      var totalAmount=0
      var totalPrice=0;
      angular.forEach(val.cost_details, function(xx,x) {
        totalAmount+=xx.total
        totalPrice+=xx.cost
      })
      $scope.totalCost.push({total:totalAmount,cost:totalPrice})
    })
    $('.ibox-content').removeClass('sk-loading');
    console.log($scope.totalCost)
  });
  $scope.state=$state
  $scope.stateParams=$stateParams
  // if ($state.current.name=="marketing.contract.show") {
  //   $state.go('marketing.contract.show.item',{id:$stateParams.id});
  // }

  $scope.cancel=function() {
    var cont=confirm("Apakah anda yakin ingin membatalkan kontrak ?");
    if (cont) {
      $.ajax({
        type: "post",
        url: baseUrl+'/marketing/contract/'+$stateParams.id+'/cancel?_token='+csrfToken,
        success: function(data){
          toastr.success("Kontrak Dibatalkan.","Berhasil!");
          $state.go('marketing.inquery');
        },
      });
    }
  }

  $scope.amandemen=function() {
    var cfs=confirm("Apakah anda yakin untuk Amandemen kontrak ini ?");
    if (cfs) {
      $http.post(baseUrl+'/marketing/contract/clone_amandemen/'+$stateParams.id).then(function(data) {
        $state.go('marketing.contract.amandemen',{id:data.data.id})
      });
    }
  }

  $scope.submitStop=function() {
    var cfs=confirm("Apakah anda yakin untuk menghentikan kontrak ini ?");
    if (cfs) {
      $http.post(baseUrl+'/marketing/contract/stop_contract/'+$stateParams.id,$scope.stopData).then(function(data) {
        $('#modalStop').modal('hide');
        $timeout(function() {
          $state.reload()
        },1000)
      });
    }
  }

  $scope.is_active=[
    {id:1,name:'<span class="badge badge-success">Aktif</span>'},
    {id:0,name:'<span class="badge badge-danger">Tidak Aktif</span>'},
  ]

  $scope.imposition=[
    {id:1,name:'Kubikasi'},
    {id:2,name:'Tonase'},
    {id:3,name:'Item'},
    {id:4,name:'Borongan'},
  ]

  $scope.stopContract=function() {
    $scope.stopData={}
    $('#modalStop').modal('show');
  }

});
app.controller('marketingContractShowItem', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail";
  $scope.formData = {}
  $scope.formData.quotation_id = $stateParams.id
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/marketing/contract/'+$stateParams.id+'/item').then(function(data) {
    $scope.data_item=data.data;

    $scope.imposition_name_arr=[]
    angular.forEach($scope.data_item.details,function(val,i) {
      if ($rootScope.in_array(val.service_type_id,[6,7])) {
        $scope.imposition_name_arr.push(val.piece.name);
      } else if (val.service_type_id==2) {
        $scope.imposition_name_arr.push("Kontainer");
      } else if (val.service_type_id==3) {
        $scope.imposition_name_arr.push("Unit");
      } else {
        $scope.imposition_name_arr.push($rootScope.findJsonId(val.imposition,$scope.imposition).name);
      }
    })
    $('.ibox-content').removeClass('sk-loading');
    var details = data.data.details
    details = details.map(function(val){
        var penawaran = val.price_inquery_full
        if(val.service.service_type_id == 15) {
            penawaran = val.over_storage_price
        } else if(val.service.service_type_id == 14) {
          penawaran = val.price_inquery_full
        } else if((val.service.service_type_id == 12 || val.service.service_type_id == 13) && val.handling_type == 1) {
          if(val.imposition == 1) {
               penawaran = val.price_inquery_handling_volume
          }
          else if(val.imposition == 2) {
               penawaran = val.price_inquery_handling_tonase
          }
          else if(val.imposition == 3) {
               penawaran = val.price_inquery_item
          }
          else {
               penawaran = val.price_inquery_full
          }
        } else if(val.service.service_type_id == 1) {
          if(val.imposition == 1) {
               penawaran = val.price_inquery_handling_volume
               if(!penawaran) {
                    penawaran = val.price_inquery_volume
               }
          }
          else if(val.imposition == 2) {
               penawaran = val.price_inquery_handling_tonase
               if(!penawaran) {
                    penawaran = val.price_inquery_tonase
               }
          }
          else if(val.imposition == 3) {
               penawaran = val.price_inquery_item
               if(!penawaran) {
                    penawaran = val.price_inquery_item
               }
          }
          else {
               penawaran = val.price_inquery_full
          }                
        }
        val['penawaran'] = penawaran
        return val
    })
    $scope.data_item.details = details
  });

  $scope.imposition=[
    {id:1,name:"Kubikasi"},
    {id:2,name:"Tonase"},
    {id:3,name:"Item"},
    {id:4,name:"Borongan"},
  ];

});
app.controller('marketingContractShowCost', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail";
  $('.sk-container').addClass('sk-loading');

  $http.get(baseUrl+'/marketing/contract/'+$stateParams.id+'/cost').then(function(data) {
    $scope.data_item=data.data;
    $('.sk-container').removeClass('sk-loading');
  });
  $scope.total=0;
  $scope.hitungTotal=function(arr) {
    $scope.total+=(arr.total+arr.cost);
  }
});
app.controller('marketingContractShowJo', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail";
  $('.sk-container').addClass('sk-loading');

  $http.get(baseUrl+'/marketing/contract/'+$stateParams.id+'/jo_history').then(function(data) {
    $scope.item=data.data;
    $('.sk-container').removeClass('sk-loading');
  });
});
app.controller('marketingContractShowDocument', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail";
  $('.ibox-content').addClass('sk-loading');
  // console.log($state.current.name);
  $scope.modalUpload=function() {
    $('#modalUpload').modal('show');
  }
  $scope.urls=baseUrl;
  $http.get(baseUrl+'/marketing/inquery/document/'+$stateParams.id).then(function(res) {
    $scope.data=res.data.item;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.uploadSubmit=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/inquery/upload_file/'+$stateParams.id+'?_token='+csrfToken,
      contentType: false,
      cache: false,
      processData: false,
      data: new FormData($('#uploadForm')[0]),
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        $('#modalUpload').modal('hide');
        toastr.success("Data Berhasil Disimpan");
        // $state.go('marketing.inquery.show.document',{id:$stateParams.id});
        $timeout(function() {
          $state.reload();
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
app.controller('marketingContractEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail";
  $('.ibox-content').addClass('sk-loading');

  // console.log($state.current.name);
  $http.get(baseUrl+'/marketing/contract/'+$stateParams.id+'/edit').then(function(data) {
    
    $scope.data=data.data.item;
    $scope.sales=data.data.sales;
    $scope.ori=data.data.editing;
    $scope.formData={
      date_start_contract: $filter('minDate')($scope.ori.date_start_contract),
      date_end_contract: $filter('minDate')($scope.ori.date_end_contract),
      send_type: $scope.ori.send_type,
      sales_id: $scope.ori.sales_id,
      sales_commision: $scope.ori.sales_commision,
      description_contract: $scope.ori.description_contract,
      is_active: $scope.ori.is_active,
      send_type: $scope.ori.send_type,
    }
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.send_type=[
    {id:1,name:"Sekali"},
    {id:2,name:"Per Hari"},
    {id:3,name:"Per Minggu"},
    {id:4,name:"Per Bulan"},
    {id:5,name:"Tidak Tentu"},
  ];

  $scope.is_active=[
    {id:1,name:"Aktif"},
    {id:0,name:"Tidak Aktif"},
  ];

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/contract/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan","Selamat!");
        $state.go('marketing.contract.show.item',{id:$stateParams.id});
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
app.controller('marketingContractAmandemen', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Amandemen Kontrak";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData={}
    $('#item_detail').hide()
    $scope.openService = function() {
          $('.tab-item').hide()
          $('#service_detail').show()
    }

    $scope.openService()

    $scope.openItem = function() {
      $('.tab-item').hide()
      $('#item_detail').show()
    }
    $scope.itemDetail = {}
    $scope.editItemPrice = function() {
        $scope.itemDetail.is_edit = true
        $scope.$broadcast('editItemPriceByQuotation', $stateParams.id)
    }

    $scope.abortItemPrice = function() {
        $scope.itemDetail.is_edit = false
        $scope.$broadcast('abortItemPrice')
    }

    $scope.formData.quotation_id = $stateParams.id

    $scope.imposition=[
        {id:1,name:"Kubikasi"},
        {id:2,name:"Tonase"},
        {id:3,name:"Item"},
        {id:4,name:"Borongan"},
    ];

  $http.get(baseUrl+'/marketing/inquery/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $scope.is_approve_count=0;
    angular.forEach(data.data.details, function(val,i) {
      $scope.is_approve_count+=(val.is_approve?0:1);
    })
    $scope.imposition_name_arr=[]
    angular.forEach(data.data.details,function(val,i) {
      if ($rootScope.in_array(val.service_type_id,[6,7])) {
        $scope.imposition_name_arr.push(val.piece.name);
      } else if (val.service_type_id==2) {
        $scope.imposition_name_arr.push("Kontainer");
      } else if (val.service_type_id==3) {
        $scope.imposition_name_arr.push("Unit");
      } else {
        $scope.imposition_name_arr.push(val.imposition_name);
      }
    })

    var dt=data.data.item;
    var details = data.data.details
    details = details.map(function(val){
        var penawaran = val.price_inquery_full
        if(val.service.service_type_id == 15) {
            penawaran = val.over_storage_price
        } else if(val.service.service_type_id == 14) {
          penawaran = val.price_inquery_full
        } else if((val.service.service_type_id == 12 || val.service.service_type_id == 13) && val.handling_type == 1) {
          if(val.imposition == 1) {
               penawaran = val.price_inquery_handling_volume
          }
          else if(val.imposition == 2) {
               penawaran = val.price_inquery_handling_tonase
          }
          else if(val.imposition == 3) {
               penawaran = val.price_inquery_item
          }
          else {
               penawaran = val.price_inquery_full
          }
        } else if(val.service.service_type_id == 1) {
          if(val.imposition == 1) {
               penawaran = val.price_inquery_handling_volume
               if(!penawaran) {
                    penawaran = val.price_inquery_volume
               }
          }
          else if(val.imposition == 2) {
               penawaran = val.price_inquery_handling_tonase
               if(!penawaran) {
                    penawaran = val.price_inquery_tonase
               }
          }
          else if(val.imposition == 3) {
               penawaran = val.price_inquery_item
               if(!penawaran) {
                    penawaran = val.price_inquery_item
               }
          }
          else {
               penawaran = val.price_inquery_full
          }                
        }
        val['penawaran'] = penawaran
        return val
    })

    $scope.data.details = details

    $scope.formData.is_new=0;
    $scope.formData.name=dt.name;
    $scope.formData.send_type=dt.send_type;
    $scope.formData.bill_type=dt.bill_type;
    $scope.formData.date_start_contract=$filter('minDate')(dt.date_start_contract);
    $scope.formData.date_end_contract=$filter('minDate')(dt.date_end_contract);
    $scope.formData.description_contract=dt.description_contract
    $scope.formData.description_amandemen=dt.description_amandemen
    $scope.formData.imposition=dt.imposition
    $scope.formData.piece_id=dt.piece_id
    $scope.formData.price_full_contract=dt.price_full_contract
    $('.ibox-content').removeClass('sk-loading');
  });

    $scope.addQuotationDetail = function() {
        $rootScope.insertBuffer()
        $state.go('marketing.inquery.show.create_detail', {id:$stateParams.id})
    } 

    $scope.editQuotationDetail = function(id) {
        $rootScope.insertBuffer()
        $state.go('marketing.inquery.show.edit_detail', {id : $stateParams.id, iddetail:id})
    } 

    $scope.send_type=[
        {id:1,name:"Sekali"},
        {id:2,name:"Per Hari"},
        {id:3,name:"Per Minggu"},
        {id:4,name:"Per Bulan"},
        {id:5,name:"Tidak Tentu"},
    ];

    $scope.bill_type=[
        {id:1,name:"Per Pengiriman"},
        {id:2,name:"Borongan"},
    ];

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/inquery/delete_detail/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        // oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
        $state.reload();
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.delete_cost=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/inquery/delete_detail_cost/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.quotation_detail_id=0;

  oTable = $('#detail_datatable').DataTable({
    processing: true,
    serverSide: true,
    ordering: false,
    searching: false,
    paging: false,
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/inquery_detail_cost_datatable',
      data: function(d) {
        d.quotation_detail_id=$scope.quotation_detail_id;
      }
    },
    columns:[
      {data:"cost_type.code",name:"cost_type.code"},
      {data:"cost_type.name",name:"cost_type.name"},
      {data:"vendor.name",name:"vendor.name"},
      {data:"total",name:"total"},
      {data:"cost",name:"cost"},
      {data:"total_cost",name:"total_cost"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.detail_cost=function(ids) {
    $scope.quotation_detail_id=ids;
    // console.log($scope.quotation_detail_id);
    $scope.div_form_detail=false;
    $scope.button_form_detail=true;

    $scope.detailCost={};
    $scope.formCost={};
    $scope.formCost.quotation_detail_id=ids;
    oTable.ajax.reload();
    $http.get(baseUrl+'/marketing/inquery/detail_cost/'+ids).then(function(data) {
      var dts=data.data;
      $scope.detailCost.route=(dts.route_id?dts.route.name:'');
      $scope.detailCost.commodity=(dts.commodity_id?dts.commodity.name:'');
      $scope.detailCost.penawaran=dts.price_inquery_tonase+dts.price_inquery_volume+dts.price_inquery_item+dts.price_inquery_full;
      $scope.detailCost.kontrak=dts.price_contract_tonase+dts.price_contract_volume+dts.price_contract_item+dts.price_contract_full;
      $scope.detailCost.description=dts.description_inquery;
      $scope.detailCost.cost=dts.cost;
      $('#modal_detail').modal('show');
    });
  }

  $scope.cancel_cost=function() {
    $scope.div_form_detail=false;
    $scope.button_form_detail=true;
  }

  $scope.addCost=function() {
    $scope.formCost={};
    $scope.cost_type_data={};
    $scope.formCost.quotation_detail_id=$scope.quotation_detail_id;
    $scope.div_form_detail=true;
    $scope.button_form_detail=false;
  }

  $scope.cost_type_data={};
  $scope.changeCT=function(id) {
    $http.get(baseUrl+'/setting/cost_type/'+id).then(function(res) {
      // console.log(res);
      $scope.cost_type_data=res.data.item;
      $scope.formCost.total=res.data.item.qty;
      $scope.formCost.cost=res.data.item.cost;
      $scope.formCost.total_cost=res.data.item.initial_cost;
      $scope.formCost.is_internal=(res.data.item.vendor_id?0:1);
      $scope.formCost.vendor_id=res.data.item.vendor_id;
    });
  }

  $scope.disBtn=false;
  $scope.submitDetailCost=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/inquery/store_detail_cost/'+$stateParams.id+'?_token='+csrfToken,
      data: $scope.formCost,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $scope.div_form_detail=false;
        $scope.button_form_detail=true;
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

  $scope.save_typing=function() {
    $http.post(baseUrl+'/marketing/contract/save_typing/'+$stateParams.id,$scope.formData).then(function(dt) {

    })
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/contract/store_amandemen/'+$stateParams.id,$scope.formData).then(function(data) {
      toastr.success("Data Berhasil Disimpan!");
      $state.go("marketing.contract.show",{id:$stateParams.id});
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
app.controller('marketingContractAmandemenDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Detail Amandemen Kontrak";
  $scope.params=$stateParams;
  $scope.formData={ minimal_detail: [] };
  $scope.cost_template=[];
  $scope.imposition=[
    {id:1,name:"Kubikasi"},
    {id:2,name:"Tonase"},
    {id:3,name:"Item"},
  ];
  $scope.formData.imposition=1;
  $scope.formData.total=0;
  $scope.formData.is_generate=1;

  $http.get(baseUrl+'/marketing/contract/create').then(function(data) {
    $scope.data=data.data;
    $scope.type_1=data.data.type_1;
    $scope.type_2=data.data.type_2;
    $scope.type_3=data.data.type_3;
    $scope.type_4=data.data.type_4;
    $scope.type_5=data.data.type_5;
    $scope.type_6=data.data.type_6;
    $scope.type_7=data.data.type_7;
  });
  $scope.div_tarif=false;
  $scope.dom_switch=function(ids,company,isedit) {
    // console.log(ids);
    $scope.formData={};
    $scope.formData.service_id=ids;
    $scope.formData.company_id=company;
    $scope.formData.imposition=1;
    $scope.div_cost_template=false;
    $scope.service = $scope.data.service.find(x => x.id == $scope.formData.service_id)
    if ($scope.type_1.indexOf(ids)!==-1) {
      $scope.formData.stype_id=1;
      $scope.formData.min_type=1;
      $scope.formData.price_tonase=0;
      $scope.formData.min_tonase=0;
      $scope.formData.price_volume=0;
      $scope.formData.min_volume=0;
      $scope.formData.price_item=0;
      $scope.formData.min_item=0;
      $scope.div_trayek=true;
      $scope.div_komoditas=true;
      $scope.div_satuan=false;
      $scope.div_moda=true;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=true;
      $scope.div_tarif=false;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_2.indexOf(ids)!==-1) {
      $scope.formData.stype_id=2;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=true;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_cost_template=true;
    } else if ($scope.type_3.indexOf(ids)!==-1) {
      $scope.formData.stype_id=3;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_cost_template=true;
    } else if ($scope.type_4.indexOf(ids)!==-1) {
      $scope.formData.stype_id=4;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_5.indexOf(ids)!==-1) {
      $scope.formData.stype_id=5;
      $scope.div_trayek=false;
      $scope.div_komoditas=true;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=false;
      $scope.div_rack=true;
      $scope.div_storage_tonase=true;
      $scope.div_storage_volume=true;
      $scope.div_handling_tonase=true;
      $scope.div_handling_volume=true;
    } else if ($scope.type_6.indexOf(ids)!==-1) {
      $scope.formData.stype_id=6;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else {
      $scope.formData.stype_id=7;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    }
  }

  $scope.cariTemplate=function() {
    $http.get(baseUrl+'/marketing/inquery/cari_route_cost', {params:{
      'service_id': $scope.formData.service_id,
      'route_id': $scope.formData.route_id,
      'vehicle_type_id': $scope.formData.vehicle_type_id,
      'container_type_id': $scope.formData.container_type_id
    } }).then(function(data) {
      $scope.cost_template=[];
      angular.forEach(data.data, function(val,i) {
        $scope.cost_template.push({
          id:val.id,
          name:val.trayek.name + " ( " +$filter('number')(val.cost)+ " )"
        });
      });
    }, function (err) {
      $scope.cost_template=[];
    });
  }

  $scope.cariTarif=function() {
    var datas = {
      company_id: $scope.formData.company_id,
      service_id: $scope.formData.service_id,
      route_id: $scope.formData.route_id,
      service_type_id: $scope.formData.stype_id,
      moda_id: $scope.formData.moda_id,
      vehicle_type_id: $scope.formData.vehicle_type_id,
      imposition: $scope.formData.imposition,
      container_type_id: $scope.formData.container_type_id,
      rack_id: $scope.formData.rack_id,
      commodity_id: $scope.formData.commodity_id,
    }

    $http.get(baseUrl+'/marketing/price_list/cari_tarif', {params:datas}).then(function(dt) {
      var data=dt.data;
      $scope.formData.price_list_id=data.pl_id;
      if (data.stype==1) {
        $scope.formData.price_imposition=data.tarif;
        $scope.formData.min_imposition=data.min;
        $scope.formData.price_list_price_full=data.tarif;
      } else if (data.stype==5) {
        $scope.formData.price_inquery_tonase=data.tarif_tonase;
        $scope.formData.price_inquery_handling_tonase=data.tarif_handling_tonase;
        $scope.formData.price_inquery_volume=data.tarif_volume;
        $scope.formData.price_inquery_handling_volume=data.tarif_handling_volume;

        $scope.formData.price_list_price_tonase=data.tarif_tonase;
        $scope.formData.price_list_price_volume=data.tarif_volume;
      } else {
        $scope.formData.price_inquery_full=data.tarif;
        $scope.formData.price_list_price_full=data.tarif;
      }
    }, function(err) {
      $scope.formData.price_list_id=null;
      delete $scope.formData.price_imposition;
      delete $scope.formData.min_imposition;
      delete $scope.formData.price_inquery_tonase;
      delete $scope.formData.price_inquery_handling_tonase;
      delete $scope.formData.price_inquery_volume;
      delete $scope.formData.price_inquery_handling_volume;
      delete $scope.formData.price_inquery_full;
      $scope.formData.price_list_price_tonase=0;
      $scope.formData.price_list_price_volume=0;
      $scope.formData.price_list_price_item=0;
      $scope.formData.price_list_price_full=0;
    });
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/inquery/store_detail/'+$stateParams.id+'?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('marketing.contract.amandemen',{id:$stateParams.id});
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

  $scope.minTypeChange = function() {
    $scope.formData.price_tonase = 0;
    $scope.formData.min_tonase = 0;
    $scope.formData.price_volume = 0;
    $scope.formData.min_volume = 0;
    $scope.formData.price_item = 0;
    $scope.formData.min_item = 0;
    $scope.formData.minimal_detail = [];
}

$scope.InsertOrUpdate = 0;
$scope.indexEdit = null;

$scope.addMinMultipleDetail = function () {
    $scope.indexEdit = null;
    $scope.price_tonase = 0;
    $scope.min_tonase = 0;
    $scope.price_volume = 0;
    $scope.min_volume = 0;
    $scope.price_item = 0;
    $scope.min_item = 0;
    $scope.InsertOrUpdate = 0;
    $('#modalMinMultipleDetail').modal('show');
}

$scope.submitFormMinMultipleDetail = function () {
    var preData = {
        price_per_kg: $scope.price_tonase,
        min_kg: $scope.min_tonase,
        price_per_m3: $scope.price_volume,
        min_m3: $scope.min_volume,
        price_per_item: $scope.price_item,
        min_item: $scope.min_item
    };
    if($scope.InsertOrUpdate == 0) {
        $scope.formData.minimal_detail.push(preData);
    }
    else {
        $scope.formData.minimal_detail[$scope.indexEdit] = preData;
    }

    $scope.indexEdit = null;
    $scope.price_tonase = 0;
    $scope.min_tonase = 0;
    $scope.price_volume = 0;
    $scope.min_volume = 0;
    $scope.price_item = 0;
    $scope.min_item = 0;
    $scope.InsertOrUpdate = 0;
    $('#modalMinMultipleDetail').modal('hide');
}

$scope.deleteMinMultipleDetail = function (index) {
    for( var i = 0; i < $scope.formData.minimal_detail.length; i++) { 
        if ( index === i) { 
            $scope.formData.minimal_detail.splice(i, 1); 
        }
    }
}

$scope.editMinMultipleDetail = function (index) {
    $scope.indexEdit = index;
    $scope.price_tonase = $scope.formData.minimal_detail[index].price_per_kg;
    $scope.min_tonase = $scope.formData.minimal_detail[index].min_kg;
    $scope.price_volume = $scope.formData.minimal_detail[index].price_per_m3;
    $scope.min_volume = $scope.formData.minimal_detail[index].min_m3;
    $scope.price_item = $scope.formData.minimal_detail[index].price_per_item;
    $scope.min_item = $scope.formData.minimal_detail[index].min_item;
    $scope.InsertOrUpdate = 1;
    $('#modalMinMultipleDetail').modal('show');
}

});
app.controller('marketingContractAmandemenEditDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Detail Amandemen Kontrak";
  $scope.params=$stateParams;
  $scope.formData={ minimal_detail: [] };
  $scope.cost_template=[];
  $scope.imposition=[
    {id:1,name:"Kubikasi"},
    {id:2,name:"Tonasse"},
    {id:3,name:"Item"},
  ];

  $scope.cariTemplate=function() {
    $http.get(baseUrl+'/marketing/inquery/cari_route_cost', {params:{
      'route_id': $scope.formData.route_id,
      'vehicle_type_id': $scope.formData.vehicle_type_id
    } }).then(function(data) {
      $scope.cost_template=[];
      angular.forEach(data.data, function(val,i) {
        $scope.cost_template.push({
          id:val.id,
          name:val.trayek.name
        });
      });
    }, function (err) {
      $scope.cost_template=[];
    });
  }

  $http.get(baseUrl+'/marketing/inquery/show_detail/'+$stateParams.iddetail).then(function(data) {
    var dt=data.data.item;
    $scope.data=data.data;
    $scope.type_1=data.data.type_1;
    $scope.type_2=data.data.type_2;
    $scope.type_3=data.data.type_3;
    $scope.type_4=data.data.type_4;
    $scope.type_5=data.data.type_5;
    $scope.type_6=data.data.type_6;
    $scope.type_7=data.data.type_7;
    $scope.formData.imposition=dt.imposition;
    $scope.formData.total=dt.total;
    $scope.formData.company_id=dt.header.company_id;
    $scope.formData.is_generate=dt.is_generate;
    $scope.formData.price_inquery_tonase=dt.price_inquery_tonase;
    $scope.formData.price_inquery_volume=dt.price_inquery_volume;
    $scope.formData.price_inquery_item=dt.price_inquery_item;
    $scope.formData.price_inquery_full=dt.price_inquery_full;
    $scope.formData.service_id=dt.service_id;
    $scope.formData.piece_id=dt.piece_id;
    $scope.formData.commodity_id=dt.commodity_id;
    $scope.formData.moda_id=dt.moda_id;
    $scope.formData.vehicle_type_id=dt.vehicle_type_id;
    $scope.formData.rack_id=dt.rack_id;
    $scope.formData.container_type_id=dt.container_type_id;
    $scope.formData.service_type_id=dt.service_type_id;
    $scope.formData.route_id=dt.route_id;
    $scope.formData.price_inquery_handling_tonase=dt.price_inquery_handling_tonase;
    $scope.formData.price_inquery_handling_volume=dt.price_inquery_handling_volume;
    $scope.formData.cost_template=dt.route_cost_id;
    $scope.formData.min_type=dt.min_type ?? 1;
    $scope.formData.minimal_detail = [];
    if (dt.imposition==1) {
      $scope.formData.price_imposition=dt.price_inquery_volume;
      $scope.formData.min_imposition=dt.price_inquery_min_volume;
    } else if (dt.imposition==2) {
      $scope.formData.price_imposition=dt.price_inquery_tonase;
      $scope.formData.min_imposition=dt.price_inquery_min_tonase;
    } else {
      $scope.formData.price_imposition=dt.price_inquery_item;
      $scope.formData.min_imposition=dt.price_inquery_min_item;
    }
    $scope.dom_switch(dt.service_id,dt.header.company_id,true);
    $scope.cariTemplate();

    if($scope.data.item.service_type_id == 1) {
      if($scope.data.item.min_type == 1) {
        $scope.formData.min_tonase = dt.price_contract_min_tonase;
        $scope.formData.price_tonase = dt.price_contract_tonase;
        $scope.formData.min_volume = dt.price_contract_min_volume;
        $scope.formData.price_volume = dt.price_contract_volume;
        $scope.formData.min_item = dt.price_contract_min_item;
        $scope.formData.price_item = dt.price_contract_item;
      }
      else if($scope.data.item.min_type == 2) {
          $scope.formData.minimal_detail = $scope.data.price_list_minimum_detail;
      }
    }
  });

  $scope.cariTarif=function() {
    $.ajax({
      type: "get",
      url: baseUrl+'/marketing/price_list/cari_tarif',
      data: {
        company_id: $scope.formData.company_id,
        service_id: $scope.formData.service_id,
        route_id: $scope.formData.route_id,
        service_type_id: $scope.formData.stype_id,
        moda_id: $scope.formData.moda_id,
        vehicle_type_id: $scope.formData.vehicle_type_id,
        imposition: $scope.formData.imposition,
        container_type_id: $scope.formData.container_type_id,
        rack_id: $scope.formData.rack_id,
        commodity_id: $scope.formData.commodity_id,
      },
      dataType: "json",
      success: function(data) {
        if (data.stype==1) {
          $scope.$apply(function() {
            $scope.formData.price_imposition=data.tarif;
            $scope.formData.min_imposition=data.min;
          })
        } else if (data.stype==5) {
          $scope.$apply(function() {
            $scope.formData.price_inquery_tonase=data.tarif_tonase;
            $scope.formData.price_inquery_handling_tonase=data.tarif_handling_tonase;
            $scope.formData.price_inquery_volume=data.tarif_volume;
            $scope.formData.price_inquery_handling_volume=data.tarif_handling_volume;
          });
        } else {
          $scope.$apply(function() {
            $scope.formData.price_inquery_full=data.tarif;
          });
        }
      },
    });
  }

  $scope.dom_switch=function(ids,company,isedit) {
    // console.log(ids);
    if (isedit==false) {
      $scope.formData={};
      $scope.formData.imposition=1;
    }
    $scope.formData.service_id=ids;
    $scope.formData.company_id=company;
    $scope.div_cost_template=false;
    $scope.service = $scope.data.service.find(x => x.id == $scope.formData.service_id)
    if ($scope.type_1.indexOf(ids)!==-1) {
      $scope.formData.stype_id=1;
      if (isedit==false) {
        $scope.formData.min_type=1;
        $scope.formData.price_tonase=0;
        $scope.formData.min_tonase=0;
        $scope.formData.price_volume=0;
        $scope.formData.min_volume=0;
        $scope.formData.price_item=0;
        $scope.formData.min_item=0;
      }
      $scope.div_trayek=true;
      $scope.div_komoditas=true;
      $scope.div_satuan=false;
      $scope.div_moda=true;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=true;
      $scope.div_tarif=false;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_2.indexOf(ids)!==-1) {
      $scope.formData.stype_id=2;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=true;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_3.indexOf(ids)!==-1) {
      $scope.formData.stype_id=3;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_cost_template=true;
    } else if ($scope.type_4.indexOf(ids)!==-1) {
      $scope.formData.stype_id=4;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_5.indexOf(ids)!==-1) {
      $scope.formData.stype_id=5;
      $scope.div_trayek=false;
      $scope.div_komoditas=true;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=false;
      $scope.div_rack=true;
      $scope.div_storage_tonase=true;
      $scope.div_storage_volume=true;
      $scope.div_handling_tonase=true;
      $scope.div_handling_volume=true;
    } else if ($scope.type_6.indexOf(ids)!==-1) {
      $scope.formData.stype_id=6;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else {
      $scope.formData.stype_id=7;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    }
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/inquery/store_detail/'+$stateParams.id+'/'+$stateParams.iddetail+'?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('marketing.contract.amandemen',{id:$stateParams.id});
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

  $scope.minTypeChange = function() {
    if($scope.data.item.service_type_id == 1) {
        if($scope.formData.min_type == 1) {
            if($scope.data.item.min_type != 1) {
                $scope.formData.price_tonase = 0;
                $scope.formData.min_tonase = 0;
                $scope.formData.price_volume = 0;
                $scope.formData.min_volume = 0;
                $scope.formData.price_item = 0;
                $scope.formData.min_item = 0;
            }
        }
        else if($scope.formData.min_type == 2) {
            if($scope.data.item.min_type != 2) {
                $scope.formData.minimal_detail = [];
            }
        }
    }
}

$scope.InsertOrUpdate = 0;
// $scope.indexEdit = null;
$scope.minimDetailId = null;

$scope.addMinMultipleDetail = function () {
    // $scope.indexEdit = null;
    $scope.price_tonase = 0;
    $scope.min_tonase = 0;
    $scope.price_volume = 0;
    $scope.min_volume = 0;
    $scope.price_item = 0;
    $scope.min_item = 0;
    $scope.InsertOrUpdate = 0;
    $('#modalMinMultipleDetail').modal('show');
}

$scope.submitFormMinMultipleDetail = function () {
    preData = {
        // id: $scope.minimDetailId,
        quotation_detail_id: $stateParams.iddetail,
        price_per_kg: $scope.price_tonase,
        min_kg: $scope.min_tonase,
        price_per_m3: $scope.price_volume,
        min_m3: $scope.min_volume,
        price_per_item: $scope.price_item,
        min_item: $scope.min_item
    };
    var urlReq = '';
    if($scope.InsertOrUpdate == 0) {
        urlReq = baseUrl+'/marketing/inquery/minimum_detail?_token='+csrfToken;
    }
    else {
        urlReq = baseUrl+'/marketing/inquery/minimum_detail/'+$scope.minimDetailId+'?_method=PUT&_token='+csrfToken;
    }

    $.ajax({
        type: "post",
        url: urlReq,
        data: preData,
        success: function(data){
            // if($scope.InsertOrUpdate == 0) {
            //     $scope.formData.minimal_detail.push(preData);
            //     console.log('insert');
            // }
            // else {
            //     $scope.formData.minimal_detail[$scope.indexEdit] = preData;
            //     console.log('update');
            // }
        
            // $scope.indexEdit = null;
            // $scope.minimDetailId = null;
            // $scope.price_tonase = 0;
            // $scope.min_tonase = 0;
            // $scope.price_volume = 0;
            // $scope.min_volume = 0;
            // $scope.price_item = 0;
            // $scope.min_item = 0;
            // $scope.InsertOrUpdate = 0;
            $('#modalMinMultipleDetail').modal('hide');
            toastr.success("Data Berhasil Disimpan");
            $timeout(function() {
                $state.reload();
            },1000);
        },
        error: function(xhr, response, status) {
            if (xhr.status==422) {
                var msgs="";
                $.each(xhr.responseJSON.errors, function(i, val) {
                    msgs+='- '+val+'<br>';
                });
                toastr.warning(msgs,"Validation Error!");
            } else {
                toastr.error(xhr.responseJSON.message,"Error has Found!");
            }
        }
    });
}

$scope.deleteMinMultipleDetail = function (index) {
    var cofs=confirm("Apakah anda yakin ?");
    if (cofs) {
        $http.delete(baseUrl+'/marketing/inquery/minimum_detail/' + $scope.formData.minimal_detail[index].id + '?_token='+csrfToken).then(function(data) {
            // for( var i = 0; i < $scope.formData.minimal_detail.length; i++) { 
            //     if ( index === i) { 
            //         $scope.formData.minimal_detail.splice(i, 1); 
            //     }
            // }
            toastr.success("Data berhasil dihapus !");
            $state.reload();
        });
    }
}

$scope.editMinMultipleDetail = function (index) {
    // $scope.indexEdit = index;
    $scope.minimDetailId = $scope.formData.minimal_detail[index].id
    $scope.price_tonase = $scope.formData.minimal_detail[index].price_per_kg;
    $scope.min_tonase = $scope.formData.minimal_detail[index].min_kg;
    $scope.price_volume = $scope.formData.minimal_detail[index].price_per_m3;
    $scope.min_volume = $scope.formData.minimal_detail[index].min_m3;
    $scope.price_item = $scope.formData.minimal_detail[index].price_per_item;
    $scope.min_item = $scope.formData.minimal_detail[index].min_item;
    $scope.InsertOrUpdate = 1;
    $('#modalMinMultipleDetail').modal('show');
}

});
