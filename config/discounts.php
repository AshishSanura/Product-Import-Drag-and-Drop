<?php
return [
    // stacking order: 'highest_first' or 'lowest_first' or array of types
    'stacking_order' => 'highest_first',

    // max total percentage that can be applied after stacking (e.g. 50 = 50%)
    'max_total_percentage' => 50,

    // rounding: 'round', 'ceil', 'floor' or integer decimal places
    'round_mode' => 'round', // supported: round, ceil, floor
    'round_decimals' => 2
];
