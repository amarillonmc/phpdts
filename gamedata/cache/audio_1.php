<?php
if(!defined('IN_GAME')) exit('Access Denied');

# BGM相关配置文件：

# 未配置的默认播放音量（单位：百分比）
$default_volume = 20;

# 会播放BGM的事件（优先级最高——会覆盖默认曲集） 
# 具体触发时用不用这个数组都无所谓，可以直接调用： $clbpara['event_bgmbook'] = Array('指定事件曲集名');
$event_bgm = Array
(
	'test' => Array('event'),
	'wth18' => Array('wth18'),
);

# 会播放BGM的地图（优先级高——会覆盖默认曲集）
$pls_bgm = Array
(
	# 在英灵殿会播放对应曲集
	34 => Array('valhalla'),
	33 => Array('pls33'),
	0 => Array('introduction'),
);

# 会播放BGM的地图组（优先级低）
$parea_bgm = Array();

# 不需要额外条件即可播放BGM的曲集
$regular_bgm = Array('besynthed');

# 曲集内包含的BGM
$bgmbook = Array
(
	'besynthed' => Array(0,1,2,5,6,7,8,9,10,18,19,20,23,24),
	'valhalla' => Array(3,4),
	'event' => Array(11,12,13),
	'wth18' => Array(14,15),
	'tutorial' => Array(16),
	'realcrimzure' => Array(17),
	'pls33' => Array(21),
	'introduction' => Array(22),
	'notYMCA' => Array(25),
	'crimsontracks' => Array(26,27,28,29,30,31,32,33,34,35,36,37),
	'azuretracks' => Array(38,39,40,41,42,43,44),
	'altazuretracks' => Array(45,46,47,48,49,50,51),
	'lilatracks' => Array(52,53,54,55,56,57,58,59,60,61,62,63,64),
	'rimefiretracks' => Array(65,66,67,68,69,70,71,72,73,74),
	'fleurtracks' => Array(75,76,77,78,79,80,81,82,83),
	'christracks' => Array(84,85,86,87,88,89,90,91,92,93,94,95,96),
	'altchristracks' => Array(97,98,99,100,101,102,103,104,105,106,107,108,109)
);

# 所有bgm编号清单：
$bgmlist = Array
(
	0 => Array(
		'name' => 'Skaven252 - Beetle Of Man',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2017-Dec-BeetleOfMan.mp3',
		'type' => 'audio/mpeg',
	),
	1 => Array(
		'name' => 'Skaven252 - 3864 Jumps',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2018-Nov-3864Jumps.mp3',
		'type' => 'audio/mpeg',
	),
	2 => Array(
		'name' => 'Skaven252 - Path to a Lenient Dusk',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2019-Feb-PathtoaLenientDusk.mp3',
		'type' => 'audio/mpeg',
	),
	3 => Array(
		'name' => '林苍月 ~ Pale Moon Rising',
		'url' => 'https://res.brdts.online/BGM/Pale%20Moon%20Rising.ogg',
		'type' => 'audio/ogg',
	),
	4 => Array(
		'name' => 'Skaven252 - Neubruder',
		'url' => 'https://res.brdts.online/BGM/mmc-2017-jan-neubruder_mix03.mp3',
		'type' => 'audio/mpeg',
	),
	5 => Array(
		'name' => 'Skaven252 - Survivor One',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2020-jan-SurvivorOne.mp3',
		'type' => 'audio/mpeg',
	),
	6 => Array(
		'name' => 'Skaven252 - Proton Halo',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2020-Mar-ProtonHalo.mp3',
		'type' => 'audio/mpeg',
	),
	7 => Array(
		'name' => 'Skaven252 - Aqua Regia',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2021-07-AquaRegia.mp3',
		'type' => 'audio/mpeg',
	),
	8 => Array(
		'name' => 'Skaven252 - RMC Nighthawk',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2022-08-RMCNighthawk.mp3',
		'type' => 'audio/mpeg',
	),
	9 => Array(
		'name' => 'Skaven252 - Gamma Katana',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2019-Jun-GammaKatana.mp3',
		'type' => 'audio/mpeg',
	),
	10 => Array(
		'name' => 'Skaven252 - Sublime Geometries',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2022-11_SublimeGeometries.mp3',
		'type' => 'audio/mpeg',
	),
	11 => Array(
		'name' => 'Skaven252 - Oathlands',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2020-Feb-Oathlands.mp3',
		'type' => 'audio/mpeg',
	),
	12 => Array(
		'name' => 'ElectricMudkip - Tons of Good Deeds...',
		'url' => 'https://res.dts.gay/BGM/BW2_DecisiveBattle.mp3',
		'type' => 'audio/mpeg',
	),
	13 => Array(
		'name' => 'EternalSushi & ElectricMudkip - ... and One Sin',
		'url' => 'https://res.dts.gay/BGM/XY_DecisiveBattle.mp3',
		'type' => 'audio/mpeg',
	),
	14 => Array(
		'name' => 'ruha(Pastel Tone Music) - Ｆｌｕｆｆｙ×Ｄｒｅａｍｙ',
		'url' => 'https://res.dts.gay/BGM/fuwafuwa/Tr09_Fluffy_Dreamy.mp3',
		'type' => 'audio/mpeg',
	),
	15 => Array(
		'name' => 'ruha(Pastel Tone Music) - Ｐｕｒｅ Ｗｉｓｈ',
		'url' => 'https://res.dts.gay/BGM/fuwafuwa/Tr10_PureWish.mp3',
		'type' => 'audio/mpeg',
	),
	16 => Array(
		'name' => 'Skaven252 - Minimum Viable People',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2022-06-MinimumViablePeople.mp3',
		'type' => 'audio/mpeg',
	),
	17 => Array(
		'name' => '真红暮·真蓝凝 ~ Azure Swear',
		'url' => 'https://res.dts.gay/BGM/AzureSwear.mp3',
		'type' => 'audio/mpeg',
	),
	18 => Array(
		'name' => 'Skaven252 - A Defender Rises',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2019-Jan-ADefenderRises.mp3',
		'type' => 'audio/mpeg',
	),
	19 => Array(
		'name' => 'Skaven252 - Data Chase',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2023-02_DataChase.mp3',
		'type' => 'audio/mpeg',
	),
	20 => Array(
		'name' => 'Skaven252 - Spectradrome',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2022-03-Spectradrome.mp3',
		'type' => 'audio/mpeg',
	),
	21 => Array(
		'name' => 'Skaven252 - Sateensyömä',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2022-04-Sateensyoma.mp3',
		'type' => 'audio/mpeg',
	),
	22 => Array(
		'name' => 'Skaven252 - Thoughtless',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2023-01-Thoughtless.mp3',
		'type' => 'audio/mpeg',
	),
	23 => Array(
		'name' => 'Skaven252 - Loserboy',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2023-04_Loserboy.mp3',
		'type' => 'audio/mpeg',
	),
	24 => Array(
		'name' => 'Skaven252 - Life As A Hole',
		'url' => 'https://res.dts.gay/BGM/Skaven252-MMC-2023-03_LifeAsAHole.mp3',
		'type' => 'audio/mpeg',
	),
	25 => Array(
		'name' => 'Turbo - Ikouze Paradise - Eurobeat Version',
		'url' => 'https://res.dts.gay/BGM/ikouze_paradise_eurobeat.mp3',
		'type' => 'audio/mpeg',
	),
	26 => Array(
		'name' => 'Hyperreal - 万全之战',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/01%20-%20Battle%20Ready.mp3',
		'type' => 'audio/mpeg',
	),
	27 => Array(
		'name' => 'Hyperreal - 狂暴之斗',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/01%20-%20Berserker.mp3',
		'type' => 'audio/mpeg',
	),
	28 => Array(
		'name' => 'Hyperreal - 黑暗之雾',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/02%20-%20Dark%20Matter.mp3',
		'type' => 'audio/mpeg',		
	),
	29 => Array(
		'name' => 'Hyperreal - 数码之幻',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/02%20-%20Digital%20Paradise.mp3',
		'type' => 'audio/mpeg',		
	),
	30 => Array(
		'name' => 'Hyperreal - 天际之野',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/02%20-%20Event%20Horizon.mp3',
		'type' => 'audio/mpeg',		
	),
	31 => Array(
		'name' => 'Hyperreal - 日食之影',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/03%20-%20Eclipse.mp3',
		'type' => 'audio/mpeg',		
	),
	32 => Array(
		'name' => 'Hyperreal - 重力之握',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/03%20-%20Gravity.mp3',
		'type' => 'audio/mpeg',		
	),
	33 => Array(
		'name' => 'Hyperreal - 兵队之间',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/03%20-%20Lined%20Up.mp3',
		'type' => 'audio/mpeg',		
	),
	34 => Array(
		'name' => 'Hyperreal - 邪恶之言',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/04%20-%20Invocation.mp3',
		'type' => 'audio/mpeg',		
	),
	35 => Array(
		'name' => 'Hyperreal - 平行之线',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/04%20-%20Parallax.mp3',
		'type' => 'audio/mpeg',		
	),
	36 => Array(
		'name' => 'Hyperreal - 散射之光',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/05%20-%20Radiant.mp3',
		'type' => 'audio/mpeg',		
	),
	37 => Array(
		'name' => 'Hyperreal - 烈日之风',
		'url' => 'https://res.dts.gay/BGM/Tracks/crimsontracks/05%20-%20Solar%20Winds.mp3',
		'type' => 'audio/mpeg',		
	),
	38 => Array(
		'name' => 'Blue Moon feat. Wan Tang',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Blue%20Moon.mp3',
		'type' => 'audio/mpeg',		
	),
	39 => Array(
		'name' => 'Keep On Dreaming feat. Wan Tang',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/KEEP%20ON%20DREAMING.mp3',
		'type' => 'audio/mpeg',		
	),
	40 => Array(
		'name' => 'Machine Says I Love You feat. Wan Tang',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Machine%20Says%20I%20LOVE%20YOU.mp3',
		'type' => 'audio/mpeg',		
	),
	41 => Array(
		'name' => 'Metropolis in Rain feat. Wan Tang',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Metropolis%20in%20Rain.mp3',
		'type' => 'audio/mpeg',		
	),
	42 => Array(
		'name' => 'Never Fade Away feat. Wan Tang',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Never%20Fade%20Away.mp3',
		'type' => 'audio/mpeg',		
	),
	43 => Array(
		'name' => 'Saturday Carnival feat. Wan Tang',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Saturday%20Carnival.mp3',
		'type' => 'audio/mpeg',		
	),
	44 => Array(
		'name' => 'Telephone Card of Rain feat. Wan Tang',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Telecard%20of%20Rain.mp3',
		'type' => 'audio/mpeg',		
	),
	45 => Array(
		'name' => 'Blue Moon',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Blue%20Moon_inst.mp3',
		'type' => 'audio/mpeg',		
	),
	46 => Array(
		'name' => 'Keep On Dreaming',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/KEEP%20ON%20DREAMING_inst.mp3',
		'type' => 'audio/mpeg',		
	),
	47 => Array(
		'name' => 'Machine Says I Love You',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Machine%20Says%20I%20LOVE%20YOU_inst.mp3',
		'type' => 'audio/mpeg',		
	),
	48 => Array(
		'name' => 'Metropolis in Rain',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Metropolis%20in%20Rain_inst.mp3',
		'type' => 'audio/mpeg',		
	),
	49 => Array(
		'name' => 'Never Fade Away',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Never%20Fade%20Away_inst.mp3',
		'type' => 'audio/mpeg',	
	),
	50 => Array(
		'name' => 'Saturday Carnival',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Saturday%20Carnival_inst.mp3',
		'type' => 'audio/mpeg',		
	),
	51 => Array(
		'name' => 'Telephone Card of Rain',
		'url' => 'https://res.dts.gay/BGM/Tracks/azuretracks/Telecard%20of%20Rain_inst.mp3',
		'type' => 'audio/mpeg',		
	),
	52 => Array(
		'name' => 'AudioCoffee - 早安',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/FromDream.mp3',
		'type' => 'audio/mpeg',		
	),
	53 => Array(
		'name' => 'AudioCoffee - 上学路',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/ToSchool.mp3',
		'type' => 'audio/mpeg',		
	),
	54 => Array(
		'name' => 'AudioCoffee - 第一节课',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/1stClass.mp3',
		'type' => 'audio/mpeg',		
	),
	55 => Array(
		'name' => 'AudioCoffee - 第二节课',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/2ndClass.mp3',
		'type' => 'audio/mpeg',		
	),
	56 => Array(
		'name' => 'AudioCoffee - 第三节课',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/3rdClass.mp3',
		'type' => 'audio/mpeg',		
	),
	57 => Array(
		'name' => 'AudioCoffee - 第四节课',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/4thClass.mp3',
		'type' => 'audio/mpeg',		
	),
	58 => Array(
		'name' => 'AudioCoffee - 中休时间',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/NoonRest.mp3',
		'type' => 'audio/mpeg',		
	),
	59 => Array(
		'name' => 'AudioCoffee - 第五节课',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/5thClass.mp3',
		'type' => 'audio/mpeg',		
	),
	60 => Array(
		'name' => 'AudioCoffee - 第六节课',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/6thClass.mp3',
		'type' => 'audio/mpeg',		
	),
	61 => Array(
		'name' => 'AudioCoffee - 第七节课',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/7thClass.mp3',
		'type' => 'audio/mpeg',		
	),
	62 => Array(
		'name' => 'AudioCoffee - 放学路',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/FromSchool.mp3',
		'type' => 'audio/mpeg',		
	),
	63 => Array(
		'name' => 'AudioCoffee - 亲亲家人',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/WithFamily.mp3',
		'type' => 'audio/mpeg',		
	),
	64 => Array(
		'name' => 'AudioCoffee - 晚安',
		'url' => 'https://res.dts.gay/BGM/Tracks/lilatracks/ToDream.mp3',
		'type' => 'audio/mpeg',		
	),
	65 => Array(
		'name' => '荒芳樹 - Another Hunter',
		'url' => 'https://res.dts.gay/BGM/Tracks/rimefiretracks/music01_Anotherhunter.mp3',
		'type' => 'audio/mpeg',		
	),
	66 => Array(
		'name' => '荒芳樹 - Castle Infiltration',
		'url' => 'https://res.dts.gay/BGM/Tracks/rimefiretracks/music02_Castleinfiltration.mp3',
		'type' => 'audio/mpeg',		
	),
	67 => Array(
		'name' => '荒芳樹 - Flower Funeral',
		'url' => 'https://res.dts.gay/BGM/Tracks/rimefiretracks/music03_Flowerfuneral.mp3',
		'type' => 'audio/mpeg',		
	),
	68 => Array(
		'name' => '荒芳樹 - Alchemy Laboratory',
		'url' => 'https://res.dts.gay/BGM/Tracks/rimefiretracks/music04_Alchemylaboratory.mp3',
		'type' => 'audio/mpeg',		
	),
	69 => Array(
		'name' => '荒芳樹 - Feast of Evil',
		'url' => 'https://res.dts.gay/BGM/Tracks/rimefiretracks/music05_Feastofevil.mp3',
		'type' => 'audio/mpeg',		
	),
	70 => Array(
		'name' => '荒芳樹 - Blast Furnace',
		'url' => 'https://res.dts.gay/BGM/Tracks/rimefiretracks/music07_Blastfurnace.mp3',
		'type' => 'audio/mpeg',		
	),
	71 => Array(
		'name' => '荒芳樹 - Defeat of Science',
		'url' => 'https://res.dts.gay/BGM/Tracks/rimefiretracks/music08_Sciencedefeat.mp3',
		'type' => 'audio/mpeg',		
	),
	72 => Array(
		'name' => '荒芳樹 - Gear Knight',
		'url' => 'https://res.dts.gay/BGM/Tracks/rimefiretracks/music09_KnightofGear.mp3',
		'type' => 'audio/mpeg',		
	),
	73 => Array(
		'name' => '荒芳樹 - Devil Weapon',
		'url' => 'https://res.dts.gay/BGM/Tracks/rimefiretracks/music10_Devilweapon.mp3',
		'type' => 'audio/mpeg',		
	),
	74 => Array(
		'name' => '荒芳樹 - Departure',
		'url' => 'https://res.dts.gay/BGM/Tracks/rimefiretracks/music11_Departure.mp3',
		'type' => 'audio/mpeg',		
	),
	75 => Array(
		'name' => '威廉·退尔礼拜堂(Chapelle de Guillaume Tell)',
		'url' => 'https://res.dts.gay/BGM/Tracks/fleurtracks/01%20-%20%20Chapelle%20de%20Guillaume%20Tell.mp3',
		'type' => 'audio/mpeg',		
	),
	76 => Array(
		'name' => '华伦城之湖(An lac de Wallenstadt)',
		'url' => 'https://res.dts.gay/BGM/Tracks/fleurtracks/02%20-%20%20Au%20lac%20de%20Wallenstadt.mp3',
		'type' => 'audio/mpeg',		
	),
	77 => Array(
		'name' => '田园(Pastorale)',
		'url' => 'https://res.dts.gay/BGM/Tracks/fleurtracks/03%20-%20%20Pastorale.mp3',
		'type' => 'audio/mpeg',		
	),
	78 => Array(
		'name' => '泉水边(An bord d’une Source)',
		'url' => 'https://res.dts.gay/BGM/Tracks/fleurtracks/04%20-%20%20Au%20bord%20d%20une%20source.mp3',
		'type' => 'audio/mpeg',		
	),
	79 => Array(
		'name' => '狂风暴雨(Orage)',
		'url' => 'https://res.dts.gay/BGM/Tracks/fleurtracks/05%20-%20%20Orage.mp3',
		'type' => 'audio/mpeg',		
	),
	80 => Array(
		'name' => '欧伯曼之谷(Valee d’Obermann)',
		'url' => 'https://res.dts.gay/BGM/Tracks/fleurtracks/06%20-%20%20Vall%20e%20d%20Obermann.mp3',
		'type' => 'audio/mpeg',		
	),
	81 => Array(
		'name' => '牧歌(Eglogue)',
		'url' => 'https://res.dts.gay/BGM/Tracks/fleurtracks/07%20-%20%20Eglogue.mp3',
		'type' => 'audio/mpeg',		
	),
	82 => Array(
		'name' => '思乡病(Le mal du Pays)',
		'url' => 'https://res.dts.gay/BGM/Tracks/fleurtracks/08%20-%20%20Le%20mal%20du%20pays.mp3',
		'type' => 'audio/mpeg',		
	),
	83 => Array(
		'name' => '日内瓦之钟(Les cloches de Geneve)',
		'url' => 'https://res.dts.gay/BGM/Tracks/fleurtracks/09%20-%20%20Les%20cloches%20de%20Gen%20ve.mp3',
		'type' => 'audio/mpeg',		
	),
	84 => Array(
		'name' => 'Bim-Bam',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/01%20Bim-Bam.mp3',
		'type' => 'audio/mpeg',		
	),
	85 => Array(
		'name' => 'Butterfly',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/01%20Schnuffelienchen%20%20%E2%80%93%20Butterfly.mp3',
		'type' => 'audio/mpeg',		
	),
	86 => Array(
		'name' => 'Haeschenparty - Disco Version',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/03%20-%20Haeschenparty_disco.mp3',
		'type' => 'audio/mpeg',		
	),
	87 => Array(
		'name' => 'Hinned Kell',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/03%20Hinned%20Kell.mp3',
		'type' => 'audio/mpeg',		
	),
	88 => Array(
		'name' => 'Adj Egy Puszika',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/05%20Adj%20Egy%20Puszik%C3%A1t.mp3',
		'type' => 'audio/mpeg',		
	),
	89 => Array(
		'name' => 'Nincs rá szó',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/06.%20Snufi%20-%20Nincs%20r%C3%A1%20sz%C3%B3.mp3',
		'type' => 'audio/mpeg',		
	),
	90 => Array(
		'name' => 'Szélvészkent',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/08%20Sz%C3%A9lv%C3%A9szk%C3%A9nt.mp3',
		'type' => 'audio/mpeg',		
	),
	91 => Array(
		'name' => 'Barátság virág',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/08.%20Snufi%20-%20Bar%C3%A1ts%C3%A1g%20vir%C3%A1g.mp3',
		'type' => 'audio/mpeg',		
	),
	92 => Array(
		'name' => 'Je t aime tellement',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/1-Je%20t%20aime%20tellement.mp3',
		'type' => 'audio/mpeg',		
	),
	93 => Array(
		'name' => 'Ez A Dallam Hozzad Szall',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/12%20Ez%20A%20Dallam%20Hozz%C3%A1d%20Sz%C3%A1ll.mp3',
		'type' => 'audio/mpeg',		
	),
	94 => Array(
		'name' => 'La Chanson Des Bisous',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/2-%20La%20Chanson%20Des%20Bisous.mp3',
		'type' => 'audio/mpeg',		
	),
	95 => Array(
		'name' => 'Je t aime tres fort',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/3-%20Je%20t%20aime%20tres%20fort.mp3',
		'type' => 'audio/mpeg',		
	),
	96 => Array(
		'name' => 'Je veux etre chez toi',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/1/7-J%20veux%20etre%20chez%20toi.mp3',
		'type' => 'audio/mpeg',		
	),
	97 => Array(
		'name' => 'Piep Piep',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/01%20-%20Piep%20Piep.mp3',
		'type' => 'audio/mpeg',		
	),
	98 => Array(
		'name' => 'Schmetterling',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/02%20Schnuffelienchen%20-%20Schmetterling.mp3',
		'type' => 'audio/mpeg',		
	),
	99 => Array(
		'name' => 'Haeschenparty - Radio Version',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/02%20-%20Haeschenparty_radio.mp3',
		'type' => 'audio/mpeg',		
	),
	100 => Array(
		'name' => 'Nur mit Dir',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/03%20-%20Nur%20Mit%20Dir.mp3',
		'type' => 'audio/mpeg',		
	),
	101 => Array(
		'name' => 'Dumdedidldei',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/05%20-%20Dumdedidldei.mp3',
		'type' => 'audio/mpeg',		
	),
	102 => Array(
		'name' => 'Schnucki Putzi',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/06%20Schnucki%20Putzi.mp3',
		'type' => 'audio/mpeg',		
	),
	103 => Array(
		'name' => 'Bumm Bumm Bumm',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/08%20-%20Bumm%20Bumm%20Bumm.mp3',
		'type' => 'audio/mpeg',		
	),
	104 => Array(
		'name' => 'Wo Bist Du Hingegangen',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/08%20Wo%20Bist%20Du%20Hingegangen.mp3',
		'type' => 'audio/mpeg',		
	),
	105 => Array(
		'name' => 'Hab Dich Gern',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/03%20Hab%20Dich%20Gern.mp3',
		'type' => 'audio/mpeg',		
	),
	106 => Array(
		'name' => 'Ich Hab Dich Lieb',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/01%20Ich%20Hab%20Dich%20Lieb.mp3',
		'type' => 'audio/mpeg',		
	),
	107 => Array(
		'name' => 'Kuschel Song',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/02%20Kuschel%20Song.mp3',
		'type' => 'audio/mpeg',		
	),
	108 => Array(
		'name' => 'Haschenlied',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/12%20-%20Haeschenlied.mp3',
		'type' => 'audio/mpeg',		
	),
	109 => Array(
		'name' => 'Ich will zu Dir',
		'url' => 'https://res.dts.gay/BGM/Tracks/christracks/0/07%20Ich%20will%20Zu%20Dir.mp3',
		'type' => 'audio/mpeg',		
	),
	110 => Array(
		'name' => '',
		'url' => '',
		'type' => 'audio/mpeg',		
	)
);

?>
