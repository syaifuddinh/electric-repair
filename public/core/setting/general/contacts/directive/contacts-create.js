contacts.directive('contactsCreate', function () {
    return {
        restrict: 'E',
        scope: {
            'is_pegawai' :'=isPegawai',
            'is_pelanggan' :'=isPelanggan',
            'is_driver' :'=isDriver',
            'is_vendor' :'=isVendor',
            'index_route' :'=indexRoute',
            'hide_type' :'=hideType',
            'id_modal_input' : '=idModalInput',
            'jo_customer_id' : '=joCustomerId'
        },
        transclude:true,
        templateUrl: '/core/setting/general/contacts/view/contacts-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, $timeout, contactsService) {
            if(!$scope.id_modal_input){
                $rootScope.pageTitle="Add Contact";
                if($stateParams.id){
                    $rootScope.pageTitle="Edit Contact";
                }
            }
            $('.ibox-content').addClass('sk-loading');
            $('[ng-model="formData.npwp_induk"]').inputmask("99.999.999.9-999.999")
            $('[ng-model="formData.npwp"]').inputmask("99.999.999.9-999.999")
            $('[ng-model="formData.contact_person_npwp"]').inputmask("99.999.999.9-999.999")

            $scope.driver_status=[
                {id:1,name:"Driver Utama"},
                {id:2,name:"Driver Cadangan"},
                {id:3,name:"Helper"},
                {id:4,name:"Driver Vendor"},
            ];
            $scope.category=[
                {id:'individual',name:"Perseorangan"},
                {id:'company',name:"Badan Usaha"},
            ];

            $scope.params = $stateParams

           $("[ng-model='formData.email']").tagsinput('items');


          new google.maps.places.Autocomplete(
          (document.getElementById('place_search')), {
            types: []
          });

            $scope.backward = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $rootScope.emptyBuffer()
                        $state.go('contact.contact')
                    }
                }
            }

            $scope.show = function() {
                if($stateParams.id) {
                    $http.get(baseUrl+'/contact/contact/'+$stateParams.id+'/edit').then(function(data) {
                        $scope.data=data.data;

                        dt=data.data.item;
                        $scope.formData={
                            company_id:dt.company_id,
                            latitude:dt.latitude,
                            longitude:dt.longitude,
                            sales_id:parseInt(dt.sales_id),
                            customer_service_id:parseInt(dt.customer_service_id),
                            is_pegawai:parseInt(dt.is_pegawai),
                            is_investor:parseInt(dt.is_investor),
                            is_pelanggan:parseInt(dt.is_pelanggan),
                            is_asuransi:parseInt(dt.is_asuransi),
                            is_supplier:parseInt(dt.is_supplier),
                            is_depo_bongkar:parseInt(dt.is_depo_bongkar),
                            is_helper:parseInt(dt.is_helper),
                            is_driver:parseInt(dt.is_driver),
                            is_vendor:parseInt(dt.is_vendor),
                            is_sales:parseInt(dt.is_sales),
                            is_kurir:parseInt(dt.is_kurir),
                            is_pengirim:parseInt(dt.is_pengirim),
                            is_penerima:parseInt(dt.is_penerima),
                            is_staff_gudang:parseInt(dt.is_staff_gudang),
                            driver_status:parseInt(dt.driver_status),
                            parent_id:dt.parent_id,
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
                            pegawai_no:dt.pegawai_no,
                            vendor_type_id:parseInt(dt.vendor_type_id),
                            address_type_id:parseInt(dt.address_type_id),
                            akun_hutang:dt.akun_hutang,
                            akun_piutang:dt.akun_piutang,
                            akun_um_supplier:dt.akun_um_supplier,
                            akun_um_customer:dt.akun_um_customer,
                            term_of_payment:dt.term_of_payment,
                            limit_piutang:dt.limit_piutang,
                            limit_hutang:dt.limit_hutang,
                            no_ktp:dt.no_ktp,
                            npwp_induk : dt.npwp,
                            npwp : dt.npwp_cabang,
                            pkp:parseInt(dt.pkp),
                            tax_id:dt.tax_id,
                            description:dt.description,
                            rek_no:dt.rek_no,
                            rek_milik:dt.rek_milik,
                            rek_bank_id:dt.rek_bank_id,
                            rek_cabang:dt.rek_cabang,
                            category: dt.category,
                            contact_person_no_2: dt.contact_person_no_2,
                            contact_person_position: dt.contact_person_position,
                            contact_person_npwp: dt.contact_person_npwp,
                            no_tdp: dt.no_tdp,
                            no_siup: dt.no_siup,
                            no_sppkp: dt.no_sppkp,
                            website: dt.website,
                            purchase_purpose: dt.purchase_purpose,
                            personal_facebook_account: dt.personal_facebook_account,
                            personal_instagram_account: dt.personal_instagram_account,
                            company_facebook_account: dt.company_facebook_account,
                            company_instagram_account: dt.company_instagram_account,
                            company_customer_service: dt.company_customer_service,
                            position: dt.position,
                            owner_name: dt.owner_name,
                        }

                        $scope.updateMap(dt.latitude,dt.longitude)

                        $("[ng-model='formData.email']").tagsinput('add', dt.email);

                        $('.ibox-content').removeClass('sk-loading');
                    });
                }
            }

            $('.icheck').iCheck({
                checkboxClass: 'icheckbox_square-green',
                radioClass: 'iradio_square-green',
            });

            $scope.showVendor = function()  {
              $http.get(baseUrl+'/setting/general/vendor').then(function(data) {
                  $scope.vendor=data.data;
              }, function(){
                  $scope.showVendor()
              });
            }
            $scope.showVendor()

            $scope.showPegawai = function()  {
              $http.get(baseUrl+'/contact/contact/pegawai').then(function(data) {
                  $scope.pegawai=data.data;
              }, function(){
                  $scope.showPegawai()
              });
            }
            $scope.showPegawai()
            
            $scope.showSales = function()  {
              $http.get(baseUrl+'/contact/contact/sales').then(function(data) {
                  $scope.sales=data.data;
              }, function(){
                  $scope.showSales()
              });
            }
            $scope.showSales()

            $http.get(baseUrl+'/contact/contact/create').then(function(data) {
                $scope.data=data.data;

                $scope.formData={
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
                    pkp:0,
                    latitude: -7.331438432711705,
                    longitude: 112.76870854695639
                }

                if($scope.is_pegawai) {
                    $scope.formData.is_pegawai = 1
                }

                if($scope.is_vendor) {
                    $scope.formData.is_vendor = 1
                }
                
                if($scope.is_driver) {
                    $scope.formData.is_driver = 1
                }
                
                if($scope.is_pelanggan) {
                    $scope.formData.is_pelanggan = 1
                }

                if($scope.jo_customer_id){
                    $scope.formData.job_order_customer_id = $scope.jo_customer_id
                }

                $scope.updateMap($scope.formData.latitude,$scope.formData.longitude)

                $timeout(function() {
                    if(location.hash.indexOf('/vendor/create') > - 1) {
                        $scope.formData.is_vendor = 1
                        $scope.hide_type = true
                    }
                    if(location.hash.indexOf('/customer/create') > - 1) {
                        $scope.formData.is_pelanggan = 1
                        $scope.hide_type = true
                    }
                }, 700)
                $scope.show()
                $('.ibox-content').removeClass('sk-loading');
            });
    
            $scope.updateMap = function(lat,lng) {
                var latLng = {}
                latLng.lat = lat
                latLng.lng = lng
                $scope.$broadcast('updateLatLng', latLng)
            }

            $scope.$on('getLatLng', function(e, v){
                $scope.formData.latitude = v.lat
                $scope.formData.longitude = v.lng
                $scope.$apply();
            })

            $scope.$on('getAddress', function(e, v){
                var addr = v.address.road + ', ' +v.address.village + ', '
                addr += v.address.county ?? v.address.city ?? null

                if(!v.address.road){
                    addr = v.display_name
                }

                $scope.formData.address = addr
                $scope.formData.postal_code = v.address.postcode
                $scope.$apply();
            })

            $scope.disBtn=false;
            $scope.submitForm=function() {
                $scope.formData.address = $("[ng-model='formData.address']").val();
                $scope.formData.email = $("[ng-model='formData.email']").val();

                if($scope.jo_customer_id){
                    $scope.formData.job_order_customer_id = $scope.jo_customer_id
                }
                
                var fd  = new FormData
                for (x in $scope.formData) {
                    fd.append(x, $scope.formData[x]);
                }

                var ktp = $('input[name="file_ktp"]')[0].files[0];
                var npwp = $('input[name="file_npwp"]')[0].files[0];
                var tdp = $('input[name="file_tdp"]')[0].files[0];
                var siup = $('input[name="file_siup"]')[0].files[0];
                var sppkp = $('input[name="file_sppkp"]')[0].files[0];
                if(ktp){
                    fd.append("file_ktp", ktp);
                }
                if(npwp){
                    fd.append("file_npwp", npwp);
                }
                if(tdp){
                    fd.append("file_tdp", tdp);
                }
                if(siup){
                    fd.append("file_siup", siup);
                }
                if(sppkp){
                    fd.append("file_sppkp", sppkp);
                }
                
                var url, method
                if($stateParams.id) {
                    url = baseUrl+'/contact/contact/' + $stateParams.id
                    method = 'post'
                    fd.append("_method", 'put');
                } else {
                    url = baseUrl+'/contact/contact'
                    method = 'post'
                } 
                url += '?_token=' + csrfToken
                $scope.disBtn=true;
                
                $.ajax({
                    type: method,
                    url: url,
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function(data){
                        $scope.$apply(function() {
                            $scope.disBtn=false;
                        });
                        toastr.success("Data Berhasil Disimpan");
                        if($scope.id_modal_input){
                            $('#'+$scope.id_modal_input).modal('hide')
                            $rootScope.$broadcast('savedContacts')
                            // setTimeout(function(){
                            //     $state.reload()
                            // }, 500)
                        } else {
                            $scope.backward()
                        }
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