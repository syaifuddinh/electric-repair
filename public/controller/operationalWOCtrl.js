app.controller('operationalWO', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, additionalFieldsService) {
    $rootScope.pageTitle="Work Order";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData = {};
    $scope.is_admin = authUser.is_admin;

    additionalFieldsService.dom.getInIndexKey('workOrder', function(list){
        $scope.initDatatable = function() {
            var additional_fields = list
            var columnDefs = [
                { 'title' : $rootScope.solog.label.general.branch },
                { 'title' : $rootScope.solog.label.general.date },
                { 'title' : $rootScope.solog.label.work_order.code },
                { 'title' : $rootScope.solog.label.general.customer },
                { 'title' : $rootScope.solog.label.work_order.name },
                { 'title' : $rootScope.solog.label.general.no_aju },
                { 'title' : $rootScope.solog.label.general.no_bl },
                { 'title' : 'Job Order' },
                { 'title' : $rootScope.solog.label.general.status },
            ]
            var columns = [
                {data:"company.name",name:"company.name"},
                {data:"date",name:"date"},
                {
                    data:null,
                    name:"code",
                    className : 'font-bold',
                    render : function(resp) {
                        var show_detail = $rootScope.roleList.includes('marketing.work_order.detail');
                        if(show_detail) {
                           return '<a ui-sref="marketing.work_order.show({id:' + resp.id + '})">' + resp.code + '</a>'
                        } else {
                           return '<span>' + resp.code + '</span>'
                        }
                    }  
                },
                {data:"customer.name",name:"customer.name",className:"font-bold"},
                {data:"name",name:"name"},
                {data:"aju_number",name:"aju_number",className:""},
                {data:"no_bl",name:"no_bl",className:""},
                {data:"total_job_order",name:"total_job_order",className:"text-right"},
                {data:"status",name:"status",className:""}
            ]

            for(x in additional_fields) {
                columns.push({
                    data : additional_fields[x].slug,
                    name : 'additional_work_orders.' + additional_fields[x].slug
                })
                columnDefs.push({
                    title : additional_fields[x].name
                })
            }

            columnDefs.push({title : ''})
            columns.push({data:"action",name:"created_at",className:"text-center"})


            columnDefs = columnDefs.map((c, i) => {
                c.targets = i
                return c
            })

            oTable = $('#datatable').DataTable({
                    processing: true,
                    serverSide: true,
                    order:[[9,'desc']],
                    ajax: {
                      headers : {'Authorization' : 'Bearer '+authUser.api_token},
                      url : baseUrl+'/api/marketing/work_order_datatable',
                      data : function(request) {
                        request['start_date'] = $scope.formData.start_date;
                        request['is_admin'] = authUser.is_admin;
                        request['user_company_id'] = authUser.company_id;
                        request['end_date'] = $scope.formData.end_date;
                        request['customer_id'] = $scope.formData.customer_id;
                        request['company_id'] = $scope.formData.company_id;
                        request['status'] = $scope.formData.status;

                        return request;
                      },
                      dataSrc: function(d) {
                          $('.ibox-content').removeClass('sk-loading');
                          return d.data;
                      }
                    },
                    columns:columns,
                    columnDefs:columnDefs,
                    createdRow: function(row, data, dataIndex) {
                      $compile(angular.element(row).contents())($scope);
                    }
            });
            $compile('thead')($scope)
        }
        $scope.initDatatable()
    })

    $scope.exportExcel = function() {
        var paramsObj = oTable.ajax.params();
        var params = $.param(paramsObj);
        var url = baseUrl + '/excel/work_order_export?';
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

  var requestTable = $('#request_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:false,
    order:[[4,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/work_order_draft_datatable',
      data: function(d) {
        d.is_done=0
      }
    },
    columns:[
      {data:"name",name:"name"},
      {data:"customer",name:"customer"},
      {data:"user",name:"user"},
      {data:"date",name:"date"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/work_order/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(error) {
        $rootScope.disBtn=false;
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

  $scope.draft_check=function() {
    $http.get(baseUrl+'/api/marketing/draft_check',{headers:{'Authorization':'Bearer '+authUser.api_token}}).then(function(data) {
      $scope.draft_count=data.data
    })
  }
  $scope.draft_check()
  $scope.viewRequest=function() {
    requestTable.ajax.reload(function() {
      $('#modalRequest').modal();
      requestTable.columns.adjust().draw();
    })
  }

  $scope.goRequest=function(id) {
    $('#modalRequest').modal('hide')
    $timeout(function() {
      $state.go('marketing.work_order.show_request',{id:id});
    },1000)
  }

});

app.controller('operationalWOInvoice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Work Order";
  $scope.formData = {};
  $http.get(baseUrl+'/marketing/work_order').then(function(data) {
    $scope.data=data.data;
  });
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    
    order:[[9,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/work_order_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['customer_id'] = $scope.formData.customer_id;
        request['company_id'] = $scope.formData.company_id;
        request['status'] = $scope.formData.status;
        request['is_invoice'] = 3;

        return request;
      }
    },
    columns:[
      {data:"company.name",name:"company.name"},
      {
        data:null,
        searchable:false,
        orderable:false,
        render: resp => $filter('fullDate')(resp.date)
      },
      {data:"code",name:"code"},
      {data:"invoice_code",name:"invoice_code"},
      {data:"customer.name",name:"customer.name",className:"font-bold"},
      {data:"name",name:"name"},
      {data:"aju_number",name:"aju_number",className:""},
      {data:"no_bl",name:"no_bl",className:""},
      {data:"total_job_order",name:"total_job_order",className:"text-right"},
      {data:"status",name:"status",className:""},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/work_order_export?';
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

  var requestTable = $('#request_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:'100%',
    // 
    order:[[4,'desc']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/work_order_draft_datatable',
      data: function(d) {
        d.is_done=0
      }
    },
    columns:[
      {data:"name",name:"name"},
      {data:"customer",name:"customer"},
      {data:"user",name:"user"},
      {data:"date",name:"date"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

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

  $scope.draft_check=function() {
    $http.get(baseUrl+'/api/marketing/draft_check',{headers:{'Authorization':'Bearer '+authUser.api_token}}).then(function(data) {
      // console.log(data.data)
      $scope.draft_count=data.data
    })
  }
  $scope.draft_check()
  $scope.viewRequest=function() {
    requestTable.ajax.reload(function() {
      $('#modalRequest').modal()
    })
  }

  $scope.goRequest=function(id) {
    $('#modalRequest').modal('hide')
    $timeout(function() {
      $state.go('marketing.work_order.show_request',{id:id});
    },1000)
  }

});
app.controller('operationalWOShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, additionalFieldsService) {
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

    additionalFieldsService.dom.get('workOrder', function(list){
        $scope.additional_fields = list
    })

  $scope.saveAs = function() {
        $state.go('marketing.work_order.save_as', {'id' : $stateParams.id})
  }

  $scope.showPiece = function() {
      $http.get(baseUrl+'/setting/general/satuan').then(function(data) {
        $scope.piece=data.data;
      }, function(){
          $scope.showPiece()
      });
  }

  $scope.openItemTab = function(e) {
     $scope.openItem = true
     $('li').removeClass('active')
     $(e).addClass('active')
  }

  $scope.openProcessTab = function(e) {
     $scope.openProcess = true
     $('li').removeClass('active')
     $(e).addClass('active')
  }

  $scope.openPriceTab = function(e) {
     $scope.openPrice = true
     $('li').removeClass('active')
     $(e).addClass('active')
  }

  $scope.closeItemTab = function(e) {
     $scope.openItem = false
     $('li').removeClass('active')
     $(e).addClass('active')
  }

  $scope.closeProcessTab = function(e) {
     $scope.openProcess = false
     $('li').removeClass('active')
     $(e).addClass('active')
  }

  $scope.closePriceTab = function(e) {
     $scope.openPrice = false
     $('li').removeClass('active')
     $(e).addClass('active')
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

  $scope.showWorkOrder = function() {
      $http.get(baseUrl+'/marketing/work_order/'+$stateParams.id).then(function(data) {
        $scope.item=data.data.item;
        $scope.isShipment=true;
        $scope.hasInvoice=data.data.item.invoice_id;
        var detail = data.data.detail;
        $scope.detail = detail
        $scope.detail_jo=data.data.detail_jo;
        $scope.cost=data.data.cost_detail;

        if(data.data.item.is_job_packet == 1) {
            $rootScope.customer_id = data.data.item.customer_id
            $scope.showPiece()
            $http.get(baseUrl+'/marketing/work_order/' + $stateParams.id + '/packet/job_order').then(function(data){
                $rootScope.job_order_id = data.data.job_order_id
                $rootScope.job_order.showKpiStatusData()
                $rootScope.job_order.showDetail($rootScope.job_order_id)
                $rootScope.job_order.showKpiLog()
            }, function() {
            })
        }

        $scope.progress_name=[]
        angular.forEach($scope.detail_jo, function(val,i) {
          var sp=val.kpi_status || {};
          if (sp.is_done && sp.type==1) {
            $scope.progress_name.push('Selesai')
          } else {
            $scope.progress_name.push('Proses')
          }
        })

        $scope.imposition_name_arr=[]
        var jsn, is_price_list = 0, is_contract = 0
        for(x in data.data.detail) {
            jsn = data.data.detail[x]
            if (jsn.quotation_detail_id) {
              is_contract = 1 
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
              is_price_list = 1 
              var val=jsn.price_list
            }
        }

        if(is_price_list == 1 && is_contract == 1) {
          price_type = 'Tarif kontrak dan tarif umum'
        } else {
            if(is_price_list == 1) {
                price_type = 'Tarif umum'
            } else if(is_contract == 1) {
                price_type = 'Tarif kontrak'
            }
        }

        $scope.item.jenis_tarif = price_type
        $('.ibox-content').removeClass('sk-loading');
      })
  }
  $scope.showWorkOrder()
});
app.controller('operationalWOShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Detail Work Order";
  item_warehouse_datatable = null
  $scope.imposition=$scope.$parent.imposition;
  $scope.data = {}
  $scope.formData={}
  $scope.item={}
  $scope.idx = 0
  $scope.baseUrl=baseUrl;
  $scope.stateParams=$stateParams;
  $scope.is_price_list = 1
  $rootScope.job_order.detail = []

  $scope.showJobOrderIdPacket = function() {
        $http.get(baseUrl+'/marketing/work_order/' + $stateParams.id + '/packet/job_order').then(function(data){
            $rootScope.job_order_id = data.data.job_order_id
            $rootScope.job_order.showKpiStatusData()
            $rootScope.job_order.showDetail($rootScope.job_order_id)
            $rootScope.job_order.showKpiLog()
        }, function() {
        })
  }

  $scope.showSetting = function() {
        $http.get(baseUrl+'/setting/setting/work_order/using_qty').then(function(data){
            $scope.using_qty = data.data
        }, function() {
            $timeout(function(){
                $scope.showSetting()
            }, 10000)
        })
  }
  $scope.showSetting()
  $scope.setIDX = function() {
      $scope.idx += 1
      return $scope.idx
  }

  $scope.show = function() {
      $http.get(baseUrl+'/marketing/work_order/'+$stateParams.id).then(function(data) {
        $scope.item=data.data.item;
        $rootScope.customer_id = data.data.item.customer_id
        if($scope.item.is_job_packet == 1) {
            $scope.showJobOrderIdPacket()
        }
        detail=data.data.detail;
        hasLCL = false;
          detail = detail.map(function(r){
              if(r.quotation_detail != null) {
                  var val = r.quotation_detail
                  var penawaran = val.price_inquery_full
                  if(val.service.service_type_id == 15) {
                      hasLCL = true
                      penawaran = val.over_storage_price
                  } else if(val.service.service_type_id == 14) {
                      penawaran = val.price_inquery_full
                  } else if((val.service.service_type_id == 12 || val.service.service_type_id == 13) && val.handling_type == 1) {
                      hasLCL = true
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
                      hasLCL = true
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
                  }
                  penawaran = 'Rp ' + $filter('number')(penawaran)
              } else {
                  val = r.price_list
                    if(val.service_type_id == 1 || ((val.service_type_id == 12 || val.service_type_id == 13) && val.handling_type == 1)) {
                        penawaran = ''
                        penawaran += 'Rp '
                        penawaran += $filter('number')(val.price_tonase) + ' (Ton)<br>'
                        penawaran += 'Rp '
                        penawaran += $filter('number')(val.price_volume) + ' (m<sup>3</sup>)<br>'
                        penawaran += 'Rp '
                        penawaran += $filter('number')(val.price_item) + ' (Per Item)<br>'
                        penawaran += 'Rp '
                        penawaran += $filter('number')(val.price_borongan) + ' (Borongan)<br>'
                        hasLCL = true
                    } else if(val.service_type_id == 15) {
                        hasLCL = true
                        penawaran = 'Rp ' + $filter('number')(val.over_storage_price)                   
                    } else {
                        penawaran = 'Rp ' + $filter('number')(val.price_full)                   
                    }
              }
                imposition = null
                if(r.price_list) {
                    handling_type = r.price_list.handling_type
                    service_type_id = r.price_list.service_type_id
                } else {
                    handling_type = r.quotation_detail.handling_type
                    service_type_id = r.quotation_detail.service_type_id
                    if(handling_type == 1) {
                        imposition = r.quotation_detail.imposition
                    }
                }
                use_container = false
                if(service_type_id == 2) {
                    use_container = true
                } else if(service_type_id == 12 || service_type_id == 13) {
                    if(handling_type == 2) {
                        use_container = true
                    }
                } else if(service_type_id == 15) {
                    hasLCL = true
                }
                r['service_type_id'] = service_type_id
                r['use_container'] = use_container
                r['penawaran'] = penawaran
              return r
          })
        $rootScope.currentImposition = imposition
        $rootScope.hasLCL = hasLCL
        $scope.detail = detail
        $scope.status_proses=[]
        angular.forEach($scope.detail,function(val,i) {
          if (val.is_done==1) {
            $scope.status_proses.push('<span class="badge badge-success">Selesai</span>')
          } else {
            $scope.status_proses.push('<span class="badge badge-warning">Proses</span>')
          }
        })
      })
  }
  $scope.show()

  $scope.cancelDone=function(val) {
    var cofs=confirm('Apakah anda ingin membatalkan selesai untuk layanan ini ?');
    if (!cofs) {
      return null;
    }
    $http.post(baseUrl+'/marketing/work_order/cancel_done/'+val).then(function(data) {
      toastr.success("Layanan Work Order Berhasil dibatalkan!");
      $state.reload()
    })
  }

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
      $state.reload()
    });
  }

  $scope.editDetail=function(jsn) {
    $scope.editData={}
    $scope.editData.description=jsn.description;
    $scope.editData.wod_id=jsn.id;
    $scope.editData.qty=jsn.qty;
    $scope.editData.use_container=jsn.use_container;
    $scope.editData.service_type_id=jsn.service_type_id;
    $('#editModal').modal('show');
  }

  $scope.approveDetail=function(value) {
    if (value.total_jo<1) {
      toastr.error("Tidak terdapat Job Order pada item ini","Maaf !")
      return null
    }
    var cofs=confirm("Apakah anda yakin ingin merubah status menjadi Selesai ?");
    if(cofs) {

      $http.post(baseUrl+'/marketing/work_order/approve_detail/'+value.id).then(function(data) {
        $scope.show()
        toastr.success("Work Order telah diselesaikan","Selamat !")
      });
    }
  }

  $scope.submitEdit=function() {
    $scope.disBtn = true
    $http.post(baseUrl+'/marketing/work_order/store_edit_detail',$scope.editData).then(function(data) {
      $scope.disBtn = false
      $('#editModal').modal('hide');
      $scope.show()
      $scope.showWorkOrder()
      toastr.success("Data Berhasil Disimpan","Selamat !")
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

  var qTable = $('#quotation_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:false,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/quotation_detail_datatable',
      data: function(d) {
        d.is_contact=1
        d.is_actived_contract=1
        d.customer_id=$scope.item.customer_id
        d.no_service_4=1;
      }
    },
    columns:[
      {data:"action_choose",name:"id"},
      {data:"code",name:"quotations.no_contract",className:"font-bold"},
      {data:"service",name:"service"},
      {data:"route_name",name:"route_name"},
      {data:"commodity_name",name:"commodity_name"},
      {data:"vehicle_type_name",name:"vehicle_type_name",className:""},
      {data:"container_type_name",name:"container_type_name",className:""},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  



  $scope.chooseKontrak=function(id,code) {
    var cofs=confirm("Apakah anda ingin menambahkan list ini ?");
    if (cofs) {
      $http.post(baseUrl+'/marketing/work_order/store_detail/'+$stateParams.id,{quotation_detail_id:id}).then(function(data) {
        $('#priceDataModal').modal('hide');
        $scope.show()
      })
    }
  }
  $scope.choosePriceList=function(jsn) {
    var cofs=confirm("Apakah anda ingin menambahkan list ini ?");
    if (cofs) {
      $http.post(baseUrl+'/marketing/work_order/store_detail/'+$stateParams.id,{price_list_id:jsn.id}).then(function(data) {
        $('#priceDataModal').modal('hide');
        $scope.show()
        $timeout(function(){
            $state.reload()
        }, 500)
      })
    }
  }

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/work_order/delete_detail/'+ids,{_token:csrfToken}).then(function success(data) {
        $scope.show();
        toastr.success("Data Berhasil Dihapus!");
        $state.reload()
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
    qTable.ajax.reload()
    
      pTable = $('#price_list_datatable').DataTable({
        processing: true,
        serverSide: true,
        scrollX:false,
        ajax: {
          headers : {'Authorization' : 'Bearer '+authUser.api_token},
          url : baseUrl+'/api/marketing/price_list_datatable',
          data:function(d) {
            d.disable4=false;
          }
        },
        columns:[
          {data:"action_choose2",name:"id",className:"text-center"},
          {data:"code",name:"code"},
          {data:"route.name",name:"route.name"},
          {data:"name",name:"name"},
          {data:"commodity.name",name:"commodity.name"},
          {data:"service.name",name:"service.name",},
          {data:"moda.name",name:"moda.name"},
          {data:"vehicle_type.name",name:"vehicle_type.name"},
          {data:"container_type.full_name",name:"container_type.name"},
        ],
        createdRow: function(row, data, dataIndex) {
          $compile(angular.element(row).contents())($scope);
        }
      });
    
    

    $('#priceDataModal').modal('show');
  }

  $scope.submitQuotationData=function() {
    $http.post(baseUrl+'/marketing/work_order')
  }

});

app.controller('operationalWOShowJobOrder', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Work Order";
  $scope.detail_jo=$scope.$parent.detail_jo;
  $scope.imposition=$scope.$parent.imposition;
  $scope.status_approve=$scope.$parent.progress_name;
});


app.controller('operationalWOShowPrice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Rincian Harga Work Order";
  $http.get(baseUrl+'/marketing/work_order/' + $stateParams.id + '/price_detail').then(function(data) {
    $scope.price_details=data.data;
  });
});

app.controller('operationalWOCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter,additionalFieldsService) {
    $rootScope.pageTitle="Add";
    $('.ibox-content').addClass('sk-loading');
    $scope.idrequest=null;

    if($stateParams.idrequest !== 'undefined')
        $scope.idrequest = $stateParams.idrequest;

    $scope.imposition=[
        {id:1,name:'Kubikasi'},
        {id:2,name:'Tonase'},
        {id:3,name:'Item'},
        {id:4,name:'Borongan'},
    ]

    $scope.formData={
        'is_job_packet' : 0
    }
    $scope.formData.additional = {}
    $scope.formData.detail=[];
    $scope.formData.date=dateNow;
    $scope.formData.company_id=compId;
    $scope.formData.type_tarif=1;
    $scope.formData.no_bl='-';
    $scope.formData.aju_number='-';
    $scope.disable4=false;
    $scope.showPL=true;

    additionalFieldsService.dom.get('workOrder', function(list){
        $scope.additional_fields = list
    })

  $http.get(baseUrl+'/marketing/work_order/create').then(function(data) {
    $scope.data=data.data;
    if ($stateParams.idrequest) {
      var dt=$rootScope.findJsonId($stateParams.idrequest,data.data.draft)
      $scope.draft=dt
      $scope.formData.id_draft=$stateParams.idrequest
      $scope.formData.customer_id=dt.customer_id
      $scope.formData.no_bl=dt.no_bl
      $scope.formData.aju_number=dt.aju_number
      $scope.formData.name=dt.name;
    }

    $('.ibox-content').removeClass('sk-loading');
  })

  $scope.showPic = function() {

      $http.get(baseUrl+'/contact/contact/' + $scope.formData.customer_id +  '/pic').then(function(data) {
        $scope.sales_name=data.data.sales_name;
        $scope.customer_service_name=data.data.customer_service_name;
      }, function(){
          $scope.showPic()
      });
  }

  $scope.resetTable=function() {
    var details = $scope.formData.detail;
    var unit, tr, removed = 0
    for(d in details) {
        unit = details[d]
        tr = $('#appendTable tbody tr')
        if(unit.quotation_detail_id) {
            $(tr[d - removed]).remove()
            delete $scope.formData.detail[d]
            ++removed
        }
    }
    $scope.disable4=false;
    $scope.showPL=true;
  }

  $scope.changeType=function(id,formData) {
    var detail=$scope.formData.detail;
    $scope.formData={};
    $scope.formData.additional = formData.additional;
    $scope.formData.detail = detail || []
    $scope.formData.work_order_id=formData.work_order_id;
    $scope.formData.company_id=formData.company_id;
    $scope.formData.customer_id=formData.customer_id;
    $scope.formData.is_job_packet=formData.is_job_packet;
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
  }

  var contractTable = $('#contract_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:false,
    order:[[1,'desc']],
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
      {data:"action_choose",name:"id",className:"text-center",orderable:false},
      {data:"name",name:"name"},
      {data:"no_contract",name:"no_contract"},
      {
        data:null,
        orderable:false,
        searchable:false, 
        render : resp => $filter('fullDate')(resp.date_end_contract)
      },
      {data:"customer.name",name:"customer.name"},
      {data:"bill_type",name:"bill_type"},
      {data:"sales.name",name:"sales.name"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });
  var priceListTable = $('#price_list_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:false,
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
      {data:"service.service_type.name",name:"service.service_type.name"},
      {data:"moda.name",name:"moda.name"},
      {data:"vehicle_type.name",name:"vehicle_type.name"},
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

  $scope.chooseQuotationDetail = function(val, i) {
        var html = ''
        if ($rootScope.in_array(val.service_type_id,[6,7])) {
            var imp = val.piece.name;
        } else if (val.service_type_id==2) {
            var imp = "Kontainer";
        } else if (val.service_type_id==3) {
            var imp = "Unit";
        } else {
            var imp = $rootScope.findJsonId(val.imposition,$scope.imposition).name;
        }
        var price = val.price_inquery_full
        if(val.service.service_type_id == 15) {
            price = val.over_storage_price
        } else if(val.service.service_type_id == 14) {
            price = val.price_inquery_full
        } else if((val.service.service_type_id == 12 || val.service.service_type_id == 13) && val.handling_type == 1) {
            if(val.imposition == 1) {
                price = val.price_inquery_handling_volume
            }
            else if(val.imposition == 2) {
                price = val.price_inquery_handling_tonase
            }
            else if(val.imposition == 3) {
                price = val.price_inquery_item
            }
            else {
                price = val.price_inquery_full
            }
        } else if(val.service.service_type_id == 1) {
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
        }

            html = ''
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
          $scope.formData.contract_code=$scope.contract.no_contract;
          $scope.formData.contract_id=$scope.contract.id;
          $('#appendTable tbody').append($compile(html)($scope))
  }

    $scope.chooseKontrak=function(value) {
        var val = value;
        $scope.contract = val
        val.service_type_id = parseInt(val.service_type_id);
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

            $scope.imposition=[
                {id:1,name:"Kubikasi"},
                {id:2,name:"Tonase"},
                {id:3,name:"Item"},
                {id:4,name:"Borongan"},
            ];
            $scope.imposition_name_arr=[];
            angular.forEach(data.data.detail, function(val,i) {
                $scope.chooseQuotationDetail(val, i)
            })
            $('#modalContract').modal('hide');
        })
    }


  $scope.counter=0
  $scope.choosePriceList=function(val) {
    // console.log(value)
    val.service_type_id = parseInt(val.service_type_id);
    if (val.service_type_id==6) {
      var imp=val.piece_name;
    } else if (val.service_type_id==2||val.service_type_id==3) {
      var imp='Unit'
    } else {
      var imp='-'
    }
    if (val.service_type_id==1 || ((val.service_type_id == 12 || val.service_type_id == 13) && val.handling_type == 1)) {
      var price="";
      price+=$filter('number')(val.price_tonase)+' (Ton)<br>'
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

  $timeout(function(){
      var hash = window.location.hash
      if(hash.indexOf('save_as') > -1) {
          $rootScope.pageTitle="Save As";
          $scope.showWorkOrder = function() {
              $http.get(baseUrl+'/marketing/work_order/'+$stateParams.id).then(function(data) {
                $scope.formData=data.data.item;
                $scope.formData.work_order_id = $stateParams.id
                $scope.formData.detail = []
                $scope.formData.date=$filter('minDate')($scope.formData.date);
                var details = data.data.detail
                var unit
                for(d in details) {
                    unit = details[d]
                    if(unit.price_list != null) {
                        $scope.choosePriceList(unit.price_list)
                    } else {
                        $scope.contract = {
                            'id' : unit.quotation_detail.header_id,
                            'no_contract' : '' 
                        }
                        $scope.chooseQuotationDetail(unit.quotation_detail, d)
                    }
                }
              })
          }
          $scope.showWorkOrder()
      }

  }, 600)

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
    $compile($('#work_order'))($scope)
    $scope.disBtn=true;
    $scope.formData.id_draft = $scope.idrequest;

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
app.controller('operationalWOShowRequest', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Permintaan Work Order";

  $http.get(baseUrl+'/marketing/work_order/show_request/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
  })

  $scope.reject = function() {
      is_reject = confirm('Apakah anda yakin ingin menolak request work order ini ?')
      if(is_reject) {
          $http.delete(baseUrl+'/marketing/work_order/request/'+$stateParams.id).then(function(data) {
              toastr.success('Request work order berhasil ditolak')
              $state.go('marketing.work_order')
          })
      }
  }
});
app.controller('operationalWOEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, additionalFieldsService) {
    $rootScope.pageTitle="Edit Work Order";
    $('.ibox-content').addClass('sk-loading');

    additionalFieldsService.dom.get('workOrder', function(list){
        $scope.additional_fields = list
    })
  
    $scope.formData={}
  $http.get(baseUrl+'/marketing/work_order/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data
    var dt=$scope.data.item

    $scope.formData.company_id=dt.company_id
    $scope.formData.customer_id=dt.customer_id
    $scope.formData.name=dt.name
    $scope.formData.aju_number=dt.aju_number
    $scope.formData.no_bl=dt.no_bl
    $scope.formData.date=$filter('minDate')(dt.date);
    $scope.formData.additional = dt.additional
    $('.ibox-content').removeClass('sk-loading');
  })

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/marketing/work_order/'+$stateParams.id,$scope.formData).then(function(data) {
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
