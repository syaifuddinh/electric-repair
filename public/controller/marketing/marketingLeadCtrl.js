app.controller('marketingLead', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="Leads";
  $('.ibox-content').addClass('sk-loading');
  $scope.filterData={}
  $scope.filterData.step=1
  $scope.is_filter = false;
  $scope.step=[
    {id:1,name:"Lead"},
    {id:2,name:"Opportunity"},
    {id:3,name:"Inquery"},
    {id:4,name:"Quotation"},
    {id:5,name:"Kontrak"},
    {id:6,name:"Batal Lead"},
    {id:7,name:"Batal Opportunity"},
    {id:8,name:"Batal Inquery"},
    {id:9,name:"Batal Quotation"},
  ]

  $http.get(baseUrl+'/marketing/lead').then(function(data) {
    $scope.data=data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right m-l-sm ',
        filename: 'Marketing - Lead - ' + new Date,
        sheetName: 'Data',
        title: 'Marketing - Lead',
        exportOptions: {
          rows: {
              selected: true
          }
        },
    }],
    searching:false,
    order:[[9,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/lead_datatable',
      data: function(d) {
        d.company_id=$scope.filterData.company_id;
        d.lead_source_id=$scope.filterData.lead_source_id;
        d.name=$scope.filterData.name;
        d.lead_status_id=$scope.filterData.lead_status_id;
        d.step=$scope.filterData.step;
      },
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"company.name",name:"company.name"},
      {
        data:null,
        orderable:false,
        searchable:false,
        render: resp => $filter('fullDate')(resp.created_at)
      },
      {data:"name",name:"name"},
      {data:"address",name:"address"},
      {data:"phone_lengkap",name:"phone_lengkap"},
      {data:"email",name:"email"},
      {data:"lead_source.name",name:"lead_source.name"},
      {data:"lead_status.status",name:"lead_status.status"},
      {data:"step",name:"step"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('marketing.leads.detail')) {
        $(row).find('td').attr('ui-sref', 'marketing.lead.show({id:' + data.id + '})')
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
    var url = baseUrl + '/excel/lead_export?';
    url += params;
    location.href = url; 
  }

  $scope.filter=function() {
    oTable.ajax.reload();
  }
  $scope.reset_filter=function() {
    $scope.filterData = {};
    $scope.filter();
  }

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/lead/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});
app.controller('marketingLeadCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle= $rootScope.solog.label.lead.title;
  $('.ibox-content').addClass('sk-loading');

  new google.maps.places.Autocomplete(
  (document.getElementById('place_search')), {
    types: []
  });

  $http.get(baseUrl+'/marketing/lead/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $scope.formData.address = $('#place_search').val();
    $http.post(baseUrl+'/marketing/lead',$scope.formData).then(function(data) {
      $state.go('marketing.lead');
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
app.controller('marketingLeadEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Lead";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={};
  new google.maps.places.Autocomplete(
  (document.getElementById('place_search')), {
    types: []
  });

  $http.get(baseUrl+'/marketing/lead/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.company_id=dt.company_id;
    $scope.formData.lead_status_id=dt.lead_status_id;
    $scope.formData.lead_source_id=dt.lead_source_id;
    $scope.formData.city_id=dt.city_id;
    $scope.formData.lead_industry_id=dt.industry_id;
    $scope.formData.sales_id=dt.sales_id;
    $scope.formData.name=dt.name;
    $scope.formData.address=dt.address;
    $scope.formData.postal_code=dt.postal_code;
    $scope.formData.phone=dt.phone;
    $scope.formData.phone2=dt.phone2;
    $scope.formData.email=dt.email;
    $scope.formData.contact_person=dt.contact_person;
    $scope.formData.contact_person_email=dt.contact_person_email;
    $scope.formData.contact_person_phone=dt.contact_person_no;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/marketing/lead/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('marketing.lead.show',{id:$stateParams.id});
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
app.controller('marketingLeadShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Lead";
  if ($state.current.name=="marketing.lead.show") {
    $state.go('marketing.lead.show.detail');
  }

});
app.controller('marketingLeadShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Lead";
  $('.sk-container').addClass('sk-loading');
  $scope.item={};

  $(".clockpick").clockpicker({
    placement:'top',
    autoclose:true,
    donetext:'DONE',
  });

  $scope.step=[
    {id:1,name:"Lead"},
    {id:2,name:"Opportunity"},
    {id:3,name:"Inquery"},
    {id:4,name:"Quotation"},
    {id:5,name:"Kontrak"},
    {id:6,name:"Batal Lead"},
    {id:7,name:"Batal Opportunity"},
    {id:8,name:"Batal Inquery"},
    {id:9,name:"Batal Quotation"},
  ]

  $http.get(baseUrl+'/marketing/lead/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.activity=data.data.activity;
    $scope.lead_status=data.data.lead_status;
    $('.sk-container').removeClass('sk-loading');
  });

  $scope.changeStatus=function() {
    $scope.statusData={};
    $scope.statusData.lead_status_id=$scope.item.lead_status_id;
    $scope.statusData.is_activity=0;
    $scope.statusData.plan_date=dateNow;
    $scope.statusData.plan_time=timeNow;
    $('#modalStatus').modal('show');
  }

  $scope.cancelLead=function() {
    var cofs=confirm("Apakah anda yakin ingin membatalkan Lead ini?");
    if (cofs) {
      $http.post(baseUrl+'/marketing/lead/cancel_lead/'+$stateParams.id).then(function(data) {
        toastr.success("Lead telah dibatalkan!");
        $state.reload();
      });
    }
  }
  $scope.cancelCancelLead=function() {
    var cofs=confirm("Apakah anda ingin mengembalikan Lead ini?");
    if (cofs) {
      $http.post(baseUrl+'/marketing/lead/cancel_cancel_lead/'+$stateParams.id).then(function(data) {
        toastr.success("Berhasil!");
        $state.reload();
      });
    }
  }

  $scope.disBtn=false;
  $scope.submitStatus=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/lead/change_status/'+$stateParams.id,$scope.statusData).then(function(data) {
      $('#modalStatus').modal('hide');
      $timeout(function() {
        $state.reload();
      },1000)
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
app.controller('marketingLeadShowActivity', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Lead";
  $('.sk-container').addClass('sk-loading');

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    
    
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/lead_activity_datatable',
      data:function(d) {
        d.id=$stateParams.id;
      },
      dataSrc: function(d) {
          $('.sk-container').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"name",name:"name"},
      {data:"date_activity",name:"date_activity"},
      {data:"is_done",name:"is_done"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/marketing/lead/delete_activity/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.done=function(ids) {
    var cfs=confirm("Apakah Aktivitas Sudah Selesai?");
    if (cfs) {
      $http.post(baseUrl+'/marketing/lead/done_activity/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Aktivitas Diselesaikan!");
      });
    }
  }

  $http.get(baseUrl+'/marketing/lead/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.activity=data.data.activity;
  });
  $scope.formData={};
  $scope.creates=function() {
    $scope.modalTitle="Tambah Aktivitas";
    $scope.formData={};
    $scope.formData.date_activity=dateNow;
    $('#modal').modal('show');
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/lead/store_activity/'+$stateParams.id,$scope.formData).then(function(data) {
      $('#modal').modal('hide');
      oTable.ajax.reload();
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
app.controller('marketingLeadShowDocument', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Lead";
  $('.sk-container').addClass('sk-loading');
  $scope.formData={};
  $scope.creates=function() {
    $scope.modalTitle="Tambah Berkas";
    $scope.formData={};
    $('#modal').modal('show');
  }
  $scope.urls=baseUrl;
  $http.get(baseUrl+'/marketing/lead/document/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $('.sk-container').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/lead/store_document/'+$stateParams.id+'?_token='+csrfToken,
      contentType: false,
      cache: false,
      processData: false,
      data: new FormData($('#forms')[0]),
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        $('#modal').modal('hide');
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

  $scope.dltfileny=function(idkirim) {
    // $scope.disBtn=true;
    if (confirm("Yakin ingin menghapus berkas?")) {
       $.ajax({
      type: "post",
      url: baseUrl+'/marketing/lead/delete_document/'+idkirim+'?_token='+csrfToken,
      contentType: false,
      cache: false,
      processData: false,
      data: new FormData($('#forms')[0]),
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        $('#modal').modal('hide');
        toastr.success("File Berhasil Dihapus");
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
    } else {
    }
   
  }

});
app.controller('marketingLeadOpportunity', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Generate Opportunity";
  $('.ibox-content').addClass('sk-loading');
  $scope.inq_name="Opportunity";
  $scope.formData={};

  $http.get(baseUrl+'/marketing/opportunity/create').then(function(data) {
    $scope.data=data.data;
    // $scope.formData.company_id=compId;
    // $scope.formData.date_opportunity=dateNow;
    $http.get(baseUrl+'/marketing/lead/cari_lead/'+$stateParams.id).then(function(data) {
      var dt=data.data;
      $scope.formData.company_id=dt.company_id;
      $scope.formData.date_opportunity=dateNow;
      $scope.formData.name=dt.name;
      $scope.formData.customer_id=dt.id;
      $scope.formData.sales_id=dt.sales_id;
      $scope.formData.customer_stage_id=dt.step;

      $('.ibox-content').removeClass('sk-loading');
    });
  });


  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/lead/store_opportunity/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('marketing.lead.show',{id:$stateParams.id});
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
app.controller('marketingLeadInquery', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Generate Inquery";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={};
  $scope.inq_name="Inquery";

  $http.get(baseUrl+'/marketing/opportunity/create').then(function(data) {
    $scope.data=data.data;
    // $scope.formData.company_id=compId;
    // $scope.formData.date_opportunity=dateNow;
  });
  $http.get(baseUrl+'/marketing/lead/cari_lead/'+$stateParams.id).then(function(data) {
    var dt=data.data;
    $scope.formData.company_id=dt.company_id;
    $scope.formData.date_opportunity=dateNow;
    $scope.formData.name=dt.name;
    $scope.formData.customer_id=dt.id;
    $scope.formData.sales_id=dt.sales_id;
    $scope.formData.customer_stage_id=dt.step;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/lead/store_inquery/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('marketing.lead.show',{id:$stateParams.id});
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
app.controller('marketingLeadQuotation', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Generate Quotation";
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

  $http.get(baseUrl+'/marketing/lead/cari_lead/'+$stateParams.id).then(function(data) {
    var dt=data.data;
    $scope.formData.company_id=dt.company_id;
    $scope.formData.date_inquery=dateNow;
    $scope.formData.name=dt.name;
    $scope.formData.customer_id=dt.id;
    $scope.formData.sales_id=dt.sales_id;
    $scope.formData.customer_stage_id=dt.step;
    $scope.formData.bill_type=1;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/marketing/lead/store_quotation/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('marketing.lead.show',{id:$stateParams.id});
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
