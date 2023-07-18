//录制处理
var recordedData = [];
var isRecording = true;
function startRecording() {
    isRecording = true;
    console.log('startRecording');
    window.alert('开始录像，录制中的游戏数据会保留在浏览器内，请不要刷新或关闭该页面。');
    // 监听所有按钮的点击事件
    document.addEventListener('click', recordButtonClick);

    var linkElement = document.querySelector('a[onclick="startRecording()"]');
    if (linkElement) {
        linkElement.textContent = ">>停止录像";
        linkElement.setAttribute('onclick', 'stopRecording()');
    }
}

document.addEventListener('click', recordButtonClick);

function stopRecording() {
    isRecording = false;

    var linkElement = document.querySelector('a[onclick="stopRecording()"]');
    if (linkElement) {
        linkElement.textContent = ">>开始录像";
        linkElement.setAttribute('onclick', 'startRecording()');
    }

    // 停止监听
    document.removeEventListener('click', recordButtonClick);
    //downloadRecordedData();
}

function downloadRecordedData() {
    var reader = new FileReader();
    reader.onload = function () {
        var arrayBuffer = reader.result;
        var uint8Array = new Uint8Array(arrayBuffer);
        var gzippedData = pako.gzip(uint8Array);
        var downloadLink = document.createElement("a");
        downloadLink.href = URL.createObjectURL(new Blob([gzippedData]));
        downloadLink.download = "recorded_data.html.gz";
        downloadLink.click();
    };
    reader.readAsArrayBuffer(new Blob(recordedData, { type: "text/html" }));
}

function recordButtonClick(event) {
    // 如果录制状态为 true，则将当前前端的全部静态网页数据保存到数组中
    if (isRecording) {
        recordedData.push(document.documentElement.outerHTML.concat("\n"));
        sendLastRecordedData(recordedData);
    }
}

function sendLastRecordedData(recordedData) {
    const nickinfoElement = document.getElementById('nickinfo');
    if (!nickinfoElement) {

        return;
    }


    const nickinfo = nickinfoElement.innerText;
    const lastRecord = recordedData[recordedData.length - 1];

    fetch('record_backend.php', {
        method: 'POST',
        body: JSON.stringify({ lastRecord, nickinfo }),
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            // 处理从后端返回的响应
            //console.log(data);
        })
        .catch(error => {
            // 处理错误
            console.error(error);
        });
}


