<?php
if(!defined('IN_GAME')) {
	exit('Access Denied');
}
//processing all fireseed related functions.
//fireseedprocess will be called constantly if a club22 player discover()s.
//fireseedrecruit will be called on corpse page of fireseed, populate the PlayerID into clbstatusc and "1" into clbstatusd
//fireseedenhance will be called from command, cost player items to add to Fireseed NPC's clbstatusd
//Other needed functions:
//sumintensity - calculate the total intensity of a given player by adding up each of Fireseed NPC's clbstatusd with the PlayerID clbstatusc
//repairfireseed - utility that reset a Fireseed NPC's equipment and HP.
//spawnfireseed - utility that spawns a Fireseed NPC at a given location pls.
function fireseedprocess($intensity){
    # $intensity = SUM of rates of fireseed NPCs under a certain club22 player's team.
    # For now, storing the owner ID of a fireseed NPC in its clbstatusc field.
    # RATES = clbstatusd of Fireseed NPCs (NPC Type 92) defaults to 0, becomes 1 when recruited and will increase when player enhance them.
	global $log,$now,$plsinfo,$db,$tablepre;

    //process Fireseed auto obtain items
    # Item obtain rate = ($intensity / 20 ) %, caps at 100%

    //process Fireseed auto drain NPC HP
    # NPC HP drain rate = $intensity, HP caps at 1

}

function fireseedrecruit($playerID, $fireseedID){

    global $log, $db, $tablepre;

    //process fireseed branding and init
    #Get Fireseed NPC to Brand
    $getBrandingFireseed = "SELECT FROM {$tablepre}players WHERE type = 92 AND pid = $fireseedID";
    $brandingFireseed = $db->query($getBrandingFireseed);

    #Brand the Fireseed NPC
    $brandingFireseed['clbstatusc'] = $playerID;
    #Init rate
    $brandingFireseed['clbstatusd'] = 1;
    #Monster Reborn that Fireseed NPC
    $brandingFireseed['hp'] = $brandingFireseed['mhp'];

    //DEBUG
    $log .= "FIRESEED $fireseedID SUCCESSFULLY BRANDED WITH PLAYERID $playerID .<br>";

}

function fireseedenhance($rate, $fireseedID){

    global $log, $db, $tablepre;

    //process fireseed rateup
    #Get Fireseed to rateup
    $getRateupFireseed = "SELECT FROM {$tablepre}players WHERE type = 92 AND pid = $fireseedID";
    $rateupFireseed = $db->query($getRateupFireseed);
    #Rate up
    $rateupFireseed['clbstatusd'] += $rate;
    #Enhacing Fireseed
    for ($i = 1; $i<=$rate; $i++){
        $rateupFireseed['mhp'] *= 1 + $rate / 10;
        $rateupFireseed['msp'] *= 1 + $rate / 10;
        $rateupFireseed['att'] += 1 + $rate * 10;
    }

    #Repair that Fireseed
    repairfireseed($fireseedID);

    //DEBUG
    $log .= "FIRESEED $fireseedID RATEUP SUCCESSFUL.<br>";

}

function sumintensity($playerID){
    global $log, $db, $tablepre;

    //process intensity SUM
    #Get all Intensity
    $checkGivenPlayerIntensity = "SELECT * FROM {$tablepre}players WHERE type = 92 AND clbstatusc = $playerID";
    $givenPlayerIntensity = $db->query($checkGivenPlayerIntensity);
    $idata = $db->fetch_array($givenPlayerIntensity);
    #Sum Intensity
    $sum = 0;

    while ($idata){
        $sum += $idata['clbstatusd'];
    }

    //DEBUG
    $log .= " <span class=\"yellow\">$sum</span> INTENSITY<br>";

    #Return Intensity Value
    return $sum;
}

function repairfireseed($fireseedID){
    global $log, $db, $tablepre;
    
    //process repairing fireseed NPC
    #Get Fireseed NPC to repair
    $checkGivenRepairableFireseed = "SELECT FROM {$tablepre}players WHERE type = 92 AND pid = $fireseedID";
    $repairFireSeed = $db->query($checkGivenRepairableFireseed);

    #Repair Fireseed
    $repairFireSeed['hp'] = $repairFireSeed['mhp'];
    $repairFireSeed['sp'] = $repairFireSeed['msp'];

    $repairFireseed['arb'] = $repairFireseed['arh'] = $repairFireseed['ara'] = $repairFireseed['arf'] = $repairFireseed['art'] = "✦修复数据";
    $repairFireseed['arbs'] = $repairFireseed['arhs'] = $repairFireseed['aras'] = $repairFireseed['arfs'] = $repairFireseed['arts'] = 1;
    $repairFireseed['arbsk'] = "a";
    $repairFireseed['arhsk'] = "B";
    $repairFireseed['arask'] = "b";
    $repairFireseed['arfsk'] = "M";
    $repairFireseed['artsk'] = "H";

    //DEBUG
    $log .= "FIRESEED $fireseedID REPAIR SUCCESSFUL.<br>";
    
}
?>