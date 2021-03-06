<?php

function gmp_shiftl($x,$n)
{
  return(gmp_mul($x,gmp_pow(2,$n)));
}

function gmp_shiftr($x,$n)
{
  return(gmp_div($x,gmp_pow(2,$n)));
}


// Individual card masks:
define('CARD_A', 1);
define('CARD_K', 2);
define('CARD_Q', 4);
define('CARD_J', 8);
define('CARD_T', 16);
define('CARD_9', 32);
define('CARD_8', 64);
define('CARD_7', 128);
define('CARD_6', 256);
define('CARD_5', 512);
define('CARD_4', 1024);
define('CARD_3', 2048);
define('CARD_2', 4096);

// Powers of 14:
define('FT0', 1);       // 14^0
define('FT1', 14);      // 14^1
define('FT2', 196);     // 14^2
define('FT3', 2744);    // 14^3
define('FT4', 38416);   // 14^4
define('FT5', 537824);  // 14^5

// 5 card hand rankings:
function HIGH_CARD($a,$b,$c,$d,$e) { return (0 + ((($a)*FT4)+(($b)*FT3)+(($c)*FT2)+(($d)*FT1)+($e))); }
function ONE_PAIR($a,$b,$c,$d)     { return (537824 + ((($a)*FT3)+(($b)*FT2)+(($c)*FT1)+($d))); }
function TWO_PAIR($a,$b,$c)        { return (576240 + ((($a)*FT2)+(($b)*FT1)+($c))); }
function THREE_OF_A_KIND($a,$b,$c) { return (578984 + ((($a)*FT2)+(($b)*FT1)+($c))); }
function STRAIGHT($a)              { return (581728 + ($a)); }
function FLIZUSH($a,$b,$c,$d,$e)   { return (581742 + ((($a)*FT4)+(($b)*FT3)+(($c)*FT2)+(($d)*FT1)+($e))); }
function FULL_HOUSE($a,$b)         { return (1119566 + ((($a)*FT1)+($b))); }
function FOUR_OF_A_KIND($a,$b)     { return (1119762 + ((($a)*FT1)+($b))); }
function STRAIGHT_FLUSH($a)        { return (1119958 + ($a)); }
function FIVE_OF_A_KIND($a)        { return (1119972 + ($a)); }
function MAX_RANK()                { return (1119985); }

// Straight bit masks (5-high through Ace-high)
$straights = array(7681, 7936, 3968, 1984, 992, 496, 248, 124, 62, 31);

//Card Bit masks (Ace through 2)
$card = array(1,2,4,8,16,32,64,128,256,512,1024,2048,4096);
$card_text = array("0","2","3","4","5","6","7","8","9","T","J","Q","K","A");
$suit_text = array("c","d","h","s");

// Lowest 13 bit bitmask
define('LOW13', 8191);

function eval_hand($cards, $wilds)
{
    global $card;
    global $card_text;
    global $suit_text;
    global $straights;

    $suits = array();
    $suit_count = array();
    $suits_and;
    $suits_or;
    $card_count;
    $quads = array();
    $trips = array();
    $pairs = array();
    $singles = array();
    $quadsi;
    $tripsi;
    $pairsi;
    $singlesi;
    $quadsm;
    $tripsm;
    $pairsm;  
    $singlesm;
    $temp;
    $temp2;
    $flush_cards = array();
    $flush_rank;
    $straight_rank;
    $k;
    $j;
    $i;
    $p;
    $q;
    
    if($wilds >= 5)
    {
	// Five aces:
        return FIVE_OF_A_KIND(13);
    }
////////////////////////DO STUFF//////////////////////////////////////////    
    $suits[0] = $cards & LOW13;
    $suits[1] = ($cards >> 13) & LOW13;
    $suits[2] = ($cards >> 26) & LOW13;
    $suits[3] = ($cards >> 39) & LOW13; 
    
    $suits_and = $suits[0] & $suits[1] & $suits[2] & $suits[3];
    $suits_or  = $suits[0] | $suits[1] | $suits[2] | $suits[3];
    
    $card_count = 0;
    for ($k=0; $k<4; $k++)
    {
	$temp = $suits[$k];
	$suit_count[$k] = 0;
	while ($temp)
	{
	    $card_count++ ;
	    $suit_count[$k]++;
	    $temp = $temp & ($temp - 1);
	}
    }    
    
    $quadsi = 0;
    $tripsi = 0;
    $pairsi = 0;
    $singlesi = 0;
    $quadsm = $suits_and;
    $tripsm = (($suits[1] & $suits[2] & $suits[3])
	| ($suits[0] & $suits[2] & $suits[3])
	| ($suits[0] & $suits[1] & $suits[3])
	| ($suits[0] & $suits[1] & $suits[2]))
	& (~$quadsm);
    $pairsm = (($suits[0] & $suits[1]) | ($suits[0] & $suits[2]) | ($suits[0] & $suits[3])
        | ($suits[1] & $suits[2]) | ($suits[1] & $suits[3])
        | ($suits[2] & $suits[3]))
        & (~$quadsm) & (~$tripsm);
    $singlesm = $suits_or & (~$quadsm) & (~$tripsm) & (~$pairsm);
    
    $temp = 1;
    for($k = 13; $k > 0; $k--)
    {
	if($quadsm & $temp)
	{
	    $quads[$quadsi++] = $k;
	}
	else if($tripsm & $temp)
	{
	    $trips[$tripsi++] = $k;
	}
	else if($pairsm & $temp)
	{
	    $pairs[$pairsi++] = $k;
	}
	else if($singlesm & $temp)
	{
	    $singles[$singlesi++] = $k;
	}
	$temp = $temp << 1;
    }
///////////////////CHECK FOR FIVE OF A KIND///////////////////////////////    
    $k = 0;
    if($wilds == 4)
    {
	if($singlesi != 0)
	    $k = $singles[0];
	if($tripsi != 0)
	{
	    if($trips[0] > $k)
		$k = $trips[0];
	}
	if($pairsi != 0)
	{
	    if($pairs[0] > $k)
		$k = $pairs[0];
	}
	if($quadsi != 0)
	{
	    if($quads[0] > $k)
		$k = $quads[0];
	}   
	if($k == 0)
	    return FOUR_OF_A_KIND(13, 0);
    }
    else if($wilds == 3)
    {
	if($pairsi != 0)
	    $k = $pairs[0];
	if($tripsi != 0)
	{
	    if($trips[0] > $k)
		$k = $trips[0];
	}
	if($quadsi != 0)
	{
	    if($quads[0] > $k)
		$k = $quads[0];
	}   
    }
    else if($wilds == 2)
    {
	if($tripsi != 0)
	    $k = $trips[0];
	if($quadsi != 0)
	{
	    if($quads[0] > $k)
		$k = $quads[0];
	}
    }
    else if($wilds == 1)
    {
	if($quadsi != 0)
	    $k = $quads[0];
    }
    if($k > 0)
	return FIVE_OF_A_KIND($k);
///////////////CHECK FOR STRAIGHT FLUSH//////////////////////////////////
    for($i = 13; $i >= 4; $i--)
    {
	for($j=0; $j < 4; $j++)
	{
	    $temp = $suits[$j] & $straights[$i-4];
	    $k = 0;
	    while ($temp)
	    {
		$k++ ;
		$temp = $temp & ($temp - 1) ;
	    }
	    if(($k + $wilds) >= 5)
	    {
		return STRAIGHT_FLUSH($i);
	    }
	}
    }
    
//////////////////CHECK FOR FOUR OF A KIND////////////////////////////////
    $k = 0;
    $i = 0;
    if($wilds == 3)
    {
	if($singlesi != 0)
	{
	    $k = $singles[0];
	    if($singlesi > 1)
		$i = $singles[1];
	}
	else
	{
	    return THREE_OF_A_KIND(13,0,0);
	}
    }
    else if($wilds == 2)
    {
	if($pairsi != 0)
	{
	    $k = $pairs[0];
	    if($pairsi > 1)
		$i = $pairs[1];
	    if($singlesi != 0)
	    {
		if($singles[0] > $i)
		    $i = $singles[0];
	    }
	}
    }
    else if($wilds == 1)
    {
	if($tripsi != 0)
	{
	    $k = $trips[0];
	    if($tripsi > 1)
		$i = $trips[1];
	    if($pairsi != 0)
	    {
		if($pairs[0] > $i)
		    $i = $pairs[0];
	    }
	    if($singlesi != 0)
	    {
		if($singles[0] > $i)
		    $i = $singles[0];
	    }
	}
    }
    else if($wilds == 0)
    {
	if($quadsi != 0)
	{
	    $k = $quads[0];
	    if($quadsi > 1)
		$i = $quads[1];
	    if($tripsi != 0)
	    {
		if($trips[0] > $i)
		    $i = $trips[0];
	    }
	    if($pairsi != 0)
	    {
		if($pairs[0] > $i)
		    $i = $pairs[0];
	    }
	    if($singlesi != 0)
	    {
		if($singles[0] > $i)
		    $i = $singles[0];
	    }
	}
    }
    if($k > 0)
	return FOUR_OF_A_KIND($k,$i);
//////////////////PROCESS 1 or 2 WILDS/////////////////////////////////////
// Check for flush with 1 or 2 wild cards
    $flush_rank = 0; //flush rank
    for($i = 0; $i < 4; $i++) // suits
    {
	if(($suit_count[$i] + $wilds) >= 5)
	{
	    $temp = 1; // ace card bitmask
	    $k = 0;    // flush_cards index
	    $p = $wilds;    // number of wilds left to use
	    $q = 13;   // ace rank
	    while($k < 5)
	    {
		if($temp & $suits[$i])
		    $flush_cards[$k++] = $q;
		else if($p > 0)
		{
		    $flush_cards[$k++] = $q;
		    $p--;
		}			
		$temp = $temp << 1;
		$q--;
	    }
	    $k = FLIZUSH($flush_cards[0],$flush_cards[1],$flush_cards[2],$flush_cards[3],$flush_cards[4]);
	    if($k > $flush_rank)
		$flush_rank = $k;
	}
    }
    
// Check for straight with 1 or 2 wild cards:
    if($flush_rank == 0)
    {
	$straight_rank = 0;
	for($i = 13; $i >= 4; $i--)
	{
	    $temp = $suits_or & $straights[$i-4];
	    $k = 0;
	    while ($temp)
	    {
		$k++ ;
		$temp = $temp & ($temp - 1) ;
	    }
	    if($k + $wilds >= 5)
	    {
		$straight_rank = STRAIGHT($i);
		break;
	    }
	}
    }

if($wilds == 2)
{
    if($flush_rank != 0)
	return $flush_rank;
    if($straight_rank != 0)
	return $straight_rank;
    if($singlesi == 0)
	return ONE_PAIR(13,0,0,0);
    if($singlesi == 1)
	return THREE_OF_A_KIND($singles[0],0,0);
    if($singlesi == 2)
	return THREE_OF_A_KIND($singles[0],$singles[1],0);		
    return THREE_OF_A_KIND($singles[0],$singles[1],$singles[2]);
}    
//////////////////PROCESS 1 WILD/////////////////////////////////
if($wilds == 1)
{
    if($pairsi >= 2)
	return FULL_HOUSE($pairs[0],$pairs[1]);
    if($flush_rank != 0)
	return $flush_rank;
    if($straight_rank != 0)
	return $straight_rank;
    if($pairsi == 1)
    {
	if($singlesi >= 2)
	    return THREE_OF_A_KIND($pairs[0],$singles[0],$singles[1]);
	if($singlesi == 1)
	    return THREE_OF_A_KIND($pairs[0],$singles[0],0);
	return THREE_OF_A_KIND($pairs[0],0,0);	
    }
    if($singlesi == 0)
	return HIGH_CARD(13,0,0,0,0);
    if($singlesi == 1)
	return ONE_PAIR($singles[0],0,0,0);
    if($singlesi == 2)
	return ONE_PAIR($singles[0],$singles[1],0,0);
    if($singlesi == 3)
	return ONE_PAIR($singles[0],$singles[1],$singles[2],0);		
    return ONE_PAIR($singles[0],$singles[1],$singles[2],$singles[3]);
}
/////////////////DONE CHECKING WILDCARDS/////////////////////////////////
////////////////CHECK FOR FULL BOAT, FLUSH, STRAIGHT, TRIPS//////////////

    if($tripsi != 0)
    {
	$k = $trips[0];
	$i = 0;
	if($pairsi != 0)
	{
	    $i = $pairs[0];
	}
	if($tripsi > 1)
	{
	    if($trips[1] > $i)
		$i = $trips[1];
	}
	if($i != 0)
	{
	    return FULL_HOUSE($k,$i);
	}

	if($flush_rank != 0)
	    return $flush_rank;
	if($straight_rank != 0)
	    return $straight_rank;
	if($singlesi == 0)
	    return THREE_OF_A_KIND($k,0,0);
	if($singlesi == 1)
	    return THREE_OF_A_KIND($k,$singles[0],0);
	return THREE_OF_A_KIND($k,$singles[0],$singles[1]);
    }
    if($flush_rank != 0)
	return $flush_rank;
    if($straight_rank != 0)
	return $straight_rank;

/////////////////CHECK FOR TWO PAIR//////////////////////////////////////
    if($pairsi >= 2)
    {
	$k = 0;
	if($pairsi >= 3)
	    $k = $pairs[2];
	if($singlesi != 0)
	{
	    if($singles[0] > $k)
		$k = $singles[0];
	}   
	return TWO_PAIR($pairs[0],$pairs[1],$k);
    }
/////////////////CHECK FOR ONE PAIR//////////////////////////////////////
    if($pairsi != 0)
    {
	if($singlesi >= 3)
	    return ONE_PAIR($pairs[0],$singles[0],$singles[1],$singles[2]);
	if($singlesi == 2)
	    return ONE_PAIR($pairs[0],$singles[0],$singles[1],0);
	if($singlesi == 1)
	    return ONE_PAIR($pairs[0],$singles[0],0,0);
	return ONE_PAIR($pairs[0],0,0,0);
    }
//////////////////////HIGH CARD/////////////////////////////////////////
    if($singlesi >= 5)
	return HIGH_CARD($singles[0],$singles[1],$singles[2],$singles[3],$singles[4]);
    if($singlesi == 4)
	return HIGH_CARD($singles[0],$singles[1],$singles[2],$singles[3],0);
    if($singlesi == 3)
	return HIGH_CARD($singles[0],$singles[1],$singles[2],0,0);
    if($singlesi == 2)
	return HIGH_CARD($singles[0],$singles[1],0,0,0);
    if($singlesi == 1)
	return HIGH_CARD($singles[0],0,0,0,0);
    return HIGH_CARD(0,0,0,0,0);
}


function eval_seqwild($cards)
{
    $mydeck;
    $suits = array();
    $mysuit;
    $k;
    $j;
    $i;
    $p;
    $q;    
    $rank;
    $myrank;
    
    $rank = eval_hand($cards, 0);
   
    $suits[0] = $cards & LOW13;
    $suits[1] = ($cards >> 13) & LOW13;
    $suits[2] = ($cards >> 26) & LOW13;
    $suits[3] = ($cards >> 39) & LOW13;    
    
    for($i = 0; $i <=3; $i++)
    {
	$mydeck = 0;
	for($q = 0; $q <=3; $q++)
	{
	    if($q != $i)
	    {
		$mydeck = ($mydeck << 13) | $suits[$q];		
	    }
	}
	$mydeck = $mydeck << 13;
	
	$j = 12;
	while($j >= 1 )
	{
	    $mysuit = $suits[$i];
	    
	    if($suits[$i] & $card[$j])
	    {
		$k = 1;
		$j--;
		while( $j >= 0 && ($suits[$i] & $card[$j]) )
		{
		    $mysuit = $mysuit & (~($card[$j] | $card[$j+1]));
		    $k++;
		    $j--;		
		}
		if($k >= 2)
		{
		    $myrank = eval_hand($mydeck | $mysuit, $k);
		    if($myrank > $rank)
			$rank = $myrank;		    
		}
	    }	 
	    $j--;   
	}
    }
    return $rank;
}

function rank_to_text($rank)
{

    global $card_text;

    $a;
    $b;
    $c;
    $d;
    $e;
    $i = 0;
    $temp;
    $text = "";
    
    $temp = $rank;
    
    if($rank == 0)
    {
	$text = "No Cards";
    }    
    else if($rank >= MAX_RANK())
    {
	$text = "Error: Hand ranking greater than MAX_RANK !";
    }    
    else if($rank < ONE_PAIR(0,0,0,0))
    {
	$a = gmp_intval(gmp_div_q($temp,FT4));
	$temp = $temp - ($a*FT4);
	$b = gmp_intval(gmp_div_q($temp,FT3));
	$temp = $temp - ($b*FT3);
	$c = gmp_intval(gmp_div_q($temp,FT2));
	$temp = $temp - ($c*FT2);
	$d = gmp_intval(gmp_div_q($temp,FT1));
	$temp = $temp - ($d*FT1);
	$e = $temp;

	$text = "HIGH CARD ({$card_text[$a]},{$card_text[$b]},{$card_text[$c]},{$card_text[$d]},{$card_text[$e]})";	
    }
    else if($rank < TWO_PAIR(0,0,0))
    {
	$temp = $temp - ONE_PAIR(0,0,0,0);
	$a = gmp_intval(gmp_div_q($temp,FT3));
	$temp = $temp - ($a*FT3);
	$b = gmp_intval(gmp_div_q($temp,FT2));
	$temp = $temp - ($b*FT2);
	$c = gmp_intval(gmp_div_q($temp,FT1));
	$temp = $temp - ($c*FT1);
	$d = $temp;

        $text = "ONE PAIR ({$card_text[$a]}) KICKERS ({$card_text[$b]},{$card_text[$c]},{$card_text[$d]})";		
    }
    else if($rank < THREE_OF_A_KIND(0,0,0))
    {
	$temp = $temp - TWO_PAIR(0,0,0);
	$a = gmp_intval(gmp_div_q($temp,FT2));
	$temp = $temp - ($a*FT2);
	$b = gmp_intval(gmp_div_q($temp,FT1));
	$temp = $temp - ($b*FT1);
	$c = $temp;

        $text = "TWO PAIR ({$card_text[$a]},{$card_text[$b]}) KICKER ({$card_text[$c]})";
    }
    else if($rank < STRAIGHT(0))
    {
	$temp = $temp - THREE_OF_A_KIND(0,0,0);
	$a = gmp_intval(gmp_div_q($temp,FT2));
	$temp = $temp - ($a*FT2);
	$b = gmp_intval(gmp_div_q($temp,FT1));
	$temp = $temp - ($b*FT1);
	$c = $temp;

	$text = "THREE OF A KIND ({$card_text[$a]}) KICKERS ({$card_text[$b]},{$card_text[$c]})";
    }
    else if($rank < FLIZUSH(0,0,0,0,0))
    {
	$temp = $temp - STRAIGHT(0);
	$a = $temp;

        $text = "STRAIGHT ({$card_text[$a]})";
    }
    else if($rank < FULL_HOUSE(0,0))
    {
	$temp = $temp - FLIZUSH(0,0,0,0,0);
	$a = gmp_intval(gmp_div_q($temp,FT4));
	$temp = $temp - ($a*FT4);
	$b = gmp_intval(gmp_div_q($temp,FT3));
	$temp = $temp - ($b*FT3);
	$c = gmp_intval(gmp_div_q($temp,FT2));
	$temp = $temp - ($c*FT2);
	$d = gmp_intval(gmp_div_q($temp,FT1));
	$temp = $temp - ($d*FT1);
	$e = $temp;
	
	$text = "FLUSH ({$card_text[$a]},{$card_text[$b]},{$card_text[$c]},{$card_text[$d]},{$card_text[$e]})";
    }
    else if($rank < FOUR_OF_A_KIND(0,0))
    {
	$temp = $temp - FULL_HOUSE(0,0);
	$a = gmp_intval(gmp_div_q($temp,FT1));
	$temp = $temp - ($a*FT1);
	$b = $temp;
	
	$text = "FULL HOUSE ({$card_text[$a]},{$card_text[$b]})";	
    }
    else if($rank < STRAIGHT_FLUSH(0))
    {
	$temp = $temp - FOUR_OF_A_KIND(0,0);
	$a = gmp_intval(gmp_div_q($temp,FT1));
	$temp = $temp - ($a*FT1);
	$b = $temp;
	
	$text = "FOUR OF A KIND ({$card_text[$a]}) KICKER ({$card_text[$b]})";
    }
    else if($rank < FIVE_OF_A_KIND(0))
    {
	$temp = $temp - STRAIGHT_FLUSH(0);
	$a = $temp;
	
	$text = "STRAIGHT FLUSH ({$card_text[$a]})";
    }
    else // if(rank < MAX_RANK)
    {
	$temp = $temp - FIVE_OF_A_KIND(0);
	$a = $temp;
	
	$text = "FIVE OF KIND ({$card_text[$a]})";
    }
    
    return $text;
}

function text_to_bit($c)
{
    switch($c)
    {
	case '2': return $card[12];
	case '3': return $card[11];
	case '4': return $card[10];
	case '5': return $card[9];
	case '6': return $card[8];
	case '7': return $card[7];
	case '8': return $card[6];
	case '9': return $card[5];
	case 't':
	case 'T': return $card[4];
	case 'j':
	case 'J': return $card[3];
	case 'q':
	case 'Q': return $card[2];
	case 'k':
	case 'K': return $card[1];
	case 'a':
	case 'A': return $card[0];
	default:  return 0;
    }    
}

function cards_to_text($cards)
{
    $temp;
    $temp2;
    $temp3;
    $suits = array();
    $first = 1;
    
    $text = "";
    
    $suits[0] = $cards & LOW13;
    $suits[1] = ($cards >> 13) & LOW13;
    $suits[2] = ($cards >> 26) & LOW13;
    $suits[3] = ($cards >> 39) & LOW13; 
    
    for ($k=0; $k<4; $k++)
    {
	$temp = $card[12]; //card[12] is '2'
	  
	for($i = 1; $i <= 13; $i++)
	{
	    if($temp & $suits[$k])
	    {
		if($first)
		{
		    $first = 0;
		}
		else
		{
		    $text = $text . " ";
		}
		
		$text = $text . $card_text[$i];
		$text = $text . $suit_text[$k];
	    }
	    $temp = $temp >> 1;
	}
    }  
    return $text;  
    
}

?>
