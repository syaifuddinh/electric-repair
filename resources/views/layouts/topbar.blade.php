<nav class="navbar navbar-static-top  " role="navigation" style="margin-bottom: 0">
<div class="navbar-header">
    <a class="navbar-minimalize minimalize-styl-2 btn btn-primary "><i class="fa fa-bars"></i> </a>
</div>
    <ul class="nav navbar-top-links navbar-right">
        <li class="dropdown" ng-controller="journalTopNotification" ng-show="roleList.includes('finance.journal.posting')">
            <a class="dropdown-toggle count-info" ng-class="{'faa-vertical animated': data.length>0}" data-toggle="dropdown">
                <i class="fa fa-check-square"></i>  <span ng-cloak ng-if="data.length>0" class="label label-danger"><% notif_length %></span>
            </a>
            <ul class="dropdown-menu dropdown-messages">
                <li ng-repeat="val in data | limitTo : 4">
                    <div class="dropdown-messages-box" ng-click="goTo(val)">
                        <div class="media-body">
                            <div class='font-weight-bold'><% val.code %></div> 
                            <p ng-bind-html="val.description.length>120? val.description.substring(0,120)+'...' : val.description"></p>
                            <small class="text-muted"><% val.date_transaction|timeago %></small>
                        </div>
                    </div>
                    <hr>
                </li>
                <li class="dropdown-divider"></li>
                <li>
                    <div class="text-center link-block">
                        <a ui-sref="finance.journal_notification" class="dropdown-item">
                            <i class="fa fa-envelope"></i> <strong>Read All</strong>
                        </a>
                    </div>
                </li>

            </ul>
        </li>
      <li class="dropdown" ng-controller="mainNotification">
        <a class="dropdown-toggle count-info" ng-class="{'faa-vertical animated': data.length>0}" data-toggle="dropdown">
            <i class="fa fa-bell"></i>  <span ng-cloak ng-if="data.length>0" class="label label-danger"><% notif_length %></span>
        </a>
        <ul class="dropdown-menu dropdown-messages">
            <li ng-repeat="val in data | limitTo : 4" ng-click="goTo(val)" class='context-menu'>
                <div class="dropdown-messages-box">
                    <div class="media-body">
                        <div class='font-weight-bold'><% val.title %></div> 
                        <div class='mg-t1 mg-b2' ng-bind="val.des"></div>
                        <small class="text-muted"><% val.date|timeago %></small>
                    </div>
                </div>
                <hr>
            </li>
            <li class="dropdown-divider"></li>
            <li>
                <div class="text-center link-block">
                    <a ui-sref="marketing.operational_notification" class="dropdown-item">
                        <i class="fa fa-envelope"></i> <strong>Read All Messages</strong>
                    </a>
                </div>
            </li>

        </ul>
      </li>
      <!-- <li class="dropdown">
          <a class="dropdown-toggle count-info faa-vertical animated" data-toggle="dropdown" href="#">
              <i class="fa fa-bell "></i>  <span class="label label-primary">8</span>
          </a>
          <ul class="dropdown-menu dropdown-alerts">
              <li>
                  <a href="mailbox.html">
                      <div>
                          <i class="fa fa-envelope fa-fw"></i> You have 16 messages
                          <span class="pull-right text-muted small">4 minutes ago</span>
                      </div>
                  </a>
              </li>
              <li class="divider"></li>
              <li>
                  <a href="profile.html">
                      <div>
                          <i class="fa fa-twitter fa-fw"></i> 3 New Followers
                          <span class="pull-right text-muted small">12 minutes ago</span>
                      </div>
                  </a>
              </li>
              <li class="divider"></li>
              <li>
                  <a href="grid_options.html">
                      <div>
                          <i class="fa fa-upload fa-fw"></i> Server Rebooted
                          <span class="pull-right text-muted small">4 minutes ago</span>
                      </div>
                  </a>
              </li>
              <li class="divider"></li>
              <li>
                  <div class="text-center link-block">
                      <a href="notifications.html">
                          <strong>See All Alerts</strong>
                          <i class="fa fa-angle-right"></i>
                      </a>
                  </div>
              </li>
          </ul>
      </li> -->


        <li>
            <a href="{{route('logout')}}" onclick="event.preventDefault();
                     document.getElementById('logout-form').submit();">
                <i class="fa fa-sign-out"></i> Log out
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                {{ csrf_field() }}
            </form>
        </li>
    </ul>
</nav>
