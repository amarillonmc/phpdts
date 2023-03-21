@ECHO OFF
SET /a RepeatTimes=5
FOR /L %%G IN (1,1,%RepeatTimes%) DO START "Batch_Process_%%G" cmd /c "bot_enable.bat"
ECHO Batch processes started.
PAUSE