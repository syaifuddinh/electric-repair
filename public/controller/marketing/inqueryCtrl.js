app.controller('marketingInquery', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Quotation";
    $scope.formData = {};
    $('.ibox-content').addClass('sk-loading');

    $http.get(baseUrl+'/marketing/opportunity').then(function(data) {
        $scope.data=data.data;
    });

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        order: [[2,'desc']],
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right m-l-sm ',
            filename: 'Marketing - Quotation - ' + new Date,
            sheetName: 'Data',
            title: 'Marketing - Quotation',
            exportOptions: {
              rows: {
                  selected: true
              }
            },
        }],
        ajax : {
          headers : {'Authorization' : 'Bearer '+authUser.api_token},
          url : baseUrl+'/api/marketing/inquery_datatable',
          data: function(request){
            request['is_parent_null'] = 1;
            request['start_date'] = $scope.formData.start_date;
            request['end_date'] = $scope.formData.end_date;
            request['customer_id'] = $scope.formData.customer_id;
            request['customer_stage_id'] = $scope.formData.customer_stage_id;
            request['status_approve'] = $scope.formData.status_approve;

            return request;
          },
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },
        columns:[
          {data:"name",name:"name"},
          {data:"code",name:"code"},
          {
            data:null,
            name:"date_inquery",
            searchable:false,
            render : resp => $filter('fullDate')(resp.date_inquery)
          },
          {data:"customer.name",name:"customer.name"},
          {data:"customer_stage.name",name:"customer_stage.name"},
          {data:"sales.name",name:"sales.name"},
          {data:"no_contract",name:"no_contract"},
          {data:"type_entryy",name:"type_entryy"},
          {data:"status_approve",name:"id"},
          {data:"action",name:"created_at",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('marketing.quotation.detail')) {
                $(row).find('td').attr('ui-sref', 'marketing.inquery.show({id:' + data.id + '})')
                $(row).find('td:last-child').removeAttr('ui-sref')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
    });

    oTable.buttons().container().appendTo('.ibox-tools')

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/quotation_export?';
    url += params;
    location.href = url;
  }

  $scope.searchData = function() {
    oTable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/inquery/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }
});
app.controller('marketingInqueryCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Add Quotation";
    if($stateParams.id) {
        $scope.id = $stateParams.id;
    }
});
app.controller('marketingInqueryEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Quotation";
  $('.ibox-content').addClass('sk-loading');

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
  $scope.imposition=[
    {id:1,name:"Kubikasi"},
    {id:2,name:"Tonase"},
    {id:3,name:"Item"},
    {id:4,name:"Borongan"},
  ];

  $scope.changeBillType=function() {
    $scope.formData.price_full_inquery=0;
    $scope.formData.imposition=null;
    $scope.changeImposition();
  }
  $scope.changeImposition=function() {
    delete $scope.formData.piece_id
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/inquery/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('marketing.inquery.show',{id:data.id});
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

app.controller('marketingInqueryShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail";
    $scope.state=$state;
    $scope.params=$stateParams;
});

app.controller('marketingInqueryShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,hardList) {
    $rootScope.pageTitle="Detail | Info";
});

app.controller('marketingInqueryShowContract', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,hardList) {
    $rootScope.pageTitle="Detail | Buat Kontrak";
});

app.controller('marketingInqueryShowCost', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail | Biaya Operasional";
  $('.sk-container').addClass('sk-loading');

  $scope.imposition=[
    {id:1,name:"Kubikasi"},
    {id:2,name:"Tonase"},
    {id:3,name:"Item"},
    {id:4,name:"Borongan"},
  ];
  $scope.imposition_name_arr=[]
  // console.log($state.current.name);
  $scope.show = function() {

      $http.get(baseUrl+'/marketing/inquery/'+$stateParams.id).then(function(data) {
        
        $scope.data=data.data;
        $('.sk-container').removeClass('sk-loading');
        angular.forEach(data.data.details,function(val,i) {
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
      }, function(){
          $scope.show()
      });
  }
  $scope.show()

});
app.controller('marketingInqueryShowDocument', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail | Dokumen";
  // console.log($state.current.name);
  $('.sk-container').addClass('sk-loading');

  $scope.modalUpload=function() {
    $('#modalUpload').modal('show');
  }
  $scope.urls=baseUrl;
  $http.get(baseUrl+'/marketing/inquery/document/'+$stateParams.id).then(function(res) {
    $scope.data=res.data.item;
    $('.sk-container').removeClass('sk-loading');
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
app.controller('marketingInqueryShowCreateDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail | Add";
  $scope.params=$stateParams;
  $scope.formData={ detail : [], minimal_detail: [], price_type : 1 };
  $scope.cost_template=[];
  $scope.imposition=[
    {id:1,name:"Kubikasi"},
    {id:2,name:"Tonase"},
    {id:3,name:"Item"},
    {id:3,name:"Borongan"}
  ];

  $scope.backward = function() {
    if($rootScope.hasBuffer()) {
        $rootScope.accessBuffer()
    } else {
      $scope.emptyBuffer()
      $state.go('marketing.inquery.show.detail', {id:$stateParams.id})
    }
  }


    $scope.sizes = [
    {
        'value' : 20,
        'unit' : 'STD'
    },
    {
        'value' : 40,
        'unit' : 'STD/HC'
    },
    {
        'value' : 40,
        'unit' : 'RF'
    },
    ];

  $scope.formData.imposition=1;
  $scope.formData.total=0;
  $scope.formData.is_generate=1;

  $scope.imposition_warehouse=[
    {id:1,name:"Kubikasi"},
    {id:2,name:"Tonase"},
    {id:3,name:"Item"},
    {id:4,name:"Borongan"}
  ]

  $scope.showHandlingType = function() {
        $http.get(baseUrl+'/setting/setting/handling_type').then(function(data){
            $scope.handling_type = data.data[0].content.settings
        }, function() {
            $timeout(function(){
                $scope.showHandlingType()
            }, 10000)
        })
  }
  $scope.showHandlingType()
  $scope.switchContainer = function() {
        $scope.container_types = $scope.data.container_type.filter(value => value.size == $scope.formData.size.value && value.unit == $scope.formData.size.unit )
  }

  $scope.typePriceWarehouse=function(imp,value) {
    if (imp==2) {
      $scope.formData.price_inquery_tonase=value
      $scope.formData.price_inquery_volume=null
    } else {
      $scope.formData.price_inquery_tonase=null
      $scope.formData.price_inquery_volume=value
    }
  }

  $scope.changeLtlLcl=function() {
    $scope.formData.vehicle_type_id=null
    $scope.formData.container_type_id=null
  }


  $scope.countTotal = function() {
      var prices = $scope.formData.detail.map(value => parseInt(value.price))
      prices = prices.filter(value => value || false)
      $scope.grandtotal = prices.reduce(
          (x, y) => x + y
      )

  }

  $scope.appendTableService=function() {
  
      $scope.formData.detail.push({
        service_id:$scope.detailData.service.id,
      })
      var index = $scope.formData.detail.findIndex(value => value.service_id == $scope.detailData.service.id);
      var html = '';
      html+="<tr>"
      html+="<td>"+$scope.detailData.service.name+"</td>"
      html+="<td>"+$scope.detailData.service.service_type.name+"</td>"
      html+="<td><input type='text' jnumber2 only-num class='form-control text-right' ng-model='formData.detail[" + index + "].price' ng-change='countTotal()'></td>"
      html+="</tr>"

      $('#appendTable tbody').append($compile(html)($scope));
      $scope.detailData = {};
  }

  $scope.getServicePacket = function() {
      $('#appendTable tbody').html('');
      $scope.formData.detail = []
      $scope.grandtotal = 0
      $scope.detailData = {}
      $http.get(baseUrl+'/marketing/combined_price/'+$scope.formData.combined_price_id).then(function(data) {
          $scope.formData.detail=[];

          angular.forEach(data.data.detail, function(value){
              $scope.detailData.service = value.service;
              $scope.appendTableService();
          })
    });
  }

  $http.get(baseUrl+'/marketing/contract/create').then(function(data) {
    $scope.data=data.data;
    $scope.container_types = data.data.container_type;
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
      $scope.div_container=true;
      $scope.div_tarif_min=true;
      $scope.div_tarif=false;
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;
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
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_cost_template=true;
      $scope.div_tarif_warehouse=false;
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
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_cost_template=true;
      $scope.div_tarif_warehouse=false;
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
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;
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
      $scope.div_warehouse=true;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=true;
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
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;
    } else if($scope.type_7.indexOf(ids)!==-1) {
      $scope.formData.stype_id=7;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;
    }
    else {
      $scope.formData.stype_id=$scope.service.service_type_id;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=false;
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;

    }

    if(!ids) {
        $scope.formData.stype_id=null;
        $scope.div_trayek=false;
        $scope.div_komoditas=false;
        $scope.div_satuan=false;
        $scope.div_moda=false;
        $scope.div_armada=false;
        $scope.div_container=false;
        $scope.div_tarif_min=false;
        $scope.div_tarif=false;
        $scope.div_warehouse=false;
        $scope.div_storage_tonase=false;
        $scope.div_storage_volume=false;
        $scope.div_handling_tonase=false;
        $scope.div_handling_volume=false;
        $scope.div_tarif_warehouse=false;
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
      warehouse_id: $scope.formData.warehouse_id,
      commodity_id: $scope.formData.commodity_id,
      imposition_warehouse: $scope.formData.imposition_warehouse,
    }
    

    $http.get(baseUrl+'/marketing/price_list/cari_tarif', {params:datas}).then(function(dt) {
      var data=dt.data;
      $scope.formData.price_list_id=data.pl_id;
      if (data.stype==1) {
        $scope.formData.price_imposition=data.tarif;
        $scope.formData.min_imposition=data.min;
        $scope.formData.price_list_price_full=data.tarif;
      } else if (data.stype==5) {
        var impw=$scope.formData.imposition_warehouse
        if (impw==2) {
          $scope.formData.price_inquery_tonase=data.tarif_tonase;
          $scope.formData.price_list_price_tonase=data.tarif_tonase;
          delete $scope.formData.price_inquery_volume;
          delete $scope.formData.price_list_price_volume;
          $scope.formData.price_warehouse=data.tarif_tonase
        } else if (impw==1) {
          $scope.formData.price_inquery_volume=data.tarif_volume;
          $scope.formData.price_list_price_volume=data.tarif_volume;
          delete $scope.formData.price_inquery_tonase;
          delete $scope.formData.price_list_price_tonase;
          $scope.formData.price_warehouse=data.tarif_volume
        } else {
          delete $scope.formData.price_inquery_tonase;
          delete $scope.formData.price_list_price_tonase;
          delete $scope.formData.price_inquery_volume;
          delete $scope.formData.price_list_price_volume;
          delete $scope.formData.price_warehouse;
        }
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
        $scope.backward()
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

$scope.minData = {}

$scope.submitFormMinMultipleDetail = function () {
    var preData = {
        price_per_kg: $scope.minData.price_tonase,
        min_kg: $scope.minData.min_tonase,
        price_per_m3: $scope.minData.price_volume,
        min_m3: $scope.minData.min_volume,
        price_per_item: $scope.minData.price_item,
        min_item: $scope.minData.min_item
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
    $scope.minData.price_tonase = $scope.formData.minimal_detail[index].price_per_kg;
    $scope.minData.min_tonase = $scope.formData.minimal_detail[index].min_kg;
    $scope.minData.price_volume = $scope.formData.minimal_detail[index].price_per_m3;
    $scope.minData.min_volume = $scope.formData.minimal_detail[index].min_m3;
    $scope.minData.price_item = $scope.formData.minimal_detail[index].price_per_item;
    $scope.minData.min_item = $scope.formData.minimal_detail[index].min_item;
    $scope.InsertOrUpdate = 1;
    $('#modalMinMultipleDetail').modal('show');
}

});
app.controller('marketingInqueryShowEditDetail', function($scope, $http, $filter, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail | Edit";
  $scope.params=$stateParams;
  $scope.formData={ detail : [], minimal_detail: [] };
  $scope.cost_template=[];
  $scope.imposition=[
    {id:1,name:"Kubikasi"},
    {id:2,name:"Tonase"},
    {id:3,name:"Item"},
    {id:4,name:"Borongan"}
  ];
  $scope.imposition_warehouse=[
    {id:1,name:"Kubikasi"},
    {id:2,name:"Tonase"},
    {id:3,name:"Item"},
    {id:4,name:"Borongan"}
  ]

  $scope.sizes = [
    {
      'id': 0,
      'value' : 20,
      'unit' : 'STD'
    },
    {
      'id': 1,
      'value' : 40,
      'unit' : 'STD/HC'
    },
    {
      'id': 2,
      'value' : 40,
      'unit' : 'RF'
    },
  ];

  $scope.showHandlingType = function() {
        $http.get(baseUrl+'/setting/setting/handling_type').then(function(data){
            $scope.handling_type = data.data[0].content.settings
        }, function() {
            $timeout(function(){
                $scope.showHandlingType()
            }, 10000)
        })
  }
  $scope.showHandlingType()

  $scope.backward = function() {
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        } else {
          $scope.emptyBuffer()
          $state.go('marketing.inquery.show.detail', {id:$stateParams.id})
        }
  }

  $scope.switchContainer = function() {
    if($scope.formData.size) {
        $scope.container_types = $scope.data.container_type.filter(value => value.size == $scope.formData.size.value && value.unit == $scope.formData.size.unit )
    }
  }

  $scope.countTotal = function() {
    var prices = $scope.formData.detail.map(value => parseInt(value.price))
    prices = prices.filter(value => value || false)
    $scope.grandtotal = prices.reduce(
        (x, y) => x + y
    )
  }

  $scope.findContainerSizeID = function() {
    var _container_type = $scope.data.container_type.filter(value => value.id == $scope.formData.container_type_id);
    if(_container_type.length > 0) {
        var _size_type = $scope.sizes.filter(value => value.value == _container_type[0].size && value.unit == _container_type[0].unit);
        if(_size_type.length > 0) {
            $scope.formData.size = _size_type[0];
        }
    }
  }

  $scope.appendTableService=function() {
  
      $scope.formData.detail.push({
        service_id:$scope.detailData.service.id,
      })
      var index = $scope.formData.detail.findIndex(value => value.service_id == $scope.detailData.service.id);
      var html = '';
      html+="<tr>"
      html+="<td>"+$scope.detailData.service.name+"</td>"
      html+="<td>"+$scope.detailData.service.service_type.name+"</td>"
      html+="<td><input type='text' jnumber2 only-num class='form-control text-right' ng-model='formData.detail[" + index + "].price' ng-change='countTotal()'></td>"
      html+="</tr>"

      $('#appendTable tbody').append($compile(html)($scope));
      $scope.detailData = {};
  }

  $scope.getServicePacket = function() {
      $('#appendTable tbody').html('');
      $scope.formData.detail = []
      $scope.grandtotal = 0
      $scope.detailData = {}
      $http.get(baseUrl+'/marketing/combined_price/'+$scope.formData.combined_price_id).then(function(data) {
          $scope.formData.detail=[];

          angular.forEach(data.data.detail, function(value){
              $scope.detailData.service = value.service;
              $scope.appendTableService();
          })
    });
  }

  $scope.cariTemplate=function() {
    $http.get(baseUrl+'/marketing/inquery/cari_route_cost', {params:{
      'route_id': $scope.formData.route_id,
      'vehicle_type_id': $scope.formData.vehicle_type_id,
      'service_id': $scope.formData.service_id,
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

  $scope.typePriceWarehouse=function(imp,value) {
    if (imp==2) {
      $scope.formData.price_inquery_tonase=value
      $scope.formData.price_inquery_volume=null
    } else {
      $scope.formData.price_inquery_tonase=null
      $scope.formData.price_inquery_volume=value
    }
  }
  $scope.show = function() {

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
        $scope.formData.handling_type = dt.handling_type
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
        $scope.formData.pallet_price=dt.pallet_price;
        $scope.formData.commodity_id=dt.commodity_id;
        $scope.formData.moda_id=dt.moda_id;
        $scope.formData.vehicle_type_id=dt.vehicle_type_id;
        $scope.formData.warehouse_id=dt.warehouse_id;
        $scope.formData.container_type_id=dt.container_type_id;
        $scope.formData.service_type_id=dt.service_type_id;
        $scope.formData.route_id=dt.route_id;
        $scope.formData.free_storage_day=dt.free_storage_day;
        $scope.formData.over_storage_price=dt.over_storage_price;
        $scope.formData.cost_template=dt.route_cost_id;
        $scope.formData.price_inquery_handling_tonase=dt.price_inquery_handling_tonase;
        $scope.formData.price_inquery_handling_volume=dt.price_inquery_handling_volume;
        $scope.formData.cost_template=dt.route_cost_id;
        $scope.formData.min_type=dt.min_type ?? 1;
        $scope.formData.minimal_detail = [];
        $scope.cariTarif()
        
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
        if (dt.service_type_id==1) {
          if (dt.vehicle_type_id) {
            $scope.formData.ltl_lcl=1
          } else {
            $scope.formData.ltl_lcl=2
          }
        }
        $scope.dom_switch(dt.service_id,dt.header.company_id,true,dt);
        if(dt.price_type == 'service') {
        } else {
          $scope.dom_switch();      
        }
        $scope.cariTemplate();

        $scope.findContainerSizeID();
        $scope.switchContainer();

        if($scope.data.item.service_type_id == 1) {
          if($scope.data.item.min_type == 1) {
            $scope.formData.min_tonase = dt.price_inquery_min_tonase;
            $scope.formData.price_tonase = dt.price_inquery_tonase;
            $scope.formData.min_volume = dt.price_inquery_min_volume;
            $scope.formData.price_volume = dt.price_inquery_volume;
            $scope.formData.min_item = dt.price_inquery_min_item;
            $scope.formData.price_item = dt.price_inquery_item;
          }
          else if($scope.data.item.min_type == 2) {
              $scope.formData.minimal_detail = $scope.data.price_list_minimum_detail;
              console.log($scope.data.price_list_minimum_detail)
          }
        }
      }, function(){
          $scope.show()
      });
  }
  $scope.show()

  $scope.cariTarif = function() {}

  $scope.changeLtlLcl=function() {
    $scope.formData.vehicle_type_id=null
    $scope.formData.container_type_id=null
  }

  $scope.dom_switch=function(ids,company,isedit,dt=null) {
    $scope.service = $scope.data.service.find(x => x.id == $scope.formData.service_id)
    if (isedit==false) {
      $scope.formData={};
      $scope.formData.imposition=1;
    }
    $scope.formData.service_id=ids;
    $scope.formData.company_id=company;
    $scope.div_cost_template=false;

    if(dt) {
      
        if (dt.service_type_id==5) {
          $scope.formData.imposition=dt.imposition
          $scope.formData.imposition_warehouse=dt.imposition
          if (dt.imposition==1) {
            $scope.formData.price_warehouse=dt.price_inquery_volume
            $scope.formData.price_inquery_volume=dt.price_inquery_volume
            $scope.formData.price_list_price_volume=dt.price_list_price_volume
          } else {
            $scope.formData.price_warehouse=dt.price_inquery_tonase
            $scope.formData.price_inquery_tonase=dt.price_inquery_tonase
            $scope.formData.price_list_price_tonase=dt.price_list_price_tonase
          }
        }
    }
    if ($scope.service.service_type_id == 1) {
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
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;
    } else if ($scope.service.service_type_id == 2) {
      $scope.formData.stype_id=2;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=true;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;
      $scope.div_cost_template=true;
    } else if ($scope.service.service_type_id == 3) {
      $scope.formData.stype_id=3;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_cost_template=true;
      $scope.div_tarif_warehouse=false;
    } else if ($scope.service.service_type_id == 4) {
      $scope.formData.stype_id=4;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;
    } else if ($scope.service.service_type_id == 5) {
      $scope.formData.stype_id=5;
      $scope.div_trayek=false;
      $scope.div_komoditas=true;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=false;
      $scope.div_warehouse=true;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=true;
    } else if ($scope.service.service_type_id == 6) {
      $scope.formData.stype_id=6;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;
    } else if($scope.service.service_type_id == 7) {
      $scope.formData.stype_id=7;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;
    } else {
      $scope.formData.stype_id=$scope.service.service_type_id;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=false;
      $scope.div_warehouse=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=true;
      $scope.div_handling_volume=false;
      $scope.div_tarif_warehouse=false;

    }

    if(!ids) {
        $scope.formData.stype_id=null;
        $scope.div_trayek=false;
        $scope.div_komoditas=false;
        $scope.div_satuan=false;
        $scope.div_moda=false;
        $scope.div_armada=false;
        $scope.div_container=false;
        $scope.div_tarif_min=false;
        $scope.div_tarif=false;
        $scope.div_warehouse=false;
        $scope.div_storage_tonase=false;
        $scope.div_storage_volume=false;
        $scope.div_handling_tonase=false;
        $scope.div_handling_volume=false;
        $scope.div_tarif_warehouse=false;
    }

    $scope.formData.stype_id = $scope.service.service_type_id
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
        $scope.backward()
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
        price_per_kg: $scope.minData.price_tonase,
        min_kg: $scope.minData.min_tonase,
        price_per_m3: $scope.minData.price_volume,
        min_m3: $scope.minData.min_volume,
        price_per_item: $scope.minData.price_item,
        min_item: $scope.minData.min_item
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
$scope.minData = {}
$scope.editMinMultipleDetail = function (index) {
    // $scope.indexEdit = index;
    $scope.minimDetailId = $scope.formData.minimal_detail[index].id
    $scope.minData.price_tonase = $scope.formData.minimal_detail[index].price_per_kg;
    $scope.minData.min_tonase = $scope.formData.minimal_detail[index].min_kg;
    $scope.minData.price_volume = $scope.formData.minimal_detail[index].price_per_m3;
    $scope.minData.min_volume = $scope.formData.minimal_detail[index].min_m3;
    $scope.minData.price_item = $scope.formData.minimal_detail[index].price_per_item;
    $scope.minData.min_item = $scope.formData.minimal_detail[index].min_item;
    $scope.InsertOrUpdate = 1;
    $('#modalMinMultipleDetail').modal('show');
}

});
app.controller('marketingInqueryShowPenawaran', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail | Penawaran Harga";
  $scope.formData={}
  $('.sk-container').addClass('sk-loading');

  $scope.detail_percent=[];
  $scope.imposition=[
    {id:1,name:"Kubikasi"},
    {id:2,name:"Tonase"},
    {id:3,name:"Item"},
    {id:4,name:"Borongan"},
  ];
  $scope.imposition_name_arr=[]
  $http.get(baseUrl+'/marketing/inquery/offering/'+$stateParams.id).then(function(data) {
    $scope.detail=data.data.detail;
    $scope.item=data.data.item;

    angular.forEach($scope.detail, function(val,i) {
      var hitung=((val.total_offer/val.penawaran)*100);
      if (hitung <= 10 || val.total_offer <= 50000000) {
        $scope.detail_percent.push({percent:hitung,approve:1}); //supervisi
      } else if (hitung <= 15 || val.total_offer <= 100000000) {
        $scope.detail_percent.push({percent:hitung,approve:2}); //manajer
      } else {
        $scope.detail_percent.push({percent:hitung,approve:3}); //manajer
      }

      if ($rootScope.in_array(val.service_type_id,[6,7])) {
        $scope.imposition_name_arr.push(val.piece_name);
      } else if (val.service_type_id==2) {
        $scope.imposition_name_arr.push("Kontainer");
      } else if (val.service_type_id==3) {
        $scope.imposition_name_arr.push("Unit");
      } else {
        $scope.imposition_name_arr.push($rootScope.findJsonId(val.imposition,$scope.imposition).name);
      }
    });
    $('.sk-container').removeClass('sk-loading');
  });

  $scope.add=function(jsn) {
    $scope.formData={}
    $scope.formData.id=jsn.quotation_detail_id
    $scope.formData.penawaran=jsn.penawaran
    $scope.formData.total_cost=jsn.total_cost
    $scope.formData.offer_percent=0
    $scope.formData.total_offer=0
    $('#offerModal').modal('show');
  }

  $scope.reject=function(jsn) {
    var ofs=confirm("Apakah anda ingin menolak negosiasi ini ?");
    if (ofs) {
      $http.post(baseUrl+'/marketing/inquery/reject_offer/'+jsn.quotation_offer_id).then(function(data) {
        toastr.success("Negosiasi Telah Ditolak!");
        $state.reload()
      })
    }
  }

  $scope.approve=function(jsn) {
    var ofs=confirm("Apakah anda ingin menyetujui negosiasi ini ?");
    if (ofs) {
      $http.post(baseUrl+'/marketing/inquery/approve_offer/'+jsn.quotation_offer_id).then(function(data) {
        toastr.success("Negosiasi Telah Disetujui!");
        $state.reload()
      })
    }
  }

  $scope.offerPercentType=function() {
    var percent=parseFloat($scope.formData.offer_percent)
    if (percent>100) {
      percent=100
      $scope.formData.offer_percent=100
      return $scope.formData.total_offer=$scope.formData.penawaran*percent/100
    }
    $scope.formData.total_offer=$scope.formData.penawaran*percent/100
  }

  $scope.totalOfferType=function() {
    var totalOffer=parseFloat($scope.formData.total_offer)
    if (totalOffer>$scope.formData.penawaran) {
      $scope.formData.total_offer=$scope.formData.penawaran;
      totalOffer=$scope.formData.penawaran;
      return $scope.formData.offer_percent=(totalOffer/$scope.formData.penawaran*100).toFixed(2)
    }
    $scope.formData.offer_percent=(totalOffer/$scope.formData.penawaran*100).toFixed(2)
  }

  $scope.detailOffer=function(jsn) {
    $scope.quotation_detail_id=jsn.quotation_detail_id;
    offerTable.ajax.reload(function() {
      $('#detailModal').modal('show');
    })
  }

  $scope.quotation_detail_id=0
  var offerTable = $('#detail_datatable').DataTable({
    processing: true,
    serverSide: true,
    ordering: false,
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/inquery_offer_datatable',
      data: function(d) {
        d.quotation_detail_id=$scope.quotation_detail_id;
      }
    },
    columns:[
      {data:"created_at",name:"created_at"},
      {data:"price",name:"price", className:"text-right"},
      {data:"total_cost",name:"total_cost", className:"text-right"},
      {data:"total_offering",name:"total_offering", className:"text-right"},
      {data:"status",name:"status"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/inquery/store_offer/'+$scope.formData.id,$scope.formData).then(function(data) {
      toastr.success("Data Berhasil Disimpan!");
      $('#offerModal').modal('hide');
      $timeout(function() {
        $state.reload()
      },1000)
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
