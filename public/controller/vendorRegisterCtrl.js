app.controller('vendorRegister', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Registrasi Vendor";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/vendor/register_vendor_datatable'
    },
    columns:[
      {data:"codes",name:"codes"},
      {data:"name",name:"name"},
      {data:"address",name:"address"},
      {data:"phone",name:"phone"},
      {data:"email",name:"email"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

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
app.controller('vendorRegisterCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Vendor";
  $('.ibox-content').toggleClass('sk-loading');

  new google.maps.places.Autocomplete(
  (document.getElementById('place_search')), {
    types: []
  });

  $scope.formData={}
  $scope.formData.is_vendor=1;
  $scope.formData.company_id=compId;
  $scope.formData.pkp=0;

  $http.get(baseUrl+'/vendor/register_vendor/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/vendor/register_vendor?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('vendor.register_vendor');
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
app.controller('vendorRegisterEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Vendor";
  $('.ibox-content').toggleClass('sk-loading');

  /*
  new google.maps.places.Autocomplete(
  (document.getElementById('place_search')), {
    types: []
  });
  */

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
      phone:dt.phone,
      phone2:dt.phone2,
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
    $('.ibox-content').toggleClass('sk-loading');
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
        $state.go('vendor.register_vendor.show',{id:$stateParams.id});
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
app.controller('vendorRegisterShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Kontak";
  if ($state.current.name=="vendor.register_vendor.show") {
    $state.go('vendor.register_vendor.show.detail',{id:$stateParams.id});
  }
  if ($state.current.name=="vendor.vendor.show") {
    $state.go('vendor.vendor.show.detail',{id:$stateParams.id});
  }
});
app.controller('vendorRegisterShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Info Vendor | Detail";

  $http.get(baseUrl+'/vendor/register_vendor/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
  }, function() {
    $state.go('vendor.register_vendor');
  });

  $scope.approve=function() {
    $http.post(baseUrl+'/vendor/register_vendor/approve/'+$stateParams.id).then(function(data) {
      toastr.success("Data Vendor Berhasil di Setujui.","Vendor Approved!");
      $state.reload();
    });
  }
});
app.controller('vendorRegisterShowDocument', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Info Vendor | Berkas";

  $scope.urls=baseUrl;
  $http.get(baseUrl+'/vendor/register_vendor/document/'+$stateParams.id).then(function(res) {
    $scope.data=res.data;
  });

  $scope.hapus_file = function(id) {
    $http.delete(baseUrl+ '/contact/contact/delete_file/' +id).then(function(data){
      toastr.success("Data Berhasil Dihapus");
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
app.controller('vendorRegisterShowPrice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tarif Vendor";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/vendor/vendor_price_datatable/'+$stateParams.id
    },
    columns:[
      {data:"cabang",name:"companies.name"},
      {data:"trayek",name:"routes.name"},
      {data:"name",name:"name"},
      {data:"price_full",name:"price_full",className:'text-right'},
      {data:"piece",name:"pieces.name"},
      {data:"layanan",name:"services.name"},
      {data:"golongan",name:"service_groups.name"},
      {data:"moda",name:"modas.name"},
      {data:"vtype",name:"vtype"},
      {data:"action",name:"created_at",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

});
app.controller('vendorRegisterShowPriceCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Tarif Vendor";
  $('.ibox-content').toggleClass('sk-loading');
  $scope.isedit=false;
  $scope.formData={};
  $http.get(baseUrl+'/vendor/register_vendor/create_price/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $scope.type_1=data.data.type_1;
    $scope.type_2=data.data.type_2;
    $scope.type_3=data.data.type_3;
    $scope.type_4=data.data.type_4;
    $scope.type_5=data.data.type_5;
    $scope.type_6=data.data.type_6;
    $scope.type_7=data.data.type_7;
    $scope.formData.company_id=data.data.item.company_id;
    $('.ibox-content').toggleClass('sk-loading');
  });
  $scope.div_tarif=false;
  $scope.dom_switch=function(ids,company,isedit) {
    // console.log(ids);
    $scope.formData={};
    $scope.formData.service_id=ids;
    $scope.formData.company_id=company;
    if ($scope.type_1.indexOf(ids)!==-1) {
      $scope.formData.stype_id=1;
      $scope.formData.price_tonase=0;
      $scope.formData.min_tonase=0;
      $scope.formData.price_volume=0;
      $scope.formData.min_volume=0;
      $scope.formData.price_item=0;
      $scope.formData.min_item=0;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
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
      url: baseUrl+'/vendor/register_vendor/store_price/'+$stateParams.id+'?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('vendor.register_vendor.show.price');
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        // console.log(xhr);
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

});
app.controller('vendorRegisterShowPriceEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Tarif Vendor";
  $('.ibox-content').toggleClass('sk-loading');
  $scope.isedit=false;

  $http.get(baseUrl+'/vendor/register_vendor/edit_price/'+$stateParams.idprice).then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.type_1=data.data.type_1;
    $scope.type_2=data.data.type_2;
    $scope.type_3=data.data.type_3;
    $scope.type_4=data.data.type_4;
    $scope.type_5=data.data.type_5;
    $scope.type_6=data.data.type_6;
    $scope.type_7=data.data.type_7;
    $scope.formData={
      company_id:dt.company_id,
      min_tonase:dt.min_tonase,
      price_tonase:dt.price_tonase,
      min_volume:dt.min_volume,
      price_volume:dt.price_volume,
      min_item:dt.min_item,
      price_item:dt.price_item,
      price_full:dt.price_full,
      route_id:dt.route_id,
      commodity_id:dt.commodity_id,
      name:dt.name,
      piece_id:dt.piece_id,
      service_id:dt.service_id,
      moda_id:dt.moda_id,
      vehicle_type_id:dt.vehicle_type_id,
      description:dt.description,
      price_handling_tonase:dt.price_handling_tonase,
      price_handling_volume:dt.price_handling_volume,
      rack_id:dt.rack_id,
      container_type_id:dt.container_type_id,
      stype_id:dt.service_type_id,
    }
    $scope.dom_switch(dt.service_id,dt.company_id,true);
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.div_tarif=false;
  $scope.dom_switch=function(ids,company,isedit) {
    // console.log(ids);
    if (isedit==false) {
      $scope.formData={};
    }
    $scope.formData.service_id=ids;
    $scope.formData.company_id=company;
    $scope.formData.code=$scope.data.item.code;
    $scope.formData.name=$scope.data.item.name;

    if ($scope.type_1.indexOf(ids)!==-1) {
      $scope.formData.stype_id=1;
      if (isedit==false) {
        $scope.formData.price_tonase=0;
        $scope.formData.min_tonase=0;
        $scope.formData.price_volume=0;
        $scope.formData.min_volume=0;
        $scope.formData.price_item=0;
        $scope.formData.min_item=0;
      }
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
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
      url: baseUrl+'/vendor/register_vendor/update_price/'+$stateParams.idprice+'?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('vendor.register_vendor.show.price');
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        // console.log(xhr);
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

});
