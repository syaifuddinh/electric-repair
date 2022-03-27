contacts.directive('contactsShow', function () {
    return {
        restrict: 'E',
        scope: {
            'is_pegawai' :'=isPegawai',
            'is_pelanggan' :'=isPelanggan',
            'is_driver' :'=isDriver',
            'id' :'=id',
            'hide_type' :'=hideType'
        },
        transclude:true,
        templateUrl: '/core/setting/general/contacts/view/contacts-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, $timeout, contactsService) {
            $scope.receivable_value=0;
            $scope.payable_value=0;
            $scope.receivable_count=0;
            $scope.payable_count=0;

            $http.get(baseUrl+'/contact/contact/'+$scope.id).then(function(data) {
                $scope.data=data.data;
            }, function() {
            });

            $http.get(baseUrl+'/contact/contact/create').then(function(data) {
                $scope.data2=data.data;
            });

        $scope.get_receivable_value=function() {
            $http.get(baseUrl+`/contact/contact/${$scope.id}/receivable_value`).then(function(d) {
                $scope.receivable_value = d.data.value
            })
        }

        $scope.get_payable_value=function() {
            $http.get(baseUrl+`/contact/contact/${$scope.id}/payable_value`).then(function(d) {
                $scope.payable_value = d.data.value
            })
        }

        $scope.get_receivable_count=function() {
            $http.get(baseUrl+`/contact/contact/${$scope.id}/receivable_count`).then(function(d) {
                $scope.receivable_count = d.data.value
            })
        }

        $scope.get_payable_count=function() {
            $http.get(baseUrl+`/contact/contact/${$scope.id}/payable_count`).then(function(d) {
                $scope.payable_count = d.data.value
            })
        }
        $scope.get_receivable_value()
        $scope.get_payable_value()

        $scope.viewDocument=function() {
          $('#modalDocument').modal()
        }

        $scope.approveCustomer = function()  {
            var is_confirm = confirm("Apakah anda yakin ?")
            if(is_confirm) {
                $http.put(baseUrl+'/contact/contact/' + $scope.id + '/approve_customer').then(function(data) {
                    toastr.success('Customer berhasil disetujui')
                    $state.go('contact.contact.show', {id:$scope.id})
                }, function(){
                    toastr.error('Terjadi kesalahan')
                });
            }
          }

        $scope.saveAs=function() {
          var dt=$scope.data;
          $scope.formSave={}
          $scope.formSave.id=dt.id
          $scope.formSave.company_id=dt.company_id
          $scope.formSave.name=dt.name
          $scope.formSave.owner_name=dt.owner_name
          $scope.formSave.address=dt.address
          $scope.formSave.city_id=dt.city_id
          $scope.formSave.postal_code=dt.postal_code
          $scope.formSave.phone=dt.phone
          $scope.formSave.phone2=dt.phone2
          $scope.formSave.fax=dt.fax
          $scope.formSave.email=dt.email
          $scope.formSave.contact_person=dt.contact_person
          $scope.formSave.contact_person_email=dt.contact_person_email
          $scope.formSave.contact_person_no=dt.contact_person_no
          $scope.formSave.pegawai_no=dt.pegawai_no
          $scope.formSave.vendor_type_id=dt.vendor_type_id
          $scope.formSave.is_pegawai=dt.is_pegawai
          $scope.formSave.is_investor=dt.is_investor
          $scope.formSave.is_pelanggan=dt.is_pelanggan
          $scope.formSave.is_asuransi=dt.is_asuransi
          $scope.formSave.is_supplier=dt.is_supplier
          $scope.formSave.is_depo_bongkar=dt.is_depo_bongkar
          $scope.formSave.is_helper=dt.is_helper
          $scope.formSave.is_driver=dt.is_driver
          $scope.formSave.is_vendor=dt.is_vendor
          $scope.formSave.is_sales=dt.is_sales
          $scope.formSave.is_kurir=dt.is_kurir
          $scope.formSave.is_pengirim=dt.is_pengirim
          $scope.formSave.is_penerima=dt.is_penerima
          $scope.formSave.is_staff_gudang=dt.is_staff_gudang
          $scope.formSave.akun_hutang=dt.akun_hutang
          $scope.formSave.akun_piutang=dt.akun_piutang
          $scope.formSave.akun_um_supplier=dt.akun_um_supplier
          $scope.formSave.akun_um_customer=dt.akun_um_customer
          $scope.formSave.term_of_payment=dt.term_of_payment
          $scope.formSave.limit_piutang=dt.limit_piutang
          $scope.formSave.limit_hutang=dt.limit_hutang
          $scope.formSave.npwp=dt.npwp
          $scope.formSave.pkp=dt.pkp
          $scope.formSave.tax_id=dt.tax_id
          $scope.formSave.description=dt.description
          $scope.formSave.no_ktp=dt.no_ktp
          $scope.formSave.rek_no=dt.rek_no
          $scope.formSave.rek_milik=dt.rek_milik
          $scope.formSave.rek_bank_id=dt.rek_bank_id
          $scope.formSave.rek_cabang=dt.rek_cabang
          $scope.formSave.address_type_id=dt.address_type_id
          $scope.formSave.category = dt.category
          $scope.formSave.contact_person_no_2 = dt.contact_person_no_2
          $scope.formSave.contact_person_position = dt.contact_person_position
          $scope.formSave.contact_person_npwp = dt.contact_person_npwp
          $scope.formSave.no_tdp = dt.no_tdp
          $scope.formSave.no_siup = dt.no_siup
          $scope.formSave.no_sppkp = dt.no_sppkp
          $scope.formSave.website = dt.website
          $scope.formSave.purchase_purpose = dt.purchase_purpose
          $scope.formSave.personal_facebook_account = dt.personal_facebook_account
          $scope.formSave.personal_instagram_account = dt.personal_instagram_account
          $scope.formSave.company_facebook_account = dt.company_facebook_account
          $scope.formSave.company_instagram_account = dt.company_instagram_account
          $scope.formSave.company_customer_service = dt.company_customer_service
          $scope.formSave.position = dt.position
          $('#modalSaveAs').modal('show');
          // $http.post(baseUrl+'/contact/contact/save_as/'+$scope.id).then(function(data) {
          //   $state.go('contact.contact');
          //   toastr.success("Data Berhasil Disimpan!");
          // });
        }

        $scope.disBtn=false;
        $scope.submitSave=function() {
          
          var fd  = new FormData
          for (x in $scope.formSave) {
              fd.append(x, $scope.formSave[x]);
          }

          var ktp = $('input[name="file_ktp_save_as"]')[0].files[0];
          if(ktp){
            fd.append("file_ktp", ktp);
          }
          
          $scope.disBtn=true;
          $.ajax({
            type: "post",
            url: baseUrl+'/contact/contact?_token='+csrfToken,
            data: fd,
            processData: false,
            contentType: false,
            success: function(data){
              $scope.$apply(function() {
                $scope.disBtn=false;
              });
              $('#modalSaveAs').modal('hide');
              toastr.success("Data Berhasil Disimpan");
              $timeout(function() {
                $state.go('contact.contact')
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
      }
    }
});