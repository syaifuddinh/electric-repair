<li class="nav-header">
    <div class="dropdown profile-element"> <span>
    		<!-- <h4 style="color: white;">SOLOG</h4> -->
        <img alt="image" src="{{asset('img/pilar.png')}}" style="max-width: 150px;" />
             </span>
             
        <a ui-sref="home">
            <span class="clear"> <span style="color: white;" class="block m-t-xs text-capitalize"> <strong class="font-bold text-lg">{{@Auth::user()->name}}</strong>
            </span> <span class="text-muted text-xs block"><% groupNameProfile %> </span> </span> </a>
    </div>
    <div class="logo-element">
        SOL
    </div>
</li>
