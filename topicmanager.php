<?php

use Xmf\Request;

//
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                  Copyright (c) 2000-2016 XOOPS.org                        //
//                       <http://xoops.org/>                             //
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
//  Author: phppp (D.J., infomax@gmail.com)                                  //
//  URL: http://xoops.org                                                    //
//  Project: Article Project                                                 //
//  ------------------------------------------------------------------------ //
include_once __DIR__ . '/header.php';

if (Request::getString('submit', '', 'POST')) {
    foreach (['forum', 'newforum', 'newtopic'] as $getint) {
        ${$getint} = Request::getInt($getint, 0, 'POST');// (int)(@$_POST[$getint]);
    }
    foreach (['topic_id'] as $getint) {
        ${$getint} = Request::getInt($getint, 0, 'POST');// (int)(@$_POST[$getint]);
    }
    if (!is_array($topic_id)) {
        $topic_id = [$topic_id];
    }
} else {
    foreach (['forum', 'topic_id'] as $getint) {
        ${$getint} = Request::getInt($getint, 0, 'GET');// (int)(@$_GET[$getint]);
    }
}

if (!$topic_id) {
    $redirect = empty($forum_id) ? 'index.php' : 'viewforum.php?forum={$forum}';
    $redirect = XOOPS_URL . '/modules/newbb/' . $redirect;
    redirect_header($redirect, 2, _MD_ERRORTOPIC);
}

/** @var \NewbbTopicHandler $topicHandler */
$topicHandler = xoops_getModuleHandler('topic', 'newbb');
/** @var \NewbbForumHandler $forumHandler */
$forumHandler = xoops_getModuleHandler('forum', 'newbb');

if (!$forum) {
    /** @var \Topic $topic_obj */
    $topic_obj = $topicHandler->get((int)$topic_id);
    if (is_object($topic_obj)) {
        $forum = $topic_obj->getVar('forum_id');
    } else {
        $redirect = XOOPS_URL . '/modules/newbb/viewtopic.php?topic_id=' . $topic_id;
        redirect_header($redirect, 2, _MD_FORUMNOEXIST);
    }
    unset($topic_obj);
}

if ($GLOBALS['xoopsModuleConfig']['wol_enabled']) {
    /** @var \NewbbOnlineHandler $onlineHandler */
    $onlineHandler = xoops_getModuleHandler('online', 'newbb');
    $onlineHandler->init($forum);
}
// irmtfan add restore to viewtopic
$action_array = [
    'merge',
    'delete',
    'restore',
    'move',
    'lock',
    'unlock',
    'sticky',
    'unsticky',
    'digest',
    'undigest'
];
foreach ($action_array as $_action) {
    $action[$_action] = [
        'name'   => $_action,
        'desc'   => constant(strtoupper("_MD_DESC_{$_action}")),
        'submit' => constant(strtoupper("_MD_{$_action}")),
        'sql'    => "topic_{$_action}=1",
        'msg'    => constant(strtoupper("_MD_TOPIC{$_action}"))
    ];
}
$action['lock']['sql']     = 'topic_status = 1';
$action['unlock']['sql']   = 'topic_status = 0';
$action['unsticky']['sql'] = 'topic_sticky = 0';
$action['undigest']['sql'] = 'topic_digest = 0';
$action['digest']['sql']   = 'topic_digest = 1, digest_time = ' . time();

// Disable cache
$GLOBALS['xoopsConfig']['module_cache'][$xoopsModule->getVar('mid')] = 0;
// irmtfan include header.php after defining $xoopsOption['template_main']
include_once $GLOBALS['xoops']->path('header.php');

if (Request::getString('submit', '', 'POST')) {
    $mode = Request::getString('mode', '', 'POST');// $_POST['mode'];

    if ('delete' === $mode) {
        foreach ($topic_id as $tid) {
            $topic_obj = $topicHandler->get($tid);
            $topicHandler->delete($topic_obj, false);
            // irmtfan - sync topic after delete
            $topicHandler->synchronization($topic_obj);
            $forumHandler->synchronization($forum);
            //$topic_obj->loadFilters("delete");
            //sync($topic_id, "topic");
            //xoops_notification_deletebyitem ($xoopsModule->getVar('mid'), 'thread', $topic_id);
        }
        // irmtfan full URL
        echo $action[$mode]['msg'] . "<p><a href='" . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/viewforum.php?forum=$forum'>" . _MD_RETURNTOTHEFORUM . "</a></p><p><a href='index.php'>" . _MD_RETURNFORUMINDEX . '</a></p>';
    } elseif ('restore' === $mode) {
        //$topicHandler = xoops_getModuleHandler('topic', 'newbb');
        $forums     = [];
        $topics_obj = $topicHandler->getAll(new Criteria('topic_id', '(' . implode(',', $topic_id) . ')', 'IN'));
        foreach (array_keys($topics_obj) as $id) {
            $topic_obj = $topics_obj[$id];
            $topicHandler->approve($topic_obj);
            $topicHandler->synchronization($topic_obj);
            $forums[$topic_obj->getVar('forum_id')] = 1;
        }
        //irmtfan remove - no need to approve posts manually - see class/post.php approve function
        $criteria_forum = new Criteria('forum_id', '(' . implode(',', array_keys($forums)) . ')', 'IN');
        $forums_obj     = $forumHandler->getAll($criteria_forum);
        foreach (array_keys($forums_obj) as $id) {
            $forumHandler->synchronization($forums_obj[$id]);
        }
        unset($topics_obj, $forums_obj);
        // irmtfan add restore to viewtopic
        $restoretopic_id = $topic_obj->getVar('topic_id');
        // irmtfan / missing in URL
        echo $action[$mode]['msg'] . "<p><a href='" . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/viewtopic.php?topic_id=$restoretopic_id'>" . _MD_VIEWTHETOPIC . '</a></p>' . "<p><a href='" . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname')
             . "/viewforum.php?forum=$forum'>" . _MD_RETURNTOTHEFORUM . '</a></p>' . "<p><a href='index.php'>" . _MD_RETURNFORUMINDEX . '</a></p>';
    } elseif ('merge' === $mode) {
        /** @var NewbbPostHandler $postHandler */
        $postHandler = xoops_getModuleHandler('post', 'newbb');
        /** @var \NewbbRateHandler $rateHandler */
        $rateHandler = xoops_getModuleHandler('rate', 'newbb');

        foreach ($topic_id as $tid) {
            $topic_obj    = $topicHandler->get($tid);
            $newtopic_obj = $topicHandler->get($newtopic);

            /* return false if destination topic is not existing */
            // irmtfan bug fix: the old topic will be deleted if user input a not exist new topic
            if (!is_object($newtopic_obj)) {
                $redirect = XOOPS_URL . '/modules/newbb/viewtopic.php?topic_id=' . $tid;
                redirect_header($redirect, 2, _MD_ERRORTOPIC);
            }
            $criteria_topic = new Criteria('topic_id', $tid);
            $criteria       = new CriteriaCompo($criteria_topic);
            $criteria->add(new Criteria('pid', 0));
            // irmtfan OR change to this for less query?:
            // $postHandler->updateAll("pid", $newtopic_obj->getVar("topic_last_post_id"), $criteria, true);
            $postHandler->updateAll('pid', $topicHandler->getTopPostId($newtopic), $criteria, true);
            $postHandler->updateAll('topic_id', $newtopic, $criteria_topic, true);
            // irmtfan update vote data instead of deleting them
            $rateHandler->updateAll('topic_id', $newtopic, $criteria_topic, true);

            $topic_views = $topic_obj->getVar('topic_views') + $newtopic_obj->getVar('topic_views');
            // irmtfan better method to update topic_views in new topic
            //$criteria_newtopic = new Criteria('topic_id', $newtopic);
            //$topicHandler->updateAll('topic_views', $topic_views, $criteria_newtopic, true);
            $newtopic_obj->setVar('topic_views', $topic_views);
            // START irmtfan poll_module and rewrite the method
            // irmtfan only move poll in old topic to new topic if new topic has not a poll
            $poll_id = $topic_obj->getVar('poll_id');
            if ($poll_id > 0 && ($newtopic_obj->getVar('poll_id') == 0)) {
                $newtopic_obj->setVar('topic_haspoll', 1);
                $newtopic_obj->setVar('poll_id', $poll_id);
                $poll_id = 0;// set to not delete the poll
                $topic_obj->setVar('topic_haspoll', 0); // set to not delete the poll
                $topic_obj->setVar('poll_id', 0);// set to not delete the poll
            }
            //update and sync newtopic after merge
            //$topicHandler->insert($newtopic_obj, true);
            $topicHandler->synchronization($newtopic_obj); // very important: only use object
            //sync newforum after merge
            $newforum = $newtopic_obj->getVar('forum_id');
            $forumHandler->synchronization($newforum);
            //hardcode remove force to delete old topic from database
            //$topicHandler->delete($topic_obj,true); // cannot use this
            $topicHandler->deleteAll($criteria_topic, true); // $force = true
            //delete poll if old topic had a poll
            $topic_obj->deletePoll($poll_id);
            //sync forum after delete old topic
            $forumHandler->synchronization($forum);
            // END irmtfan poll_module and rewrite the method
        }
        echo $action[$mode]['msg'] . // irmtfan full URL
             "<p><a href='" . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/viewtopic.php?topic_id=$newtopic'>" . _MD_VIEWTHETOPIC . '</a></p>' . "<p><a href='" . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/viewforum.php?forum=$forum'>" . _MD_RETURNTOTHEFORUM
             . '</a></p>' . "<p><a href='" . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/index.php'>" . _MD_RETURNFORUMINDEX . '</a></p>';
    } elseif ('move' === $mode) {
        if ($newforum > 0) {
            $topic_id  = $topic_id[0];
            $topic_obj = $topicHandler->get($topic_id);
            $topic_obj->loadFilters('update');
            $topic_obj->setVar('forum_id', $newforum, true);
            $topicHandler->insert($topic_obj, true);
            $topic_obj->loadFilters('update');

            $sql = sprintf('UPDATE %s SET forum_id = %u WHERE topic_id = %u', $GLOBALS['xoopsDB']->prefix('bb_posts'), $newforum, $topic_id);
            if (!$r = $GLOBALS['xoopsDB']->query($sql)) {
                return false;
            }
            $forumHandler->synchronization($forum);
            $forumHandler->synchronization($newforum);
            // irmtfan full URL
            echo $action[$mode]['msg'] . "<p><a href='" . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/viewtopic.php?topic_id=$topic_id&amp;forum=$newforum'>" . _MD_GOTONEWFORUM . "</a></p><p><a href='" . XOOPS_URL . "/modules/newbb/index.php'>" . _MD_RETURNFORUMINDEX . '</a></p>';
        } else {
            // irmtfan - issue with javascript:history.go(-1)
            redirect_header($_SERVER['HTTP_REFERER'], 2, _MD_ERRORFORUM);
        }
    } else {
        $topic_id  = $topic_id[0];
        $forum     = $topicHandler->get($topic_id, 'forum_id');
        $forum_new = !empty($newtopic) ? $topicHandler->get($newtopic, 'forum_id') : 0;

        if (!$forumHandler->getPermission($forum, 'moderate')
            || (!empty($forum_new)
                && !$forumHandler->getPermission($forum_new, 'reply'))        // The forum for the topic to be merged to
            || (!empty($newforum) && !$forumHandler->getPermission($newforum, 'post')) // The forum to be moved to
        ) {
            redirect_header(XOOPS_URL . "/modules/newbb/viewtopic.php?forum=$forum&amp;topic_id=$topic_id", 2, _NOPERM);
        }

        if (!empty($action[$mode]['sql'])) {
            $sql = sprintf('UPDATE %s SET ' . $action[$mode]['sql'] . ' WHERE topic_id = %u', $GLOBALS['xoopsDB']->prefix('bb_topics'), $topic_id);
            if (!$r = $GLOBALS['xoopsDB']->query($sql)) {
                redirect_header(XOOPS_URL . "/modules/newbb/viewtopic.php?forum=$forum&amp;topic_id=$topic_id&amp;order=$order&amp;viewmode=$viewmode", 2, _MD_ERROR_BACK . '<br>sql:' . $sql);
            }
        } else {
            redirect_header(XOOPS_URL . "/modules/newbb/viewtopic.php?forum=$forum&amp;topic_id=$topic_id", 2, _MD_ERROR_BACK);
        }
        if ('digest' === $mode && $GLOBALS['xoopsDB']->getAffectedRows()) {
            $topic_obj    = $topicHandler->get($topic_id);
            /** @var \NewbbStatsHandler $statsHandler */
            $statsHandler = xoops_getModuleHandler('stats', 'newbb');
            $statsHandler->update($topic_obj->getVar('forum_id'), 'digest');
            $userstatsHandler = xoops_getModuleHandler('userstats', 'newbb');
            if ($user_stat = $userstatsHandler->get($topic_obj->getVar('topic_poster'))) {
                $z = $user_stat->getVar('user_digests') + 1;
                $user_stat->setVar('user_digests', (int)$z);
                $userstatsHandler->insert($user_stat);
            }
        }
        // irmtfan full URL
        echo $action[$mode]['msg'] . "<p><a href='" . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/viewtopic.php?topic_id=$topic_id&amp;forum=$forum'>" . _MD_VIEWTHETOPIC . "</a></p><p><a href='" . XOOPS_URL . "/modules/newbb/viewforum.php?forum=$forum'>" . _MD_RETURNFORUMINDEX
             . '</a></p>';
    }
} else {  // No submit
    $mode = Request::getString('mode', '', 'GET'); //$_GET['mode'];
    echo "<form action='" . Request::getString('PHP_SELF', '', 'SERVER') . "' method='post'>";
    echo "<table border='0' cellpadding='1' cellspacing='0' align='center' width='95%'>";
    echo "<tr><td class='bg2'>";
    echo "<table border='0' cellpadding='1' cellspacing='1' width='100%'>";
    echo "<tr class='bg3' align='left'>";
    echo "<td colspan='2' align='center'>" . $action[$mode]['desc'] . '</td></tr>';

    if ($mode === 'move') {
        echo '<tr><td class="bg3">' . _MD_MOVETOPICTO . '</td><td class="bg1">';
        $box = '<select name="newforum" size="1">';

        /** @var \NewbbCategoryHandler $categoryHandler */
        $categoryHandler = xoops_getModuleHandler('category', 'newbb');
        $categories      = $categoryHandler->getByPermission('access');
        $forums          = $forumHandler->getForumsByCategory(array_keys($categories), 'post', false);

        if (count($categories) > 0 && count($forums) > 0) {
            foreach (array_keys($forums) as $key) {
                /** @var \NewbbCategory[] $categories */
                $box .= "<option value='-1'>[" . $categories[$key]->getVar('cat_title') . ']</option>';
                foreach ($forums[$key] as $forumid => $_forum) {
                    $box .= "<option value='" . $forumid . "'>-- " . $_forum['title'] . '</option>';
                    if (!isset($_forum['sub'])) {
                        continue;
                    }
                    foreach (array_keys($_forum['sub']) as $fid) {
                        $box .= "<option value='" . $fid . "'>---- " . $_forum['sub'][$fid]['title'] . '</option>';
                    }
                }
            }
        } else {
            $box .= "<option value='-1'>" . _MD_NOFORUMINDB . '</option>';
        }
        unset($forums, $categories);

        echo $box;
        echo '</select></td></tr>';
    }
    if ($mode === 'merge') {
        echo '<tr><td class="bg3">' . _MD_MERGETOPICTO . '</td><td class="bg1">';
        echo _MD_TOPIC . "ID-$topic_id -> ID: <input name='newtopic' value='' />";
        echo '</td></tr>';
    }
    echo '<tr class="bg3"><td colspan="2" align="center">';
    echo "<input type='hidden' name='mode' value='" . $action[$mode]['name'] . "' />";
    echo "<input type='hidden' name='topic_id' value='" . $topic_id . "' />";
    echo "<input type='hidden' name='forum' value='" . $forum . "' />";
    echo "<input type='submit' name='submit' value='" . $action[$mode]['submit'] . "' />";
    echo '</td></tr></form></table></td></tr></table>';
}
// irmtfan move to footer.php
include_once __DIR__ . '/footer.php';
include $GLOBALS['xoops']->path('footer.php');
