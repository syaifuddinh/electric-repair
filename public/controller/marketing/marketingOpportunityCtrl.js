app.controller('marketingOppo', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Opportunity";
  $('.ibox-content').addClass('sk-loading');
  $scope.isFilter = false;
  $scope.formData = {};

  $http.get(baseUrl+'/marketing/opportunity').then(function(data) {
    $scope.data=data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order: [[7,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right m-l-sm ',
        filename: 'Marketing - Opportunity - ' + new Date,
        sheetName: 'Data',
        title: 'Marketing - Opportunity',
        exportOptions: {
          rows: {
              selected: true
          }
        },
    }],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/opportunity_datatable',
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
      {data:"code_opportunity",name:"code_opportunity",className:"font-bold"},
      {data:"date_opportunity",name:"date_opportunity"},
      {data:"customer.name",name:"customer.name",className:"font-bold"},
      {data:"customer_stage.name",name:"customer_stage.name"},
      {data:"sales_opportunity.name",name:"sales_opportunity.name"},
      {data:"description_opportunity",name:"description_opportunity",className:""},
      {data:"status",name:"status",className:""},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('marketing.opportunity.detail')) {
        $(row).find('td').attr('ui-sref', 'marketing.opportunity.show({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo('.ibox-tools')

  $compile($("table"))($scope)

  $scope.showCustomer = function() {
      $http.get(baseUrl+'/contact/contact/customer').then(function(data) {
        $scope.customers=data.data;
      }, function(){
          $scope.showCustomer()
      });
  }
  $scope.showCustomer()
  
  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/opportunity_export?';
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
      $http.delete(baseUrl+'/marketing/opportunity/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});

app.controller('marketingOppoCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Opportunity";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={};

  $http.get(baseUrl+'/marketing/opportunity/create').then(function(data) {
    $scope.data=data.data;
    $scope.formData.company_id=compId;
    $scope.formData.date_opportunity=dateNow;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/opportunity',$scope.formData).then(function(data) {
      $state.go('marketing.opportunity');
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
app.controller('marketingOppoEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Opportunity";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={};

  $http.get(baseUrl+'/marketing/opportunity/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.customer_id=dt.customer_id
    $scope.formData.company_id=dt.company_id;
    if (dt.date_opportunity) {
      $scope.formData.date_opportunity=$filter('minDate')(dt.date_opportunity);
    }
    $scope.formData.customer_stage_id=dt.customer_stage_id;
    $scope.formData.sales_id=dt.sales_opportunity_id;
    $scope.formData.description_opportunity=dt.description_opportunity;

    if (dt.interest) {
      $scope.formData.interest=[]
      var string=dt.interest+''
      var split=string.split(",")
      angular.forEach(split, function(val,i) {
        $scope.formData.interest.push(parseInt(val))
      })
    }
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/marketing/opportunity/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('marketing.opportunity');
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

app.controller('marketingOppoShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Opportunity";
  $('.ibox-content').addClass('sk-loading');

  console.log($state);
  $http.get(baseUrl+'/marketing/opportunity/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.stage=data.data.stage;
    $scope.detail=data.data.detail;
    $scope.service_group=data.data.service_group;

    $scope.interest_text=""
    if (data.data.item.interest) {
      var interest = data.data.item.interest;

      var split= typeof(interest) == 'string' ? interest.split(',') : [ interest ];
      angular.forEach(split, function(val,i) {

        $scope.interest_text+=$rootScope.findJsonId(val,$scope.service_group).name;
        $scope.interest_text += i == split.length - 1 ? '' : ', ';
      })
    }
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.status=[
    {id:1,name:"Opportunity"},
    {id:2,name:"Inquery"},
    {id:3,name:"Quotation"},
    {id:4,name:"Kontrak"},
    {id:5,name:"Batal Opportunity"},
    {id:6,name:"Batal Inquery"},
    {id:7,name:"Batal Quotation"},
  ]


  $scope.cancelOpportunity=function() {
    var cofs=confirm("Apakah anda yakin ingin membatalkan Opportunity ini?");
    if (cofs) {
      $http.post(baseUrl+'/marketing/opportunity/cancel_opportunity/'+$stateParams.id).then(function(data) {
        toastr.success("Opportunity telah dibatalkan!");
        $state.reload();
      });
    }
  }
  $scope.cancelCancelOpportunity=function() {
    var cofs=confirm("Apakah anda yakin ingin mengembalikan Opportunity ini?");
    if (cofs) {
      $http.post(baseUrl+'/marketing/opportunity/cancel_cancel_opportunity/'+$stateParams.id).then(function(data) {
        toastr.success("Berhasil!");
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

  $scope.addActivity=function() {
    $scope.dataActivity={};
    $scope.activityData={};
    $http.get(baseUrl+'/marketing/opportunity/data_activity/'+$stateParams.id).then(function(data) {
      $scope.dataActivity=data.data;
      $scope.activityData.date_activity=dateNow;
      $('#modalActivity').modal('show');
    });
  }

  $scope.disBtn=false;
  $scope.submitActivity=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/opportunity/store_activity/'+$stateParams.id+'?_token='+csrfToken,
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

app.controller('marketingOppoGenerate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Tambah Inquery";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={};
  $scope.is_edit=true;
  $scope.is_generated = true;

  $http.get(baseUrl+'/marketing/inquery_qt/create').then(function(data) {
    $scope.data=data.data;
    $scope.formData.opportunity_id=$stateParams.id;
    $scope.formData.opportunity = $filter('filter')($scope.data.opportunity, {'id': $stateParams.id})[0];
    $scope.changeOpportunity($stateParams.id);
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
      $scope.formData.date_inquery=$filter("minDate")(dt.date_opportunity);
      //$scope.formData.company_id = dt.company_id;
      // $scope.formData.customer = $filter('filter')($scope.data.customers, {'id': dt.customer_id})[0];
      $scope.formData.customer_id = dt.customer_id
      $scope.formData.customer_stage = $filter('filter')($scope.data.stage, {'id': dt.customer_stage_id})[0];
    }, function(err) {
      $scope.oppoData={};
    });
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $scope.postData = $scope.formData;
    $scope.postData.customer_stage_id = $scope.formData.customer_stage.id;
    $scope.postData.opportunity_id = $scope.formData.opportunity.id;

    // delete $scope.postData.customer;
    // delete $scope.postData.customer_stage;
    // delete $scope.postData.sales;
    // delete $scope.postData.opportunity;

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
app.controller('marketingOppoQuotation', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Buat Quotation";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={};

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

  $http.get(baseUrl+'/marketing/inquery/create').then(function(data) {
    $scope.data=data.data;
  });

  $http.get(baseUrl+'/marketing/inquery_qt/cari_oppo/'+$stateParams.id).then(function(data) {
    $scope.oppoData=data.data;
    var dt=data.data;
    $scope.formData.date_inquery=dateNow;
    $scope.formData.company_id=dt.company_id;
    $scope.formData.customer_id=dt.customer_id;
    $scope.formData.customer_stage_id=dt.customer_stage_id;
    $scope.formData.sales_id=dt.sales_opportunity_id;
    $scope.formData.bill_type=1;
    $scope.formData.is_generate=true;
    $scope.formData.inquery_id=$stateParams.id;
    $('.ibox-content').removeClass('sk-loading');
  }, function(err) {
    $scope.oppoData={};
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/inquery', $scope.formData).then(function(data) {
      $state.go('marketing.opportunity.show',{id:$stateParams.id});
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
