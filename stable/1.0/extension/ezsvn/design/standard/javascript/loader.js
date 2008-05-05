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
		var o_getH = target.document.getElementsByTagName('BODY')[0];

		o_set.style.height = o_getH.scrollHeight;
	} catch (e) {
	}
}

function lon(target)
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
alert(target.document.getElementById("loaderContainer").style.display);
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