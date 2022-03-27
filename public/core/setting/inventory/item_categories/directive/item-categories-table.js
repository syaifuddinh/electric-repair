itemCategories.directive('itemCategoriesTable', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            hideAddButton : '=hideAddButton',
            defaultRackId : '=defaultRackId'
        },
        transclude:true,
        templateUrl: '/core/setting/inventory/item_categories/view/item-categories-table.html',
        link: function (scope, el, attr, ngModel) {
        },
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $state) {
            $scope.formData = {};
            $('.ibox-content').addClass('sk-loading');

            oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                ordering:false,
                scrollX : false,
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                ajax: {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/inventory/category_datatable',
                    data : e => Object.assign(e, $scope.formData),
                    dataSrc: function(d) {
                        $('.ibox-content').removeClass('sk-loading');
                        return d.data;
                    }
                },
                dom: 'Blfrtip',
                buttons: [
                    {
                        extend : 'excel',
                        enabled : true,
                        action : newExportAction,
                        text : '<span class="fa fa-file-excel-o"></span> Export Excel',
                        className : 'btn btn-default btn-sm',
                        filename : 'Inventory - Setting - Storage Type | '+new Date(),
                        sheetName : 'Data',
                        title: 'Inventory - Setting - Storage Type',
                        exportOptions: {
                            rows: {
                                selected: true
                            }
                        },
                    },
                ],
                columns:[
                    {data:"pname",name:"parent.name"},
                    {data:"code",name:"code"},
                    {data:"name",name:"name"},
                    {data:"is_asset",name:"is_asset",className:"text-center"},
                    {data:"is_jasa",name:"is_jasa",className:"text-center"},
                    {data:"description",name:"description"},
                    {data:null,name:"id",className:"text-center",render: function(e) {
                        var id = e.id
                        return `<a ng-click="show(${id})"><span class="fa fa-folder-o"></span></a>`
                    }},
                ],
                createdRow: function(row, data, dataIndex) {
                    if($rootScope.roleList.includes('inventory.category.edit')) {
                        $(row).find('td').attr('ng-click', 'show(' + data.id + ')')
                        $(row).find('td:last-child').removeAttr('ng-click')
                    } else {
                        $(oTable.table().node()).removeClass('table-hover')
                    }
                    $compile(angular.element(row).contents())($scope);
                }
            });

            oTable.buttons().container().appendTo( '#category_export_button' );

            $scope.show = function(id) {
                $rootScope.insertBuffer()
                $state.go('inventory.category.show', { id : id })
            }

            $scope.searchData = function() {
                if($scope.defaultRackId) {
                    $scope.formData.default_rack_id = $scope.defaultRackId;
                }
                oTable.ajax.reload();
            }
            $scope.searchData()

            $scope.resetFilter = function() {
                $scope.formData = {};
                oTable.ajax.reload();
            }
        }
    }
});