/*function hotkey(evt) 
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
}*/

var ms;
hotkey_ok = true;
refchat_ok = true;
function hotkey(evt) 
{ 
	if(hotkey_ok && document.activeElement.tagName != 'INPUT'){
		evt = (evt) ? evt : ((window.event) ? window.event : '');
		var ky = evt.keyCode ? evt.keyCode : evt.which;
		flag=1;//是否完成冷却
		if (ms!=undefined) {
			if (ms>0) flag=0;
		}	
		//双字母id=冷却时间内不可执行的操作 单字母可以执行
		if(!evt.ctrlKey && !evt.altKey && !evt.shiftKey){
			if(ky==90){
				flag==1 ? hotkey_click('zz') : hotkey_click('z');
			}
			else if(ky==65){
				flag==1 ? hotkey_click('aa') : hotkey_click('a');
			}
			else if(ky==83){
				flag==1 ? hotkey_click('ss') : hotkey_click('s');
			}
			else if(ky==68){
				flag==1 ? hotkey_click('dd') : hotkey_click('d');
			}
			else if(ky==81){
				flag==1 ? hotkey_click('qq') : hotkey_click('q');
			}
			else if(ky==87){
				flag==1 ? hotkey_click('ww') : hotkey_click('w');
			}
			else if(ky==69){
				flag==1 ? hotkey_click('ee') : hotkey_click('e');
			}
			else if(ky==88){
				flag==1 ? hotkey_click('xx') : hotkey_click('x');
			}
			else if(ky==67){
				flag==1 ? hotkey_click('cc') : hotkey_click('c');
			}
			else if(ky==86){
				hotkey_click('v');
			}
			else if(ky >= 49 && ky <= 54){
				var kc=(ky-48).toString();
				flag==1 ? hotkey_click(kc+kc) : hotkey_click(kc);
			}
		}
	}	
}

function hotkey_click(hkid){
	var hk = $(hkid);
	if (hk) hk.click();
	else if ((hkid == 'zz' || hkid == 'z' || hkid == 'x') && $('zx')) $('zx').click();
	else if (hkid.length > 1) {
		hk = $(hkid.substr(0,1));
		if (hk) hk.click();
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
		ms = 0;
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
	if($('change_emr') != null && $('change_emr').checked) $('change_emr').value=1;
	if($('change_emax') != null && $('change_emax').checked) $('change_emax').value=1;
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
function IconMover(idiv,islct,ipre){
	inum = $(islct).selectedIndex;
	$(idiv).innerHTML = '<img src="img/' + ipre + '_' + inum + '.gif" alt="' + inum + '">';
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
var lastRun = 0; var delay = 50;
function postCmd(formName,sendto){
	var oXmlHttp = zXmlHttp.createRequest();
	var sBody = getRequestBody(document.forms[formName]);
	oXmlHttp.open("post", sendto, true);
	oXmlHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	const now = Date.now();
	if (lastRun && now - lastRun < delay) {
		//console.log('上次响应时间：' + lastRun + ' ' + delay + '毫秒内无法重复执行。' + '当前时间刻：' + now);
		return;
	}
	lastRun = now;
	//console.log('执行了一次指令，当前时间：' + now);
	oXmlHttp.onreadystatechange = function () {
		if (oXmlHttp.readyState == 4) {
			if (oXmlHttp.status == 200) {
				if (oXmlHttp.responseText!='')
				{
					showData(oXmlHttp.responseText);
				}
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
	if ($('HsUipfcGhU'))	//ˢ��ҳ����
	{
		window.location.reload();
	}
	if($('open-dialog'))
	{
		dialogid = $('open-dialog').innerHTML;
		showModalDialog($(dialogid));
	}
}

var refchat = null;

function chat(mode,reftime) {
	clearTimeout(refchat);
	var oXmlHttp = zXmlHttp.createRequest();
	var sBody = getRequestBody(document.forms['sendchat']);
	if(mode == 'news') oXmlHttp.open("post", "news.php", true);
	else oXmlHttp.open("post", "chat.php", true);
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
	if(mode == 'news') refchat = setTimeout("chat('news',rtime)",rtime);
	else refchat = setTimeout("chat('ref',rtime)",rtime);
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
	}else{
		$('newslist').innerHTML = jsonchat;
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

//元素合成界面的ajax效果 仅作美化使用
function getEmitmeR(type=0) {
	if(type == 1)
	{	
		var r = document.getElementById("emitme_max_r").value;
		var e = document.getElementById("emax").value;
		$('s_emitme_max').innerHTML = Math.round(e*(r/100));
	}
	else
	{
		var r = document.getElementById("emitme_r").value;
		$('s_emitme_r').innerHTML = r;
		$('s_emitms_r').innerHTML = 100-r;
		$('sr_warning').innerHTML = '';
		if(r>79 || r<21)
		{
			$('sr_warning').innerHTML = '警告：过度干预可能引发灾难性的后果！<br>';
		}
	}
}

function AddElementsToList(ekey,enums)
{
	var list = $('emixlist').value;
	var nums = $('emixnums').value ;
	var desc = $('emixinfo').innerHTML;
	var keyarr = list.length>0 ? list.split('+') : [];
	var numsarr = nums.length>0 ? nums.split('+') : [];
	var descarr = desc.length>0 ? desc.split('、') : [];
	if(enums <= 0)
	{
		window.alert("至少要投入1份元素！");
		return;
	}
	if(($('maxe' + ekey + 'num').value - enums) < 0)
	{
		window.alert("输入了超过了库存的数量。");
		return;
	}
	if(keyarr.length >= 6)
	{
		window.alert("最多可分六次投入");
		return;
	}
	$('maxe' + ekey + 'num').value -= enums;
	$('e' + ekey + 'num').value = $('maxe' + ekey + 'num').value;
	keyarr.push(ekey);
	numsarr.push(enums);
	descarr.push(enums + ' 份 ' + $('edesc' + ekey).innerHTML);
	$('emixlist').value = keyarr.join('+');
	$('emixnums').value = numsarr.join('+');
	$('emixinfo').innerHTML = descarr.join('、');
	$('emixinfotop').style.display = 'block';
}

function AddMixElements(emix_arr) {
	var list = $('emixlist').value;
	var nums = $('emixnums').value;
	var desc = $('emixinfo').innerHTML;
	var keyarr = [];
	var numsarr = [];
	var descarr = [];
	const esum = [];
	
	for (let i = 0; i < emix_arr.length; i++) {
		esum[emix_arr[i][0]] = (esum[emix_arr[i][0]] || 0) + emix_arr[i][1];
	}		
	for (let i = 0; i < emix_arr.length; i++) {
		if($('maxe' + emix_arr[i][0] + 'num') === null || ($('maxe' + emix_arr[i][0] + 'num').value - esum[emix_arr[i][0]]) < 0) {
			window.alert("合成所需的元素数量不足。");
			return;
		}
	}
	for (let i = 0; i < emix_arr.length; i++) {
		$('maxe' + emix_arr[i][0] + 'num').value -= emix_arr[i][1];
		$('e' + emix_arr[i][0] + 'num').value = $('maxe' + emix_arr[i][0] + 'num').value;
		keyarr.push(emix_arr[i][0]);
		numsarr.push(emix_arr[i][1]);
		descarr.push(emix_arr[i][1] + ' 份 ' + $('edesc' + emix_arr[i][0]).innerHTML);
	}
	$('emixlist').value = keyarr.join('+');
	$('emixnums').value = numsarr.join('+');
	$('emixinfo').innerHTML = descarr.join('、');
	$('emixinfotop').style.display = 'block';
}

function changeVolume(cv){ 
	var v = $('gamebgm').volume;
	v = v+cv;
	v = Math.min(1,v); v = Math.max(0,v); 	v = v.toFixed(2);
	Cookie.setCookie("volume",v, {
		expireHours: 24*30,
		path: "/",
	});
	s = Math.round(v*100);
	$('gamebgm').volume = v;
	$('volume_num').innerHTML = s+'%';
}

function changeBGM(mode=1){
	var bgmlist = JSON.parse($('bgmlist').innerHTML);
	var nowbgm = Math.round($('nowbgm').innerHTML);
	nowbgm = nowbgm + mode;
	if(nowbgm < 0){
		nowbgm = bgmlist.length - 1;
	}else{
		nowbgm = nowbgm % bgmlist.length;
	}
	$('gbgm').src = bgmlist[nowbgm].url;
	$('gbgm').type = bgmlist[nowbgm].type;
	$('nowbgm').innerHTML = nowbgm;
	Cookie.setCookie("nowbgmid",bgmlist[nowbgm].id, {
		path: "/",
	});
	var v = $('gamebgm').volume;
	$('bgmname').innerHTML = bgmlist[nowbgm].name;
	$('gamebgm').load();
	$('gamebgm').volume = v;
	$('gamebgm').play();
}

function changePages(mode,cPages)
{
	var nowpage = Number($(mode + 'markpage').innerHTML);
	var endpage = Number($(mode + 'endpage').innerHTML);
	if(nowpage < 0 || nowpage > endpage)
	{
		nowpage = 0;
	}
	var nextpage = nowpage + cPages;
	$(mode + 'markpage').innerHTML = nowpage + cPages;
	$(mode + nowpage).style.display="none";
	$(mode + nextpage).style.display="inline-block";
	$('shooting_previous').style.display = nextpage > 0 ? 'inline-block' : 'none';
	$('shooting_next').style.display = (nextpage >= endpage) ? 'none' : 'inline-block';
	$('shooting_ending').style.display = (nextpage == endpage) ? 'inline-block' : 'none';
}

////////////////////////////////////////////////////////////////////////
///////////////////////////称号技能鼠标悬浮特效////////////////////////////
////////////////////////////////////////////////////////////////////////

function skill_unacquired_mouseover(e)
{
	var children = this.childNodes;
	for (var i = 0; i < children.length; i++) 
	{
		var child = children[i];
		if (child.className == 'skill_unacquired') 
		{
			child.className = 'skill_unacquired_transparent';
		}
		if (child.className == 'skill_unacquired_hint') 
		{
			child.className = 'skill_unacquired_hint_transparent';
		}
	}
}

function skill_unacquired_mouseout(e)
{
	var children = this.childNodes;
	for (var i = 0; i < children.length; i++) 
	{
		var child = children[i];
		if (child.className == 'skill_unacquired_transparent') 
		{
			child.className = 'skill_unacquired'; 
		}
		if (child.className == 'skill_unacquired_hint_transparent') 
		{
			child.className = 'skill_unacquired_hint';
    	}
	}
}

function selectRecordedFile() {
    var input = document.createElement("input");
    input.type = "file";
    input.accept = ".gz"; 

    input.click();

    // 处理选择的文件
    input.onchange = function (event) {
        var file = event.target.files[0]; 
        console.log("选择的文件:", file);

        displayRecordedData(file);
    };
}

// 显示单个页面的内容
function showPage(pageContent, currentPageIndex) {
    var recordedDataDiv = document.getElementById('recordedData');
    recordedDataDiv.innerHTML = pageContent[currentPageIndex];

    var previousPageButton = document.createElement('button');
    previousPageButton.textContent = '上一页';
    previousPageButton.onclick = function () {
        if (currentPageIndex > 0) {
            currentPageIndex--;
            showPage(pageContent, currentPageIndex);
        } else {
            recordedDataDiv.innerHTML = '已经到达第一页';
        }
    }
    var nextPageButton = document.createElement('button');
    nextPageButton.textContent = '下一页';
    nextPageButton.onclick = function () {
        if (currentPageIndex < pageContent.length - 1) {
            currentPageIndex++;
            showPage(pageContent, currentPageIndex);
        } else {
            recordedDataDiv.innerHTML = '已经到达最后一页';
        }
    };
    // 阻止链接跳转
    var links = recordedDataDiv.getElementsByTagName('a');
    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function (event) {
            event.preventDefault();
        });
    }
    // 删除具有 id="hidden-model" 的元素
    var hiddenModelElement = document.getElementById('hidden-model');
    if (hiddenModelElement) {
        hiddenModelElement.remove();
    }

    var progressBar = document.createElement('div');
    progressBar.className = 'progress-bar';
    progressBar.style.width = ((currentPageIndex + 1) / pageContent.length) * 100 + '%';
    
    var progressBar = document.createElement('input');
    progressBar.type = 'range';
    progressBar.min = 0;
    progressBar.max = pageContent.length - 1;
    progressBar.value = currentPageIndex;
    progressBar.oninput = function () {
        currentPageIndex = parseInt(progressBar.value);
        showPage(pageContent, currentPageIndex);
    };
    
    recordedDataDiv.insertBefore(progressBar, recordedDataDiv.firstChild);
    recordedDataDiv.insertBefore(nextPageButton, recordedDataDiv.firstChild);
    recordedDataDiv.insertBefore(previousPageButton, recordedDataDiv.firstChild);
}


function displayRecordedData(file) {
    // 检查是否选择了文件
    if (file) {
        // 创建一个FileReader对象来读取文件内容
        var reader = new FileReader();
        reader.onload = function () {
            // 将文件内容转换为ArrayBuffer
            var arrayBuffer = reader.result;
            // 创建一个Uint8Array来存储ArrayBuffer的数据
            var uint8Array = new Uint8Array(arrayBuffer);

            // 解压缩文件数据
            var inflatedData = pako.inflate(uint8Array, { to: 'string' });
            // 将解压缩后的数据按页进行切分
            var pages = inflatedData.split('\n<html>');

            // 逐页展示记录的内容
            var recordedDataDiv = document.getElementById('recordedData');
            recordedDataDiv.innerHTML = ''; // 清空之前的内容

            // 创建一个包含每页内容的数组
            var pageContent = [];
            for (var i = 0; i < pages.length; i++) {
                pageContent.push(pages[i]);
            }

            // 显示第一页的内容
            showPage(pageContent, 0);
        };
        reader.readAsArrayBuffer(file);
    } else { // 如果没有选择文件，则显示选择文件的提示消息 
        var noticeDiv = document.getElementById('notice'); noticeDiv.textContent = '请先选择一个录像文件';
    }
}

/*window.onbeforeunload = function () {
    if (isRecording) {
        window.alert('你正在录制游戏，之后将会自动下载录制数据。');
        downloadRecordedData();
    }
};*/
  
  