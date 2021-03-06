<?php
//
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                  Copyright (c) 2000-2016 XOOPS.org                        //
//                       <https://xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, https://xoops.org/, http://jp.xoops.org/ //
// Project: XOOPS Project                                                    //
// ------------------------------------------------------------------------- //

use Xmf\Request;

include_once __DIR__ . '/admin_header.php';
include_once $GLOBALS['xoops']->path('class/pagenav.php');

$op   = Request::getCmd('op', Request::getCmd('op', 'default', 'POST'), 'GET'); // !empty($_GET['op'])? $_GET['op'] : (!empty($_POST['op'])?$_POST['op']:"default");
$item = Request::getString('op', Request::getInt('item', 'process', 'POST'), 'GET'); //!empty($_GET['op'])? $_GET['item'] : (!empty($_POST['item'])?$_POST['item']:"process");

$start = Request::getInt('start', 0, 'GET');
//$reportHandler = xoops_getModuleHandler('report', 'newbb');

xoops_cp_header();
switch ($op) {
    case 'delete':
        $digest_ids    = Request::getArray('digest_id', '', 'POST');
        /** @var \NewbbDigestHandler $digestHandler */
        $digestHandler = xoops_getModuleHandler('digest', 'newbb');
        if ($digest_ids !== '') {
            foreach ($digest_ids as $did => $value) {
                $digestHandler->delete($did);
            }
        }
        redirect_header('admin_digest.php', 1);
        break;

    default:
        include_once $GLOBALS['xoops']->path('class/xoopsformloader.php');

        $limit = 5;
        $adminObject->displayNavigation(basename(__FILE__));

        //if (!$newXoopsModuleGui) loadModuleAdminMenu(7,_AM_NEWBB_DIGESTADMIN);
        //    else $adminObject->displayNavigation(basename(__FILE__));
        echo '<form action="' . xoops_getenv('PHP_SELF') . '" method="post">';
        echo "<table border='0' cellpadding='4' cellspacing='1' width='100%' class='outer'>";
        echo "<tr align='center'>";
        echo "<td class='bg3'>" . _AM_NEWBB_DIGESTCONTENT . '</td>';
        echo "<td class='bg3' width='2%'>" . _DELETE . '</td>';
        echo '</tr>';

        $digests       = [];
        $digestHandler = xoops_getModuleHandler('digest', 'newbb');
        $digests       = $digestHandler->getAllDigests($start, $limit);
        foreach ($digests as $digest) {
            echo "<tr class='odd' align='left'>";
            echo '<td><strong>#' . $digest['digest_id'] . ' @ ' . formatTimestamp($digest['digest_time']) . '</strong><br>' . str_replace("\n", '<br>', $digest['digest_content']) . '</td>';
            echo "<td align='center' ><input type='checkbox' name='digest_id[" . $digest['digest_id'] . "]' value='1' /></td>";
            echo '</tr>';
            echo "<tr colspan='2'><td height='2'></td></tr>";
        }
        $submit = new XoopsFormButton('', 'submit', _SUBMIT, 'submit');
        echo "<tr colspan='2'><td align='center'>" . $submit->render() . '</td></tr>';
        $hidden = new XoopsFormHidden('op', 'delete');
        echo $hidden->render();
        $hidden = new XoopsFormHidden('item', $item);
        echo $hidden->render() . '</form>';

        echo '</table>';

        $nav = new XoopsPageNav($digestHandler->getDigestCount(), $limit, $start, 'start');
        echo $nav->renderNav(4);

        break;
}
include_once __DIR__ . '/admin_footer.php';
