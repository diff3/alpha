<?php

/*
  Author: X'Genesis Qhulut <XGenesis-Qhulut@protonmail.com>
  Date:   August 2022

  See LICENSE for license details.
*/

// GAME OBJECTS

// See: https://github.com/cmangos/issues/wiki/gameobject_template

function extraGameObjectInformation ($id, $row)
  {
  global $maps, $quests;


  echo "<p><div class='simulate_box gameobject'>\n";
  echo "<h3 style='color:yellow;'>" . fixHTML ($row ['name'] ) . "</h3>\n";
  echo expandSimple (GAMEOBJECT_TYPE, $row ['type'], false);
  if ($row ['mingold'])
    echo "<p>Money: " . convertGold ($row ['mingold']) . ' to ' . convertGold ($row ['maxgold']);

  if ($row ['type'] == GAMEOBJECT_TYPE_CHEST)
    {
    if ($row ['data2'])
      echo "<p>Restock time: " . convertTimeGeneral ($row ['data2'] * 1000);
    if ($row ['data3'])
      echo "<br>Consumable\n";
    if ($row ['data4'])
      {
      echo "<br>Loot attempts allowed: " . $row ['data4'];
      if ($row ['data5'] != $row ['data4'])
        echo ' to ' . $row ['data5']. "\n";
      }
    } // end of chest

  echo "</div>\n";    // end of simulation box

 // ==========================================================================================================

 // show spawn points - Eastern Kingdoms'
  $results = dbQueryParam ("SELECT * FROM ".SPAWNS_GAMEOBJECTS."
            WHERE spawn_entry = ? AND ignored = 0 AND spawn_map = 0", array ('i', &$id));

  if (count ($results) > 0)
    showSpawnPoints ($results, 'Spawn points - Eastern Kingdoms', 'alpha_world.spawns_gameobjects',
                    'spawn_positionX', 'spawn_positionY', 'spawn_positionZ', 'spawn_map');

 // show spawn points - Kalimdor
  $results = dbQueryParam ("SELECT * FROM ".SPAWNS_GAMEOBJECTS."
            WHERE spawn_entry = ? AND ignored = 0 AND spawn_map = 1", array ('i', &$id));

  if (count ($results) > 0)
    showSpawnPoints ($results, 'Spawn points - Kalimdor', 'alpha_world.spawns_gameobjects',
                    'spawn_positionX', 'spawn_positionY', 'spawn_positionZ', 'spawn_map');


  // show spawn points - everywhere else
  $results = dbQueryParam ("SELECT * FROM ".SPAWNS_GAMEOBJECTS."
            WHERE spawn_entry = ? AND ignored = 0 AND spawn_map > 1", array ('i', &$id));

  if (count ($results) > 0)
    showSpawnPoints ($results, 'Spawn points - Instances', 'alpha_world.spawns_gameobjects',
                    'spawn_positionX', 'spawn_positionY', 'spawn_positionZ', 'spawn_map');

  // what quests they give
  $results = dbQueryParam ("SELECT * FROM ".GAMEOBJECT_QUESTRELATION." WHERE entry = ?", array ('i', &$id));

/*  I'm not so sure about this now ... The quest should exist, right?

  $results = dbQueryParam ("SELECT T1.* FROM ".GAMEOBJECT_QUESTRELATION." AS T1
                            INNER JOIN ".QUEST_TEMPLATE." AS T2 ON (T1.quest = T2.entry)
                            WHERE T1.entry = ? AND T2.ignored = 0", array ('i', &$id));
*/

  if (count ($results) > 0)
    {
    echo "<div class='item_list' >\n";
    echo "<h2 title='Table: alpha_world.gameobject_questrelation'>Game object starts these quests</h2><ul>\n";
    foreach ($results as $questRow)
      {
      listThing ($quests, $questRow ['quest'], 'show_quest');
      } // for each quest starter GO
    echo "</ul>\n";
    echo "</div>\n";
    }

 // what quests they finish
  $results = dbQueryParam ("SELECT * FROM ".GAMEOBJECT_INVOLVEDRELATION." WHERE entry = ?", array ('i', &$id));

/*  I'm not so sure about this now ... The quest should exist, right?

  $results = dbQueryParam ("SELECT T1.* FROM ".GAMEOBJECT_INVOLVEDRELATION." AS T1
                            INNER JOIN ".QUEST_TEMPLATE." AS T2 ON (T1.quest = T2.entry)
                            WHERE T1.entry = ? AND T2.ignored = 0", array ('i', &$id));
*/

  if (count ($results) > 0)
    {
    echo "<div class='item_list' >\n";
    echo "<h2 title='Table: alpha_world.gameobject_involvedrelation'>Game object finishes these quests</h2><ul>\n";
    foreach ($results as $questRow)
      {
      listThing ($quests, $questRow ['quest'], 'show_quest');
      } // for each quest starter GO
    echo "</ul>\n";
    echo "</div>\n";
    }


  // ---------------- CHEST LOOT -----------------

  // show chest loot, which includes mining and herb nodes


  if ($row ['type'] == GAMEOBJECT_TYPE_CHEST)
    {
    $lootResults = dbQueryParam ("SELECT * FROM ".GAMEOBJECT_LOOT_TEMPLATE." WHERE entry = ?", array ('i', &$row ['data1']));
    usort($lootResults, 'item_compare');
    listItems ('Gameobject loot', 'alpha_world.gameobject_loot_template', count ($lootResults), $lootResults,
      function ($row)
        {
        echo "<li>" . lookupItemHelper ($row ['item'], $row ['mincountOrRef']) . ' — ' .
             $row ['ChanceOrQuestChance'] . '%';
        } // end listing function
        );
    } // end of chest type


  } // end of extraGameObjectInformation

function showOneGameObject ()
  {
  global $id;

  showOneThing (GAMEOBJECT_TEMPLATE, 'alpha_world.gameobject_template', 'entry', $id, "Game Object", "name",
    array (
        'faction' => 'npc_faction',
        'type' => 'gameobject_type',

    ), 'extraGameObjectInformation');


  } // end of showOneGameObject


function showGameObjects ()
  {
  global $where, $params, $npc_factions, $sort_order;


  $sortFields = array (
    'entry',
    'name',
    'faction',
  );

  if (!in_array ($sort_order, $sortFields))
    $sort_order = 'name';

  $td  = function ($s) use (&$row) { tdx ($row  [$s]); };
  $tdr = function ($s) use (&$row) { tdx ($row  [$s], 'tdr'); };

  $results = setUpSearch ('Game Objects',
                          $sortFields,          // fields we can sort on
                          array ('Entry', 'Name', 'Faction'),    // headings
                          'entry',              // key
                          array ('name'),       // searchable fields
                          GAMEOBJECT_TEMPLATE,  // table
                          '');                  // extra conditions

  if (!$results)
    return;

  foreach ($results as $row)
    {
    echo "<tr>\n";
    $id = $row ['entry'];
    tdhr ("<a href='?action=show_go&id=$id'>$id</a>");
    $tdr ('name');
    tdxr (expandSimple ($npc_factions, $row ["faction"]));;
    showFilterColumn ($row);

    echo "</tr>\n";
    }

  wrapUpSearch ();

  } // end of showGameObjects
  ?>
