app.controller('financeCash', function($scope, $http, $filter, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Transaksi Kas/Bank";
  $rootScope.currentPage = ''
  $scope.formData = {};
  $scope.formData.start_date = "";
  $scope.formData.end_date = "";
  $scope.formData.company_id = "";
  $scope.is_admin = authUser.is_admin;

  $http.get(baseUrl+'/finance/cash_transaction/create').then(function(data) {
    $scope.data=data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[8,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/cash_transaction_datatable',
      data: function(d) {
        d['start_date'] = $scope.formData.start_date;
        d['end_date'] = $scope.formData.end_date;
        d['company_id'] = $scope.formData.company_id;
        d['status'] = $scope.formData.status;
        d['status_cost'] = $scope.formData.status_cost;
      }
    },
    columns:[
      // {data:"checkbox",name:"checkbox",className:"text-center",orderable:false},
      {data:"code",name:"code"},
      {data:"company.name",name:"company.name"},
      { 
        data:null,
        name:'date_transaction',
        searchable:false,
        render : resp => $filter('fullDate')(resp.date_transaction)
      },
      {data:"type_transaction.name",name:"type_transaction.name"},
      // {data:"reff",name:"reff"},
      {data:"total",name:"total"},
      {data:"description",name:"description"},
      {data:"status",name:"status", className : 'text-center'},
      {data:"status_cost_name",name:"status_cost_name"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('finance.transaction_cash.detail')) {
        $(row).find('td').attr('ui-sref', 'finance.cash_transaction.show({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.searchData = function() {
    oTable.ajax.reload();
  }

  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }

  $scope.approve=function(id) {
    // console.log(id)
    var conf=confirm("Apakah anda ingin menyetujui transaksi kas ini ?")
    if (conf) {
      $http.post(baseUrl+'/finance/cash_transaction/approve/'+id).then(function(data) {
        oTable.ajax.reload()
        toastr.success("Transaksi Kas Telah Disetujui!");
      },function(err) {
        toastr.error(err.data.message,"Maaf!");
      });
    }
  }

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/finance/cash_transaction/'+ids,{_token:csrfToken}).then(function success(data) {
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

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/transaksi_kas_bank_export?';
    url += params;
    location.href = url; 
  }
});
app.controller('financeCashCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Transaksi Kas/Bank";
  $('.ibox-content').toggleClass('sk-loading');
  $scope.kasbon_id = (typeof $stateParams.id !== 'undefined') ? $stateParams.id : 0;
  $scope.isDisableHeader=false
  $scope.isDisablePiutang=false
  $scope.disBtn=false;

  $scope.formData={};
  $scope.formData.date_transaction=dateNow;
  $scope.formData.company_id=compId;
  $scope.formData.detail=[];
  $scope.formData.det=[];

  $scope.detail={};
  $scope.detail.jenis=1;
  $scope.in_out=[
    {id:1,name:"Masuk"},
    {id:2,name:"Keluar"},
  ];

  if($scope.kasbon_id > 0) {
    $scope.in_out=[{id:2,name:"Keluar"}];
    $scope.formData.jenis = 2;
  }

  $scope.type=[
    {id:1,name:"Kas"},
    {id:2,name:"Bank"},
  ];
  $scope.totalAmount=0;
  $scope.urut=0;

  $http.get(baseUrl+'/finance/cash_transaction/create').then(function(data) {
    $scope.data=data.data;
    $scope.vendors=$scope.data.vendor
    $scope.accounts=$scope.data.account

    $('.ibox-content').toggleClass('sk-loading');
  });


  job_order_cost_datatable = $('#job_order_cost_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:false,
    dom: 'frtp',
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/job_order_cost_datatable',
      data : function(d) {
          d.cost_type = 2
      }
    },
    columns:[
      // {data:"checkbox",name:"checkbox",className:"text-center",orderable:false},
      {data:"code",name:"job_orders.code"},
      {data:"cost_type",name:"cost_types.name"},
      {data:"vendor",name:"contacts.name"},
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.qty)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.price)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.total_price)
      }
    ],
    createdRow: function(row, data, dataIndex) {
      $(row).find('td').attr('ng-click', 'selectJobOrderCost($event.currentTarget)')
      $compile(angular.element(row).contents())($scope);
    }
  });


  manifest_cost_datatable = $('#manifest_cost_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:false,
    dom: 'frtp',
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/manifest_cost_datatable',
      data : function(d) {
          d.cost_type = 2
      }
    },
    columns:[
      // {data:"checkbox",name:"checkbox",className:"text-center",orderable:false},
      {data:"code",name:"manifests.code"},
      {data:"cost_type",name:"cost_types.name"},
      {data:"vendor",name:"contacts.name"},
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.qty)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.price)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.total_price)
      }
    ],
    createdRow: function(row, data, dataIndex) {
      $(row).find('td').attr('ng-click', 'selectManifestCost($event.currentTarget)')
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.openJobOrderCost = function() {
      $('#job_order_cost').show()
      $('#manifest_cost').hide()
      $('#job_order_cost_btn').addClass('active')
      $('#manifest_cost_btn').removeClass('active')
  }
  $scope.openJobOrderCost()

  $scope.openManifestCost = function() {
      manifest_cost_datatable.ajax.reload()
      $('#job_order_cost').hide()
      $('#manifest_cost').show()
      $('#job_order_cost_btn').removeClass('active')
      $('#manifest_cost_btn').addClass('active')
  }

  $scope.selectJobOrderCost = function(obj) {
      var tr = $(obj)
      var data = job_order_cost_datatable.row(tr).data()
      if($scope.is_edit != 1) {
          $scope.detail.code = data.code
          $scope.detail.name = data.cost_type
          $scope.detail.job_order_cost_id = data.id
          $scope.detail.manifest_cost_id = null
          $scope.detail.account_id = data.account_id
          $scope.detail.amount = data.total_price
          $scope.detail.description = data.description
      } else {

          $scope.formData.detail[$scope.currentIndex].code = data.code
          $scope.formData.detail[$scope.currentIndex].name = data.cost_type
          $scope.formData.detail[$scope.currentIndex].job_order_cost_id = data.id
          $scope.formData.detail[$scope.currentIndex].manifest_cost_id = null
          $scope.formData.detail[$scope.currentIndex].account_id = data.account_id
          $scope.formData.detail[$scope.currentIndex].description = data.description

          $scope.hitungAmount();
          $scope.currentIndex = null
          $scope.is_edit = 0          
      }
      $('#costModal').modal('hide')

  }

  $scope.selectManifestCost = function(obj) {
      var tr = $(obj)
      var data = manifest_cost_datatable.row(tr).data()
      if($scope.is_edit != 1) {
          $scope.detail.code = data.code
          $scope.detail.name = data.cost_type
          $scope.detail.job_order_cost_id = null
          $scope.detail.manifest_cost_id = data.id
          $scope.detail.account_id = data.account_id
          $scope.detail.amount = data.total_price
          $scope.detail.description = data.description
      } else {
          $scope.formData.detail[$scope.currentIndex].code = data.code
          $scope.formData.detail[$scope.currentIndex].name = data.cost_type
          $scope.formData.detail[$scope.currentIndex].job_order_cost_id = null
          $scope.formData.detail[$scope.currentIndex].manifest_cost_id = data.id
          $scope.formData.detail[$scope.currentIndex].account_id = data.account_id
          $scope.formData.detail[$scope.currentIndex].description = data.description
          $scope.hitungAmount();
          $scope.currentIndex = null
          $scope.is_edit = 0          
      }
      $('#costModal').modal('hide')
  }

  $scope.showCostModal = function(is_edit = 0, currentIndex = null) {
      $scope.is_edit = is_edit
      $scope.currentIndex = currentIndex
      job_order_cost_datatable.ajax.reload()
      $('#costModal').modal()
  }

  $scope.inOutCheck=function()
  {
    //masuk
    if ($scope.formData.jenis == 1) {
      $scope.detail.jenis=1 //biaya
      $scope.isDisablePiutang=true
    }else {
      $scope.isDisablePiutang=false
    }
    return
  }
  $scope.detailJournalCheck=function()
  {
    let detailJournal=$scope.formData.detail
    if(detailJournal.length > 0) {
      $scope.isDisableHeader=true
    }else {
      $scope.isDisableHeader=false
    }
  }
  $scope.append=function() {
    if ($scope.detail.jenis == 2 && typeof $scope.detail.vendor_id == "undefined") {
       toastr.warning("vendor harus diisi, apabila jenis yang dipilih piutang karyawan.");
       return;
    }
    $scope.dcp=null;
    $scope.app_description=function() {
      if ($scope.detail.description) {
        $scope.dcp=$scope.detail.description;
        return $scope.detail.description;
      } else {
        $scope.dcp=null;
        return '';
      }
    }
    $scope.vdr_id=null;
    $scope.app_vendor=function() {
      if ($scope.detail.vendor_id) {
        $scope.vdr_id=$scope.detail.vendor_id.id;
        return $scope.detail.vendor_id.name;
      } else {
        $scope.vdr_id=null;
        return '';
      }
    }

    if(typeof $('#detail_file').prop('files')[0] !== 'undefined')
        $scope.uploadFile();
    else
        $scope.populateTable();
  }
  $scope.change_cash_bank=function(type) {
    // $scope.cash_bank=$filter('filter')($scope.data.account, {no_cash_bank: $scope.formData.type, company_id: $scope.formData.company_id}, true);
    $scope.cash_bank=[];
    angular.forEach($scope.data.account, function(val,i) {
      if (type==1) {
        if (val.no_cash_bank==1) {
          $scope.cash_bank.push({id:val.id,name:val.account_name});
        }
      } else {
        if (val.no_cash_bank==2) {
          $scope.cash_bank.push({id:val.id,name:val.account_name});
        }
      }
    });

  }
  $scope.jenisChange=function(){
    let whereVendor = {is_vendor: 1, vendor_status_approve: 2}
    let wherePegawai = {is_pegawai: 1}

    if ($scope.detail.jenis == 1) { //biaya
      $scope.data.vendor=$filter('filter')($scope.vendors, whereVendor, true)
    }else { //piutang
      $scope.data.vendor=$filter('filter')($scope.vendors, wherePegawai, true)
    }
  }
  $scope.vendorChange=function(){
    if ($scope.detail.jenis == 2) { //piutang
      let accountPiutang=$scope.data.accountDefaultPiutang

      if ($scope.detail.vendor_id.akun_piutang == null) {
        $scope.data.account=$filter('filter')($scope.accounts, {id: accountPiutang.id}, true)
        $scope.detail.account_id=$scope.data.account[0]
        return
      }

      $scope.data.account=$filter('filter')($scope.accounts, {id: $scope.detail.vendor_id.akun_piutang}, true)
      $scope.detail.account_id=$scope.data.account[0]
    }
  }
  $scope.hitungAmount=function() {
    $scope.totalAmount=0;
    if($scope.formData.detail.length > 0){
      angular.forEach($scope.formData.detail, function(val,i) {
        console.log(val)
        if (val) {
          $scope.totalAmount+=parseFloat(val.amount);
        }
      })
    } else {
        $scope.totalAmount = 0
    }
  }
  $scope.deleteAppend=function(ids) {
    $scope.deleteFile($scope.formData.detail[ids].file);

    $('#row-'+ids).remove();
    rowDetail=$scope.formData.detail
    rowDet=$scope.formData.det
    rowDetail.splice(ids, 1)
    rowDet.splice(ids, 1)
    $scope.formData.detail = rowDetail
    $scope.detailJournalCheck();
    $scope.hitungAmount();
    $scope.urut = $scope.formData.detail.length;
  }
  $scope.submitForm=function() {
    $scope.formData.kasbon_id = $scope.kasbon_id;

    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/finance/cash_transaction?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $rootScope.backward();
        // if($scope.kasbon_id > 0)
        //     $state.go('finance.kas_bon');
        // else
        //     $state.go('finance.cash_transaction');
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

  $scope.populateTable=function(filename=""){
    var html="";
    html+="<tr id='row-"+$scope.urut+"'>";
    html+='<td ng-click="showCostModal(1, '+$scope.urut+')"><% formData.detail['+$scope.urut+'].code %></td>';
    html+='<td ng-click="showCostModal(1, '+$scope.urut+')"><% formData.detail['+$scope.urut+'].name %></td>';
    html+="<td>"+($scope.detail.account_id?$scope.detail.account_id.account_name:'(unset)')+"</td>";
    html+="<td>"+filename+"</td>"
    html+="<td>"+($scope.detail.description?$scope.detail.description:'')+"</td>";
    html+="<td><input type='text' class='form-control text-center' jnumber2 only-num ng-change='formData.detail["+$scope.urut+"].amount=formData.det["+$scope.urut+"].amount;hitungAmount()' ng-model='formData.det["+$scope.urut+"].amount'></td>"
    // html+="<td>"+$filter('number')($scope.detail.amount)+"</td>";
    html+="<td><a ng-click='deleteAppend("+$scope.urut+")'><span class='fa fa-trash'></span></a></td>";
    html+="</tr>";

    $('#appendTable tbody').append($compile(html)($scope));
    var data_push = {
        account_id:($scope.detail.account_id?$scope.detail.account_id.id:null),
        description:($scope.detail.description?$scope.detail.description:''),
        amount:$scope.detail.amount,
        jenis:$scope.detail.jenis,
        code:$scope.detail.code,
        name:$scope.detail.name,
        job_order_cost_id:$scope.detail.job_order_cost_id,
        manifest_cost_id:$scope.detail.manifest_cost_id,
        file:filename
    }

    $scope.formData.detail.push(data_push);
    $scope.formData.det.push(
      {amount:$scope.detail.amount}
    )

    $scope.urut = $scope.formData.detail.length;
    $scope.detail={};
    $scope.detail.jenis=1;
    $scope.detailJournalCheck();
    $scope.hitungAmount();
    $('#detail_file').val('');
  }

  $scope.deleteFile = function(filename) {
    $.ajax({
      type: "delete",
      url: baseUrl+'/finance/cash_transaction/delete_bukti?filename='+filename+'&_token='+csrfToken,
      contentType: false,
      cache: false,
      processData: false,
      success: function(data) {
      },
      error: function(xhr, response, status) {
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

  $scope.uploadFile=function() {
    var file_data = $('#detail_file').prop('files')[0];
    var form_data = new FormData();
    form_data.append('file', file_data);

    $.ajax({
      type: "post",
      url: baseUrl+'/finance/cash_transaction/upload_bukti?_token='+csrfToken,
      contentType: false,
      cache: false,
      processData: false,
      data: form_data,
      success: function(data) {
        $scope.populateTable(data.file);
      },
      error: function(xhr, response, status) {
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
app.controller('financeCashShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Detail Transaksi Kas/Bank";
  $('.ibox-content').toggleClass('sk-loading');

  $scope.disBtn = false

  $scope.show = function() {
      $http.get(baseUrl+'/finance/cash_transaction/'+$stateParams.id).then(function(data) {
          $scope.item=data.data.item;
          $scope.detail=data.data.detail;
          $scope.can_approve = data.data.can_approve;
          $scope.totalAll=0;
          angular.forEach(data.data.detail,function(val,i) {
            $scope.totalAll+=val.amount;
            $scope.detail[i].hasFile = val.uploaded_file != "" && val.uploaded_file != null;
            $scope.detail[i].fileLink = baseUrl + '/files/' + val.uploaded_file;
          });
          $('.ibox-content').removeClass('sk-loading');
      }, function(){
          $scope.show()
      });
  }
  $scope.show()
  

  
  job_order_cost_datatable = $('#job_order_cost_datatable').DataTable({
    processing: true,
    serverSide: true,
    dom: 'frtp',
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/job_order_cost_datatable',
      data : function(d) {
          d.cost_type = 2
      }
    },
    columns:[
      // {data:"checkbox",name:"checkbox",className:"text-center",orderable:false},
      {data:"code",name:"job_orders.code"},
      {data:"cost_type",name:"cost_types.name"},
      {data:"vendor",name:"contacts.name"},
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.qty)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.price)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.total_price)
      }
    ],
    createdRow: function(row, data, dataIndex) {
      $(row).find('td').attr('ng-click', 'selectJobOrderCost($event.currentTarget)')
      $compile(angular.element(row).contents())($scope);
    }
  });


  manifest_cost_datatable = $('#manifest_cost_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:false,
    dom: 'frtp',
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/manifest_cost_datatable',
      data : function(d) {
          d.cost_type = 2
      }
    },
    columns:[
      // {data:"checkbox",name:"checkbox",className:"text-center",orderable:false},
      {data:"code",name:"manifests.code"},
      {data:"cost_type",name:"cost_types.name"},
      {data:"vendor",name:"contacts.name"},
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.qty)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.price)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.total_price)
      }
    ],
    createdRow: function(row, data, dataIndex) {
      $(row).find('td').attr('ng-click', 'selectManifestCost($event.currentTarget)')
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.selectJobOrderCost = function(obj) {
      if($scope.saveState != 1) {
          $scope.saveState = 1
          var tr = $(obj)
          var data = job_order_cost_datatable.row(tr).data()
          $scope.cost_detail = {}
          $scope.cost_detail.job_order_cost_id = data.id
          $scope.cost_detail.manifest_cost_id = null
          $scope.cost_detail.amount = data.total_price
          $scope.cost_detail.description = data.description

          $http.put(baseUrl+'/finance/cash_transaction/detail/manifest/' + $scope.detail[$scope.currentIndex].id, $scope.cost_detail).then(function(data) {
            $scope.saveState = 0
            toastr.success('Data berhasil disimpan')    
            $('#costModal').modal('hide')
            $scope.show()
          },function(err) {
            $scope.saveState = 0
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
            $('#costModal').modal('hide')
          });
      }
  }

  $scope.selectManifestCost = function(obj) {
      if($scope.saveState != 1) {
          $scope.saveState = 1
          var tr = $(obj)
          var data = manifest_cost_datatable.row(tr).data()
          $scope.cost_detail = {}
          $scope.cost_detail.job_order_cost_id = null
          $scope.cost_detail.manifest_cost_id = data.id
          $scope.cost_detail.amount = data.total_price
          $scope.cost_detail.description = data.description

          $http.put(baseUrl+'/finance/cash_transaction/detail/manifest/' + $scope.detail[$scope.currentIndex].id, $scope.cost_detail).then(function(data) {
            $scope.saveState = 0
            toastr.success('Data berhasil disimpan')    
            $('#costModal').modal('hide')
            $scope.show()
          },function(err) {
            $scope.saveState = 0
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
            $('#costModal').modal('hide')
          });
      }
  }

  $scope.showCostModal = function(is_edit = 0, currentIndex) {
      $scope.is_edit = is_edit
      $scope.currentIndex = currentIndex
      job_order_cost_datatable.ajax.reload()
      $('#costModal').modal()
  }

  $scope.openJobOrderCost = function() {
      job_order_cost_datatable.ajax.reload()
      $('#job_order_cost').show()
      $('#manifest_cost').hide()
      $('#job_order_cost_btn').addClass('active')
      $('#manifest_cost_btn').removeClass('active')
  }
  $scope.openJobOrderCost()

  $scope.openManifestCost = function() {
      manifest_cost_datatable.ajax.reload()
      $('#job_order_cost').hide()
      $('#manifest_cost').show()
      $('#job_order_cost_btn').removeClass('active')
      $('#manifest_cost_btn').addClass('active')
  }

  $scope.back = function() {
    if($rootScope.currentPage == '') {
        $state.go('finance.cash_transaction')
    } else {
        $rootScope.currentPage = ''
        $state.go('finance.kas_bon')

    }
  }

  $scope.reject=function(id) {
    var conf=confirm("Apakah anda ingin membatalkan transaksi kas ini ?")
    if (conf) {
      $scope.disBtn=true
      $http.post(baseUrl+'/finance/cash_transaction/reject/'+id).then(function(data) {
        $scope.disBtn=false
        toastr.success("Transaksi Kas Telah Dibatalkan!");
        $state.go('finance.cash_transaction')
      },function(err) {
        $scope.disBtn=false
        toastr.error(err.data.message,"Maaf!");
      });
    }
  }

  $scope.approve=function(id) {
    // console.log(id)
    var conf=confirm("Apakah anda ingin menyetujui pengeluaran kas ini ?")
    if (conf) {
      $http.post(baseUrl+'/finance/cash_transaction/approve/'+id).then(function(data) {
        toastr.success("Transaksi Kas telah disetujui!");
        $state.go('finance.cash_transaction');
      },function(err) {
        toastr.error(err.data.message,"Maaf!");
      });
    }
  }

});
app.controller('financeCashEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Transaksi Kas/Bank";
  $('.ibox-content').toggleClass('sk-loading');
  $scope.isDisable = true;
  $scope.formData={};
  $scope.detail={};
  $scope.in_out=[
    {id:1,name:"Masuk"},
    {id:2,name:"Keluar"},
  ];
  $scope.urut=0;
  $scope.type=[
    {id:1,name:"Kas"},
    {id:2,name:"Bank"},
  ];
  $scope.totalAmount=0;
  $scope.formData.detail=[];
  $scope.formData.det=[];


  job_order_cost_datatable = $('#job_order_cost_datatable').DataTable({
    processing: true,
    serverSide: true,
    dom: 'frtp',
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/job_order_cost_datatable',
      data : function(d) {
          d.cost_type = 2
      }
    },
    columns:[
      // {data:"checkbox",name:"checkbox",className:"text-center",orderable:false},
      {data:"code",name:"job_orders.code"},
      {data:"cost_type",name:"cost_types.name"},
      {data:"vendor",name:"contacts.name"},
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.qty)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.price)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.total_price)
      }
    ],
    createdRow: function(row, data, dataIndex) {
      $(row).find('td').attr('ng-click', 'selectJobOrderCost($event.currentTarget)')
      $compile(angular.element(row).contents())($scope);
    }
  });


  manifest_cost_datatable = $('#manifest_cost_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX:false,
    dom: 'frtp',
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/manifest_cost_datatable',
      data : function(d) {
          d.cost_type = 2
      }
    },
    columns:[
      // {data:"checkbox",name:"checkbox",className:"text-center",orderable:false},
      {data:"code",name:"manifests.code"},
      {data:"cost_type",name:"cost_types.name"},
      {data:"vendor",name:"contacts.name"},
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.qty)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.price)
      },
      {
        data:null,
        className:'text-right',
        orderable:false,
        searchable : false,
        render:resp => $filter('number')(resp.total_price)
      }
    ],
    createdRow: function(row, data, dataIndex) {
      $(row).find('td').attr('ng-click', 'selectManifestCost($event.currentTarget)')
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.openJobOrderCost = function() {
      $('#job_order_cost').show()
      $('#manifest_cost').hide()
      $('#job_order_cost_btn').addClass('active')
      $('#manifest_cost_btn').removeClass('active')
  }
  $scope.openJobOrderCost()

  $scope.openManifestCost = function() {
      manifest_cost_datatable.ajax.reload()
      $('#job_order_cost').hide()
      $('#manifest_cost').show()
      $('#job_order_cost_btn').removeClass('active')
      $('#manifest_cost_btn').addClass('active')
  }
  $scope.selectJobOrderCost = function(obj) {
      var tr = $(obj)
      var data = job_order_cost_datatable.row(tr).data()
      if($scope.is_edit != 1) {
          $scope.detail.code = data.code
          $scope.detail.name = data.cost_type
          $scope.detail.job_order_cost_id = data.id
          $scope.detail.manifest_cost_id = null
          $scope.detail.account_id = data.account_id
          $scope.detail.amount = data.total_price
          $scope.detail.description = data.description
      } else {

          $scope.formData.detail[$scope.currentIndex].code = data.code
          $scope.formData.detail[$scope.currentIndex].name = data.cost_type
          $scope.formData.detail[$scope.currentIndex].job_order_cost_id = data.id
          $scope.formData.detail[$scope.currentIndex].manifest_cost_id = null
          $scope.formData.detail[$scope.currentIndex].description = data.description
          $scope.hitungAmount();
          $scope.currentIndex = null
          $scope.is_edit = 0          
      }
      $('#costModal').modal('hide')

  }

  $scope.selectManifestCost = function(obj) {
      var tr = $(obj)
      var data = manifest_cost_datatable.row(tr).data()
      if($scope.is_edit != 1) {
          $scope.detail.code = data.code
          $scope.detail.name = data.cost_type
          $scope.detail.job_order_cost_id = null
          $scope.detail.manifest_cost_id = data.id
          $scope.detail.amount = data.total_price
          $scope.detail.description = data.description
      } else {
          $scope.formData.detail[$scope.currentIndex].code = data.code
          $scope.formData.detail[$scope.currentIndex].name = data.cost_type
          $scope.formData.detail[$scope.currentIndex].job_order_cost_id = null
          $scope.formData.detail[$scope.currentIndex].manifest_cost_id = data.id
          $scope.formData.detail[$scope.currentIndex].description = data.description

          $scope.hitungAmount();
          $scope.currentIndex = null
          $scope.is_edit = 0          
      }
      $('#costModal').modal('hide')
  }

  $scope.showCostModal = function(is_edit = 0, currentIndex = null) {
      $scope.is_edit = is_edit
      $scope.currentIndex = currentIndex
      job_order_cost_datatable.ajax.reload()
      $('#costModal').modal()
  }

  $http.get(baseUrl+'/finance/cash_transaction/'+$stateParams.id+'/edit').then(function(data) {
    // console.log(data.data.detail.length);
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.date_transaction=$filter('minDate')(dt.date_transaction);
    $scope.formData.company_id=dt.company_id;
    $scope.formData.jenis=dt.jenis;
    $scope.formData.type=dt.type;
    $scope.formData.description=dt.description;
    $scope.change_cash_bank(dt.type);
    $scope.formData.cash_bank=dt.account_id;

    angular.forEach(data.data.detail, function(val,i) {
      $scope.formData.detail.push({
        id: val.id,
        account_id: val.account_id,
        code: val.code,
        name: val.name,
        job_order_cost_id: val.job_order_cost_id,
        manifest_cost_id: val.manifest_cost_id,
        file: val.uploaded_file,
        description: val.description,
        amount: val.amount,
        jenis: val.jenis,
      })
    });

    $scope.urut=data.data.detail.length;
    $scope.hitungAmount();
    $('.ibox-content').toggleClass('sk-loading');
  },function(err) {
    toastr.error(err.data.message,"Maaf!");
    $state.go('finance.cash_transaction');
  });
  $scope.append=function() {
    $scope.dcp=null;
    $scope.app_description=function() {
      if ($scope.detail.description) {
        $scope.dcp=$scope.detail.description;
        return $scope.detail.description;
      } else {
        $scope.dcp=null;
        return '';
      }
    }
    $scope.vdr_id=null;
    $scope.app_vendor=function() {
      if ($scope.detail.vendor_id) {
        $scope.vdr_id=$scope.detail.vendor_id.id;
        return $scope.detail.vendor_id.name;
      } else {
        $scope.vdr_id=null;
        return '';
      }
    }

    if(typeof $('#detail_file').prop('files')[0] !== 'undefined')
        $scope.uploadFile();
    else
        $scope.populateTable();


  }
  $scope.change_cash_bank=function(type) {
    $scope.cash_bank=[];
    angular.forEach($scope.data.account, function(val,i) {
      if (type==1) {
        if (val.no_cash_bank==1) {
          $scope.cash_bank.push({id:val.id,name:val.account_name});
        }
      } else {
        if (val.no_cash_bank==2) {
          $scope.cash_bank.push({id:val.id,name:val.account_name});
        }
      }
    });
  }

  $scope.hitungAmount=function() {
    $scope.totalAmount=0;
    if($scope.formData.detail){
      angular.forEach($scope.formData.detail, function(val,i) {
        if (val) {
          $scope.totalAmount+=parseFloat(val.amount);
        }
      })
    }
  }

  $scope.deleteAppend=function(ids) {
    $scope.deleteFile($scope.formData.detail[ids].file);

    $('#row-'+ids).remove();
    rowDetail=$scope.formData.detail
    rowDet=$scope.formData.det
    rowDetail.splice(ids, 1)
    rowDet.splice(ids, 1)
    $scope.formData.detail = rowDetail
    $scope.detailJournalCheck();
    $scope.hitungAmount();
    $scope.urut = $scope.formData.detail.length;
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "put",
      url: baseUrl+'/finance/cash_transaction/'+$stateParams.id+'?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('finance.cash_transaction');
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

  $scope.populateTable = function(filename='') {
    var html="";
    html+="<tr id='row-"+$scope.urut+"'>";
    html+='<td ng-click="showCostModal(1, '+$scope.urut+')"><% formData.detail['+$scope.urut+'].code %></td>';
    html+='<td ng-click="showCostModal(1, '+$scope.urut+')"><% formData.detail['+$scope.urut+'].name %></td>';    html+='<td><select class="form-control" data-placeholder-text-single="\'Pilih Akun\'" chosen allow-single-deselect="false" ng-model="formData.detail['+$scope.urut+'].account_id" ng-options="s.id as s.account_name for s in data.account"><option value=""></option></select></td>';
    html+='<td>'+filename+'</td>';
    html+='<td><input type="text" class="form-control" ng-model="formData.detail['+$scope.urut+'].description"></td>';
    html+='<td><input type="text" class="form-control" ng-change="hitungAmount()" jnumber2 only-num ng-model="formData.detail['+$scope.urut+'].amount"></td>';
    // html+="<td><input type='text' class='form-control text-center' jnumber2 only-num ng-change='formData.detail["+$scope.urut+"].amount=formData.det["+$scope.urut+"].amount;hitungAmount()' ng-model='formData.det["+$scope.urut+"].amount'></td>"
    // html+="<td>"+$filter('number')($scope.detail.amount)+"</td>";
    html+="<td><a ng-click='deleteAppend("+$scope.urut+")'><span class='fa fa-trash'></span></a></td>";
    html+="</tr>";

    $('#appendTable tbody').append($compile(html)($scope));
    $scope.formData.detail.push(
      {
        id:0,
        account_id:($scope.detail.account_id?$scope.detail.account_id.id:null),
        file:filename,
        description:($scope.detail.description?$scope.detail.description:''),
        amount:$scope.detail.amount,
        jenis:$scope.detail.jenis,
        job_order_cost_id:$scope.detail.job_order_cost_id,
        manifest_cost_id:$scope.detail.manifest_cost_id,
      }
    )

    //alert($scope.formData.detail[$scope.urut]);

    $scope.urut = $scope.formData.detail.length;
    $scope.detail={};
    $scope.detail.jenis=1;
    $scope.hitungAmount();
  }

  $scope.deleteFile = function(filename) {
    $.ajax({
      type: "delete",
      url: baseUrl+'/finance/cash_transaction/delete_bukti?filename='+filename+'&_token='+csrfToken,
      contentType: false,
      cache: false,
      processData: false,
      success: function(data) {
      },
      error: function(xhr, response, status) {
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

  $scope.uploadFile=function() {
    var file_data = $('#detail_file').prop('files')[0];
    var form_data = new FormData();
    form_data.append('file', file_data);

    $.ajax({
      type: "post",
      url: baseUrl+'/finance/cash_transaction/upload_bukti?_token='+csrfToken,
      contentType: false,
      cache: false,
      processData: false,
      data: form_data,
      success: function(data) {
        $scope.populateTable(data.file);
      },
      error: function(xhr, response, status) {
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
