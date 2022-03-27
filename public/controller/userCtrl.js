app.controller('settingUserIndex', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
  $rootScope.pageTitle="User Management";
  $('.ibox-content').addClass('sk-loading');
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/user_datatable',
      data:function(d) {
        d.user_now=userProfile.id
      },
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"name",name:"name"},
      {data:"email",name:"email"},
      {data:"company.name",name:"company.name"},
      {
          data:null,
          searchable:false,
          name:"last_login",
          render:resp => $filter('fullDate')(resp.last_login)
      },
      {data:"action",name:"action", sortable: false,className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      if($rootScope.roleList.includes('setting.user_management.detail')) {
          $(row).find('td').attr('ui-sref', 'setting.user.show.personal({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
          $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.deletes=function(id) {
    var cofs=confirm("Apakah Anda Yakin ?");
    if (cofs) {
      $('.ibox-content').addClass('sk-loading');
      $http.delete(baseUrl+'/setting/user/'+id).then(function(res){
        oTable.ajax.reload();
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
      $('.ibox-content').removeClass('sk-loading');
    }
  }
});
app.controller('settingUserGroup', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Grup User";
  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    searching: false,
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/setting/group_datatable'
    },
    columns:[
      {data:"name",name:"name"},
      {data:"action",name:"action",className:"text-center",sorting: false},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.edits=function(id,name) {
    $scope.formData.name=name;
    $scope.modalTitle="Edit Group";
    $scope.urls=baseUrl+'/setting/user/store_group/'+id;
    $('#modals').modal('show');
  }

  $scope.formData={
    _token:csrfToken
  }
  $scope.adds=function() {
    $scope.formData.name='';
    $scope.modalTitle="Add Group";
    $scope.urls=baseUrl+'/setting/user/store_group';
    $('#modals').modal('show');
  }
  $scope.submitForm=function() {
    $.ajax({
      type: "post",
      url: $scope.urls,
      data: $scope.formData,
      success: function(data){
        toastr.success('Data Berhasil disimpan!');
        $('#modals').modal('hide');
        oTable.ajax.reload();
      },
    });
  }

  $scope.deletes=function(id) {
    var cofs=confirm("Apakah Anda Yakin ?");
    if (cofs) {
      $http.delete(baseUrl+'/setting/user/user_group/'+id).then(function(res){
        oTable.ajax.reload();
      });
    }
  }
});
app.controller('settingUserGroupPrevilage', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Grup User Previlage";
  $http.get(baseUrl+'/setting/user/group_previlage/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $scope.previlageName=data.data.gtype.name;
    angular.forEach(data.data.role, function(val,i) {
      $scope.formData.detail.push({
        id:val.id,
        slug:val.slug,
        include:(data.data.previlage.indexOf(val.id)?1:0)
      })
    })

  });

  $scope.deletes=function(id) {
    var cofs=confirm("Apakah Anda Yakin ?");
    if (cofs) {
      $http.delete(baseUrl+'/setting/group_previlage/'+id).then(function(res){
        oTable.ajax.reload();
      });
    }
  }
  $scope.formData={}
  $scope.formData.id=$stateParams.id
  $scope.formData.detail=[]
  $scope.all=0

  $scope.alls=function() {
    var value = $scope.all
    if (value) {
      angular.forEach($scope.formData.detail, function(val,i) {
        $scope.formData.detail[i].include = $scope.formData.detail[i].id
      })
    } else {
      angular.forEach($scope.formData.detail, function(val,i) {
        $scope.formData.detail[i].include=0
      })
    }
  }

  $scope.submitForm=function() {
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/user/store_group_previlage/'+$stateParams.id,
      data: $('#previlageForm').serialize()+'&_token='+csrfToken,
      success: function(data){
        toastr.success("Data Berhasil Disimpan");
      },
    });
  }
});

app.controller('settingUserCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Add User";
  $('.ibox-content').addClass('sk-loading');
  $scope.goBack=function() {
    $state.go('setting.user');
  }
  $http.get(baseUrl+'/setting/user/create').then(function(data){
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });
  $scope.formData={}
  $scope.formData.is_admin=0

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/user?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('setting.user');
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
app.controller('settingUserEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit User";
  $('.ibox-content').addClass('sk-loading');
  $scope.goBack = function() {
    $state.go('setting.user');
  }
  $scope.loading=false;

    $http.get(baseUrl+'/setting/user/'+$stateParams.id+'/edit')
        .then(function(data)
    {
        $scope.data=data.data;
        $scope.formData = {
            _token:csrfToken,
            _method:'put',
            company_id:data.data.item.company_id,
            name:data.data.item.name,
            username:data.data.item.username,
            email:data.data.item.email,
            city_id:data.data.item.city_id,
            edit:true,
            group_id:data.data.item.group_id,
            contact_id: data.data.item.contact_id,
            is_admin:data.data.item.is_admin
        }
        $('.ibox-content').removeClass('sk-loading');
    });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $http.put(`${baseUrl}/setting/user/${$stateParams.id}`, $scope.formData).then(function(e) {
      $scope.disBtn=false;
      toastr.success("Data Berhasil Disimpan");
      $state.go('setting.user');
    }).catch(function(xhr) {
      $scope.disBtn=false;
      if (xhr.status==422) {
        var msgs="";
        $.each(xhr.responseJSON.errors, function(i, val) {
          msgs+=val+'<br>';
        });
        toastr.warning(msgs,"Validation Error!");
      } else {
        toastr.error(xhr.responseJSON.message,"Error has Found!");
      }      
    })
  }

});
app.controller('settingUserShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail User";
  $http.get(baseUrl+'/setting/user/'+$stateParams.id).then(function(res) {
    $scope.masterItem=res.data;
  })
});
app.controller('settingUserShowPersonal', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Personal Info";
});
app.controller('settingUserShowPassword', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Personal Info";
    $scope.formData={
        _token:csrfToken
    }

    $scope.submitForm=function() {
        var payload = $scope.formData
        $rootScope.disBtn=true;
        $http.post(baseUrl+'/setting/user/change_password/'+$stateParams.id, payload).then(function(resp) {
            $rootScope.disBtn=false;
            toastr.success(resp.data.message)
            fn(resp)
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
        });
    }
});
app.controller('settingUserShowNotification', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Setting Notifikasi";
  $scope.formData={}
  $scope.formData.detail=[]
  $scope.checkAll=0
  $http.get(baseUrl+'/setting/user/notification/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;

    angular.forEach(data.data.notification_type, function(val,i) {
      var vale=0
      angular.forEach(data.data.notification_type_user, function(x,i) {
        if (x.notification_type_id==val.id) {
          return vale=1;
        }
      })
      $scope.formData.detail.push({
        'value' : vale,
        'notification_type_id' : val.id
      })
    })
  });

  $scope.changeAll=function(val) {
    angular.forEach($scope.formData.detail,function(x,i) {
      if (val) {
        x.value=1
      } else {
        x.value=0
      }
    })
  }

  $scope.submitForm=function() {
    // console.log($stateParams);
    $http.post(baseUrl+'/setting/user/store_notification/'+$stateParams.id,$scope.formData).then(function(data) {
      toastr.success("Data berhasil disimpan!");
      $state.reload();
    })
  }
});
app.controller('settingUserShowPrevilage', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $interval) {
    $rootScope.pageTitle="User Previlage";
    $('.sk-container').addClass('sk-loading');
    $scope.formData={}
    $scope.formData.group=[]
    $scope.formData.detail=[]
    $scope.all=0

    $scope.inheritValue = function(e, id) {
        var el = $(e.currentTarget)
        var checked = el[0].checked
        var checkbox = $('#' + id).find('[type="checkbox"]')
        var v
        for(i = 0;i < checkbox.length;i++) {
            v = checkbox[i]
            console.log(v)
            if(v.checked != checked) {
                $(v).trigger('click')
            }
        }
    }

    $scope.renderPrevilage = function(element, roles) {
        if(roles && roles.length > 0) {
            roles.forEach(function(v){
                var data = {
                    id:v.id,
                    slug:v.slug,
                    include:v.include
                }
                if(data.include) {
                    data.include = data.id
                }
                $scope.formData.detail.push(data)
                var length = $scope.formData.detail.length - 1
                var el = $(element)
                var id = 'el' + v.id
                var newEl = $(`
                    <div class="row">
                        <div class="pd-4 bb-1 b-gray col-md-10">
                            <span class='text-uppercase title'>
                                ${v.name}
                            </span>
                        </div>
                        <div class="pd-4 bb-1 b-gray col-md-2">
                            <input class="pull-right" ng-click="inheritValue($event, '${id}')" type="checkbox" id="check${v.id}" ng-model="formData.detail[${length}].include" ng-value='${v.id}' value='${v.id}' name="role_id[]" ng-true-value="${v.id}" ng-false-value="0">
                    </div>
                `);
                if(v.deep == 1) {
                    newEl.find('.title').addClass('font-bold')
                } else {
                    var paddingRight = (v.deep - 1) * 9;
                    paddingRight += 'mm'
                    newEl.find('.title').css('padding-left', paddingRight)

                }
                if(v.roles && v.roles.length > 0) {
                    var childEl = $('<div class="col-md-12"></div>')
                    childEl.attr('id', id)
                    newEl.append(childEl)
                }
                el.append(newEl)
                $scope.renderPrevilage('#' + id, v.roles)
            })
        }

        $compile($(element))($scope)
    }

    $scope.show = function() {

        $http.get(baseUrl+'/setting/user/role/'+$stateParams.id).then(function(res) {
            $scope.roles=res.data;

            $scope.renderPrevilage('#previlage_body', $scope.roles.role)
            $('.sk-container').removeClass('sk-loading');
        }, function(){
            $scope.show()
        });
    }

    $scope.show()

    $scope.alls=function() {
        var value = $scope.all
        $scope.counter = 0
        $scope.startInterval()
    }

    $scope.startInterval = function() {
        $scope.counterInterval = $interval(function(){
            var i
            for(i = $scope.counter;i < $scope.counter + 5;i++) {
                if(i < $scope.formData.detail.length) {
                    if($scope.all) {
                        $scope.formData.detail[i].include = $scope.formData.detail[i].id
                    } else {
                        $scope.formData.detail[i].include = 0
                    }
                    $scope.counter += 1
                    $scope.startInterval()
                } else {
                    $scope.stopInterval()
                }
            }
        }, 20)
    }

  $scope.stopInterval = function() {
      if($scope.counterInterval) {
          clearInterval($scope.counterInterval)
      }
  }

  $scope.submitForm=function() {
    // console.log($('#forms').serialize()+'&_token='+csrfToken);
    $.ajax({
      type: "post",
      url: baseUrl+'/setting/user/save_role/'+$stateParams.id,
      data: $('#forms').serialize()+'&_token='+csrfToken,
      dataType: "json",
      success: function(data_save){
        // $state.go('setting.user.show.personal')
        $http.post(baseUrl+'/setting/user/role_array').then(function(data) {
            $rootScope.roleList=[];
            $rootScope.roleList=data.data;
            if (data_save.status=="OK") {
                toastr.error(data_save.message,"Oops!");
            } else {
                toastr.success("Data berhasil disimpan","Sukses!");
            }
        })
      }
    });
  }
});
