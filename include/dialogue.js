// 对话系统的JavaScript扩展

// 扩展changePages函数，处理对话选择页面
function dialogueChangePages(mode, cPages) {
    console.log('dialogueChangePages called with mode=' + mode + ', cPages=' + cPages);

    var nowpage = Number(document.getElementById(mode + 'markpage').innerHTML);
    var endpage = Number(document.getElementById(mode + 'endpage').innerHTML);
    var maxdkey = endpage + 1; // 选择页面的ID

    console.log('Current page: ' + nowpage + ', End page: ' + endpage + ', Choice page ID: ' + maxdkey);

    if(nowpage < 0 || nowpage > endpage) {
        nowpage = 0;
        console.log('Reset nowpage to 0');
    }

    var nextpage = nowpage + cPages;
    console.log('Next page: ' + nextpage);
    document.getElementById(mode + 'markpage').innerHTML = nextpage;

    // 隐藏当前页面
    var currentPage = document.getElementById(mode + nowpage);
    if(currentPage) {
        currentPage.style.display = "none";
        console.log('Hiding current page: ' + mode + nowpage);
    } else {
        console.error('Current page not found: ' + mode + nowpage);
    }

    // 如果下一页是选择页面
    if(nextpage > endpage) {
        console.log('Next page is choice page');
        // 显示选择页面
        var choicePage = document.getElementById(mode + maxdkey);
        console.log('Looking for choice page with ID: ' + mode + maxdkey);

        // 列出所有可用的元素ID
        console.log('Available elements:');
        var allElements = document.getElementsByTagName('*');
        for(var i=0; i<allElements.length; i++) {
            if(allElements[i].id && allElements[i].id.startsWith(mode)) {
                console.log('- ' + allElements[i].id);
            }
        }

        if(choicePage) {
            choicePage.style.display = "block";
            console.log("Showing choice page: " + mode + maxdkey);
        } else {
            console.error("Choice page not found: " + mode + maxdkey);
        }
    } else {
        console.log('Next page is dialogue page');
        // 显示普通对话页面
        var dialoguePage = document.getElementById(mode + nextpage);
        if(dialoguePage) {
            dialoguePage.style.display = "block";
            console.log("Showing dialogue page: " + mode + nextpage);
        } else {
            console.error("Dialogue page not found: " + mode + nextpage);
        }
    }
}

// 覆盖原有的changePages函数
function changePages(mode, cPages) {
    // 如果是对话系统，使用dialogueChangePages
    if(mode === 'd') {
        dialogueChangePages(mode, cPages);
    } else {
        // 否则使用原有的逻辑
        var nowpage = Number(document.getElementById(mode + 'markpage').innerHTML);
        var endpage = Number(document.getElementById(mode + 'endpage').innerHTML);

        if(nowpage < 0 || nowpage > endpage) {
            nowpage = 0;
        }

        var nextpage = nowpage + cPages;
        document.getElementById(mode + 'markpage').innerHTML = nowpage + cPages;

        var currentPage = document.getElementById(mode + nowpage);
        if(currentPage) {
            currentPage.style.display = "none";
        }

        var nextPageElement = document.getElementById(mode + nextpage);
        if(nextPageElement) {
            nextPageElement.style.display = "inline-block";
        }

        var prevButton = document.getElementById('shooting_previous');
        if(prevButton) {
            prevButton.style.display = nextpage > 0 ? 'inline-block' : 'none';
        }

        var nextButton = document.getElementById('shooting_next');
        if(nextButton) {
            nextButton.style.display = (nextpage >= endpage) ? 'none' : 'inline-block';
        }

        var endingButton = document.getElementById('shooting_ending');
        if(endingButton) {
            endingButton.style.display = (nextpage == endpage) ? 'inline-block' : 'none';
        }
    }
}

// 处理对话选择；不要假定当前临时指令页拥有 #command 或 #mode。
// Handle a dialogue choice without assuming the current transient command page has #command or #mode.
function handleDialogueChoice(dialogueId, choiceIndex) {
    var commandValue = 'dialogue_choice ' + dialogueId + ' ' + choiceIndex;
    var gameForm = document.forms['gamecmd'];
    var dialogueElement = document.getElementById('dialogue');
    var choiceButtons = document.querySelectorAll('#dialogue input.cmdbutton');

    if(!gameForm) {
        console.error('Game command form not found.');
        return false;
    }

    for(var i = 0; i < choiceButtons.length; i++) {
        choiceButtons[i].disabled = true;
    }

    var processingDiv = document.getElementById('dialogue-choice-processing');
    if(!processingDiv && dialogueElement) {
        processingDiv = document.createElement('div');
        processingDiv.id = 'dialogue-choice-processing';
        processingDiv.style.textAlign = 'center';
        processingDiv.style.padding = '10px';
        dialogueElement.appendChild(processingDiv);
    }
    if(processingDiv) {
        processingDiv.innerHTML = '<span style="color: yellow; font-weight: bold;">正在处理选择...</span>';
    }

    var requestCompleted = false;
    var recoveryTimer = null;
    function recoverChoice(error) {
        if(requestCompleted) return;
        requestCompleted = true;
        if(recoveryTimer) window.clearTimeout(recoveryTimer);
        if(processingDiv) {
            processingDiv.innerHTML = '<span style="color: yellow; font-weight: bold;">请求未完成，正在重新载入游戏页...</span>';
        }
        window.setTimeout(function() {
            window.location.reload();
        }, 250);
        if(error && error.message) console.error('Dialogue choice request failed:', error.message);
    }
    function finishChoice() {
        if(requestCompleted) return;
        requestCompleted = true;
        if(recoveryTimer) window.clearTimeout(recoveryTimer);
    }

    // 即使尸体、战果等页面存在同名 radio，也只提交此处指定的强制选择。
    // Submit only this mandatory choice even when corpse/result pages contain same-named radio controls.
    recoveryTimer = window.setTimeout(function() {
        if(!requestCompleted && document.getElementById('dialogue') === dialogueElement) {
            recoverChoice({message: '指令请求超时。'});
        }
    }, 15000);

    try {
        var requestStarted = postCmd('gamecmd', 'command.php', {
            data: {mode: 'command', command: commandValue},
            // 选择按钮已在本函数中锁定，不能让通用的 50ms 节流静默吞掉强制选择。
            // Buttons are already locked here, so the generic 50ms throttle must not silently drop this choice.
            bypassDelay: true,
            timeout: 15000,
            onSuccess: finishChoice,
            onError: recoverChoice
        });
        if(requestStarted === false && !requestCompleted) {
            recoverChoice({message: '指令未能开始发送。'});
        }
    } catch(error) {
        recoverChoice(error);
    }

    return false;
}
