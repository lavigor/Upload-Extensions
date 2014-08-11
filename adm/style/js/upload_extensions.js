; (function ($, window, document) {
	// do stuff here and use $, window and document safely
	// https://www.phpbb.com/community/viewtopic.php?p=13589106#p13589106
	$("a.simpledialog").simpleDialog({
	    opacity: 0.1,
	    width: '650px',
		height: '600px'
	});

	$("#submit").click(function () {
		$("#submit").css("display", "none");
		$("#upload").css("display", "inline-block");
	});

	$(".delete_link").click(function () {
		$(".successbox").css("display", "none");
	})
})(jQuery, window, document);

function browseFile() 
{
	document.getElementById('extupload').click();
}

function setFileName() 
{
	document.getElementById('fake_upload').value = document.getElementById("extupload").files[0].name;
}

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
