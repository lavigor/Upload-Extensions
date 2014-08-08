(function ($, window, document) {
	// do stuff here and use $, window and document safely
	// https://www.phpbb.com/community/viewtopic.php?p=13589106#p13589106
	time = 1;
	function progress() {
		var element = $('#ProgressStatus');
		if (time === 10) element.css("right", "9px");
		element.html(time);
	//	if (time === 0) clearInterval(progress);
		time++;
	}
	setInterval(progress, 1000);
	
		$("a.simpledialog").simpleDialog({
	    opacity: 0.1,
	    width: '650px',
		height: '730px'
	});
})(jQuery, window, document);

function loadXMLDoc(url)
{
	var xmlhttp;
	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
	  	xmlhttp=new XMLHttpRequest();
	} else
	{// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
			document.getElementById("filecontent").style.display="block";
			document.getElementById("filecontent").innerHTML=xmlhttp.responseText;
		}
	}
	xmlhttp.open("GET",url,true);
	xmlhttp.send();
}