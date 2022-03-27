<li ng-show="roleList.includes('contact')">
    <a><i class="fa fa-id-card"></i> <span class="nav-label">Contacts</span><span class="fa arrow"></span></a>
    <ul class="nav nav-second-level collapse" ui-sref-active="active">
        <li ui-sref-active="active" ng-show="roleList.includes('contact.contact')"><a ui-sref="contact.contact"><span class="nav-label">All Contacts</span></a></li>
    </ul>
</li>