<?php

/**
 * Data file for Pacific/Norfolk timezone, compiled from the olson data.
 *
 * Auto-generated by the phing olson task on 09/19/2008 17:06:40
 *
 * @package    agavi
 * @subpackage translation
 *
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      0.11.0
 *
 * @version    $Id$
 */

return array (
  'types' => 
  array (
    0 => 
    array (
      'rawOffset' => 40320,
      'dstOffset' => 0,
      'name' => 'NMT',
    ),
    1 => 
    array (
      'rawOffset' => 41400,
      'dstOffset' => 0,
      'name' => 'NFT',
    ),
  ),
  'rules' => 
  array (
    0 => 
    array (
      'time' => -2177493112,
      'type' => 0,
    ),
    1 => 
    array (
      'time' => -599656320,
      'type' => 1,
    ),
  ),
  'finalRule' => 
  array (
    'type' => 'static',
    'name' => 'NFT',
    'offset' => 41400,
    'startYear' => 1951,
  ),
  'name' => 'Pacific/Norfolk',
);

?>