function hotkey(evt) 
{ 
	if(document.activeElement.tagName != 'INPUT'){
		evt = (evt) ? evt : ((window.event) ? window.event : '');
		var ky = evt.keyCode ? evt.keyCode : evt.which;
		if(!evt.ctrlKey && !evt.altKey && !evt.shiftKey){
			if(ky==90){
				$('submit').click();
			}
		}
	}	
}

//update time
function updateTime(timing,mode)
{
	if(timing){
		t = timing;
		tm = mode;
		h = Math.floor(t/3600);
		m = Math.floor((t%3600)/60);
		s = t%60;
		// add a zero in front of numbers<10
		h=checkTime(h);
		m=checkTime(m);
		s=checkTime(s);
		$('timing').innerHTML = h + ':' + m + ':' +s;
		tm ? t++ : t--;
		setTimeout("updateTime(t,tm)",1000);
	}
	else{
		window.location.reload(); 
	}
}


function demiSecTimer(){
	if($('timer') && ms>=itv)	{
		ms -= itv;
		var sec = Math.floor(ms/1000);
		var dsec = Math.floor((ms%1000)/100);
		$('timer').innerHTML = sec + '.' + dsec;
	}	else {
		clearInterval(timerid);
		delete timerid;
	}
}

function demiSecTimerStarter(msec){
	itv = 100;//by millisecend
	ms = msec;
	timerid = setInterval("demiSecTimer()",itv);
}

function itemmixchooser(){
	for(i=1;i<=6;i++){
		var mname = 'mitm'+i;
		if($(mname) != null){
			if($(mname).checked){
				$(mname).value=i;
			}
		}
	}
}

//icon select
//function iconMover(){
//	gd = document.valid.gender[0].checked ? 'm' : 'f';
//	inum = document.valid.icon.selectedIndex;
//	$('iconImg').innerHTML = '<img src="img/' + gd + '_' + inum + '.gif" alt="' + inum + '">';
//}
function userIconMover(){
	ugd = $('male').checked ? 'm' : 'f';
	uinum = $('icon').selectedIndex;
	$('userIconImg').innerHTML = '<img src="img/' + ugd + '_' + uinum + '.gif" alt="' + uinum + '">';
}
function dniconMover(){
	dngd = $('male').checked ? 'm' : 'f';
	dninum = $('dnicon').selectedIndex;
	$('dniconImg').innerHTML = '<img src="img/' + dngd + '_' + dninum + '.gif" alt="' + dninum + '">';
}

function showNotice(sNotice) {
	$('notice').innerText = sNotice;
}

function sl(id) {
	$(id).checked = true;
}

//function postCommand(){
//	$('submit').disabled = true;
//	var oXmlHttp = zXmlHttp.createRequest();
//	var sBody = getRequestBody(document.forms['gamecmd']);
//	oXmlHttp.open("post", "command.php", true);
//	oXmlHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
//	oXmlHttp.onreadystatechange = function () {
//		if (oXmlHttp.readyState == 4) {
//			if (oXmlHttp.status == 200) {
//				showGamedata(oXmlHttp.responseText);
//				$('submit').disabled = false;
//			} else {
//				showNotice(oXmlHttp.statusText);
//			}
//		}
//	};
//	oXmlHttp.send(sBody);
//}
//
//function showGamedata(sGamedata){
//	gamedata = sGamedata.parseJSON();
//	if(gamedata['url']) {
//		window.location.href = gamedata['url'];
//	} else if(!gamedata['main']) {
//		//window.location.href = 'index.php';
//		$('notice').innerHTML = sGamedata;
//	}
//	//timer = 0;
//	for(var id in gamedata) {
//		if(id == 'toJSONString' || id == 'timer') {
//			continue;
//		} else if(gamedata[id]){
//			if(id == 'team'){
//				$('team').value = gamedata['team'];
//			}else{
//				$(id).innerHTML = gamedata[id];
//			}
//		} else{
//			$(id).innerHTML = '';
//		}
//		
//	}
//	if(gamedata['timer'] && typeof(timerid)=='undefined'){
//		demiSecTimerStarter(gamedata['timer']);
//	}
//}

//function postRegCommand(){
//	$('post').disabled = true;
//	$('reset').disabled = true;
//	var oXmlHttp = zXmlHttp.createRequest();
//	var sBody = getRequestBody(document.forms['reg']);
//	oXmlHttp.open("post", "register.php", true);
//	oXmlHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
//	oXmlHttp.onreadystatechange = function () {
//		if (oXmlHttp.readyState == 4) {
//			if (oXmlHttp.status == 200) {
//				$('post').disabled = false;
//				$('reset').disabled = false;
//				showRegdata(oXmlHttp.responseText);
//			} else {
//				showNotice(oXmlHttp.statusText);
//			}
//		}
//	};
//	oXmlHttp.send(sBody);
//}
//
//function showRegdata(sRegdata){
//	regdata = sRegdata.parseJSON();
//	for(var id in regdata) {
//		if(id == 'toJSONString') {
//			continue;
//		} else if(regdata[id]){
//			$(id).innerHTML = regdata[id];
//		} else{
//			$(id).innerHTML = '';
//		}		
//	}
//}

//function showNews(n){
//	var oXmlHttp = zXmlHttp.createRequest();
//
//	oXmlHttp.open("post", "news.php", true);
//	oXmlHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
//	oXmlHttp.onreadystatechange = function () {
//		if (oXmlHttp.readyState == 4) {
//			if (oXmlHttp.status == 200) {
//				showNewsdata(oXmlHttp.responseText);
//			} else {
//				showNotice(oXmlHttp.statusText);
//			}
//		}
//	};
//	oXmlHttp.send('newsmode=' + n);
//}
//
//function showNewsdata(newsdata) {
//	news = newsdata.parseJSON();
//	if(news['msg']){
//		newchat = '';
//		for(var nid in news['msg']) {
//			if(nid == 'toJSONString') {continue;}
//			newchat += news['msg'][nid];
//		}
//		$('newsinfo').innerHTML = newchat;
//	} else {
//		$('newsinfo').innerHTML = news;
//	}
//}

//function showAlive(mode){
//	//window.location.href = 'alive.php?alivemode=' + mode;
//	
//	var oXmlHttp = zXmlHttp.createRequest();
//	
//	oXmlHttp.open("post", "alive.php", true);
//	oXmlHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
//	oXmlHttp.onreadystatechange = function () {
//		if (oXmlHttp.readyState == 4) {
//			if (oXmlHttp.status == 200) {
//				showAlivedata(oXmlHttp.responseText);
//			} else {
//				showNotice(oXmlHttp.statusText);
//			}
//		}
//	};
//	oXmlHttp.send('alivemode=' + mode);
//}
//function showAlivedata(alivedata) {
//	alive = alivedata.parseJSON();
//	$('alivelist').innerHTML = alive;
//}

function postCmd(formName,sendto){
	var oXmlHttp = zXmlHttp.createRequest();
	var sBody = getRequestBody(document.forms[formName]);
	oXmlHttp.open("post", sendto, true);
	oXmlHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	oXmlHttp.onreadystatechange = function () {
		if (oXmlHttp.readyState == 4) {
			if (oXmlHttp.status == 200) {
				showData(oXmlHttp.responseText);
			} else {
				showNotice(oXmlHttp.statusText);
			}
		}
	}
	oXmlHttp.send(sBody);
}

function showData(sdata){
	shwData = sdata.parseJSON();
	if(shwData['url']) {
		window.location.href = shwData['url'];
	}else if(!shwData['innerHTML']) {
		$('error').innerHTML=sdata;
			//window.location.href = 'index.php';
	}else{
		sDv = shwData['value'];
		for(var id in sDv){
			if($(id)!=null){
				$(id).value = sDv[id];
			}
		}
		sDi = shwData['innerHTML'];
		for(var id in sDi){
			if($(id)!=null){
				if(sDi['id'] !== ''){
					$(id).innerHTML = sDi[id];
				}else{
					$(id).innerHTML = '';
				}
			}
		}
		sDd = shwData['display'];
		for(var id in sDd){
			if($(id)!=null){
				
				$(id).style.display = sDd[id];
			}
		}
	}
	if(shwData['timer'] && typeof(timerid)=='undefined'){
		demiSecTimerStarter(shwData['timer']);
	}
	if ($('HsUipfcGhU'))	//刷新页面标记
	{
		window.location.reload();
	}
}

var refchat = null;

function chat(mode,reftime) {
	clearTimeout(refchat);
	var oXmlHttp = zXmlHttp.createRequest();
	var sBody = getRequestBody(document.forms['sendchat']);
	oXmlHttp.open("post", "chat.php", true);
	oXmlHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	oXmlHttp.onreadystatechange = function () {
		if (oXmlHttp.readyState == 4) {
			if (oXmlHttp.status == 200) {
				showChatdata(oXmlHttp.responseText);
			} else {
				showNotice(oXmlHttp.statusText);
			}
		}
	};
	oXmlHttp.send(sBody);
	if(mode == 'send'){$('chatmsg').value = '';$('sendmode').value = 'ref';}
	rtime = reftime;
	refchat = setTimeout("chat('ref',rtime)",rtime);
}


function showChatdata(jsonchat) {
	chatdata = jsonchat.parseJSON();
	if(chatdata['msg']) {
		$('lastcid').value=chatdata['lastcid'];
		newchat = '';
		for(var cid in chatdata['msg']) {
			if(cid == 'toJSONString') {continue;}
			newchat += chatdata['msg'][cid];
		}
		$('chatlist').innerHTML = newchat + $('chatlist').innerHTML;
	}			
}

function openShutManager(oSourceObj,oTargetObj,shutAble,oOpenTip,oShutTip){
	var sourceObj = typeof oSourceObj == "string" ? document.getElementById(oSourceObj) : oSourceObj;
	var targetObj = typeof oTargetObj == "string" ? document.getElementById(oTargetObj) : oTargetObj;
	var openTip = oOpenTip || "";
	var shutTip = oShutTip || "";
	if(targetObj.style.display!="none"){
	   if(shutAble) return;
	   targetObj.style.display="none";
	   if(openTip  &&  shutTip){
	    sourceObj.innerHTML = shutTip; 
	   }
	} else {
	   targetObj.style.display="block";
	   if(openTip  &&  shutTip){
	    sourceObj.innerHTML = openTip; 
	   }
	}
}

//1
