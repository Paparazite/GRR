<?php
/**
 * my_account_modif_listes.php
 * Page "Ajax" utilisée pour générer les listes de domaines et de ressources, en liaison avec my_account.php
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-13 16:08$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
//Arguments passés par la méthode GET :
//$use_site : 'y' (fonctionnalité multisite activée) ou 'n' (fonctionnalité multisite désactivée)
//$id_site : l'identifiant du site
//$default_area : domaine par défaut
//$default_room : ressource par défaut
//$session_login : identifiant
//$type :   'ressource'-> on actualise la liste des ressources
//			'domaine'-> on actualise la liste des domaines
//$action : 1-> on actualise la liste des ressources
//			2-> on vide la liste des ressources

include "include/admin.inc.php";
    
if ((authGetUserLevel(getUserName(), -1) < 1))
{
	showAccessDenied("");
	exit();
}

if ($_GET['type'] == "domaine")
{
 // Initialisation
	if (isset($_GET["id_site"]))
        $id_site = intval($_GET["id_site"]);
	else
		die();
	if (isset($_GET["default_area"]))
		$default_area = intval($_GET["default_area"]);
	else
		die();
	if (isset($_GET["session_login"]))
		$session_login = $_GET["session_login"];
	else
		die();
	if (isset($_GET["use_site"]))
		$use_site = $_GET["use_site"];
	else
		die();
	if ($use_site == 'y'){
 		// on a activé les sites
		if ($id_site != -1){
			$sql = "SELECT a.id, a.area_name,a.access,a.order_display
		FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_site_area j
        ON a.id=j.id_area
		WHERE j.id_site=$id_site
		ORDER BY a.order_display, a.area_name";
		} 
		else{
			$sql = "";
		}
	}
	else{
		$sql = "SELECT id, area_name,access
		FROM ".TABLE_PREFIX."_area
		ORDER BY order_display, area_name";
	}
	if (($id_site!=-1) || ($use_site=='n')){
		$resultat = grr_sql_query($sql);
	}
	$display_liste = '<div class="form-group"><label class="control-label col-md-3 col-sm-3 col-xs-4" for="id_area">'.get_vocab('default_area').'</label><div class="col col-md-4 col-sm-6 col-xs-8"><select class="form-control" id="id_area" name="id_area" onchange="modifier_liste_ressources(1)"><option value="-1">'.get_vocab('choose_an_area').'</option>'."\n";
	if (($id_site!=-1) || ($use_site=='n')){
		foreach($resultat as $row)
		{
			if (authUserAccesArea($session_login, $row['id']) != 0)
			{
				$display_liste .=  '              <option value="'.$row['id'].'"';
				if ($default_area == $row['id'])
					$display_liste .= ' selected="selected" ';
				$display_liste .= '>'.htmlspecialchars($row['area_name']);
				if ($row['access']=='r')
					$display_liste .= ' ('.get_vocab('restricted').')';
				$display_liste .= '</option>'."\n";
			}
		}
        grr_sql_free($resultat);
	}
	$display_liste .= '            </select>';
	$display_liste .=  '</div>
</div>'."\n";
}
if ($_GET['type'] == "ressource")
{
	if ($_GET['action'] == 2)
	{
	//on vide la liste des ressources
		$display_liste = '<div class="form-group"><label class="control-label col-md-3 col-sm-3 col-xs-4" for="id_room">'.get_vocab('default_room').'</label><div class="col col-md-4 col-sm-6 col-xs-8"><select class="form-control" name="id_room" id="id_room"><option value="-1">'.get_vocab('default_room_all').'</option></select></div></div>'."\n";
	}
	else
	{
        if (isset($_GET["default_room"]))
            $default_room = intval($_GET["default_room"]);
        else
            die();
		if (isset($_GET["id_area"]))
			$id_area = intval($_GET["id_area"]);
        elseif(isset($_GET["default_area"]))
            $id_area = intval($_GET["default_area"]);
		else
			$id_area = -1;
		$sql = "SELECT id, room_name
		FROM ".TABLE_PREFIX."_room
		WHERE area_id='".$id_area."'";
		// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
		$tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
		foreach ($tab_rooms_noaccess as $key)
		{
			$sql .= " and id != $key ";
		}
		$sql .= " ORDER BY order_display,room_name";
		$resultat = grr_sql_query($sql);
        $display_liste = "";
		$display_liste .= '<div class="form-group"><label class="control-label col-md-3 col-sm-3 col-xs-4" for="id_room">'.get_vocab('default_room').'</label><div class="col col-md-4 col-sm-6 col-xs-8"><select class="form-control" name="id_room" id="id_room"><option value="-1"';
		if ($default_room == -1)
			$display_liste .= ' selected="selected" ';
		$display_liste .= ' >'.get_vocab('default_room_all').'</option>'."\n".
		'<option value="-2"';
		if ($default_room == -2)
			$display_liste .= ' selected="selected" ';
		$display_liste .= ' >'.get_vocab('default_room_week_all').'</option>'."\n".
		'<option value="-3"';
		if ($default_room == -3)
			$display_liste .= ' selected="selected" ';
		$display_liste .= ' >'.get_vocab('default_room_month_all').'</option>'."\n".
		'<option value="-4"';
		if ($default_room == -4)
			$display_liste .= ' selected="selected" ';
		$display_liste .= ' >'.get_vocab('default_room_month_all_bis').'</option>'."\n";
		foreach($resultat as $row)
		{
			$display_liste .=  '              <option value="'.$row['id'].'"';
			if ($default_room == $row['id'])
				$display_liste .= ' selected="selected" ';
			$display_liste .= '>'.htmlspecialchars($row['room_name']).' '.get_vocab('display_week');
			$display_liste .= '</option>'."\n";
		}
        grr_sql_free($resultat);
		$display_liste .= '</select></div></div>'."\n";
	}
}
header("Content-Type: text/html;charset=utf-8");
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo $display_liste;
?>
