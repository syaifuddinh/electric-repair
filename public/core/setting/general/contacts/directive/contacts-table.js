contacts.directive('contactsTable', function () {
    return {
        restrict: 'E',
        scope: {
            'is_pelanggan' :'=isPelanggan',
            'is_customer' :'=isCustomer',
            'is_driver' :'=isDriver',
            'is_vendor' :'=isVendor',
            'edit_route' :'=editRoute',
            'hide_type_filter' :'=hideTypeFilter',
            'add_to_pegawai' :'=addToPegawai',
            'add_to_pelanggan' :'=addToPelanggan',
            'add_to_driver' :'=addToDriver'
        },
        transclude:true,
        templateUrl: '/core/setting/general/contacts/view/contacts-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, $timeout, contactsService) {
            $scope.isFilter = false;
            $scope.formData = {};

            $scope.filterType = {
                is_asuransi : 0,
                is_depo_bongkar : 0,
                is_driver : 0,
                is_helper : 0,
                is_kurir : 0,
                is_pegawai : 0,
                is_pelanggan : 0,
                is_penerima : 0,
                is_pengirim : 0,
                is_sales : 0,
                is_staff_gudang : 0,
                is_supplier : 0,
                is_vendor : 0,
            }

            oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                order: [],
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                dom: 'Blfrtip',
                buttons: [{
                    extend: 'excel',
                    enabled: true,
                    action: newExportAction,
                    text: '<span class="fa fa-file-excel-o"></span> Export Excel',
                    className: 'btn btn-default btn-sm pull-right',
                    filename: 'Contact',
                    sheetName: 'Data',
                    title: 'Contact',
                    exportOptions: {
                      rows: {
                        selected: true
                      }
                    },
                }],
                ajax : {
                  headers : {'Authorization' : 'Bearer '+authUser.api_token},
                  url : baseUrl+'/api/contact/contact_datatable',
                  data : function(req) {
                    if($scope.is_vendor) {
                        req['is_vendor'] = $scope.is_vendor;
                    }
                    req['is_active'] = $scope.formData.is_active;
                    req['filter_types'] = {}
                    for (const [key, value] of Object.entries($scope.filterType)){
                      req['filter_types'][key] = value
                    }
                    return req;
                  }
                },
                columns:[
                  {data:"name",name:"name"},
                  {data:"address",name:"address"},
                  {data:"cityname",name:"cityname"},
                  {data:"phone",name:"phone"},
                  {
                    data:null,
                    render : function(resp) {
                      var is_active = resp.is_active;
                      var outp = is_active == 1 ? 'Aktif' : 'Tidak Aktif';
                      var class_name = is_active == 1 ? 'primary' : 'danger';

                      outp = "<div class='label label-" + class_name + "'>" + outp + "</div>";
                      return outp;
                    },
                    name:"is_active",
                    className : 'text-center'
                  },
                  {
                        data:null,
                        searchable:false,
                        orderable:false,
                        className:"text-center",
                        render : function(resp) {
                            var btn = ''
                            btn += "<a ng-show=\"$root.roleList.includes('contact.contact.detail')\" ng-click='show(" + resp.id + ")' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;"
                            btn +="<a ng-show=\"$root.roleList.includes('contact.contact.edit')\" ng-click=\"edit(" + resp.id + ")\" ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
                            if(resp.is_active == 1) {
                                btn += "<a ng-show=\"$root.roleList.includes('contact.contact.delete')\" ng-click='deletes(" + resp.id + ")'><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>"
                            } else {
                                btn += "<a ng-click='activate(" + resp.id + ")'><span class='fa fa-check' data-toggle='tooltip' title='Aktifkan'></span></a>"; 
                            }

                            return btn
                        }
                   },
                ],
                createdRow: function(row, data, dataIndex) {
                  if($rootScope.roleList.includes('contact.contact.detail')) {
                      $(row).find('td').attr('ui-sref', 'contact.contact.show({id:' + data.id + '})')
                      $(row).find('td:last-child').removeAttr('ui-sref')
                  } else {
                      $(oTable.table().node()).removeClass('table-hover')
                  }
                  $compile(angular.element(row).contents())($scope);
                }
            });

            oTable.buttons().container().appendTo('.ibox-tools')

            $scope.edit = function(id) {
                if($scope.edit_route) {
                    $state.go($scope.edit_route, {id : id})
                } else {
                    $state.go("contact.contact.edit", {id : id})
                }
            }

            $scope.show = function(id) {
                $rootScope.insertBuffer()
                $state.go('contact.contact.show', {'id' : id})
            }

            $scope.add = function() {
                $rootScope.insertBuffer()
                if($scope.add_to_pegawai) {
                    $state.go('depo.operator.create')
                } else if ($scope.add_to_driver) {
                    $state.go('driver.driver.create')
                } else if ($scope.add_to_pelanggan) {
                    $state.go('contact.customer.create')
                } else {
                    $state.go('contact.contact.create')
                }
            }

            $scope.searchData = function(){
                oTable.ajax.reload();
            }

            $scope.resetFilter = function() {
                $scope.formData = {};
                $scope.filterType = {
                    is_asuransi : 0,
                    is_depo_bongkar : 0,
                    is_driver : 0,
                    is_helper : 0,
                    is_kurir : 0,
                    is_pegawai : 0,
                    is_pelanggan : 0,
                    is_penerima : 0,
                    is_pengirim : 0,
                    is_sales : 0,
                    is_staff_gudang : 0,
                    is_supplier : 0,
                    is_vendor : 0,
                }
                if($scope.is_pegawai) {
                    $scope.filterType.is_pegawai = $scope.is_pegawai
                }
                if($scope.is_driver) {
                    $scope.filterType.is_driver = $scope.is_driver
                }
                if($scope.is_pelanggan) {
                    $scope.filterType.is_pelanggan = $scope.is_pelanggan
                }
                $scope.searchData();
            }
            $scope.resetFilter()

            $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                    $http.delete(baseUrl+'/contact/contact/'+ids,{_token:csrfToken}).then(function(res) {
                        if (res.status==200) {
                            oTable.ajax.reload();
                            toastr.success("Data Berhasil Dinonaktifkan!");
                        } else {
                            toastr.error("Error Has Found");
                        }
                    });
                }
            }

            $scope.activate=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                    $http.put(baseUrl+'/contact/contact/'+ids + '/activate',{_token:csrfToken}).then(function() {
                        oTable.ajax.reload();
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
            }   
        }
    }
});