racks.directive('racksIndex', function(){
    return {
        restrict: 'E',
        scope : {
            companyId : '=companyId'
        },
        templateUrl : '/core/setting/inventory/racks/view/index.html',
        controller : function($scope, $attrs, $http, $rootScope,$state,$stateParams,$timeout,$compile, racksService, $filter) {
            $scope.filterData = {}
            $scope.filterData.company_id = $scope.companyId
            $scope.formData = {}

            var createdRow = function(row, data, dataIndex) {
                var col = $(row).find('td:first-child')
                var txt = col.text()
                var a = $('<a></a>')
                var id = data.id
                a.attr('ui-sref', 'operational_rack.setting.rack.show({id:' + id + '})')
                col.empty()
                a.append(txt)
                col.append(a)
                $compile(col)($scope)
            }

            $scope.createdRow = createdRow

            var options = {
                order: [],
                ajax : {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : racksService.url.datatable(),
                    data : a => Object.assign(a, $scope.filterData)
                },
                columnDefs : [
                    { title : $rootScope.solog.label.general.code },
                    { title : $rootScope.solog.label.general.name },
                    { title : $rootScope.solog.label.general.address },
                    { title : $rootScope.solog.label.general.branch },
                    { title : $rootScope.solog.label.general.volume_capacity },
                    { title : $rootScope.solog.label.general.weight_capacity },
                    { title : $rootScope.solog.label.general.status },
                    { title : ''},
                ],
                columns:[
                    {data:"code",name:"code"},
                    {data:"name",name:"name"},
                    {data:"address",name:"address"},
                    {data:"company.name",name:"company.name"},
                    {
                        data:null,
                        searchable:false,
                        name:"capacity_volume",
                        className:'text-right',
                        render : resp => $filter('number')(resp.capacity_volume)
                    },
                    {
                        data:null,
                        searchable:false,
                        name:"capacity_tonase",
                        className:'text-right',
                        render : resp => $filter('number')(resp.capacity_tonase)
                    },
                    {data:"is_active",name:"is_active",className:"text-center"},
                    {
                        data:null,
                        searchable:false,
                        orderable:false,
                        render : function(item) {
                            var jsn = JSON.stringify(item)
                            var r = '<a ng-show="$root.roleList.includes(\'operational_rack.setting.rack.edit\')" ng-click=\'edit(' + jsn + ')\'><i class="fa fa-edit"></i></a>'
                            return r
                        }
                    }
                ],
            }
            $scope.options = options

             
              $('#modal').on('hidden.bs.modal', function(){
                  if($rootScope.hasBuffer()) {
                      $rootScope.accessBuffer()
                  }
              })

            $scope.add = function() {
                $scope.modalTitle = $rootScope.solog.label.racks.add
                $scope.formData.company_id = parseInt($scope.companyId)
                $('#modal').modal()
            }

            if($rootScope.hasBuffer()) {
                  $timeout(function(){
                      $scope.add()
                  }, 400)
            }

            $scope.edit = function(jsn) {
                $scope.modalTitle = $rootScope.solog.label.racks.add
                $scope.formData = {}
                $scope.formData.id = jsn.id
                $scope.formData.company_id = parseInt(jsn.company_id)
                $scope.formData.code = jsn.code
                $scope.formData.name = jsn.name
                $scope.formData.address = jsn.address
                $scope.formData.capacity_volume = jsn.capacity_volume
                $scope.formData.capacity_tonase = jsn.capacity_tonase
                $scope.formData.luas_lahan = jsn.luas_lahan
                $scope.formData.luas_bangunan = jsn.luas_bangunan
                $scope.formData.luas_gudang = jsn.luas_gudang
                $('#modal').modal()
            }

            $scope.submitForm = function() {
                $rootScope.disBtn = true
                racksService.api.store($scope.formData, function() {
                    $scope.options.datatable.ajax.reload()
                    $('#modal').modal('hide')
                    if($rootScope.hasBuffer()) {
                        $timeout(function() {
                            $rootScope.accessBuffer()
                        }, 300)
                    }
                })
            }

            $scope.createdRow
        }
    }
});