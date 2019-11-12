<?php

$totalCompetitors = 14;
$competitorsPerIt = 4;
$competitorsPerTwo = getCombinations($competitorsPerIt,2);
$nrOfCompetitorsPerTwo = count($competitorsPerTwo);

 function getCombinations( int $total, int $amountPerCombination ): array {
     $combinations = [];

     $numbers = [];
     for ( $i = 1 ; $i <= $total ; $i++ ) {
         $numbers[] = $i;
     }

     $itemSuccess = function ( int $newNumber): bool {
         return true; // ($newNumber % 2) === 1;
     };
     /**
      * @param array|int[] $batch
      * @return bool
      */
     $endSuccess = function( array $batch) use ( $amountPerCombination ) : bool {
         return $amountPerCombination === count( $batch );
//         if ($amountPerCombination !== count( $batch ) ) {
//             return false;
//         }
//         $sum = 0;
//         foreach( $batch as $number ) { $sum += $number; }
//         return $sum === 3;
     };

     /**
      * @param array|int[] $list
      * @param array|int[] $batch
      * @return bool
      */
     $showC = function(array $list, array $batch = []) use ( &$showC, $itemSuccess, $endSuccess, $amountPerCombination, &$combinations ) : bool {
         if ($endSuccess($batch)) {
             $combinations[] = $batch; // echo implode( ',', $batch) . PHP_EOL;
             return true;
         }
         if ((count($list) + count($batch)) < $amountPerCombination) {
             return false;
         }
         $numberToTry = array_shift($list );
         if ($numberToTry !== null && $itemSuccess($numberToTry)) {
             $batch[] = $numberToTry;
             if ($showC(array_slice($list, 0), $batch) === true) {
                 // return true; // uncomment for one solution
             }
             array_pop($batch);
             return $showC($list, $batch);

         }
         return $showC($list, $batch);
     };

     $showC($numbers);
     return $combinations;
}

$combinations = getCombinations($totalCompetitors,$competitorsPerIt);
echo count($combinations) . " combinations found" . PHP_EOL;

function getTwentySix( array $combinationIt, int $totalCompetitors, int $nrOfCompetitorsPerTwo, array &$successFulFnc ) : bool {
    $allAreTwentySix = function ( array $nrInBatches ) use ( $nrOfCompetitorsPerTwo ): bool {
        foreach( $nrInBatches as $number ) {
            if( $number !== 26 ) {
                return false;
            }
        }
        return true;
    };
    $lessThanXDiff = function( array $nrInBatches, array $combination,int $x ) : bool {
        foreach( $combination as $number ) {
            $newAmount = array_key_exists( $number, $nrInBatches ) ? $nrInBatches[$number] : 0;
            foreach( $nrInBatches as $nrIt ) {
                if( ($nrIt+$x) < $newAmount ) {
                    return false;
                }
            }
        }
        return true;
    };

    $nrInBatches = [];
    $xDiff = 2;
    foreach( $combinationIt as $combination ) {
        if( !$lessThanXDiff($nrInBatches, $combination, $xDiff ) ) {
            $combinationIt[] = $combination;
            continue;
        }
        foreach( $combination as $number ) {
            if( !array_key_exists( $number, $nrInBatches ) ) {
                $nrInBatches[$number] = 0;
            }
            $nrInBatches[$number]++;

            if( $nrInBatches[$number] > 24 ) {
                $xDiff = 1;
            }
            if( $nrInBatches[$number] > 26 ) {
                $s = ""; foreach( $nrInBatches as $idx => $number ) { $s .= $idx . '=>' . $number .","; }
                echo "failed " . $s . PHP_EOL;
                return false;
            }
        }
        $successFulFnc[] = $combination;
        if( count($nrInBatches) === $totalCompetitors && $allAreTwentySix($nrInBatches) ) {
            echo "major succes!" . PHP_EOL;
            return true;
        }
    }
    return false;
}


$successFul = [];
while( !getTwentySix($combinations, $totalCompetitors, $nrOfCompetitorsPerTwo, $successFul)) {
    shuffle ( $combinations );
    $successFul = [];
    usleep(100);
}

$max = (($totalCompetitors-1) * ($totalCompetitors/2) ); // * $nrOfCompetitorsPerTwo;
$batchNr = 1;
foreach( $successFul as $combination ) {
    foreach( $competitorsPerTwo as $competitors ) {
        echo 'batch ' . $batchNr . ', ' . $combination[$competitors[0]-1] . ', ' . $combination[$competitors[1]-1] . PHP_EOL;
    }
    if( $batchNr === $max ) {
        break;
    }
    $batchNr++;
}