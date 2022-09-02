<?php

/*
  Author: X'Genesis Qhulut <XGenesis-Qhulut@protonmail.com>
  Date:   August 2022

  See LICENSE for license details.
*/

// CREATURES (NPCs)

// See: https://mangoszero-docs.readthedocs.io/en/latest/database/world/creature-loot-template.html


function trainer_spell_compare ($a, $b)
  {
  global $spells;
  if ($spells [$a ['playerspell']] == $spells [$b ['playerspell']])
    return $a ['playerspell'] <=> $b ['playerspell'];
  return $spells [$a ['playerspell']] <=> $spells [$b ['playerspell']];
  } // end of trainer_spell_compare

function item_compare ($a, $b)
  {
  global $items;
  return $items [$a ['item']] <=> $items [$b ['item']];
  } // end of item_compare

function reference_item_compare ($a, $b)
  {
  global $items;
  return $items [$a ['refItem']] <=> $items [$b ['refItem']];
  } // end of reference_item_compare

function extraCreatureInformation ($id, $row)
  {
  global $quests, $items, $maps, $spells;
  global $documentRoot, $executionDir;




  // ---------------- LOOT-----------------

  // show loot

  $loot_id = $row ['loot_id'];
  if (!$loot_id)
    $loot_id = $id;


  $creature_loot_template = CREATURE_LOOT_TEMPLATE;
  $reference_loot_template = REFERENCE_LOOT_TEMPLATE;

  $results = dbQueryParam ("SELECT * FROM $creature_loot_template
                            WHERE entry = ? AND mincountOrRef > 0",
                            array ('i', &$loot_id));

  // count quest items - they have a negative drop chance
  $count = 0;
  usort($results, 'item_compare');
  foreach ($results as $lootRow)
    if ($lootRow ['ChanceOrQuestChance'] < 0)
        $count++;

  listItems ('Quest item loot', 'alpha_world.creature_loot_template', $count, $results,
    function ($row)
      {
      $chance = $row ['ChanceOrQuestChance'];
      if ($chance >= 0)
        return true; // didn't list it
      echo "<li>" . lookupItemHelper ($row ['item'], $row ['mincountOrRef']) . ' — ' .
           -$chance . "%\n";
      } // end listing function
      );

  // now do the other drops
  // count of remaining items
  $count = count ($results) - $count; // all items minus quest items
  listItems ('Loot', 'alpha_world.creature_loot_template', $count, $results,
    function ($row)
      {
      $chance = $row ['ChanceOrQuestChance'];
      if ($chance < 0)
        return true; // didn't list it
      echo "<li>" . lookupItemHelper ($row ['item'], $row ['mincountOrRef']) . ' — ' .
           $chance . "%\n";
      } // end listing function
      );



  // reference loot - the creature_loot_template table points to the reference_loot_template table
  // if the mincountOrRef field is negative, which may lead to multiple loot items for one reference
  // item - I presume to allow batches of loot to be attached to one creature_loot_template entry

  $lootResults = dbQueryParam ("SELECT $reference_loot_template.item AS refItem,
                            $creature_loot_template.ChanceOrQuestChance AS chance,
                            $reference_loot_template.mincountOrRef as minCount
                           FROM $creature_loot_template
                                INNER JOIN $reference_loot_template
                                  ON ($reference_loot_template.entry = $creature_loot_template.item)
                           WHERE $creature_loot_template.entry = ?
                                AND $creature_loot_template.mincountOrRef < 0",
                           array ('i', &$loot_id));


  usort ($lootResults, 'reference_item_compare');
  listItems ('Reference loot', 'alpha_world.reference_loot_template', count ($lootResults), $lootResults,
    function ($row)
      {
      $chance = $row ['chance'];
      if ($chance >= 0)
        $chance .= "%\n";
      else
        $chance = -$chance . "% (quest)";
      echo "<li>" . lookupItemHelper ($row ['refItem'], $row ['minCount']) . ' — ' .
           $chance;
      } // end listing function
      );


  // ---------------- PICK POCKETING LOOT -----------------

  // show pickpocketing loot

  $loot_id = $row ['pickpocket_loot_id'];
  if (!$loot_id)
    $loot_id = $id;

  $lootResults = dbQueryParam ("SELECT * FROM ".PICKPOCKETING_LOOT_TEMPLATE." WHERE entry = ?", array ('i', &$loot_id));
  usort($lootResults, 'item_compare');
  listItems ('Pickpocketing loot', 'alpha_world.pickpocketing_loot_template', count ($lootResults), $lootResults,
    function ($row)
      {
      echo "<li>" . lookupItemHelper ($row ['item'], $row ['mincountOrRef']) . ' — ' .
           $row ['ChanceOrQuestChance'] . '%';
      } // end listing function
      );



  // ---------------- SKINNING LOOT -----------------

  // show skinning loot

  $loot_id = $row ['skinning_loot_id'];
  if (!$loot_id)
    $loot_id = $id;

  $lootResults = dbQueryParam ("SELECT * FROM ".SKINNING_LOOT_TEMPLATE." WHERE entry = ?", array ('i', &$loot_id));
  usort($lootResults, 'item_compare');
  listItems ('Skinning loot', 'alpha_world.skinning_loot_template', count ($lootResults), $lootResults,
    function ($row)
      {
      echo "<li>" . lookupItemHelper ($row ['item'], $row ['mincountOrRef']) . ' — ' .
           $row ['ChanceOrQuestChance'] . '%';
      } // end listing function
      );



  } // end of extraCreatureInformation

function showOneCreature ()
  {
  global $id;
  global $documentRoot, $executionDir;
  global $quests, $spells, $items;

  $extras = array (
        'spell_id1' => 'spell',
        'spell_id2' => 'spell',
        'spell_id3' => 'spell',
        'spell_id4' => 'spell',
        'faction'   => 'npc_faction',
        'mechanic_immune_mask' => 'mechanic_mask',
        'school_immune_mask' => 'school_mask',    // I think this might be out by one
        'inhabit_type'  => 'inhabit_type_mask',
        'movement_type' => 'movement_type',
        'flags_extra'   => 'flags_extra_mask',
        'npc_flags'     => 'npc_flags_mask',
        'rank'          => 'rank',
        'gold_min'      => 'gold',
        'gold_max'      => 'gold',
        'dmg_school'    => 'spell_school',
        'base_attack_time'    => 'time',
        'ranged_attack_time'    => 'time',
        'type'    => 'creature_type',

         'static_flags'   => 'creature_static_flags',
         'school_immune_mask' => 'mask',

 //       'trainer_spell' => 'spell',   // Hmmm, must have the wrong end of the stick here

    );

  // we need the creature info in this function
  $row = dbQueryOneParam ("SELECT * FROM ".CREATURE_TEMPLATE." WHERE entry = ?", array ('i', &$id));

  if ($row ['npc_flags'] & TRAINER_FLAG)
    {
    $extras  ['trainer_type'] = 'trainer_type';
    $extras  ['trainer_class'] = 'class';
    $extras  ['trainer_race'] = 'race';
    }

$name = fixHTML ($row ['name']);
if ($row ['subname'])
  $name .= ' <' . $row ['subname'] . '>';
$pageType = 'Creature';

echo "
      <!-- PAGE CONTAINER-->
      <section class='main-page-container'>
        <!-- PAGE TITLE -->
        <div class='page-title'>
          <div>
            <button class='page-title__goback'>
              <i class='fas fa-angle-left'></i>
            </button>
            <h1>$name</h1>
          </div>
          <div>
            <i class='page-title__database fas fa-database'></i>
            <i class='page-title__angle fas fa-angle-right'></i>
            <p class='page-title__table'>$pageType</p>
          </div>
        </div>
        <!-- END PAGE TITLE -->

        <!-- PAGE CONTENT -->
        <div class='creature-details page-content'>
          <div class='creature-details__informations'>
            <div class='creature-details__informations__details1'>
              <div class='tiny-title'>
                <h2 class='tiny-title__heading'>General</h2>
                <div class='tiny-title__bar'></div>
              </div>
";


  // ---------------- IMAGE OF CREATURE -----------------

  for ($i = 1; $i <= 4; $i++)
    {
    if ($row ["display_id$i"])
      {
      $display_id = $row ["display_id$i"];
      $icon = $display_id . '.webp';
      if (file_exists ("$documentRoot$executionDir/creatures/$icon"))
        echo "
        <!-- MODEL DISPLAY ID -->
        <img
          class='model-display'
          src='creatures/$icon'
          alt='Creature model'
        />
        <!-- END MODEL DISPLAY ID -->
        ";
      } // end of if non-zero display ID
    } // end of for all 4 possible display IDs

  $limit = array (
    'entry',
    'display_id1',
    'level_min',
    'mana_min',
    'armor',
    'faction',
    'npc_flags',

  );
  showOneThing (CREATURE_TEMPLATE, 'alpha_world.creature_template', 'entry',
              $id, "Creature", "name", $extras, $limit);


  echo "</div>\n";  // end of details__informations__details1

  echo "<div class='creature-details__informations__details2'>\n";


    echo " <!-- SPAWN POINTS - EASTERN KINGDOMS --> \n";

    $where = '(spawn_entry1 = ? OR spawn_entry2 = ? OR spawn_entry3 = ? OR spawn_entry4 = ?)' .
             ' AND ignored = 0 ';
    $param = array ('iiii', &$id, &$id, &$id, &$id);

    // show spawn points - Eastern Kingdoms
    $results = dbQueryParam ("SELECT * FROM ".SPAWNS_CREATURES."
          WHERE $where AND map = 0", $param) ;

    listSpawnPoints ($results, 'Spawn points - Eastern Kingdoms', 'alpha_world.spawns_creatures',
                  'position_x', 'position_y', 'position_z', 'map');

    echo " <!-- SPAWN POINTS - KALIMDOR --> \n";

    // show spawn points - Kalimdor
    $results = dbQueryParam ("SELECT * FROM ".SPAWNS_CREATURES."
          WHERE $where AND map = 1", $param);

    listSpawnPoints ($results, 'Spawn points - Kalimdor', 'alpha_world.spawns_creatures',
                  'position_x', 'position_y', 'position_z', 'map');


    echo " <!-- SPAWN POINTS - OTHER --> \n";

    // show spawn points - other
    $results = dbQueryParam ("SELECT * FROM ".SPAWNS_CREATURES."
          WHERE $where AND map > 1", $param);

    listSpawnPoints ($results, 'Spawn points - Instances', 'alpha_world.spawns_creatures',
                  'position_x', 'position_y', 'position_z', 'map');


     echo "  <!-- END SPAWN POINTS -->\n";

 // ---------------- QUESTS -----------------

  // show quests they start

  // what quests they give
  $results = dbQueryParam ("SELECT * FROM ".CREATURE_QUEST_STARTER." WHERE entry = ?", array ('i', &$id));

  listItems ('NPC starts these quests', 'alpha_world.creature_quest_starter', count ($results), $results,
    function ($row) use ($quests)
      {
      listThing ($quests, $row ['quest'], 'show_quest');
      } // end listing function
      );

  // what quests they finish
  $results = dbQueryParam ("SELECT * FROM ".CREATURE_QUEST_FINISHER." WHERE entry = ?", array ('i', &$id));

  listItems ('NPC finishes these quests', 'alpha_world.creature_quest_finisher', count ($results), $results,
    function ($row) use ($quests)
      {
      listThing ($quests, $row ['quest'], 'show_quest');
      } // end listing function
      );


  echo "</div>\n";  // end of creature-details__informations__details1  (spawn points, quests, etc.)


echo "  <!-- MAP -->
     <aside class='creatures-details__map'>\n";

    echo " <!-- SPAWN POINTS ON MAP - EASTERN KINGDOMS --> \n";

    $where = '(spawn_entry1 = ? OR spawn_entry2 = ? OR spawn_entry3 = ? OR spawn_entry4 = ?)' .
             ' AND ignored = 0 ';
    $param = array ('iiii', &$id, &$id, &$id, &$id);

    // show spawn points - Eastern Kingdoms
    $results = dbQueryParam ("SELECT * FROM ".SPAWNS_CREATURES."
          WHERE $where AND map = 0", $param) ;

    showSpawnPoints ($results, 'Spawn points - Eastern Kingdoms', 'alpha_world.spawns_creatures',
                  'position_x', 'position_y', 'position_z', 'map');

    echo " <!-- SPAWN POINTS ON MAP - KALIMDOR --> \n";

    // show spawn points - Kalimdor
    $results = dbQueryParam ("SELECT * FROM ".SPAWNS_CREATURES."
          WHERE $where AND map = 1", $param);

    showSpawnPoints ($results, 'Spawn points - Kalimdor', 'alpha_world.spawns_creatures',
                  'position_x', 'position_y', 'position_z', 'map');


     echo "  <!-- END MAP SPAWN POINTS -->\n";

  echo "  </aside>  <!-- END MAP -->\n";

  echo "</div>\n";  // end of creature-details__informations (stuff at top)



  // ---------------- SPELL LISTS --------------


  if ($row ['spell_list_id'])
    {
    $spellListRow = dbQueryOneParam ("SELECT * FROM ".CREATURE_SPELLS." WHERE entry = ?", array ('i', &$row ['spell_list_id']));
    if ($spellListRow)
      {

      echo "
      <div class='creature-details__items'>
      <div class='tiny-title'>
      <h2 class='tiny-title__heading' title='Table: alpha_world.creature_spells'>Spells this NPC casts</h2>
      </div>";
      echo "<ul>\n";

      for ($i = 1; $i <= 8; $i++)
        {
        if ($spellListRow ["spellId_$i"])
          {
          echo "<li>";
          echo (lookupThing ($spells,   $spellListRow ["spellId_$i"], 'show_spell'));
          echo "<ul>\n";
          echo "<li>Probability: " . $spellListRow ["probability_$i"] . "%";
          echo "<li>Target type: " . expandSimple (TARGET_TYPE, $spellListRow ["castTarget_$i"]);
          if ($spellListRow ["targetParam1_$i"] || $spellListRow ["targetParam2_$i"])
            {
            echo " (param1: " . $spellListRow ["targetParam1_$i"];
            echo ", param2: " . $spellListRow ["targetParam2_$i"] . ")";
            }
          if ($spellListRow ["castFlags_$i"])
            echo "<li>Flags: "  . expandShiftedMask (SPELL_CAST_FLAGS, $spellListRow ["castFlags_$i"], false);

          echo "<li>Initial delay: "  . $spellListRow ["delayInitialMin_$i"];
          if ($spellListRow ["delayInitialMin_$i"] != $spellListRow ["delayInitialMax_$i"])
            echo ' to ' . $spellListRow ["delayInitialMax_$i"];
          echo "<li>Repeat delay: "  . $spellListRow ["delayRepeatMin_$i"];
          if ($spellListRow ["delayRepeatMin_$i"] != $spellListRow ["delayRepeatMax_$i"])
            echo ' to ' . $spellListRow ["delayRepeatMax_$i"];
          if ($spellListRow ["scriptId_$i"])
            echo "<li>Script ID: "  . $spellListRow ["scriptId_$i"];
          echo "</ul>\n";
          }   // end of if this spell entry is there (non-zero)
        } // end of for each of the 8 possible spells
      echo "</ul>\n";
      } // if we found the spell list

    echo "</div>\n";  // end of creature-details__items

    } // end of if they had a spell_list_id


 // ---------------- VENDOR ITEMS -----------------


 // what they sell
  $results = dbQueryParam ("SELECT * FROM ".NPC_VENDOR." WHERE entry = ?", array ('i', &$id));
  usort($results, 'item_compare');

  listItems ('NPC sells', 'alpha_world.npc_vendor', count ($results), $results,
    function ($row) use ($items)
      {
      listThing ($items, $row ['item'], 'show_item');
      $maxcount = $row ['maxcount'];
      if ($maxcount  > 0)
        echo (" (limit $maxcount)");
      } // end listing function
      );




  // ---------------- TRAINER ITEMS -----------------

  // what they train

  $results = dbQueryParam ("SELECT * FROM ".TRAINER_TEMPLATE." WHERE template_entry = ?",
                            array ('i', &$row ['trainer_id']));

  usort($results, 'trainer_spell_compare');
  listItems ('NPC trains', 'alpha_world.trainer_id', count ($results), $results,
    function ($row) use ($spells)
      {
      listThing ($spells, $row ['playerspell'], 'show_spell');
      } // end listing function
      );


  echo "<div class='creature-details__items'>\n";

  showOneThing (CREATURE_TEMPLATE, 'alpha_world.creature_template', 'entry',
              $id, "Database entry for NPC", "name", $extras);
  echo "</div>\n";  // end of creature-details__items




  echo "</div>  <!-- END PAGE CONTENT -->\n";  // end of creature-details page-content

  echo "</section>  <!-- END PAGE CONTAINER-->";

  } // end of showOneCreature


function showCreatures ()
  {
  global $where, $params, $sort_order;

  $sortFields = array (
    'entry',
    'name',
    'subname',
    'name',
    'level_min',
  );

  if (!in_array ($sort_order, $sortFields))
    $sort_order = 'name';

  echo "<h2>Creatures (NPCs)</h2>\n";

  $td  = function ($s) use (&$row) { tdx ($row  [$s]); };
  $tdr = function ($s) use (&$row) { tdx ($row  [$s], 'tdr'); };

  setUpSearch ('entry', array ('name', 'subname'));

  $offset = getQueryOffset(); // based on the requested page number

  $results = dbQueryParam ("SELECT * FROM ".CREATURE_TEMPLATE." $where AND entry <= " . MAX_CREATURE .
                            " ORDER BY $sort_order LIMIT $offset, " . QUERY_LIMIT,
                            $params);

  if (!showSearchForm ($sortFields, $results, CREATURE_TEMPLATE, "$where AND entry <= " . MAX_CREATURE))
    return;

  echo "<table class='search_results'>\n";
  headings (array ('Entry', 'Name', 'Subname', 'Level'));
  foreach ($results as $row)
    {
    echo "<tr>\n";
    $id = $row ['entry'];
    tdhr ("<a href='?action=show_creature&id=$id'>$id</a>");
    $tdr ('name');
    $tdr ('subname');
    if ($row ['level_min'] != $row ['level_max'])
      tdxr  ($row ['level_min'] . '-' . $row ['level_max'] );
    else
      $tdr ('level_min');
    showFilterColumn ($row);

    echo "</tr>\n";
    }
  echo "</table>\n";

  showCount ($results);

  } // end of showCreatures
?>
