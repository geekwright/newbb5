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
//  Author: phppp (D.J., infomax@gmail.com)                                  //
//  URL: https://xoops.org                                                    //
//  Project: Article Project                                                 //
//  ------------------------------------------------------------------------ //

use Xmf\Highlighter;
use Xmf\Request;

// defined('XOOPS_ROOT_PATH') || exit('Restricted access.');

defined('NEWBB_FUNCTIONS_INI') || include $GLOBALS['xoops']->path('modules/newbb/include/functions.ini.php');

/**
 * Class Post
 */
class NewbbPost extends XoopsObject
{
    //class Post extends XoopsObject {
    public $attachmentArray = [];

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->initVar('post_id', XOBJ_DTYPE_INT);
        $this->initVar('topic_id', XOBJ_DTYPE_INT, 0, true);
        $this->initVar('forum_id', XOBJ_DTYPE_INT, 0, true);
        $this->initVar('post_time', XOBJ_DTYPE_INT, 0, true);
        //        $this->initVar('poster_ip', XOBJ_DTYPE_INT, 0);
        $this->initVar('poster_ip', XOBJ_DTYPE_TXTBOX, '');
        $this->initVar('poster_name', XOBJ_DTYPE_TXTBOX, '');
        $this->initVar('subject', XOBJ_DTYPE_TXTBOX, '', true);
        $this->initVar('pid', XOBJ_DTYPE_INT, 0);
        $this->initVar('dohtml', XOBJ_DTYPE_INT, 0);
        $this->initVar('dosmiley', XOBJ_DTYPE_INT, 1);
        $this->initVar('doxcode', XOBJ_DTYPE_INT, 1);
        $this->initVar('doimage', XOBJ_DTYPE_INT, 1);
        $this->initVar('dobr', XOBJ_DTYPE_INT, 1);
        $this->initVar('uid', XOBJ_DTYPE_INT, 1);
        $this->initVar('icon', XOBJ_DTYPE_TXTBOX, '');
        $this->initVar('attachsig', XOBJ_DTYPE_INT, 0);
        $this->initVar('approved', XOBJ_DTYPE_INT, 1);
        $this->initVar('post_karma', XOBJ_DTYPE_INT, 0);
        $this->initVar('require_reply', XOBJ_DTYPE_INT, 0);
        $this->initVar('attachment', XOBJ_DTYPE_TXTAREA, '');
        $this->initVar('post_text', XOBJ_DTYPE_TXTAREA, '');
        $this->initVar('post_edit', XOBJ_DTYPE_TXTAREA, '');
    }

    // ////////////////////////////////////////////////////////////////////////////////////
    // attachment functions    TODO: there should be a file/attachment management class
    /**
     * @return array|mixed|null
     */
    public function getAttachment()
    {
        if (count($this->attachmentArray)) {
            return $this->attachmentArray;
        }
        $attachment = $this->getVar('attachment');
        if (empty($attachment)) {
            $this->attachmentArray = [];
        } else {
            $this->attachmentArray = @unserialize(base64_decode($attachment));
        }

        return $this->attachmentArray;
    }

    /**
     * @param $attachKey
     * @return bool
     */
    public function incrementDownload($attachKey)
    {
        if (!$attachKey) {
            return false;
        }
        $this->attachmentArray[(string)$attachKey]['numDownload']++;

        return $this->attachmentArray[(string)$attachKey]['numDownload'];
    }

    /**
     * @return bool
     */
    public function saveAttachment()
    {
        $attachmentSave = '';
        if (is_array($this->attachmentArray) && count($this->attachmentArray) > 0) {
            $attachmentSave = base64_encode(serialize($this->attachmentArray));
        }
        $this->setVar('attachment', $attachmentSave);
        $sql = 'UPDATE ' . $GLOBALS['xoopsDB']->prefix('newbb_posts') . ' SET attachment=' . $GLOBALS['xoopsDB']->quoteString($attachmentSave) . ' WHERE post_id = ' . $this->getVar('post_id');
        if (!$result = $GLOBALS['xoopsDB']->queryF($sql)) {
            //xoops_error($GLOBALS['xoopsDB']->error());
            return false;
        }

        return true;
    }

    /**
     * @param  array|null $attachArray
     * @return bool
     */
    public function deleteAttachment($attachArray = null)
    {
        $attachOld = $this->getAttachment();
        if (!is_array($attachOld) || count($attachOld) < 1) {
            return true;
        }
        $this->attachmentArray = [];

        if ($attachArray === null) {
            $attachArray = array_keys($attachOld);
        } // to delete all!
        if (!is_array($attachArray)) {
            $attachArray = [$attachArray];
        }

        foreach ($attachOld as $key => $attach) {
            if (in_array($key, $attachArray)) {
                @unlink($GLOBALS['xoops']->path($GLOBALS['xoopsModuleConfig']['dir_attachments'] . '/' . $attach['name_saved']));
                @unlink($GLOBALS['xoops']->path($GLOBALS['xoopsModuleConfig']['dir_attachments'] . '/thumbs/' . $attach['name_saved'])); // delete thumbnails
                continue;
            }
            $this->attachmentArray[$key] = $attach;
        }
        $attachmentSave = '';
        if (is_array($this->attachmentArray) && count($this->attachmentArray) > 0) {
            $attachmentSave = base64_encode(serialize($this->attachmentArray));
        }
        $this->setVar('attachment', $attachmentSave);

        return true;
    }

    /**
     * @param  string $name_saved
     * @param  string $nameDisplay
     * @param  string $mimetype
     * @param  int    $numDownload
     * @return bool
     */
    public function setAttachment($name_saved = '', $nameDisplay = '', $mimetype = '', $numDownload = 0)
    {
        static $counter = 0;
        $this->attachmentArray = $this->getAttachment();
        if ($name_saved) {
            $key                         = (string)(time() + $counter++);
            $this->attachmentArray[$key] = [
                'name_saved'  => $name_saved,
                'nameDisplay' => empty($nameDisplay) ? $nameDisplay : $name_saved,
                'mimetype'    => $mimetype,
                'numDownload' => empty($numDownload) ? (int)$numDownload : 0
            ];
        }
        $attachmentSave = null;
        if (is_array($this->attachmentArray)) {
            $attachmentSave = base64_encode(serialize($this->attachmentArray));
        }
        $this->setVar('attachment', $attachmentSave);

        return true;
    }

    /**
     * TODO: refactor
     * @param  bool $asSource
     * @return string
     */
    public function displayAttachment($asSource = false)
    {
        global $xoopsModule;

        $post_attachment = '';
        $attachments     = $this->getAttachment();
        if (is_array($attachments) && count($attachments) > 0) {
            $iconHandler = newbbGetIconHandler();
            $mime_path   = $iconHandler->getPath('mime');
            include_once dirname(__DIR__) . '/include/functions.image.php';
            $image_extensions = ['jpg', 'jpeg', 'gif', 'png', 'bmp']; // need improve !!!
            $post_attachment  .= '<br><strong>' . _MD_NEWBB_ATTACHMENT . '</strong>:';
            $post_attachment  .= '<br><hr size="1" noshade="noshade" /><br>';
            foreach ($attachments as $key => $att) {
                $file_extension = ltrim(strrchr($att['name_saved'], '.'), '.');
                $filetype       = $file_extension;
                if (file_exists($GLOBALS['xoops']->path($mime_path . '/' . $filetype . '.gif'))) {
                    $icon_filetype = XOOPS_URL . '/' . $mime_path . '/' . $filetype . '.gif';
                } else {
                    $icon_filetype = XOOPS_URL . '/' . $mime_path . '/unknown.gif';
                }
                $file_size = @filesize($GLOBALS['xoops']->path($GLOBALS['xoopsModuleConfig']['dir_attachments'] . '/' . $att['name_saved']));
                $file_size = number_format($file_size / 1024, 2) . ' KB';
                if (in_array(strtolower($file_extension), $image_extensions)
                    && $GLOBALS['xoopsModuleConfig']['media_allowed']) {
                    $post_attachment .= '<br><img src="' . $icon_filetype . '" alt="' . $filetype . '" /><strong>&nbsp; ' . $att['nameDisplay'] . '</strong> <small>(' . $file_size . ')</small>';
                    $post_attachment .= '<br>' . newbbAttachmentImage($att['name_saved']);
                    $isDisplayed     = true;
                } else {
                    if (empty($GLOBALS['xoopsModuleConfig']['show_userattach'])) {
                        $post_attachment .= '<a href="'
                                            . XOOPS_URL
                                            . '/modules/'
                                            . $xoopsModule->getVar('dirname', 'n')
                                            . '/dl_attachment.php?attachid='
                                            . $key
                                            . '&amp;post_id='
                                            . $this->getVar('post_id')
                                            . '"> <img src="'
                                            . $icon_filetype
                                            . '" alt="'
                                            . $filetype
                                            . '" /> '
                                            . $att['nameDisplay']
                                            . '</a> '
                                            . _MD_NEWBB_FILESIZE
                                            . ': '
                                            . $file_size
                                            . '; '
                                            . _MD_NEWBB_HITS
                                            . ': '
                                            . $att['numDownload'];
                    } elseif ($GLOBALS['xoopsUser'] && $GLOBALS['xoopsUser']->uid() > 0
                              && $GLOBALS['xoopsUser']->isactive()) {
                        $post_attachment .= '<a href="'
                                            . XOOPS_URL
                                            . '/modules/'
                                            . $xoopsModule->getVar('dirname', 'n')
                                            . '/dl_attachment.php?attachid='
                                            . $key
                                            . '&amp;post_id='
                                            . $this->getVar('post_id')
                                            . '"> <img src="'
                                            . $icon_filetype
                                            . '" alt="'
                                            . $filetype
                                            . '" /> '
                                            . $att['nameDisplay']
                                            . '</a> '
                                            . _MD_NEWBB_FILESIZE
                                            . ': '
                                            . $file_size
                                            . '; '
                                            . _MD_NEWBB_HITS
                                            . ': '
                                            . $att['numDownload'];
                    } else {
                        $post_attachment .= _MD_NEWBB_SEENOTGUEST;
                    }
                }
                $post_attachment .= '<br>';
            }
        }

        return $post_attachment;
    }
    // attachment functions
    // ////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param  string $poster_name
     * @param  string $post_editmsg
     * @return bool
     */
    public function setPostEdit($poster_name = '', $post_editmsg = '')
    {
        $edit_user = '';
        if (empty($GLOBALS['xoopsModuleConfig']['recordedit_timelimit'])
            || (time() - $this->getVar('post_time')) < $GLOBALS['xoopsModuleConfig']['recordedit_timelimit'] * 60
            || $this->getVar('approved') < 1) {
            return true;
        }
        if (is_object($GLOBALS['xoopsUser']) && $GLOBALS['xoopsUser']->isActive()) {
            if ($GLOBALS['xoopsModuleConfig']['show_realname'] && $GLOBALS['xoopsUser']->getVar('name')) {
                $edit_user = $GLOBALS['xoopsUser']->getVar('name');
            } else {
                $edit_user = $GLOBALS['xoopsUser']->getVar('uname');
            }
        }
        $post_edit              = [];
        $post_edit['edit_user'] = $edit_user; // (?) The proper way is to store uid instead of name.
        // However, to save queries when displaying, the current way is ok.
        $post_edit['edit_time'] = time();
        $post_edit['edit_msg']  = $post_editmsg;

        $post_edits = $this->getVar('post_edit');
        if (!empty($post_edits)) {
            $post_edits = unserialize(base64_decode($post_edits));
        }
        if (!is_array($post_edits)) {
            $post_edits = [];
        }
        $post_edits[] = $post_edit;
        $post_edit    = base64_encode(serialize($post_edits));
        unset($post_edits);
        $this->setVar('post_edit', $post_edit);

        return true;
    }

    /**
     * @return bool|string
     */
    public function displayPostEdit()
    {
        global $myts;

        if (empty($GLOBALS['xoopsModuleConfig']['recordedit_timelimit'])) {
            return false;
        }

        $post_edit  = '';
        $post_edits = $this->getVar('post_edit');
        if (!empty($post_edits)) {
            $post_edits = unserialize(base64_decode($post_edits));
        }
        if (!isset($post_edits) || !is_array($post_edits)) {
            $post_edits = [];
        }
        if (is_array($post_edits) && count($post_edits) > 0) {
            foreach ($post_edits as $postedit) {
                $edit_time = (int)$postedit['edit_time'];
                $edit_user = $postedit['edit_user'];
                $edit_msg  = (!empty($postedit['edit_msg'])) ? $postedit['edit_msg'] : '';
                // Start irmtfan add option to do only the latest edit when do_latestedit=0 (Alfred)
                if (empty($GLOBALS['xoopsModuleConfig']['do_latestedit'])) {
                    $post_edit = '';
                }
                // End irmtfan add option to do only the latest edit when do_latestedit=0 (Alfred)
                // START hacked by irmtfan
                // display/save all edit records.
                $post_edit .= _MD_NEWBB_EDITEDBY . ' ' . $edit_user . ' ' . _MD_NEWBB_ON . ' ' . formatTimestamp((int)$edit_time) . '<br>';
                // if reason is not empty
                if ($edit_msg !== '') {
                    $post_edit .= _MD_NEWBB_EDITEDMSG . ' ' . $edit_msg . '<br>';
                }
                // START hacked by irmtfan
            }
        }

        return $post_edit;
    }

    /**
     * @return array
     */
    public function &getPostBody()
    {
        global $viewtopic_users;
        $newbbConfig = newbbLoadConfig();
        include_once __DIR__ . '/../include/functions.user.php';
        include_once __DIR__ . '/../include/functions.render.php';

        $uid = is_object($GLOBALS['xoopsUser']) ? $GLOBALS['xoopsUser']->getVar('uid') : 0;
        /** @var NewbbKarmaHandler $karmaHandler */
        $karmaHandler = xoops_getModuleHandler('karma', 'newbb');
        $user_karma   = $karmaHandler->getUserKarma();

        $post               = [];
        $post['attachment'] = false;
        $post_text          = newbbDisplayTarea($this->vars['post_text']['value'], $this->getVar('dohtml'), $this->getVar('dosmiley'), $this->getVar('doxcode'), $this->getVar('doimage'), $this->getVar('dobr'));
        if (newbbIsAdmin($this->getVar('forum_id')) || $this->checkIdentity()) {
            $post['text'] = $post_text . '<br>' . $this->displayAttachment();
        } elseif ($newbbConfig['enable_karma'] && $this->getVar('post_karma') > $user_karma) {
            $post['text'] = sprintf(_MD_NEWBB_KARMA_REQUIREMENT, $user_karma, $this->getVar('post_karma'));
        } elseif ($newbbConfig['allow_require_reply'] && $this->getVar('require_reply')
                  && (!$uid || !isset($viewtopic_users[$uid]))) {
            $post['text'] = _MD_NEWBB_REPLY_REQUIREMENT;
        } else {
            $post['text'] = $post_text . '<br>' . $this->displayAttachment();
        }
        /** @var XoopsMemberHandler $memberHandler */
        $memberHandler = xoops_getHandler('member');
        $eachposter    = $memberHandler->getUser($this->getVar('uid'));
        if (is_object($eachposter) && $eachposter->isActive()) {
            if ($newbbConfig['show_realname'] && $eachposter->getVar('name')) {
                $post['author'] = $eachposter->getVar('name');
            } else {
                $post['author'] = $eachposter->getVar('uname');
            }
            unset($eachposter);
        } else {
            $post['author'] = $this->getVar('poster_name') ?: $GLOBALS['xoopsConfig']['anonymous'];
        }

        $post['subject'] = newbbHtmlspecialchars($this->vars['subject']['value']);

        $post['date'] = $this->getVar('post_time');

        return $post;
    }

    /**
     * @return bool
     */
    public function isTopic()
    {
        return !$this->getVar('pid');
    }

    /**
     * @param  string $action_tag
     * @return bool
     */
    public function checkTimelimit($action_tag = 'edit_timelimit')
    {
        $newbbConfig = newbbLoadConfig();
        if (empty($newbbConfig['edit_timelimit'])) {
            return true;
        }

        return ($this->getVar('post_time') > time() - $newbbConfig[$action_tag] * 60);
    }

    /**
     * @param  int $uid
     * @return bool
     */
    public function checkIdentity($uid = -1)
    {
        $uid = ($uid > -1) ? $uid : (is_object($GLOBALS['xoopsUser']) ? $GLOBALS['xoopsUser']->getVar('uid') : 0);
        if ($this->getVar('uid') > 0) {
            $user_ok = ($uid == $this->getVar('uid'));
        } else {
            static $user_ip;
            if (!isset($user_ip)) {
                $user_ip = \Xmf\IPAddress::fromRequest()->asReadable();
            }
            $user_ok = ($user_ip == $this->getVar('poster_ip'));
        }

        return $user_ok;
    }

    // TODO: cleaning up and merge with post hanldings in viewpost.php

    /**
     * @param $isAdmin
     * @return array
     */
    public function showPost($isAdmin)
    {
        global $xoopsModule, $myts;
        global $forumUrl, $forumImage, $forumObject, $online, $viewmode;
        global $viewtopic_users, $viewtopic_posters, $topicObject, $user_karma;
        global $order, $start, $total_posts, $topic_status;
        static $post_NO = 0;
        static $name_anonymous;
        /** @var NewbbTopicHandler $topicHandler */
        $topicHandler = xoops_getModuleHandler('topic', 'newbb');
        if (!isset($name_anonymous)) {
            $name_anonymous = $myts->htmlSpecialChars($GLOBALS['xoopsConfig']['anonymous']);
        }

        include_once __DIR__ . '/../include/functions.time.php';
        include_once __DIR__ . '/../include/functions.render.php';

        $post_id  = $this->getVar('post_id');
        $topic_id = $this->getVar('topic_id');
        $forum_id = $this->getVar('forum_id');

        $query_vars              = ['status', 'order', 'start', 'mode', 'viewmode'];
        $query_array             = [];
        $query_array['topic_id'] = "topic_id={$topic_id}";
        foreach ($query_vars as $var) {
            if (Request::getString($var, '', 'GET')) {
                $query_array[$var] = "{$var}=" . Request::getString($var, '', 'GET');
            }
        }
        $page_query = htmlspecialchars(implode('&', array_values($query_array)));

        $uid = is_object($GLOBALS['xoopsUser']) ? $GLOBALS['xoopsUser']->getVar('uid') : 0;

        ++$post_NO;
        if (strtolower($order) === 'desc') {
            $post_no = $total_posts - ($start + $post_NO) + 1;
        } else {
            $post_no = $start + $post_NO;
        }

        if ($isAdmin || $this->checkIdentity()) {
            $post_text       = $this->getVar('post_text');
            $post_attachment = $this->displayAttachment();
        } elseif ($GLOBALS['xoopsModuleConfig']['enable_karma'] && $this->getVar('post_karma') > $user_karma) {
            $post_text       = "<div class='karma'>" . sprintf(_MD_NEWBB_KARMA_REQUIREMENT, $user_karma, $this->getVar('post_karma')) . '</div>';
            $post_attachment = '';
        } elseif ($GLOBALS['xoopsModuleConfig']['allow_require_reply'] && $this->getVar('require_reply')
                  && (!$uid || !in_array($uid, $viewtopic_posters))) {
            $post_text       = "<div class='karma'>" . _MD_NEWBB_REPLY_REQUIREMENT . '</div>';
            $post_attachment = '';
        } else {
            $post_text       = $this->getVar('post_text');
            $post_attachment = $this->displayAttachment();
        }

        // Hightlight search words
        $post_title = $this->getVar('subject');
        if ($keywords = Request::getString('keywords', '', 'GET')) {
            //$keywords   = $myts->htmlSpecialChars(trim(urldecode(Request::getString('keywords', '', 'GET'))));
            $post_text  = Highlighter::apply($keywords, $post_text, '<mark>', '</mark>');
            $post_title = Highlighter::apply($keywords, $post_title, '<mark>', '</mark>');
        }

        if (isset($viewtopic_users[$this->getVar('uid')])) {
            $poster = $viewtopic_users[$this->getVar('uid')];
        } else {
            $name   = ($post_name = $this->getVar('poster_name')) ? $post_name : $name_anonymous;
            $poster = [
                'poster_uid' => 0,
                'name'       => $name,
                'link'       => $name
            ];
        }

        if ($posticon = $this->getVar('icon')) {
            $post_image = '<a name="' . $post_id . '"><img src="' . XOOPS_URL . '/images/subject/' . $posticon . '" alt="" /></a>';
        } else {
            $post_image = '<a name="' . $post_id . '"><img src="' . XOOPS_URL . '/images/icons/posticon.gif" alt="" /></a>';
        }

        $thread_buttons = [];
        $mod_buttons    = [];

        if ($isAdmin && ($GLOBALS['xoopsUser'] && $GLOBALS['xoopsUser']->getVar('uid') !== $this->getVar('uid'))
            && $this->getVar('uid') > 0) {
            $mod_buttons['bann']['image']    = newbbDisplayImage('p_bann', _MD_NEWBB_SUSPEND_MANAGEMENT);
            $mod_buttons['bann']['link']     = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . '/moderate.php?forum=' . $forum_id . '&amp;uid=' . $this->getVar('uid');
            $mod_buttons['bann']['name']     = _MD_NEWBB_SUSPEND_MANAGEMENT;
            $thread_buttons['bann']['image'] = newbbDisplayImage('p_bann', _MD_NEWBB_SUSPEND_MANAGEMENT);
            $thread_buttons['bann']['link']  = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . '/moderate.php?forum=' . $forum_id . '&amp;uid=' . $this->getVar('uid');
            $thread_buttons['bann']['name']  = _MD_NEWBB_SUSPEND_MANAGEMENT;
        }

        if ($GLOBALS['xoopsModuleConfig']['enable_permcheck']) {
//            /** @var NewbbTopicHandler $topicHandler */
//            $topicHandler = xoops_getModuleHandler('topic', 'newbb');
            $topic_status = $topicObject->getVar('topic_status');
            if ($topicHandler->getPermission($forum_id, $topic_status, 'edit')) {
                $edit_ok = ($isAdmin || ($this->checkIdentity() && $this->checkTimelimit('edit_timelimit')));

                if ($edit_ok) {
                    $thread_buttons['edit']['image'] = newbbDisplayImage('p_edit', _EDIT);
                    $thread_buttons['edit']['link']  = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/edit.php?{$page_query}";
                    $thread_buttons['edit']['name']  = _EDIT;
                    $mod_buttons['edit']['image']    = newbbDisplayImage('p_edit', _EDIT);
                    $mod_buttons['edit']['link']     = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/edit.php?{$page_query}";
                    $mod_buttons['edit']['name']     = _EDIT;
                }
            }

            if ($topicHandler->getPermission($forum_id, $topic_status, 'delete')) {
                $delete_ok = ($isAdmin || ($this->checkIdentity() && $this->checkTimelimit('delete_timelimit')));

                if ($delete_ok) {
                    $thread_buttons['delete']['image'] = newbbDisplayImage('p_delete', _DELETE);
                    $thread_buttons['delete']['link']  = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/delete.php?{$page_query}";
                    $thread_buttons['delete']['name']  = _DELETE;
                    $mod_buttons['delete']['image']    = newbbDisplayImage('p_delete', _DELETE);
                    $mod_buttons['delete']['link']     = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/delete.php?{$page_query}";
                    $mod_buttons['delete']['name']     = _DELETE;
                }
            }
            if ($topicHandler->getPermission($forum_id, $topic_status, 'reply')) {
                $thread_buttons['reply']['image'] = newbbDisplayImage('p_reply', _MD_NEWBB_REPLY);
                $thread_buttons['reply']['link']  = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/reply.php?{$page_query}";
                $thread_buttons['reply']['name']  = _MD_NEWBB_REPLY;

                $thread_buttons['quote']['image'] = newbbDisplayImage('p_quote', _MD_NEWBB_QUOTE);
                $thread_buttons['quote']['link']  = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/reply.php?{$page_query}&amp;quotedac=1";
                $thread_buttons['quote']['name']  = _MD_NEWBB_QUOTE;
            }
        } else {
            $mod_buttons['edit']['image'] = newbbDisplayImage('p_edit', _EDIT);
            $mod_buttons['edit']['link']  = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/edit.php?{$page_query}";
            $mod_buttons['edit']['name']  = _EDIT;

            $mod_buttons['delete']['image'] = newbbDisplayImage('p_delete', _DELETE);
            $mod_buttons['delete']['link']  = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/delete.php?{$page_query}";
            $mod_buttons['delete']['name']  = _DELETE;

            $thread_buttons['reply']['image'] = newbbDisplayImage('p_reply', _MD_NEWBB_REPLY);
            $thread_buttons['reply']['link']  = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/reply.php?{$page_query}";
            $thread_buttons['reply']['name']  = _MD_NEWBB_REPLY;
        }

        if (!$isAdmin && $GLOBALS['xoopsModuleConfig']['reportmod_enabled']) {
            $thread_buttons['report']['image'] = newbbDisplayImage('p_report', _MD_NEWBB_REPORT);
            $thread_buttons['report']['link']  = XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . "/report.php?{$page_query}";
            $thread_buttons['report']['name']  = _MD_NEWBB_REPORT;
        }

        $thread_action = [];
        // irmtfan add pdf permission
        if (file_exists(XOOPS_PATH . '/vendor/tcpdf/tcpdf.php')
            && $topicHandler->getPermission($forum_id, $topic_status, 'pdf')) {
            $thread_action['pdf']['image']  = newbbDisplayImage('pdf', _MD_NEWBB_PDF);
            $thread_action['pdf']['link']   = XOOPS_URL . '/modules/newbb/makepdf.php?type=post&amp;pageid=0';
            $thread_action['pdf']['name']   = _MD_NEWBB_PDF;
            $thread_action['pdf']['target'] = '_blank';
        }
        // irmtfan add print permission
        if ($topicHandler->getPermission($forum_id, $topic_status, 'print')) {
            $thread_action['print']['image']  = newbbDisplayImage('printer', _MD_NEWBB_PRINT);
            $thread_action['print']['link']   = XOOPS_URL . '/modules/newbb/print.php?form=2&amp;forum=' . $forum_id . '&amp;topic_id=' . $topic_id;
            $thread_action['print']['name']   = _MD_NEWBB_PRINT;
            $thread_action['print']['target'] = '_blank';
        }

        if ($GLOBALS['xoopsModuleConfig']['show_sociallinks']) {
            $full_title  = $this->getVar('subject');
            $clean_title = preg_replace('/[^A-Za-z0-9-]+/', '+', $this->getVar('subject'));
            $full_link   = XOOPS_URL . '/modules/newbb/viewtopic.php?post_id=' . $post_id;

            $thread_action['social_twitter']['image']  = newbbDisplayImage('twitter', _MD_NEWBB_SHARE_TWITTER);
            $thread_action['social_twitter']['link']   = 'http://twitter.com/share?text=' . $clean_title . '&amp;url=' . $full_link;
            $thread_action['social_twitter']['name']   = _MD_NEWBB_SHARE_TWITTER;
            $thread_action['social_twitter']['target'] = '_blank';

            $thread_action['social_facebook']['image']  = newbbDisplayImage('facebook', _MD_NEWBB_SHARE_FACEBOOK);
            $thread_action['social_facebook']['link']   = 'http://www.facebook.com/sharer.php?u=' . $full_link;
            $thread_action['social_facebook']['name']   = _MD_NEWBB_SHARE_FACEBOOK;
            $thread_action['social_facebook']['target'] = '_blank';

            $thread_action['social_gplus']['image']  = newbbDisplayImage('googleplus', _MD_NEWBB_SHARE_GOOGLEPLUS);
            $thread_action['social_gplus']['link']   = 'https://plusone.google.com/_/+1/confirm?hl=en&url=' . $full_link;
            $thread_action['social_gplus']['name']   = _MD_NEWBB_SHARE_GOOGLEPLUS;
            $thread_action['social_gplus']['target'] = '_blank';

            $thread_action['social_linkedin']['image']  = newbbDisplayImage('linkedin', _MD_NEWBB_SHARE_LINKEDIN);
            $thread_action['social_linkedin']['link']   = 'http://www.linkedin.com/shareArticle?mini=true&amp;title=' . $full_title . '&amp;url=' . $full_link;
            $thread_action['social_linkedin']['name']   = _MD_NEWBB_SHARE_LINKEDIN;
            $thread_action['social_linkedin']['target'] = '_blank';

            $thread_action['social_delicious']['image']  = newbbDisplayImage('delicious', _MD_NEWBB_SHARE_DELICIOUS);
            $thread_action['social_delicious']['link']   = 'http://del.icio.us/post?title=' . $full_title . '&amp;url=' . $full_link;
            $thread_action['social_delicious']['name']   = _MD_NEWBB_SHARE_DELICIOUS;
            $thread_action['social_delicious']['target'] = '_blank';

            $thread_action['social_digg']['image']  = newbbDisplayImage('digg', _MD_NEWBB_SHARE_DIGG);
            $thread_action['social_digg']['link']   = 'http://digg.com/submit?phase=2&amp;title=' . $full_title . '&amp;url=' . $full_link;
            $thread_action['social_digg']['name']   = _MD_NEWBB_SHARE_DIGG;
            $thread_action['social_digg']['target'] = '_blank';

            $thread_action['social_reddit']['image']  = newbbDisplayImage('reddit', _MD_NEWBB_SHARE_REDDIT);
            $thread_action['social_reddit']['link']   = 'http://reddit.com/submit?title=' . $full_title . '&amp;url=' . $full_link;
            $thread_action['social_reddit']['name']   = _MD_NEWBB_SHARE_REDDIT;
            $thread_action['social_reddit']['target'] = '_blank';

            $thread_action['social_wong']['image']  = newbbDisplayImage('wong', _MD_NEWBB_SHARE_MRWONG);
            $thread_action['social_wong']['link']   = 'http://www.mister-wong.de/index.php?action=addurl&bm_url=' . $full_link;
            $thread_action['social_wong']['name']   = _MD_NEWBB_SHARE_MRWONG;
            $thread_action['social_wong']['target'] = '_blank';
        }

        $post = [
            'post_id'         => $post_id,
            'post_parent_id'  => $this->getVar('pid'),
            'post_date'       => newbbFormatTimestamp($this->getVar('post_time')),
            'post_image'      => $post_image,
            'post_title'      => $post_title,
            // irmtfan $post_title to add highlight keywords
            'post_text'       => $post_text,
            'post_attachment' => $post_attachment,
            'post_edit'       => $this->displayPostEdit(),
            'post_no'         => $post_no,
            'post_signature'  => $this->getVar('attachsig') ? @$poster['signature'] : '',
            //            'poster_ip'       => ($isAdmin && $GLOBALS['xoopsModuleConfig']['show_ip']) ? long2ip($this->getVar('poster_ip')) : '',
            'poster_ip'       => ($isAdmin
                                  && $GLOBALS['xoopsModuleConfig']['show_ip']) ? $this->getVar('poster_ip') : '',
            'thread_action'   => $thread_action,
            'thread_buttons'  => $thread_buttons,
            'mod_buttons'     => $mod_buttons,
            'poster'          => $poster,
            'post_permalink'  => '<a href="' . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . '/viewtopic.php?post_id=' . $post_id . '"></a>'
        ];

        unset($thread_buttons, $mod_buttons, $eachposter);

        return $post;
    }
}

/**
 * Class NewbbPostHandler
 */
class NewbbPostHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param XoopsDatabase|null $db
     */
    public function __construct(XoopsDatabase $db)
    {
        parent::__construct($db, 'newbb_posts', 'NewbbPost', 'post_id', 'subject');
    }

    /**
     * @param  mixed $id
     * @param  null  $var
     * @return null|XoopsObject
     */
    public function get($id = null, $var = null) //get($id)
    {
        $id   = (int)$id;
        $post = null;
        $sql  = 'SELECT p.*, t.* FROM ' . $this->db->prefix('newbb_posts') . ' p LEFT JOIN ' . $this->db->prefix('newbb_posts_text') . ' t ON p.post_id=t.post_id WHERE p.post_id=' . $id;
        if ($array = $this->db->fetchArray($this->db->query($sql))) {
            $post = $this->create(false);
            $post->assignVars($array);
        }

        return $post;
    }

    /**
     * @param  int             $limit
     * @param  int             $start
     * @param  CriteriaElement $criteria
     * @param  null            $fields
     * @param  bool            $asObject
     * @param  int             $topic_id
     * @param  int             $approved
     * @return array
     */
    //    public function getByLimit($topic_id, $limit, $approved = 1)
    public function &getByLimit(
        $limit = 0,
        $start = 0,
        CriteriaElement $criteria = null,
        $fields = null,
        $asObject = true,
        $topic_id = 0,
        $approved = 1
    ) {
        $sql    = 'SELECT p.*, t.*, tp.topic_status FROM '
                  . $this->db->prefix('newbb_posts')
                  . ' p LEFT JOIN '
                  . $this->db->prefix('newbb_posts_text')
                  . ' t ON p.post_id=t.post_id LEFT JOIN '
                  . $this->db->prefix('newbb_topics')
                  . ' tp ON tp.topic_id=p.topic_id WHERE p.topic_id='
                  . $topic_id
                  . ' AND p.approved ='
                  . $approved
                  . ' ORDER BY p.post_time DESC';
        $result = $this->db->query($sql, $limit, 0);
        $ret    = [];
        while ($myrow = $this->db->fetchArray($result)) {
            $post = $this->create(false);
            $post->assignVars($myrow);

            $ret[$myrow['post_id']] = $post;
            unset($post);
        }

        return $ret;
    }

    /**
     * @param NewbbPost $post
     * @return mixed
     */
    public function getPostForPDF(&$post)
    {
        return $post->getPostBody(true);
    }

    /**
     * @param NewbbPost $post
     * @return mixed
     */
    public function getPostForPrint(&$post)
    {
        return $post->getPostBody();
    }

    /**
     * @param  NewbbPost $post
     * @param  bool      $force
     * @return bool
     */
    public function approve(&$post, $force = false)
    {
        if (empty($post)) {
            return false;
        }
        if (is_numeric($post)) {
            $post = $this->get($post);
        }
        $post_id = $post->getVar('post_id');

        $wasApproved = $post->getVar('approved');
        // irmtfan approve post if the approved = 0 (pending) or -1 (deleted)
        if (empty($force) && $wasApproved > 0) {
            return true;
        }
        $post->setVar('approved', 1);
        $this->insert($post, true);

        /** @var \NewbbTopicHandler $topicHandler */
        $topicHandler = xoops_getModuleHandler('topic', 'newbb');
        $topicObject    = $topicHandler->get($post->getVar('topic_id'));
        if ($topicObject->getVar('topic_last_post_id') < $post->getVar('post_id')) {
            $topicObject->setVar('topic_last_post_id', $post->getVar('post_id'));
        }
        if ($post->isTopic()) {
            $topicObject->setVar('approved', 1);
        } else {
            $topicObject->setVar('topic_replies', $topicObject->getVar('topic_replies') + 1);
        }
        $topicHandler->insert($topicObject, true);

        /** @var \NewbbForumHandler $forumHandler */
        $forumHandler = xoops_getModuleHandler('forum', 'newbb');
        $forumObject    = $forumHandler->get($post->getVar('forum_id'));
        if ($forumObject->getVar('forum_last_post_id') < $post->getVar('post_id')) {
            $forumObject->setVar('forum_last_post_id', $post->getVar('post_id'));
        }
        $forumObject->setVar('forum_posts', $forumObject->getVar('forum_posts') + 1);
        if ($post->isTopic()) {
            $forumObject->setVar('forum_topics', $forumObject->getVar('forum_topics') + 1);
        }
        $forumHandler->insert($forumObject, true);

        // Update user stats
        if ($post->getVar('uid') > 0) {
            /** @var XoopsMemberHandler $memberHandler */
            $memberHandler = xoops_getHandler('member');
            $poster        = $memberHandler->getUser($post->getVar('uid'));
            if (is_object($poster) && $post->getVar('uid') == $poster->getVar('uid')) {
                $poster->setVar('posts', $poster->getVar('posts') + 1);
                $res = $memberHandler->insertUser($poster, true);
                unset($poster);
            }
        }

        // Update forum stats
        /** @var NewbbStatsHandler $statsHandler */
        $statsHandler = xoops_getModuleHandler('stats', 'newbb');
        $statsHandler->update($post->getVar('forum_id'), 'post');
        if ($post->isTopic()) {
            $statsHandler->update($post->getVar('forum_id'), 'topic');
        }

        return true;
    }

    /**
     * @param  XoopsObject $post
     * @param  bool        $force
     * @return bool
     */
    public function insert(XoopsObject $post, $force = true) //insert(&$post, $force = true)
    {
        $topicObject = null;
        // Set the post time
        // The time should be "publish" time. To be adjusted later
        if (!$post->getVar('post_time')) {
            $post->setVar('post_time', time());
        }

        /** @var \NewbbTopicHandler $topicHandler */
        $topicHandler = xoops_getModuleHandler('topic', 'newbb');
        // Verify the topic ID
        if ($topic_id = $post->getVar('topic_id')) {
            $topicObject = $topicHandler->get($topic_id);
            // Invalid topic OR the topic is no approved and the post is not top post
            if (!$topicObject//    || (!$post->isTopic() && $topicObject->getVar("approved") < 1)
            ) {
                return false;
            }
        }
        if (empty($topic_id)) {
            $post->setVar('topic_id', 0);
            $post->setVar('pid', 0);
            $post->setNew();
            $topicObject = $topicHandler->create();
        }
        $textHandler    = xoops_getModuleHandler('text', 'newbb');
        $post_text_vars = ['post_text', 'post_edit', 'dohtml', 'doxcode', 'dosmiley', 'doimage', 'dobr'];
        if ($post->isNew()) {
            if (!$topic_id = $post->getVar('topic_id')) {
                $topicObject->setVar('topic_title', $post->getVar('subject', 'n'));
                $topicObject->setVar('topic_poster', $post->getVar('uid'));
                $topicObject->setVar('forum_id', $post->getVar('forum_id'));
                $topicObject->setVar('topic_time', $post->getVar('post_time'));
                $topicObject->setVar('poster_name', $post->getVar('poster_name'));
                $topicObject->setVar('approved', $post->getVar('approved'));

                if (!$topic_id = $topicHandler->insert($topicObject, $force)) {
                    $post->deleteAttachment();
                    $post->setErrors('insert topic error');

                    //xoops_error($topicObject->getErrors());
                    return false;
                }
                $post->setVar('topic_id', $topic_id);

                $pid = 0;
                $post->setVar('pid', 0);
            } elseif (!$post->getVar('pid')) {
                $pid = $topicHandler->getTopPostId($topic_id);
                $post->setVar('pid', $pid);
            }

            $textObject = $textHandler->create();
            foreach ($post_text_vars as $key) {
                $textObject->vars[$key] = $post->vars[$key];
            }
            $post->destroyVars($post_text_vars);

            //            if (!$post_id = parent::insert($post, $force)) {
            //                return false;
            //            }

            if (!$post_id = parent::insert($post, $force)) {
                return false;
            } else {
                $post->unsetNew();
            }

            $textObject->setVar('post_id', $post_id);
            if (!$textHandler->insert($textObject, $force)) {
                $this->delete($post);
                $post->setErrors('post text insert error');

                //xoops_error($textObject->getErrors());
                return false;
            }
            if ($post->getVar('approved') > 0) {
                $this->approve($post, true);
            }
            $post->setVar('post_id', $post_id);
        } else {
            if ($post->isTopic()) {
                if ($post->getVar('subject') !== $topicObject->getVar('topic_title')) {
                    $topicObject->setVar('topic_title', $post->getVar('subject', 'n'));
                }
                if ($post->getVar('approved') !== $topicObject->getVar('approved')) {
                    $topicObject->setVar('approved', $post->getVar('approved'));
                }
                $topicObject->setDirty();
                if (!$result = $topicHandler->insert($topicObject, $force)) {
                    $post->setErrors('update topic error');

                    //xoops_error($topicObject->getErrors());
                    return false;
                }
            }
            $textObject = $textHandler->get($post->getVar('post_id'));
            $textObject->setDirty();
            foreach ($post_text_vars as $key) {
                $textObject->vars[$key] = $post->vars[$key];
            }
            $post->destroyVars($post_text_vars);
            if (!$post_id = parent::insert($post, $force)) {
                //xoops_error($post->getErrors());
                return false;
            } else {
                $post->unsetNew();
            }
            if (!$textHandler->insert($textObject, $force)) {
                $post->setErrors('update post text error');

                //xoops_error($textObject->getErrors());
                return false;
            }
        }

        return $post->getVar('post_id');
    }

    /**
     * @param  XoopsObject|NewbbPost $post
     * @param  bool                  $isDeleteOne
     * @param  bool                  $force
     * @return bool
     */
    public function delete(XoopsObject $post, $isDeleteOne = true, $force = false)
    {
        if (!is_object($post) || $post->getVar('post_id') == 0) {
            return false;
        }

        if ($isDeleteOne) {
            if ($post->isTopic()) {
                $criteria = new CriteriaCompo(new Criteria('topic_id', $post->getVar('topic_id')));
                $criteria->add(new Criteria('approved', 1));
                $criteria->add(new Criteria('pid', 0, '>'));
                if ($this->getPostCount($criteria) > 0) {
                    return false;
                }
            }

            return $this->myDelete($post, $force);
        } else {
            require_once $GLOBALS['xoops']->path('class/xoopstree.php');
            $mytree = new XoopsTree($this->db->prefix('newbb_posts'), 'post_id', 'pid');
            $arr    = $mytree->getAllChild($post->getVar('post_id'));
            // irmtfan - delete childs in a reverse order
            for ($i = count($arr) - 1; $i >= 0; $i--) {
                $childpost = $this->create(false);
                $childpost->assignVars($arr[$i]);
                $this->myDelete($childpost, $force);
                unset($childpost);
            }
            $this->myDelete($post, $force);
        }

        return true;
    }

    /**
     * @param  NewbbPost $post
     * @param  bool      $force
     * @return bool
     */
    public function myDelete(NewbbPost $post, $force = false)
    {
        global $xoopsModule;

        if (!is_object($post) || $post->getVar('post_id') == 0) {
            return false;
        }

        /* Set active post as deleted */
        if ($post->getVar('approved') > 0 && empty($force)) {
            $sql = 'UPDATE ' . $this->db->prefix('newbb_posts') . ' SET approved = -1 WHERE post_id = ' . $post->getVar('post_id');
            if (!$result = $this->db->queryF($sql)) {
            }
            /* delete pending post directly */
        } else {
            $sql = sprintf('DELETE FROM %s WHERE post_id = %u', $this->db->prefix('newbb_posts'), $post->getVar('post_id'));
            if (!$result = $this->db->queryF($sql)) {
                $post->setErrors('delete post error: ' . $sql);

                return false;
            }
            $post->deleteAttachment();

            $sql = sprintf('DELETE FROM %s WHERE post_id = %u', $this->db->prefix('newbb_posts_text'), $post->getVar('post_id'));
            if (!$result = $this->db->queryF($sql)) {
                $post->setErrors('Could not remove post text: ' . $sql);

                return false;
            }
        }

        if ($post->isTopic()) {
            $topicHandler = xoops_getModuleHandler('topic', 'newbb');
            /** @var Topic $topicObject */
            $topicObject = $topicHandler->get($post->getVar('topic_id'));
            if (is_object($topicObject) && $topicObject->getVar('approved') > 0 && empty($force)) {
                $topiccount_toupdate = 1;
                $topicObject->setVar('approved', -1);
                $topicHandler->insert($topicObject);
                xoops_notification_deletebyitem($xoopsModule->getVar('mid'), 'thread', $post->getVar('topic_id'));
            } else {
                if (is_object($topicObject)) {
                    if ($topicObject->getVar('approved') > 0) {
                        xoops_notification_deletebyitem($xoopsModule->getVar('mid'), 'thread', $post->getVar('topic_id'));
                    }

                    $poll_id = $topicObject->getVar('poll_id');
                    // START irmtfan poll_module
                    $topicObject->deletePoll($poll_id);
                    // END irmtfan poll_module
                }

                $sql = sprintf('DELETE FROM %s WHERE topic_id = %u', $this->db->prefix('newbb_topics'), $post->getVar('topic_id'));
                if (!$result = $this->db->queryF($sql)) {
                    //xoops_error($this->db->error());
                }
                $sql = sprintf('DELETE FROM %s WHERE topic_id = %u', $this->db->prefix('newbb_votedata'), $post->getVar('topic_id'));
                if (!$result = $this->db->queryF($sql)) {
                    //xoops_error($this->db->error());
                }
            }
        } else {
            $sql = 'UPDATE ' . $this->db->prefix('newbb_topics') . ' t
                            LEFT JOIN ' . $this->db->prefix('newbb_posts') . ' p ON p.topic_id = t.topic_id
                            SET t.topic_last_post_id = p.post_id
                            WHERE t.topic_last_post_id = ' . $post->getVar('post_id') . '
                                    AND p.post_id = (SELECT MAX(post_id) FROM ' . $this->db->prefix('newbb_posts') . ' WHERE topic_id=t.topic_id)';
            if (!$result = $this->db->queryF($sql)) {
            }
        }

        $postcount_toupdate = $post->getVar('approved');

        if ($postcount_toupdate > 0) {

            // Update user stats
            if ($post->getVar('uid') > 0) {
                /** @var \XoopsMemberHandler $memberHandler */
                $memberHandler = xoops_getHandler('member');
                $poster        = $memberHandler->getUser($post->getVar('uid'));
                if (is_object($poster) && $post->getVar('uid') == $poster->getVar('uid')) {
                    $poster->setVar('posts', $poster->getVar('posts') - 1);
                    $res = $memberHandler->insertUser($poster, true);
                    unset($poster);
                }
            }
            // irmtfan - just update the pid for approved posts when the post is not topic (pid=0)
            if (!$post->isTopic()) {
                $sql = 'UPDATE ' . $this->db->prefix('newbb_posts') . ' SET pid = ' . $post->getVar('pid') . ' WHERE approved=1 AND pid=' . $post->getVar('post_id');
                if (!$result = $this->db->queryF($sql)) {
                    //xoops_error($this->db->error());
                }
            }
        }

        return true;
    }

    // START irmtfan enhance getPostCount when there is join (read_mode = 2)

    /**
     * @param  null $criteria
     * @param  null $join
     * @return int|null
     */
    public function getPostCount($criteria = null, $join = null)
    {
        // if not join get the count from XOOPS/class/model/stats as before
        if (empty($join)) {
            return parent::getCount($criteria);
        }

        $sql = 'SELECT COUNT(*) as count' . ' FROM ' . $this->db->prefix('newbb_posts') . ' AS p' . ' LEFT JOIN ' . $this->db->prefix('newbb_posts_text') . ' AS t ON t.post_id = p.post_id';
        // LEFT JOIN
        $sql .= $join;
        // WHERE
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' ' . $criteria->renderWhere();
        }
        if (!$result = $this->db->query($sql)) {
            //xoops_error($this->db->error().'<br>'.$sql);
            return null;
        }
        $myrow = $this->db->fetchArray($result);
        $count = $myrow['count'];

        return $count;
    }
    // END irmtfan enhance getPostCount when there is join (read_mode = 2)
    /*
     * TODO: combining viewtopic.php
     */
    /**
     * @param  null $criteria
     * @param  int  $limit
     * @param  int  $start
     * @param  null $join
     * @return array
     */
    public function getPostsByLimit($criteria = null, $limit = 1, $start = 0, $join = null)
    {
        $ret = [];
        $sql = 'SELECT p.*, t.* ' . ' FROM ' . $this->db->prefix('newbb_posts') . ' AS p' . ' LEFT JOIN ' . $this->db->prefix('newbb_posts_text') . ' AS t ON t.post_id = p.post_id';
        if (!empty($join)) {
            $sql .= $join;
        }
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' ' . $criteria->renderWhere();
            if ($criteria->getSort() !== '') {
                $sql .= ' ORDER BY ' . $criteria->getSort() . ' ' . $criteria->getOrder();
            }
        }
        $result = $this->db->query($sql, (int)$limit, (int)$start);
        if (!$result) {
            //xoops_error($this->db->error());
            return $ret;
        }
        while ($myrow = $this->db->fetchArray($result)) {
            $post = $this->create(false);
            $post->assignVars($myrow);
            $ret[$myrow['post_id']] = $post;
            unset($post);
        }

        return $ret;
    }

    /**
     * @return bool
     */
    public function synchronization()
    {
        //$this->cleanOrphan();
        return true;
    }

    /**
     * clean orphan items from database
     *
     * @param  string $table_link
     * @param  string $field_link
     * @param  string $field_object
     * @return bool   true on success
     */
    public function cleanOrphan($table_link = '', $field_link = '', $field_object = '') //cleanOrphan()
    {
        $this->deleteAll(new Criteria('post_time', 0), true, true);
        parent::cleanOrphan($this->db->prefix('newbb_topics'), 'topic_id');
        parent::cleanOrphan($this->db->prefix('newbb_posts_text'), 'post_id');

        $sql = 'DELETE FROM ' . $this->db->prefix('newbb_posts_text') . ' WHERE (post_id NOT IN ( SELECT DISTINCT post_id FROM ' . $this->table . ') )';
        if (!$result = $this->db->queryF($sql)) {
            //xoops_error($this->db->error());
            return false;
        }

        return true;
    }

    /**
     * clean expired objects from database
     *
     * @param  int $expire time limit for expiration
     * @return bool true on success
     */
    public function cleanExpires($expire = 0)
    {
        // irmtfan if 0 no cleanup look include/plugin.php
        if (!func_num_args()) {
            $newbbConfig = newbbLoadConfig();
            $expire      = isset($newbbConfig['pending_expire']) ? (int)$newbbConfig['pending_expire'] : 7;
            $expire      = $expire * 24 * 3600; // days to seconds
        }
        if (empty($expire)) {
            return false;
        }
        $crit_expire = new CriteriaCompo(new Criteria('approved', 0, '<='));
        //if (!empty($expire)) {
        $crit_expire->add(new Criteria('post_time', time() - (int)$expire, '<'));

        //}
        return $this->deleteAll($crit_expire, true/*, true*/);
    }
}
