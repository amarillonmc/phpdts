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
//movefiressed - utility that moves a Fireseed NPC to a given location pls.
function fireseedprocess($intensity){
    # $intensity = SUM of rates of fireseed NPCs under a certain club22 player's team.
    # For now, storing the owner ID of a fireseed NPC in its clbstatusc field.
    # RATES = clbstatusd of Fireseed NPCs (NPC Type 92) defaults to 0, becomes 1 when recruited and will increase when player enhance them.
	global $log,$now,$plsinfo,$db,$tablepre;

    //process Fireseed auto obtain items
    # Item obtain rate = ($intensity / 20 ) %, caps at 100%

    # Generate Fireseed $pls Pool

    # Process Get Item - Spawning an item from pool into player's $itm0 

    //process Fireseed auto drain NPC HP
    # NPC HP drain rate = $intensity, HP caps at 1

    # NPC HP drain Logic
    //Get ALL valid NPC Data that matches Fireseed $pls pool

    //Calculate new $hp value of the NPCs by subtracting $hp with $intensity in a while loop
    // ... do a check to ensure we don't kill off any of the NPC.

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
    #Fireseed joins dummy team
    $brandingFireseed['teamID'] = $playerID. "FireSeedCrew"; //maybe generate a random string when creating club22 and use that for this.
    $brandingFireseed['teampass'] = 'HerNameIsMAPLE';
    #Monster Reborn that Fireseed NPC
    $brandingFireseed['hp'] = $brandingFireseed['mhp'];
    #Also drag them out from Horizon - cuz new update
    $brandingFireseed['horizon'] = 0;

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
    //Ultimately those should be configable from a file.
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
    $checkGivenPlayerIntensity = "SELECT SUM(clbstatusd) FROM {$tablepre}players WHERE type = 92 AND clbstatusc = $playerID";
    $givenPlayerIntensity = $db->query($checkGivenPlayerIntensity);

    #Sum Intensity
    $sum = $givenPlayerIntensity['SUM(clbstatusd)'];

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

function spawnFireseed($pls, $playerID = 0, $intensity = 0){
    global $log, $db, $tablepre;

    //Create 1 真实的火种 at given $pls location.
/*     $db->query("INSERT INTO {$tablepre}players 
    (type, name, icon, hp, mhp, sp, msp, att, def, pls, lvl, money, 
    wep, wepk, wepe, weps, wepsk,
    arb, arbk, arbe, arbs, arbsk,
    arh, arhk, arhe, arhs, arhsk,
    ara, arak, arae, aras, arask, 
    arf, arfk, arfe, arfs, arfsk,
    art, artk, arte, arts, artsk,
    clbstatusc, clbstatusd) 
    VALUES 
    (92, '✦真实的火种', '30', '17', '17', '23', '23', '27', '99999', $pls, '13', '250',
    '✧真实之础','WF','1','50','',
    '◆篝火','DB','1','1','a',
    '◆埋火','DH','1','1','B',
    '◆残火','DA','1','1','b',
    '◆永火','DF','1','1','M',
    '◆焰火','A','1','1','H',
    $playerID, $intensity)"); */
    addnpc(92,10,1,0,Array('clbstatusc' => $playerID, 'clbstatusd' => $intensity,));

    //DEBUG
    $log .= "TRUE FIRESEED ADD SUCCESSFUL.<br>";
    //Assign Dummy Team, 
//    if ($playerID != 0){
//
 //   }
    //DUMMY, Reserved for possible club skill.
}

function moveFireseed($fireseedID, $pls){
    global $log, $db, $tablepre;
    //Process moving Fireseed NPC
    #Get Fireseed NPC to Move
    $checkMovableFireseed = "SELECT FROM {$tablepre}players WHERE type = 92 AND pid = $fireseedID";
    $movableFireseed = $db->query($checkMovableFireseed);

    $movableFireseed['pls'] = $pls;
    //DEBUG
    $log .= "FIRESEED $fireseedID MOVED TO PLACE $pls .<br>";
}
?>