items.directive('itemTable', function () {
    return {
        restrict: 'E',
        scope: {
            is_container_part : '=isContainerPart',
            is_container_yard : '=isContainerYard',
            is_pallet : '=isPallet',
            harga_jual_greater_than : "=hargaJualGreaterThan",
            is_merchandise : '=isMerchandise',
            is_service : '=isService',
            hide_export : '=hideExport',
            quotation_id : '=quotationId',
            defaultRackId : '=defaultRackId',
            detailRoute : '=detailRoute',
            editRoute : '=editRoute'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/setting/inventory/items/view/item-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, itemsService) {
            if(!$scope.formData) {
                $scope.formData = {}
            }

            if($scope.is_container_part) {
                $scope.formData.is_container_part = $scope.is_container_part
            }

            if($scope.is_container_yard) {
                $scope.formData.is_container_yard = $scope.is_container_yard
            }

            if($scope.is_pallet) {
                $scope.formData.is_pallet = $scope.is_pallet
            }

            var columnDefs = [
                {title : $rootScope.solog.label.general.code},
                {title : $rootScope.solog.label.item.name},
                {title : $rootScope.solog.label.general.category}
            ]

            var columns = [
                  {data:"code",name:"code"},
                  {data:"name",name:"name"},
                  {data:"category",name:"categories.name"}
            ]

            if(!$attrs['hideSalePrice']) {
                columnDefs.push(
                    {title : $rootScope.solog.label.general.sale_price},
                )
                columns.push(
                  {data:null,name:"harga_jual",className:"text-right sale_price",render: e => $filter('number')(e.harga_jual)}
                )
            }

            if(!$attrs['hidePurchasePrice']) {
                columnDefs.push(
                    {title : $rootScope.solog.label.general.purchase_price},
                )
                columns.push(
                    {data:null,name:"harga_beli",className:"text-right purchase_price",render: e => $filter('number')(e.harga_beli)},
                )
            }

            if(!$attrs['hideDescription']) {
                columnDefs.push(
                    {title : $rootScope.solog.label.general.description}
                )
                columns.push(
                    {data:"description",name:"description"},
                )
            }

            if(!$attrs['hideAction']) {
                columnDefs.push({title : ''})
                columns.push({data:null,name:"id",className:"text-center",render:function(e) {
                    let html = `
                      <a ng-click="show(${e.id})"><span class="fa fa-folder-o"></span></a>&nbsp;
                      <a ng-show="$root.roleList.includes('inventory.item.edit')" ng-click="edit(${e.id})"><span class="fa fa-pencil"></span></a>&nbsp;
                      <a ng-show="$root.roleList.includes('inventory.item.delete')" ng-click="deletes(${e.id})"><span class="fa fa-trash"></span></a>
                    `
                    return html
                  }})
            }


            if($attrs['showQuotationPrice']) {
                $scope.formData.show_quotation_price = 1
            }

            $scope.fileName = 'Master ' + ($scope.is_pallet ? 'Pallet' : 'Item')

           var options = {
                order:[[1,'desc']],
                lengthMenu:[[10,25,50,100],[10,25,50,100]],
                ajax: {
                  headers : {'Authorization' : 'Bearer '+authUser.api_token},
                  url : itemsService.url.datatable(),
                  data : e => Object.assign(e, $scope.formData),
                  dataSrc: function(d) {
                    $('.ibox-content').removeClass('sk-loading');
                    return d.data;
                  }
                },
                buttons: [
                  {
                    'extend' : 'excel',
                    'enabled' : true,
                    'action' : newExportAction,
                    'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                    'className' : 'btn btn-default btn-sm',
                    'filename' : $scope.fileName + ' - ' + new Date,
                    'title' : $scope.fileName,
                  },
                ],
                columnDefs : columnDefs,
                columns:columns
            }

            if(!$scope.hide_export) {
                options.dom = 'Blfrtip'
            }
            
            if($attrs['inputMode']) {
                $scope.createdRow = function(row, data) {
                    var first = $(row).find('td:first-child');
                    var second = $(row).find('td:nth-child(2)');
                    var a, b
                    a = $('<a ng-click="chooseItem(' + data.id + ')">' + first.text() + '</a>')
                    first.empty()
                    first.append(a)
                    b = $('<a ng-click="chooseItem(' + data.id + ')">' + second.text() + '</a>')
                    second.empty()
                    second.append(b)
                    $compile(angular.element(row).contents())($scope)
                }
            }
            $scope.options = options

            $timeout( function() {
                if(!$scope.hide_export) {
                    $scope.options.datatable.buttons().container().appendTo( '#export_button' )
                }
            }, 1000)

            $scope.filter = function() {
                if($scope.defaultRackId) {
                    $scope.formData.default_rack_id = $scope.defaultRackId
                }
                if($scope.category_id) {
                    $scope.formData.category_id = $scope.category_id
                }

                if($scope.is_merchandise) {
                    $scope.formData.is_merchandise = $scope.is_merchandise
                }

                if($scope.quotation_id) {
                    $scope.formData.quotation_id = $scope.quotation_id
                }

                if($scope.harga_jual_greater_than !== undefined && $scope.harga_jual_greater_than !== null) {
                    $scope.formData.harga_jual_greater_than = $scope.harga_jual_greater_than
                }

                $scope.$broadcast('reload', 0)
            }
            $scope.filter()

            $scope.reset = function() {
                $scope.formData = {}
                $scope.filter()
            }

            $scope.show = function(id) {
                if($scope.detailRoute) {
                    $state.go($scope.detailRoute, {'id' : id})
                } else {
                    $rootScope.insertBuffer()
                    $state.go('inventory.item.show', {'id' : id})
                }
            }

            $scope.edit = function(id) {
                if($scope.editRoute) {
                    $state.go($scope.editRoute, {'id' : id})
                } else {
                    $rootScope.insertBuffer()
                    $state.go('inventory.item.edit', {'id' : id})
                }
            }

            $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                    $http.delete(baseUrl+'/inventory/item/'+ids,{_token:csrfToken}).then(function success(data) {
                        $scope.searchData();
                        toastr.success("Data Berhasil Dihapus!");
                    }, function error(data) {
                        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
                    });
                }
            }

            $scope.searchData = function() {
                if($scope.is_service) {
                    $scope.formData.is_service = $scope.is_service
                }
                $scope.$broadcast('reloadItem', $scope.formData)
            }
            $scope.searchData()

            $scope.resetFilter = function() {
                $scope.formData = {};
                $scope.searchData()
            }
            
            $scope.chooseItem = function(id) {
                itemsService.api.show(id, function(dt){
                    $scope.$emit('chooseItem', dt)
                })
            }

            $scope.$on('editItemPriceByQuotation', function(e, quotation_id){
                $scope.$broadcast('destroy')
                $scope.createdRow = function(row, data, dataIndex) {
                    var salePriceColumn = $(row).find('.sale_price')
                    var salePrice = data.harga_jual
                    var text = $('<input type="number">')
                    text.attr('item-id', data.id)
                    text.addClass('form-control')
                    salePrice = parseInt(salePrice)
                    text.val(salePrice)
                    salePriceColumn.html('')
                    salePriceColumn.append(text)

                    text.keyup(function(){
                        var item_id = $(this).attr('item-id')
                        var params = {}
                        params.price = $(this).val()
                        $http.post(baseUrl+'/marketing/inquery/' + quotation_id + '/item/' + item_id, params).then(function(data) {
                        }, function(error) {
                            $rootScope.disBtn=false;
                            if (error.status==422) {
                                var det="";
                                angular.forEach(error.data.errors,function(val,i) {
                                    det+="- "+val+"<br>";
                                });
                                toastr.warning(det,error.data.message);
                            } else {
                                toastr.error(error.data.message,"Error Has Found !");
                            }
                        })
                    })
                }
                $scope.$broadcast('init')
            })

            $scope.$on('reloadItem', function(e, v) {
                $scope.formData = Object.assign($scope.formData, v)
                $scope.filter()
            })


            $scope.$on('resetItem', function(e, v) {
                $scope.reset()
            })

            $scope.$on('abortItemPrice', function(e, params){
                $scope.$broadcast('destroy')
                $scope.createdRow = null
                $scope.$broadcast('init')
                $scope.options.datatable.buttons().container().appendTo( '#export_button' )
            })

            $compile($('thead'))($scope)
            $timeout(function(){
                $scope.options.datatable.buttons().container().appendTo( '#export_button' )
            }, 500)
        }
    }
});