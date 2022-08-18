<?php

/*
  Author: X'Genesis Qhulut <XGenesis-Qhulut@protonmail.com>
  Date:   August 2022

  See LICENSE for license details.
*/

// MAPS

function showOneMap ($id)
  {
  showOneThing (MAP, 'alpha_dbc.map', 'ID', $id, "Map", "ID",  array ());
  } // end of showOneMap

function showMaps ()
  {
  global $where, $params, $sort_order;


  $sortFields = array (
    'ID',
    'Directory',
  );

  if (!in_array ($sort_order, $sortFields))
    $sort_order = 'Directory';


  echo "<h2>Maps</h2>\n";

  $td  = function ($s) use (&$row) { tdx ($row  [$s]); };
  $tdr = function ($s) use (&$row) { tdx ($row  [$s], 'tdr'); };

  setUpSearch ('id', array ('directory'));

  $results = dbQueryParam ("SELECT * FROM ".MAP." $where ORDER BY $sort_order, ID LIMIT " . QUERY_LIMIT,
                    $params);

  if (!showSearchForm ($sortFields, $results))
    return;

  echo "<table class='search_results'>\n";
  headings (array ('ID', 'Name'));
  foreach ($results as $row)
    {
    echo "<tr>\n";
    $id = $row ['ID'];
    tdhr ("<a href='?action=show_map&id=$id'>$id</a>");
    $td ('Directory');
    echo "</tr>\n";
    }
  echo "</table>\n";

  showCount ($results);

  } // end of showMaps
?>
