{* 
NAME: LOADER INTERMEDIATE SCREEN

Blocks client to hit any were else on the screen after a certain event has been triggerd.

USEAGE:

{include uri="design:gui/loader.tpl"}

<input class="button" type="submit" name="Skip" value="Skip" onclick="lon('{"Please wait..."|i18n("extension/ezsvn")}');">

*}
<link rel="stylesheet" type="text/css" href={"stylesheets/loader.css"|ezdesign}>
{literal}
<script language="javascript" type="text/javascript">
function addLoadEvent( func )
{
  var oldonload = window.onload;
  if (typeof window.onload != 'function')
  {
      window.onload = func;
  }
  else
  {
      window.onload = function() {  oldonload();  func(); }
  }
}


function lsetup_handler(e)
{
	lsetup(this);
	return true;
}

function lsetup(target)
{
	try {
		if (!target)
			target = this;

		var o_set = target.document.getElementById('loaderContainerWH');
		var o_getH = target.document.getElementsByTagName('maincontent')[0];
		o_set.style.height = o_getH.scrollHeight;
	} catch (e) {
	}
}

function lon( text, target)
{
	try {
		if (parent.visibilityToolbar)
			parent.visibilityToolbar.set_display("standbyDisplayNoControls");
	} catch (e) {}

	try {
		if (!target)
			target = this;

		lsetup(target);
        
		if (!target._lon_disabled_arr)
			target._lon_disabled_arr = new Array();
		else if (target._lon_disabled_arr.length > 0)
			return true;

		target.document.getElementById("loaderContainer").style.display = "";
		var select_arr = target.document.getElementsByTagName("select");
        writeLayer( "loader-on-text", text );
		for (var i = 0; i < select_arr.length; i++) {
			if (select_arr[i].disabled)
				continue;

			select_arr[i].disabled = true;
			_lon_disabled_arr.pop(select_arr[i]);
			var clone = target.document.createElement("input");
			clone.type = "hidden";
			clone.name = select_arr[i].name;
			var values = new Array();
			for (var n = 0; n < select_arr[i].length; n++) {
				if (select_arr[i][n].selected) {
					values[values.length] = select_arr[i][n].value;
				}
			}
			clone.value = values.join(",");
			select_arr[i].parentNode.insertBefore(clone, select_arr[i]);
		}
	} catch (e) {
		return false;
	}
	return true;
}

function loff(target)
{
	try {
		if (parent.visibilityToolbar) { 
			parent.visibilityToolbar.set_display(visibilityCount
												 ? "standbyDisplay"
												 : "standbyDisplayNoControls");
		}
	} catch (e) {}

	try {
		if (!target)
			target = this;

		target.document.getElementById("loaderContainer").style.display = "none";

		if (target._lon_disabled_arr) {
			while(_lon_disabled_arr.legth > 0) {
				var select = _lon_disabled_arr.push();
				select.disabled = false;

				var clones_arr = target.document.getElementsByName(select.name);
				for (var n = 0; n < clones_arr.length; n++) {
					if ("hidden" == clones_arr[n].type)
						clones_arr[n].parent.removeChild(clones_arr[n]);
				}
			}
		}
	} catch (e) {
		return false;
	}
	return true;
}

function lsubmit(f)
{
	try {
		if (f.lock.value == "true")
			return false;
		f.lock.value = "true";
	} catch (e) {
	}

	lon();

	try {
		f.submit(f);
	} catch (e) {
		return false;
	}
	return true;
}
function writeLayer( layerID, txt )
{
    if( document.getElementById )
    {
        document.getElementById(layerID).innerHTML = txt;
    }
    else if( document.all )
    {
        document.all[layerID].innerHTML = txt;
    }
    else if( document.layers )
    {
        with( document.layers[layerID].document )
        {
           open();
           write( txt );
           close();
        }
    }
}
</script>
<script type="text/javascript">
<!--
addLoadEvent( loff );
addLoadEvent( function()
{
  // do some extra stuff here
} );
//-->
</script>
{/literal}
<div id="loaderContainer" onClick="return false;">
    <div id="loaderContainerWH">
        <div id="loader">
            <div class="message-feedback">
                <h2 id="loader-on-text">Please wait...</h2>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
<!--
document.getElementById("loaderContainer").style.display = "none";
//-->
</script>