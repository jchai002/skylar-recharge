<?php
require_once(__DIR__.'/../includes/config.php');

$subscription_ids = array_unique([
51516908,
51237289,
49841236,
51252805,
49956040,
49459269,
49164149,
51737113,
49165114,
51268738,
51696610,
49124124,
51533698,
51207442,
50821426,
50146152,
50985399,
49489594,
51562147,
49102260,
50013175,
51201615,
51081714,
50974167,
49728501,
50761870,
50584101,
51029930,
49372468,
51565091,
50688759,
50572517,
51443853,
50407145,
49347543,
51516242,
49175108,
50381137,
49376301,
50147081,
51055147,
50144061,
49712128,
49723848,
50465598,
51503879,
51537236,
51539112,
50440636,
50528017,
51547598,
51319138,
50914081,
49775445,
51490576,
51746186,
49169266,
50427655,
51526026,
51121478,
49334611,
51547038,
51516578,
49624576,
51060950,
50218253,
50592483,
51412561,
51774268,
49433413,
51384102,
50379671,
49092186,
49154306,
51158434,
50564944,
49311969,
49560843,
51051628,
49778386,
51548975,
51058799,
51268278,
51627497,
50689666,
49823854,
49958148,
50465151,
49706659,
50837952,
51721673,
51407704,
51516433,
50143354,
49965734,
51182914,
50402831,
49958567,
51527818,
50522613,
49303068,
51187066,
49957369,
51580261,
50591803,
51119604,
50482071,
51749832,
49489654,
50472991,
51019191,
49485401,
49319611,
50505448,
51372594,
51468676,
50167347,
49638445,
50969189,
50990831,
50911701,
51373596,
51106932,
51621469,
49637771,
51204450,
51139585,
50189258,
50947876,
49850700,
50392182,
51091481,
50145248,
51009692,
50784249,
51613267,
51431792,
49312515,
51074851,
51224117,
51743304,
51727643,
51083270,
51175395,
49830390,
51634285,
49396302,
51101285,
51532693,
49398210,
49491264,
50329432,
50814062,
50841183,
51111099,
51485541,
50479916,
51090047,
51094988,
49351345,
51053048,
50464328,
49567085,
51345369,
50356804,
49909792,
50355903,
50145920,
50667081,
50641289,
51500384,
51577477,
49161983,
51229945,
50829441,
49541619,
51117842,
51404097,
51037656,
49706910,
51517570,
49939204,
51111655,
51445432,
51093780,
50300424,
50148538,
49848422,
51159414,
51008384,
51435512,
49108400,
50054734,
49389085,
50309919,
51017499,
51552407,
49483806,
49440132,
50365834,
49154397,
50259952,
51547352,
49440202,
50249386,
51491461,
50545905,
49947566,
49905627,
50951315,
51131792,
51432630,
51149067,
50763985,
51704562,
49450454,
51577318,
49735267,
51365658,
49150673,
51127220,
49257158,
49714398,
51131087,
51153344,
49851002,
50141556,
51056875,
49156459,
51250350,
51362268,
51514996,
50338264,
49125517,
51095391,
49686243,
49135614,
51202745,
51311531,
50362735,
50141747,
50622193,
50473269,
51649667,
51329658,
50690845,
51642184,
49461252,
51515838,
50444008,
51271619,
49201283,
51122167,
51508131,
51541158,
50239661,
50488518,
51043701,
51315706,
50883470,
49153006,
51411242,
51132411,
50924162,
51459935,
50283409,
49439107,
50473129,
49416867,
49327938,
50375030,
50671118,
51436711,
49492425,
49834925,
51062050,
51083514,
49259805,
51649565,
51603163,
51466338,
50272740,
51268979,
51654082,
51471770,
51636977,
50867797,
50670408,
50145452,
51680731,
51205001,
51579412,
49150906,
51125164,
51571823,
49157079,
50211052,
51302052,
50832650,
51773669,
49159181,
51172634,
51548915,
50386630,
49272514,
49348165,
50974083,
50249614,
49723449,
49271141,
51480809,
51749559,
51252265,
50778312,
50766973,
50557179,
49092859,
49100856,
51737077,
51542660,
50801726,
49159141,
50032585,
50067093,
51480626,
51240202,
49745695,
51519536,
51122458,
51784919,
49159906,
51193220,
50777718,
51537462,
50372541,
50192409,
50545649,
51011032,
50277458,
50245104,
50897493,
51337898,
50688732,
51150684,
51014094,
50150578,
51527294,
50680767,
51535511,
51430974,
51107433,
50752099,
49284005,
49087520,
50010360,
49711158,
51218108,
51374905,
51369783,
51074884,
50972568,
50866251,
50755167,
51351753,
50523272,
50641797,
50478113,
50143707,
49150549,
50716918,
51389190,
51333967,
51753075,
49186216,
51439884,
50566601,
49086463,
49243783,
51052934,
49980844,
49950695,
50733282,
50184022,
51051100,
49970259,
50250368,
51088469,
50262422,
51219403,
51802900,
51041538,
51625682,
49735199,
51090112,
50512271,
51783045,
51412348,
50689520,
51540110,
51709833,
50868012,
51306232,
51198732,
49236923,
49340642,
51128137,
51664871,
49740648,
51081716,
51798196,
49168162,
49951943,
49352149,
49096737,
49375107,
49182099,
50446984,
50447812,
51129551,
51503803,
50853486,
51106681,
51302638,
51399789,
51360238,
51380863,
50152142,
49126963,
50141471,
49299087,
50477572,
51541726,
49978627,
50769261,
49164060,
49484486,
50157094,
50873577,
50991929,
51756914,
51092319,
49785449,
51773332,
51733152,
51473693,
49385561,
51196054,
49485316,
51355651,
51747719,
49606025,
50943891,
50052311,
49208015,
49259778,
51695011,
51361656,
51303915,
49831191,
51106225,
49340041,
49382388,
51030725,
51181360,
51711821,
50167093,
49694490,
50939169,
51157660,
51098511,
51027341,
49173746,
51036801,
49780355,
50730509,
51082841,
51278931,
51022582,
50808269,
49152712,
51666891,
49098399,
51019042,
51749895,
51717164,
51696459,
50469574,
49837302,
51640234,
50002211,
49148380,
50701477,
49891807,
49899221,
50683899,
50747670,
50494139,
50727418,
50215827,
51036797,
51544720,
51317626,
50112391,
49190353,
51083155,
49161737,
51034845,
50452866,
49152662,
50881438,
51362775,
49741683,
51274332,
51338046,
49921492,
50620488,
51802186,
51542772,
49185102,
51003653,
51027159,
50213396,
49657419,
51318706,
49151293,
51750391,
50141285,
51304437,
51312690,
51087297,
51237924,
51042192,
49980029,
51028309,
51637291,
50144398,
50198207,
51007867,
50874043,
51169075,
50851767,
49412541,
49916652,
49229047,
49152071,
51707472,
49449163,
50501710,
50206971,
50634794,
51698271,
50936383,
51590900,
51317575,
49102706,
51524604,
50442839,
51027518,
49275230,
49152340,
50259592,
50327696,
50247131,
50182191,
51155277,
51740833,
51091671,
49741414,
51616904,
51298037,
51198560,
49646909,
50501486,
50868007,
51531114,
51098531,
49789047,
51356286,
51752020,
51002417,
51575661,
50740257,
51755362,
51691410,
50431699,
51084884,
50390115,
50464423,
49751431,
49147152,
51712585,
51433124,
50982109,
50893889,
49470051,
51047840,
51544594,
51450051,
50637983,
49768912,
49781083,
50433616,
51434092,
51288221,
51057047,
51249074,
50433932,
51132896,
51565791,
50677888,
50896268,
50145977,
50825847,
51143093,
50696014,
50334159,
51587530,
51434608,
51058800,
49744943,
50759761,
51434313,
50334531,
49648163,
50010057,
50068665,
51208201,
49275507,
51672205,
49186268,
51110544,
49887029,
50155448,
51572638,
51234503,
49838079,
51303762,
50187076,
49121576,
49094244,
51705072,
51234027,
51420157,
49337795,
50432363,
51024045,
49592830,
50274908,
49161445,
49678662,
49398782,
49842306,
49351832,
49562651,
51085898,
50651348,
49730089,
50640455,
51255408,
49539670,
50400540,
51039924,
51050461,
51747186,
51463159,
49093032,
50357759,
51206622,
49868677,
49436614,
50665110,
50794675,
49325163,
50433650,
49280493,
50909445,
49533725,
49370137,
51780920,
49167937,
51539407,
49489722,
50151023,
51024809,
51003391,
50142135,
49757462,
50146719,
50757454,
50784282,
51126861,
49469477,
51628205,
51126707,
51064417,
49710796,
51027158,
51101257,
49809496,
49211915,
50548990,
50354249,
50254790,
50804823,
49872112,
49235476,
49747560,
51443908,
51747403,
50881835,
50162524,
50712705,
51769225,
49446574,
51786410,
50406459,
50730775,
49155847,
51074376,
50518188,
49874194,
50261771,
49762757,
51577328,
49401174,
51102247,
51758546,
51308464,
49638455,
50435534,
49341471,
50522995,
51104983,
49155813,
50386168,
50763487,
50751982,
51095961,
51150112,
50153459,
49286842,
49511462,
49245829,
50186222,
49396829,
49710181,
51101704,
51256872,
50825713,
50446995,
49151023,
51751197,
50732169,
49407514,
51250286,
51194843,
49097784,
51146299,
51253429,
51519398,
51758400,
50754699,
49115355,
51184749,
50146382,
50970112,
51091545,
50141542,
51100346,
51087462,
49151504,
49507171,
50982603,
51465634,
49112776,
50822345,
51316427,
51343361,
50859244,
51342611,
51087133,
51648580,
50149673,
51082442,
49210200,
49183883,
49876779,
49507663,
49154297,
51091643,
51537196,
51336157,
49631178,
51533428,
49871966,
50959490,
49829879,
49732831,
49949664,
50162895,
51666839,
51093736,
50227705,
50881989,
49820953,
49170153,
50989350,
50973350,
49577668,
51306049,
49751008,
51515671,
50201582,
50352757,
50989548,
51304958,
50440156,
51589005,
49177790,
49229712,
49820022,
51086143,
51116774,
50956196,
51297328,
51036836,
50766179,
50480147,
51161678,
50806143,
51383624,
50154818,
49681942,
50729525,
50174642,
49243293,
50949459,
50843250,
51577498,
51432726,
51115296,
51579024,
51016106,
50637217,
51779566,
49175684,
50955038,
51695711,
49234674,
51523864,
51514499,
50891618,
51516197,
49178383,
50405925,
50457677,
51160439,
49704089,
49188841,
51517500,
50142439,
51504539,
51083803,
51034657,
51020797,
49460968,
51726629,
51271913,
50969749,
51501280,
50345457,
51108418,
50765083,
49588976,
51421193,
50446611,
50507732,
49388386,
51542693,
50365816,
50309211,
50286512,
51160586,
50900549,
50142168,
51114176,
50371886,
51502133,
49448161,
50895165,
51006097,
51547315,
51450066,
51209705,
51493435,
49210908,
51006560,
50680076,
51376808,
49677280,
50048907,
50876082,
51374913,
50673939,
50355819,
51720314,
51569148,
51600214,
51552753,
49471437,
51457979,
51245635,
51561806,
50061032,
51508997,
51083136,
49220834,
50591024,
49170029,
51662200,
51307658,
49160270,
49177886,
50141768,
51086582,
49481273,
51693885,
49262268,
49422018,
49834539,
50778047,
49773490,
49262050,
49161678,
50363264,
49377140,
49214487,
51039181,
51532199,
49468602,
50142956,
51106612,
50214671,
50912476,
50972152,
51253331,
51178657,
50307901,
50213793,
50498507,
51073361,
50452687,
49878636,
50867791,
50551619,
49362923,
50165097,
51594319,
51143643,
50845006,
50914997,
49214121,
51091649,
51725199,
50642631,
51255453,
50142321,
49154540,
49420498,
51037404,
50229991,
50143223,
49387270,
51042705,
51243919,
51365694,
51522567,
50280740,
50620613,
49559053,
51315573,
50912481,
49093158,
49429401,
50639159,
51099515,
50489076,
51104253,
49264563,
51296753,
51600746,
50437973,
50489853,
51540419,
50927122,
49236903,
51084399,
49951832,
51335643,
49769774,
51090554,
50981382,
50845073,
50956762,
50968234,
51519439,
50536455,
51287101,
49760785,
51243985,
50756376,
49182084,
51147366,
51576521,
49153865,
51105093,
49185720,
51157630,
51566047,
50853469,
50403540,
51072392,
49474169,
51012007,
51172580,
50189193,
51444062,
50961007,
49098865,
49272141,
50524916,
51537846,
49521147,
51076626,
50953474,
51082892,
49158672,
50782421,
42154381,
51009753,
51512156,
50384874,
51759047,
51152980,
50938154,
51252119,
49154365,
50927850,
49855373,
49209067,
49832247,
50672058,
50701592,
50699893,
51756981,
49288757,
51135012,
51145726,
49165514,
49151905,
50972264,
51244471,
49233972,
50141970,
49259461,
51372551,
50520129,
50780680,
51722758,
49157009,
49593311,
50055509,
51557768,
50355697,
50558393,
49646891,
49237274,
50856752,
49302818,
50136413,
51775638,
50583161,
50671833,
50279714,
51431248,
50703323,
51775324,
50483579,
51087325,
50978650,
50100077,
49854309,
50222818,
50599474,
51609344,
49330058,
49840551,
51229356,
49682997,
50909115,
49209079,
51557047,
51237741,
50371290,
49728752,
49589972,
50844595,
49227492,
49243924,
51148837,
50734062,
49799631,
49339005,
49425616,
51592928,
49154253,
51383628,
50158733,
51721196,
49675697,
50459430,
50142292,
51170580,
51104079,
49428753,
51182788,
51588394,
51180621,
50138955,
49280565,
51001725,
51206474,
49269198,
51587697,
51510678,
50677062,
51096649,
51293607,
51088384,
50707223,
49272385,
51277406,
50142115,
51435372,
49221038,
50156391,
49116178,
50963262,
49182218,
50612033,
51601582,
50487458,
50872877,
51309644,
50555639,
50548903,
49799083,
51436521,
50936018,
51594453,
50759993,
50155557,
49093432,
49092275,
50742246,
51369139,
49770735,
50917134,
51317551,
49301101,
51209639,
50634037,
50970690,
50591479,
50701103,
50350767,
50973676,
50720140,
51516006,
49456247,
49677076,
51793681,
51077881,
51082146,
51200406,
49695515,
49189317,
51101437,
49174549,
50602686,
50783786,
51640445,
51264419,
51506205,
49164179,
51230965,
51761830,
51084458,
49312355,
50174484,
50761065,
51078668,
50213154,
51087621,
49693131,
51038163,
51164247,
49445764,
51356821,
49763087,
51546113,
49160957,
49755545,
49093332,
49747094,
49731318,
49360549,
51191891,
51327057,
51114880,
50954670,
49362270,
50976350,
49660038,
50351264,
50160235,
51533967,
51746090,
49793844,
51512238,
51006281,
49242650,
50752470,
51749752,
51522612,
50289758,
49354255,
50707040,
51466641,
50386157,
50333728,
50833769,
50939338,
50797001,
51030445,
49725755,
51007295,
50971972,
49175857,
49958065,
49397743,
51078531,
50906945,
49632014,
51759543,
51116145,
50768795,
49588235,
51090288,
50359939,
50878083,
51533347,
51773336,
51430659,
51695373,
50705263,
51568198,
51515502,
51723079,
49094329,
51174209,
50667851,
51333779,
51439115,
50964803,
50705858,
50283627,
50685949,
51535001,
50672533,
50603940,
49259798,
49165483,
50601829,
49427393,
49285073,
49151339,
50357956,
51102944,
51025948,
49739418,
50150377,
50377175,
50163996,
50037212,
49125552,
51106845,
49712785,
49384305,
51526289,
49269877,
49432827,
50849072,
50932830,
51062851,
51054564,
51759229,
51356460,
51529234,
50934144,
50841351,
50979370,
51089250,
49411589,
50564243,
50443027,
51123673,
50979593,
49139818,
50236985,
50941620,
49174659,
51460780,
49162623,
49268424,
51751092,
49149680,
51081740,
49179860,
50983223,
50886080,
51099595,
49093811,
49823759,
50756912,
51376942,
50145369,
50866291,
50253585,
50099254,
50608328,
49168236,
50561746,
50171753,
51087605,
50667814,
50876223,
51007112,
51664790,
50161556,
51473903,
49716066,
50762584,
51447195,
50979556,
50150102,
51669051,
51343079,
50679738,
50181858,
50716907,
51744111,
51531478,
50148227,
50625934,
50558822,
50735275,
49950827,
51322985,
51646206,
50759771,
50744660,
49491256,
49170218,
50171713,
49209875,
49877412,
49293374,
50447058,
50763790,
49949226,
50981758,
50375648,
49462541,
51535261,
50603880,
50850044,
51002496,
51756729,
50765786,
51434940,
49714404,
49376454,
51298163,
49175266,
50861944,
50820862,
49881523,
49300404,
50667395,
51111317,
49161236,
51162869,
51746144,
50000278,
50977958,
49865622,
51733379,
50185574,
49457124,
50742071,
50386286,
49828383,
50308109,
50402873,
50201429,
49449208,
50485400,
50432516,
51176949,
50230970,
50551518,
51573751,
49752885,
50158896,
49520506,
49744684,
49330739,
50920998,
50732094,
50847637,
51256213,
50367856,
49200082,
51286023,
50157467,
51008058,
51784057,
50402323,
49862969,
50832177,
51153108,
51532982,
50154321,
49152678,
51522802,
51320215,
50664215,
50431300,
50434019,
50970578,
51090021,
50934799,
51527489,
51525736,
49718438,
51459400,
51784857,
49207785,
50186993,
50842198,
51129876,
51696044,
51334423,
50044684,
49209274,
51104515,
49866048,
49264793,
50985340,
49829897,
51538781,
49498522,
50247026,
49212333,
49111473,
49297037,
51357155,
50947162,
51463956,
49383391,
50824796,
51038464,
50996625,
49522487,
51585593,
51243577,
50738628,
49317231,
50718585,
51649021,
51605306,
51400201,
51516106,
51132472,
49173411,
51231046,
51133679,
51515745,
50143046,
49563526,
50367385,
49319855,
51388268,
50932196,
49497725,
49151742,
50968216,
49152457,
51520989,
49152522,
50968897,
51554918,
51081487,
51316364,
50225085,
49317651,
51008475,
50737810,
50291131,
51804646,
51516838,
51628960,
50172475,
50147796,
49193546,
51083414,
50802223,
50628894,
51098043,
49792449,
51402916,
50686855,
49104277,
50918932,
49413624,
49310061,
51018662,
51432629,
51553702,
49962600,
50844316,
50750186,
51420911,
51353405,
49493528,
49749106,
51090255,
49282672,
49623917,
50958789,
51159130,
50839413,
51158540,
49965050,
51086725,
49158478,
51320838,
51103888,
50449876,
49964198,
50411146,
51746624,
51575000,
50918818,
50980635,
49164735,
51267363,
50149993,
50959519,
51083038,
50535000,
51749999,
50383923,
51713695,
49811359,
51563076,
49182943,
51413657,
51086513,
50494622,
50774021,
51051971,
51406040,
49235482,
51609183,
49799435,
49219938,
49299781,
50152893,
51456993,
51042688,
51756259,
49846540,
51752729,
51520431,
49758135,
49624661,
50309493,
49515320,
50543304,
51237600,
51769912,
50972243,
51375539,
51335886,
51480119,
50172362,
51094619,
50331624,
49810210,
51746343,
50720217,
51404377,
50546628,
49482153,
50162210,
51085882,
51622940,
51715656,
51032285,
50560206,
49905761,
51107104,
51309855,
51734629,
49208743,
51023385,
49810023,
50733961,
50361315,
50315729,
49303113,
50897743,
49167767,
51124262,
51055892,
50200482,
51140624,
49928998,
51248050,
51328616,
51647919,
49209109,
50440077,
49690165,
51087146,
50141816,
51709037,
50528142,
51475319,
50521626,
51082370,
51600795,
49863167,
50143330,
51148052,
49471280,
50010192,
50764074,
51184646,
49091208,
51521828,
49323408,
51614837,
50610415,
49162306,
49873378,
51565478,
51689121,
50190952,
49502953,
49215846,
51309780,
49688323,
50913378,
51070665,
49437945,
51432021,
50785334,
50654057,
51224281,
49844308,
50692681,
50778545,
50669080,
51759486,
51055020,
51246291,
50304287,
51669047,
49985873,
51520740,
51006938,
50870705,
49425333,
51175962,
51783142,
50979731,
49472608,
51107009,
51240455,
51296344,
49306792,
49429320,
49705417,
50993425,
51294643,
51088440,
51251984,
49248835,
49374448,
51018517,
51556959,
50834791,
50755474,
49095365,
49251224,
49430295,
51058044,
50448412,
49198892,
50965627,
50990635,
51088633,
51323580,
49219014,
51017933,
49565090,
51370609,
51621964,
49191764,
51576331,
50545821,
49093687,
51318465,
51077327,
50559242,
51056913,
50702046,
51551623,
49181231,
50346126,
50168244,
50153312,
49761830,
50458068,
50802874,
49337484,
50990280,
51268658,
50990408,
50195251,
49718727,
49679576,
49342875,
51635024,
49093017,
49373320,
49951150,
51189349,
49278183,
49092613,
50275938,
49795695,
50821789,
51519397,
49214081,
51719894,
49327878,
49820579,
49672237,
51568974,
50187711,
49287658,
50542105,
50888584,
49424097,
49188508,
49347882,
51085891,
51230753,
50549604,
51019028,
51603740,
49152730,
49522935,
49787360,
50158492,
50428777,
49380793,
51775430,
50328401,
50190168,
49712889,
50841015,
50142959,
49982706,
50843588,
51526142,
49092054,
51232722,
50873808,
49243495,
51651814,
51518193,
51070587,
51084671,
49327970,
50894505,
51747047,
50248166,
49974721,
51749981,
51756939,
51127796,
51703774,
50183331,
49567667,
51663940,
49151362,
51037554,
51531055,
51520504,
50436804,
51747359,
50355858,
50956605,
51779995,
50889558,
51129384,
51492289,
49181604,
51078702,
49151826,
51727924,
51119319,
51145254,
50991384,
51286996,
50164442,
51713513,
50144505,
51780774,
49541742,
50990510,
50237738,
51303772,
51195056,
51090542,
51612056,
50470220,
50483761,
49091544,
49883807,
49766165,
51222952,
49738479,
51431777,
51757559,
51584151,
51603134,
50496959,
51737483,
50934723,
50905194,
49164161,
51255907,
51557277,
49164178,
51677782,
51620881,
49394146,
49777234,
49098759,
50645003,
51174069,
49458499,
50325293,
49095040,
49372127,
50375458,
50370388,
50693103,
51050192,
49161121,
49296891,
51040779,
50434227,
49521511,
50535668,
49645072,
50374550,
49871368,
50949014,
49210309,
49556557,
49153991,
51537835,
50485971,
51757189,
51333099,
51082632,
49154665,
50282421,
49787397,
51748747,
51247319,
51183776,
49154069,
50311543,
51088925,
50149668,
51059594,
50718697,
49242396,
49940021,
51715885,
51519418,
51648650,
49770208,
49650905,
49110158,
50011403,
51112850,
49165288,
51726919,
49271054,
49182518,
50612339,
50914468,
51177903,
51475876,
49790749,
49880441,
51316330,
50806738,
49184212,
49294114,
49213345,
50511014,
51747952,
51517514,
50541324,
50823768,
51720689,
50012613,
49374156,
50197451,
50940210,
51002546,
49411000,
51191930,
51227969,
51259684,
50228173,
51020412,
49575966,
51082901,
51084811,
51018140,
49208580,
51721239,
51382522,
50715675,
51656106,
50375780,
51697354,
49127207,
51203333,
49761775,
51265810,
50975084,
51091528,
50216138,
49520950,
49359604,
50372275,
49390210,
49423849,
49655630,
51195997,
49182111,
51016338,
51090177,
50906711,
49377894,
51463003,
51755571,
50728298,
51243235,
51740800,
51260611,
49103438,
49109309,
50471371,
51749621,
51126935,
51163698,
51086793,
49261660,
50440620,
51418885,
49718267,
51106493,
51236994,
49480425,
50564714,
51521942,
51088838,
50431890,
51577629,
51250785,
50974822,
50700039,
51518699,
51434016,
51474414,
51140113,
49179857,
50979779,
49433937,
50942691,
49193997,
50781535,
51472195,
51585081,
50961613,
49371803,
49122562,
51543705,
51417462,
51295575,
50141777,
50408371,
50645352,
49167797,
51346821,
51168020,
51632792,
49503215,
51296910,
51782357,
50178675,
51681050,
50950376,
49842542,
49870986,
51200465,
51287298,
51765037,
51295877,
51078320,
51408745,
50836032,
51582621,
50610670,
51156564,
51231438,
50453884,
49357442,
51374724,
49717118,
49155773,
51366467,
49738449,
50156680,
51037410,
50355136,
51747710,
51555929,
50764560,
50056055,
49716593,
50808878,
49698232,
50152910,
50159069,
50352128,
51130295,
51519517,
51545136,
51122393,
50154023,
50746707,
49300170,
49462000,
49217670,
51189422,
51063345,
49382853,
50978268,
50496592,
51250425,
49163106,
49970705,
50961045,
51022605,
51650368,
49588080,
50565003,
51264829,
51304645,
51588979,
49739393,
51140892,
50366968,
49199369,
49158801,
51310926,
50146156,
49930827,
51083644,
51083625,
50674302,
51593972,
51122157,
49092633,
49971962,
51210555,
51369760,
50166816,
51230567,
49476528,
49137163,
49742477,
51466346,
51747364,
51268380,
49403592,
51692408,
50142703,
50028024,
49157655,
49382012,
51751825,
49285251,
51806190,
50486618,
49707153,
50142031,
49824475,
51126905,
49833253,
49230546,
51197162,
49429530,
51321996,
49418273,
50191007,
51169489,
50161232,
49918569,
50642455,
50347226,
50679995,
51095413,
49944209,
51101842,
51252000,
51227530,
50555097,
51061199,
50884471,
50299232,
50806094,
51230630,
50869229,
50142054,
51749887,
49451964,
51257471,
50329310,
49969845,
50555392,
51586905,
51533076,
49628113,
50714076,
51061360,
49756882,
50937954,
50835064,
49207666,
49876961,
51166743,
50983583,
51159181,
50008999,
50638086,
49268373,
49915443,
49443695,
50144297,
50382359,
51783437,
51097485,
50605277,
49840984,
51573663,
51702081,
51671652,
50396741,
51125252,
51356319,
49401026,
50658623,
51147699,
49376791,
51272103,
50516230,
51325202,
50908433,
50765151,
49179970,
51746333,
49306950,
51651963,
50200065,
49350358,
51082734,
50465795,
49768924,
51062553,
51571789,
50164564,
50525268,
51212540,
51292850,
50548499,
51027384,
49423801,
51152885,
51261974,
50927207,
51182264,
51088523,
51540460,
49153092,
50142259,
50352225,
49710391,
51311010,
50959759,
51092384,
50152981,
50131027,
51085849,
49978519,
51744896,
49727968,
51355403,
51526422,
50527464,
49156393,
49740311,
50709422,
49189921,
50354685,
49156429,
51518110,
50142082,
51761177,
49247040,
51223009,
51523793,
50640110,
50151406,
49527157,
51630105,
49178893,
49163281,
49348154,
50815260,
50302064,
50248361,
49969104,
49369406,
51656363,
49797252,
51748133,
50625204,
49632607,
49722876,
51643082,
50967050,
51679956,
49290668,
51473941,
50188458,
49783192,
50938202,
49161750,
51436258,
51129493,
51137943,
49754019,
50999251,
51410416,
49774619,
49739820,
50971618,
51430411,
49826291,
51794853,
49720521,
51213616,
49298015,
49338965,
50294508,
50869615,
51605011,
51728313,
51518718,
49154795,
51375987,
51091655,
49673127,
49153897,
51516870,
49419023,
49706099,
51297357,
49133214,
49157637,
49153139,
50817830,
51254631,
50144825,
50717011,
49514566,
51522698,
49351374,
49428333,
50876678,
51747871,
50995794,
51412651,
49357054,
51288484,
51485784,
49150778,
51597618,
51771719,
49158527,
49159714,
50394430,
49157240,
49903375,
51124623,
50154416,
50727726,
51244997,
50307716,
49450928,
51803265,
50487620,
49329155,
51128640,
50175323,
50568671,
49940982,
50323645,
51202141,
49715805,
51143917,
49151214,
51520494,
51445480,
51166901,
50825792,
50631036,
51161261,
50737488,
51082847,
51085715,
50737461,
50625573,
51194062,
51116018,
50870315,
50443133,
51392336,
49158640,
51224772,
51073229,
49279728,
51308187,
49211972,
51270557,
49282048,
50973592,
51341663,
50161396,
49340681,
49508403,
50432004,
49713419,
49375050,
50739428,
51569075,
49423412,
51118208,
51587403,
51132789,
51017528,
49552279,
49376362,
51483485,
51179028,
49763959,
49153039,
49370220,
49121487,
49508243,
51697893,
49220960,
51295446,
51371284,
49101728,
49794097,
49756013,
49183545,
50849162,
50936022,
50476607,
51082668,
51688132,
51609037,
51637316,
51304033,
50166871,
51606250,
50917469,
51316881,
49394134,
51406581,
50146119,
51516538,
51332031,
50692962,
51326675,
51385598,
51518564,
49530866,
50439502,
50025039,
50457609,
49654371,
50975010,
50531717,
50985817,
50829091,
50015590,
51780885,
51753393,
51355647,
50657634,
49173910,
49283990,
50579475,
51192463,
51088584,
50899871,
50673897,
51197695,
50496507,
51788379,
49160002,
50178755,
50538203,
50265853,
49523205,
50989342,
51169106,
50684345,
51589093,
49160750,
51310019,
49157244,
51278044,
51053545,
49092461,
51093249,
50555486,
50633468,
51051655,
49204263,
49095039,
50878114,
49799134,
51246015,
51706694,
50345347,
51241526,
51802799,
50836173,
50435003,
51108372,
51806775,
50740107,
50975402,
50196648,
51765675,
51056861,
51160444,
49972572,
49114332,
50146017,
51703198,
50291336,
51253888,
51375239,
50974936,
51180820,
50962215,
49897830,
51258220,
51485915,
50156563,
50384022,
51082846,
49094385,
51768027,
50989316,
50157934,
51164108,
49807861,
49721138,
51325430,
51145478,
51312718,
50689596,
50939516,
49308879,
49155709,
50354650,
51543612,
51307675,
50698473,
51165252,
51521034,
51801654,
51093398,
51178629,
50141694,
50991183,
51087193,
50255701,
50164920,
51365902,
51083269,
51106531,
51082675,
50558343,
50146400,
51679981,
51733969,
51053678,
51082480,
51613633,
49510636,
50511239,
50662945,
51756088,
51018326,
51325147,
50217594,
51236547,
50600662,
51688241,
49720983,
51511648,
50371459,
50567449,
51404348,
51258322,
49153183,
51563937,
49093291,
50896171,
50744213,
49098223,
49265350,
50014583,
49822240,
50161963,
51427465,
49635092,
51087223,
49158595,
49167701,
49561040,
50971849,
51493267,
49907846,
49484600,
51085794,
49729523,
51096037,
51063733,
51177225,
51740363,
49823302,
50396825,
51083718,
50679519,
49178873,
49712200,
50380495,
51755850,
50532765,
51531421,
51258138,
51525680,
49447600,
50142714,
51549674,
49154822,
50662663,
51734661,
49400995,
51629885,
50524735,
51294810,
49493271,
51743982,
51025218,
51536007,
51187115,
49570096,
49296789,
49903781,
50638861,
50964534,
51539315,
51535687,
50533537,
51756756,
51657506,
50805514,
51319756,
49338542,
51576351,
50844667,
49527323,
51093707,
51083604,
51123560,
51566067,
50967931,
50608192,
49685027,
51729339,
51357324,
51520670,
50899632,
49264278,
50474980,
50905215,
51117467,
51631566,
51641797,
50978153,
50819801,
49311385,
49343244,
49379401,
50297273,
50426581,
51310937,
50800436,
49348622,
51058916,
49951224,
49192927,
51636560,
49378963,
49965171,
49154855,
51250478,
49151218,
49468738,
51050190,
51436404,
49688426,
50982242,
49133086,
50599682,
51507594,
49193106,
51478394,
49794469,
51170638,
49743222,
50805005,
51119064,
49588249,
49246468,
50737359,
51184175,
51249110,
51152850,
50883883,
50818479,
50623945,
50899990,
50187216,
51179528,
51230510,
50167703,
49421393,
50690059,
51752487,
50704655,
51197950,
50597027,
51337487,
51764479,
50564416,
51707868,
51087338,
50707221,
50979740,
50751405,
51565480,
50747993,
51802093,
51286859,
50879910,
49926714,
50528795,
51746278,
50202487,
50903257,
51099394,
49184007,
50926329,
49159905,
51088354,
49943008,
51405759,
51267908,
51059096,
50677147,
49655476,
49521354,
50143133,
50815276,
51500425,
51418688,
51086104,
50187517,
50855558,
51549414,
49926736,
51490006,
51081579,
50661726,
50191331,
50212635,
50975329,
51087025,
51073810,
51060128,
50678183,
51143800,
51638469,
49214157,
49771525,
51383610,
51518081,
50217939,
51024230,
50698920,
51076689,
51187038,
51268211,
50142502,
49659896,
50605018,
51701615,
49625762,
51532099,
49207573,
49234845,
49244080,
50186992,
51283303,
49097058,
51734934,
50491295,
51331911,
51517896,
49408460,
49207979,
51747931,
50364088,
51746827,
51574490,
50167253,
49718726,
51267498,
50197392,
49520518,
51054861,
51622408,
51197941,
51432723,
50602580,
51516015,
50619045,
50299031,
49093797,
51097403,
49203587,
49098592,
50972451,
51257994,
51319112,
50546716,
49806489,
51715535,
49212087,
50293587,
49280461,
50258444,
51005214,
50152651,
51746028,
51164359,
51535376,
49258471,
51205097,
49897943,
49215928,
49212230,
50488208,
51523057,
50129488,
51198622,
50151701,
50455341,
51081664,
51625112,
51058392,
51030800,
49780302,
49483276,
50819805,
51375770,
51521158,
50546150,
51158892,
50674472,
51649274,
51008954,
51436898,
50815359,
51172843,
50473577,
50931513,
50672025,
49508854,
51172408,
50898354,
49399943,
50038976,
49146247,
50572466,
51517546,
51134052,
49314089,
50046269,
50804264,
49815622,
50972076,
50380721,
50687853,
50946348,
51746190,
51601267,
50524226,
51147843,
49568298,
51406034,
49747674,
51567575,
50356256,
51294500,
49147258,
50624105,
51572336,
51072112,
50563353,
49163498,
49162992,
49871880,
50467181,
50418969,
51243264,
51103707,
49508936,
51684757,
50151464,
51059480,
51412675,
51787274,
49470157,
50174790,
50150083,
49311789,
51031785,
50159511,
49287718,
49958497,
50289483,
49238182,
50876253,
50747115,
50490518,
49166455,
51520910,
49811885,
49377175,
51208471,
49327894,
50435167,
51268047,
51087439,
51807023,
51746656,
51091586,
51614212,
50590084,
49522068,
49114923,
51650894,
49087614,
50315290,
51630303,
50714969,
50670437,
51327738,
49919529,
49180698,
50186062,
51516821,
51102501,
50900647,
49716543,
51095717,
51339105,
50914718,
50891398,
]);

$output = [];
$outstream = fopen("php://output", 'w');
fputcsv($outstream, ['Subscription ID', 'Sub "status"', 'Deleted']);

$stmt = $db->prepare("SELECT * FROM rc_subscriptions WHERE recharge_id=?");
foreach($subscription_ids as $subscription_id){
	$stmt->execute([$subscription_id]);
	$row = $stmt->fetch();
	$output[$subscription_id] = [
		'status' => $row['status'],
		'deleted' => is_null($row['deleted_at']) ? 'no' : 'yes',
	];
	fputcsv($outstream, [$subscription_id, $row['status'], is_null($row['deleted_at']) ? 'no' : 'yes']);
}
fclose($outstream);