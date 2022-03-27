app.controller('contactVendor', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Vendors";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom: 'Blfrtip',
    buttons: [{
        extend: 'excel',
        enabled: true,
        action: newExportAction,
        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
        className: 'btn btn-default btn-sm pull-right m-l-sm',
        filename: 'Contact Vendor - ' + new Date,
        sheetName: 'Data',
        title: 'Contact Vendor',
        exportOptions: {
          rows: {
            selected: true
          }
        },
    }],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/vendor/vendor_datatable'
    },
    columns:[
      {data:"name",name:"name"},
      {data:"address",name:"address"},
      {data:"phone",name:"phone"},
      {data:"email",name:"email"},
      {data:"action_fr_contact",name:"action_fr_contact",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('vendor.vendor.detail')) {
          $(row).find('td').attr('ui-sref', 'contact.vendor.show({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo('.ibox-tools')

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/contact/contact/'+ids,{_token:csrfToken}).then(function(res) {
        if (res.status==200) {
          oTable.ajax.reload();
          toastr.success("Data Berhasil Dihapus!");
        } else {
          toastr.error("Error Has Found");
        }
      });
    }
  }

});
app.controller('contactVendorEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Vendor";

  new google.maps.places.Autocomplete(
  (document.getElementById('place_search')), {
    types: []
  });

  $scope.formData={}
  $scope.formData.is_vendor=1;

  $http.get(baseUrl+'/vendor/register_vendor/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData={
      company_id:dt.company_id,
      is_vendor:dt.is_vendor,
      code:dt.code,
      name:dt.name,
      address:dt.address,
      city_id:dt.city_id,
      postal_code:dt.postal_code,
      phone:dt.phone ? dt.phone : '',
      phone2:dt.phone2 ? dt.phone2 : '',
      fax:dt.fax,
      email:dt.email,
      contact_person:dt.contact_person,
      contact_person_email:dt.contact_person_email,
      contact_person_no:dt.contact_person_no,
      vendor_type_id:dt.vendor_type_id,
      akun_hutang:dt.akun_hutang,
      akun_piutang:dt.akun_piutang,
      akun_um_supplier:dt.akun_um_supplier,
      akun_um_customer:dt.akun_um_customer,
      term_of_payment:dt.term_of_payment,
      limit_piutang:dt.limit_piutang,
      limit_hutang:dt.limit_hutang,
      npwp:dt.npwp,
      pkp:dt.pkp,
      tax_id:dt.tax_id,
      description:dt.description,
      rek_no:dt.rek_no,
      rek_milik:dt.rek_milik,
      rek_bank_id:dt.rek_bank_id,
      rek_cabang:dt.rek_cabang,
    }
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/vendor/register_vendor/'+$stateParams.id+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('contact.vendor.show',{id:$stateParams.id});
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
app.controller('contactVendorShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Kontak";
    if ($state.current.name=="contact.vendor.show") {
        $state.go('contact.vendor.show.detail',{id:$stateParams.id});
    }
});
app.controller('contactVendorShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Info Vendor | Detail";

  $http.get(baseUrl+'/vendor/register_vendor/'+$stateParams.id).then(function(data) {
        $scope.data=data.data;
        $scope.data.phone2 = $scope.data.phone2 ? $scope.data.phone2 : null;
        console.log($scope.data.phone2)
  }, function() {
        $state.go('contact.vendor');
  });

  $scope.approve=function() {
    $http.post(baseUrl+'/vendor/register_vendor/approve/'+$stateParams.id).then(function(data) {
      toastr.success("Data Vendor Berhasil di Setujui.","Vendor Approved!");
      $state.reload();
    });
  }
});
app.controller('contactVendorShowDocument', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Info Vendor | Berkas";

  $scope.urls=baseUrl;
  $http.get(baseUrl+'/vendor/register_vendor/document/'+$stateParams.id).then(function(res) {
    $scope.data=res.data;
  });

  $scope.deletes=function(ids) {
    $http.post(baseUrl+'/vendor/register_vendor/delete_file/'+ids, {params: {"_token":csrfToken}}).then(function(data) {
      toastr.success("File Deleted!");
      $state.reload();
    });
  }

  $scope.disBtn=false;
  $scope.uploadSubmit=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/vendor/register_vendor/upload_file/'+$stateParams.id+'?_token='+csrfToken,
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

app.controller('contactVendorShowPrice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.vendor_prices.title;
    $scope.id = $stateParams.id
});

app.controller('contactVendorShowPriceCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Add";
    $scope.vendor_id = $stateParams.id
    $scope.idprice = $stateParams.idprice
});

app.controller('contactVendorShowApp', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Info Vendor | User Aplikasi";
  $scope.params=$stateParams;
  $scope.formData={}
  $http.get(baseUrl+'/contact/contact/user_application/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item
    $scope.user=data.data.user

    $scope.formData.name=$scope.item.name
    $scope.formData.email=$scope.item.email
    $scope.formData.password=$scope.item.password
  })

  $scope.submitForm=function() {
    $http.post(baseUrl+'/contact/contact/contact_store_user/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.reload();
      toastr.success(data.data.message);
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
