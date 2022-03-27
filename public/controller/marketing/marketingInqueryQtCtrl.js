app.controller('marketingInqueryQt', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Inquiry";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData = {};
  $scope.inquery_status = [
    {id:2, name:'Inquery'},
    {id:6, name:'Batal Inquery'}
  ];

  $scope.showCustomer = function() {
      $http.get(baseUrl+'/contact/contact/customer').then(function(data) {
        $scope.customers=data.data;
      }, function(){
          $scope.showCustomer()
      });
  }
  $scope.showCustomer()

  $http.get(baseUrl+'/marketing/opportunity').then(function(data) {
    $scope.data=data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[1,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right m-l-sm ',
        filename: 'Marketing - Inquiry - ' + new Date,
        sheetName: 'Data',
        title: 'Marketing - Inquiry',
        exportOptions: {
          rows: {
              selected: true
          }
        },
    }],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/inquery_qt_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['customer_id'] = $scope.formData.customer_id;
        request['customer_stage_id'] = $scope.formData.customer_stage_id;
        request['status'] = $scope.formData.status;

        return request;
      },
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"code_inquery",name:"code_inquery",className:"font-bold"},
      {data:"date_inquery",name:"date_inquery"},
      {data:"customer.name",name:"customer.name",className:"font-bold"},
      {data:"customer_stage.name",name:"customer_stage.name"},
      {data:"sales_inquery.name",name:"sales_inquery.name"},
      {data:"description_inquery",name:"description_inquery",className:""},
      {data:"status",name:"status",className:""},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if(true) {
        $(row).find('td').attr('ui-sref', 'marketing.inquery_qt.show({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo('.ibox-tools')

  $compile($('thead'))($scope)

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/inquery_export?';
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
      $http.delete(baseUrl+'/marketing/inquery_qt/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});

app.controller('marketingInqueryQtCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Tambah Inquery";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={};

  $http.get(baseUrl+'/marketing/inquery_qt/create').then(function(data) {
    $scope.data=data.data;
    $scope.formData.company_id=compId;
    $scope.formData.date_inquery=dateNow;
    if (data.data.prospect) {
      $scope.formData.customer_stage_id=data.data.prospect.id;
    }
    $scope.opportunities = $scope.data.opportunity;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.send_type=[
    {id:1,name:"Sekali"},
    {id:2,name:"Per Hari"},
    {id:3,name:"Per Minggu"},
    {id:4,name:"Per Bulan"},
    {id:5,name:"Tidak Tentu"},
  ];

  $scope.oppoData={};
  $scope.changeOpportunity=function(id) {
    $http.get(baseUrl+'/marketing/inquery_qt/cari_oppo/'+id).then(function(data) {
      $scope.oppoData=data.data;
      var dt=data.data;
      //$scope.formData.company_id = dt.company_id;
      $scope.formData.customer = $filter('filter')($scope.data.customer, {'id': dt.customer_id})[0];
      $scope.formData.customer_stage = $filter('filter')($scope.data.stage, {'id': dt.customer_stage_id})[0];
      $scope.formData.sales = $filter('filter')($scope.data.sales, {'id': dt.sales_opportunity_id})[0];
    }, function(err) {
      $scope.oppoData={};
    });
  }

  $scope.showCustomer = function() {
      $http.get(baseUrl+'/contact/contact/customer').then(function(data) {
        $scope.customers=data.data;
      }, function(){
          $scope.showCustomer()
      });
  }
  $scope.showCustomer()

  $scope.changeCustomer = function(id) {
    $scope.data.opportunity = $filter('filter')($scope.opportunities, {customer_id: id});
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $scope.postData = $scope.formData;
    
    if($scope.formData.customer)
        $scope.postData.customer_id = $scope.formData.customer.id;
    
    if($scope.formData.customer_stage)
        $scope.postData.customer_stage_id = $scope.formData.customer_stage.id;
    
    if($scope.formData.sales)
        $scope.postData.sales_id = $scope.formData.sales.id;
    
    if($scope.formData.opportunity)
        $scope.postData.opportunity_id = $scope.formData.opportunity.id;


    $http.post(baseUrl+'/marketing/inquery_qt',$scope.postData).then(function(data) {
      $state.go('marketing.inquery_qt');
      toastr.success("Data Berhasil Disimpan.","Berhasil!");
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

app.controller('marketingInqueryQtShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Inquery";
    $scope.notBatal = true;
    $('.ibox-content').addClass('sk-loading');

  // Akses data yang terpilih
  $http.get(baseUrl+'/marketing/inquery_qt/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.detail=data.data.detail;

    if($scope.item.status > 4)
        $scope.notBatal = false;

    $scope.fileUrl = baseUrl;
    $('.ibox-content').removeClass('sk-loading');
  });

  // Tipe pengiriman
  $scope.send_type=[
    {id:1,name:"Sekali"},
    {id:2,name:"Per Hari"},
    {id:3,name:"Per Minggu"},
    {id:4,name:"Per Bulan"},
    {id:5,name:"Tidak Tentu"},
  ];


  $scope.addActivity=function() {
    $scope.dataActivity={};
    $scope.activityData={};
    $http.get(baseUrl+'/marketing/opportunity/data_activity/'+$stateParams.id).then(function(data) {
      $scope.dataActivity=data.data;
      $scope.activityData.date_activity=dateNow;
      $('#modalActivity').modal('show');
    });
  }

  $scope.status=[
    {id:1,name:"Opportunity"},
    {id:2,name:"Inquery"},
    {id:3,name:"Quotation"},
    {id:4,name:"Kontrak"},
    {id:5,name:"Batal Opportunity"},
    {id:6,name:"Batal Inquery"},
    {id:7,name:"Batal Quotation"},
  ]

  $scope.cancelInquery=function() {
    var cofs=confirm("Apakah anda yakin ingin membatalkan Inquery ini?");
    if (cofs) {
      $http.post(baseUrl+'/marketing/opportunity/cancel_inquery/'+$stateParams.id).then(function(data) {
        toastr.success("Inquery telah dibatalkan!");
        $state.reload();
      });
    }
  }

  $scope.cancelCancelInquery=function() {
    var cofs=confirm("Apakah anda yakin ingin mengembalikan Inquery ini?");
    if (cofs) {
      $http.post(baseUrl+'/marketing/opportunity/cancel_cancel_inquery/'+$stateParams.id).then(function(data) {
        toastr.success("Beerhasil!");
        $state.reload();
      });
    }
  }


  $scope.isDone=function(id) {
    var conf=confirm("Apakah Aktivitas ini sudah Selesai ?");
    if (conf) {
      $http.post(baseUrl+'/marketing/opportunity/done_activity/'+id).then(function(data) {
        $state.reload();
        toastr.success("Aktivitas Selesai!","Berhasil");
      });
    }
  }

  $scope.deleteActivity=function(id) {
    var conf=confirm("Apakah ingin menghapus aktivitas ini ?");
    if (conf) {
      $http.delete(baseUrl+'/marketing/opportunity/delete_activity/'+id).then(function(data) {
        $state.reload();
        toastr.success("Aktivitas Dihapus!","Berhasil");
      });
    }
  }

  $scope.disBtn=false;
  $scope.submitActivity=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/inquery_qt/store_activity/'+$stateParams.id+'?_token='+csrfToken,
      contentType: false,
      cache: false,
      processData: false,
      data: new FormData($('#forms')[0]),
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        $('#modalActivity').modal('hide');
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
app.controller('marketingInqueryQtEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Inquery";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={};
  $scope.is_edit=true;
  $http.get(baseUrl+'/marketing/inquery_qt/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.customer = $filter('filter')($scope.data.customer, {'id': dt.customer_id})[0];
    $scope.formData.customer_stage = $filter('filter')($scope.data.stage, {'id': dt.customer_stage_id})[0];
    $scope.formData.sales_id = dt.sales_inquery_id;
    $scope.formData.opportunity = $filter('filter')($scope.data.opportunity, {'id': dt.opportunity_id});
    $scope.formData.type_send=dt.type_send;
    $scope.formData.description_inquery=dt.description_inquery;
    $scope.formData.date_inquery=$filter('minDate')(dt.date_inquery);
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.send_type=[
    {id:1,name:"Sekali"},
    {id:2,name:"Per Hari"},
    {id:3,name:"Per Minggu"},
    {id:4,name:"Per Bulan"},
    {id:5,name:"Tidak Tentu"},
  ];

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $scope.postData = $scope.formData;
    $scope.postData.customer_id = $scope.formData.customer.id;
    $scope.postData.customer_stage_id = $scope.formData.customer_stage.id;
    $scope.postData.sales_id = $scope.formData.sales_id;
    $scope.postData.opportunity_id = $scope.formData.opportunity.id;

    delete $scope.postData.customer;
    delete $scope.postData.customer_stage;
    delete $scope.postData.sales;
    delete $scope.postData.opportunity;
    $http.put(baseUrl+'/marketing/inquery_qt/'+$stateParams.id,$scope.postData).then(function(data) {
      $state.go('marketing.inquery_qt');
      toastr.success("Data Berhasil Disimpan.","Berhasil!");
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
app.controller('marketingInqueryQtGenerate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Generate Quotation";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData={};

    $scope.send_type=[
        {id:1,name:"Sekali"},
        {id:2,name:"Per Hari"},
        {id:3,name:"Per Minggu"},
        {id:4,name:"Per Bulan"},
        {id:5,name:"Tidak Tentu"}
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

    $http.get(baseUrl+'/marketing/inquery/create').then(function(data) {
        $scope.data=data.data;
    });

  $http.get(baseUrl+'/marketing/inquery_qt/cari_oppo/'+$stateParams.id).then(function(data) {
    var dt=data.data;
    $scope.formData.company_id=parseInt(dt.company_id);
    $scope.formData.customer_id=parseInt(dt.customer_id);
    $scope.formData.customer_stage_id=parseInt(dt.customer_stage_id);
    $scope.formData.sales_id=parseInt(dt.sales_inquery_id);
    $scope.formData.send_type=parseInt(dt.type_send);
    $scope.formData.description_inquery=dt.description_inquery;


    $scope.formData.no_inquery = dt.code_inquery;
    $scope.formData.bill_type=1;
    $scope.formData.date_inquery=dateNow;
    $scope.formData.is_generate=true;
    $scope.formData.inquery_id=parseInt($stateParams.id);
    $('.ibox-content').removeClass('sk-loading');
  }, function(err) {
    $scope.oppoData={};
  });


  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/inquery?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('marketing.inquery');
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
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

// inquery customer ----------------------
app.controller('marketingInqueryCustomer', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Inquery Customer";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData = {};
  $http.get(baseUrl+'/marketing/opportunity').then(function(data) {
    $scope.data=data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Bfrtip',
    buttons: [
      {
        extend : 'excel',
        enabled : true,
        text : '<span class="fa fa-file-excel-o"></span> Export Excel',
        className : 'btn btn-default btn-sm',
        filename : 'Inquery Customer - '+new Date(),
        messageTop : 'Inquery Customer ',
        sheetName : 'Data',
        title : 'Inquery Customer '
      }
    ],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/inquery_customer_datatable',
      data : function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['customer_id'] = $scope.formData.customer_id;
        request['status'] = $scope.formData.status;

        return request;
      },
      dataSrc: function(d) {
          $('.ibox-content').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"customer.name",name:"customer.name",className:"font-bold"},
      {data:"name",name:"name"},
      {data:"created_at",name:"created_at"},
      {data:"is_done",name:"is_done",className:""},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
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

  oTable.buttons().container().appendTo('#export_button');
});
app.controller('marketingInqueryCustomerShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Inquery Customer";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/api/customer/inquery/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $('.ibox-content').removeClass('sk-loading');
  });

});
