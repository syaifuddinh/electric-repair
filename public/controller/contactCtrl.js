app.controller('contactContact', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Contact";
});

app.controller('contactContactCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Add Contact";
});

app.controller('contactContactEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Edit Contact";
});

app.controller('contactContactShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, contactsService) {
    $rootScope.pageTitle="Detail Contact";
    $scope.state=$state;
    $scope.stateParams=$stateParams;
    $scope.id = $stateParams.id

    $scope.showShipperConsignee = null;
    $scope.showContractHistory = null;
    $scope.showReceivable = null;
    $scope.showCustomerChannel = null;

    contactsService.api.show($scope.id, function(v) {
        if(v.is_pelanggan) {
            $scope.showShipperConsignee = true;
            $scope.showContractHistory = true;
            $scope.showReceivable = true;
            $scope.showCustomerChannel = true;            
        } 
    })
});


app.controller('contactContactShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Contact | Data";
});

app.controller('contactContactShowDocument', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Contact | Berkas";

  $http.get(baseUrl+'/contact/contact/show_file/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/contact/contact/upload_document/'+$stateParams.id+'?_token='+csrfToken,
      contentType: false,
      cache: false,
      processData: false,
      data: new FormData($('#uploadForm')[0]),
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        // $state.reload();
        $('#modalDocument').modal('hide')
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
  $scope.delete_file=function(id) {
    var cofs=confirm("Apakah anda yakin ?");
    if (!cofs) {
      return null;
    }
    $http.delete(baseUrl+'/contact/contact/delete_file/'+id).then(function(data) {
      toastr.success("Berkas telah dihapus");
      $state.reload()
    })
  }
});
app.controller('contactContactShowAddressShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Contact | Data";
  $scope.params=$stateParams;

  $http.get(baseUrl+'/contact/contact/show_address/'+$stateParams.idaddress).then(function(data) {
    $scope.data=data.data;
  });
});
app.controller('contactContactShowAddress', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Contact | Alamat Kirim & Terima";
  $scope.params=$stateParams;

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order: [[1,'asc']],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/contact/contact_address_datatable/'+$stateParams.id
    },
    columns:[
      {data:"contact_address.code",name:"contact_address.code"},
      {data:"contact_address.name",name:"contact_address.name"},
      {data:"contact_address.address",name:"contact_address.address"},
      {data:"contact_address.city.name",name:"contact_address.city.name"},
      {data:"contact_address.phone",name:"contact_address.phone"},
      {data:"typename",name:"address_types.name"},
      {data:"tagihname",name:"tagih.name"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/contact/contact/delete_address/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});
app.controller('contactContactShowAddressEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Contact | Edit Alamat Kirim & Terima";
  $scope.params=$stateParams;

  $scope.formData={};
  $http.get(baseUrl+'/contact/contact/edit_address/'+$stateParams.idaddress).then(function(data) {
    $scope.data=data.data;
    $scope.formData.name=data.data.item.name;
    $scope.formData.address_type_id=data.data.address.address_type_id;
    $scope.formData.contact_bill_id=data.data.address.contact_bill_id;
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/contact/contact/update_address/'+$stateParams.idaddress+'?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('contact.contact.show.address',{id:$stateParams.id});
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
app.controller('contactContactShowAddressCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Contact | Tambah Alamat Kirim & Terima";
  $scope.params=$stateParams;

  new google.maps.places.Autocomplete(
  (document.getElementById('place_search')), {
    types: []
  });

  $http.get(baseUrl+'/contact/contact/create_address').then(function(data) {
    $scope.data=data.data;

    $scope.formData={
      contact_id:$stateParams.id,
      company_id:compId,
      is_pegawai:0,
      is_investor:0,
      is_pelanggan:0,
      is_asuransi:0,
      is_supplier:0,
      is_depo_bongkar:0,
      is_helper:0,
      is_driver:0,
      is_vendor:0,
      is_sales:0,
      is_kurir:0,
      is_pengirim:0,
      is_penerima:0,
      is_staff_gudang:0,
      pkp:0
    }

  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/contact/contact/store_address?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('contact.contact.show.address',{id:$stateParams.id});
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
app.controller('contactContactShowAddressCreatef', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Contact | Tambah Alamat Kirim & Terima";
  $scope.params=$stateParams;
  $scope.formData={};
  $scope.formData.contact_id=$stateParams.id;
  $http.get(baseUrl+'/contact/contact/create_address_f').then(function(data) {
    $scope.data=data.data;
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/contact/contact/store_address_f?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('contact.contact.show.address',{id:$stateParams.id});
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

app.controller('contactContactShowUser', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Contact | User Aplikasi";
  $scope.params=$stateParams;
  $scope.formData={}
  $http.get(baseUrl+'/contact/contact/user_application/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item
    $scope.user=data.data.user

    if ($scope.user) {
      var dt=$scope.user;
      $scope.formData.user_id=dt.id
      $scope.formData.emails=dt.email
      $scope.formData.passwords=""
      $scope.formData.is_customer=1
      $scope.formData.is_vendor=dt.is_vendor
      $scope.formData.is_driver=dt.is_driver
    } else {
      $scope.formData.emails=$scope.item.email
      $scope.formData.passwords=""
      $scope.formData.is_customer=1
      $scope.formData.is_vendor=0
      $scope.formData.is_driver=0
    }
  })

  $scope.submitForm=function() {
    $http.post(baseUrl+'/contact/contact/store_user_application/'+$stateParams.id,$scope.formData).then(function(data) {
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
