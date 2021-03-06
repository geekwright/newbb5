<div class="forum_header">
    <div class="forum_title">
        <h2><a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/index.php"><{$lang_forum_index}></a></h2>
        <!-- irmtfan hardcode removed align="left" -->
        <hr class="align_left" width="50%" size="1"/>
        <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/index.php"><{$smarty.const._MD_NEWBB_FORUMHOME}></a>
        <span class="delimiter">&raquo;</span>
        <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/index.php?cat=<{$category.id}>"><{$category.title}></a>
        <{if $parentforum}>
            <{foreach item=forum from=$parentforum}>
                <span class="delimiter">&raquo;</span>
                <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewforum.php?forum=<{$forum.forum_id}>"><{$forum.forum_name}></a>
            <{/foreach}>
        <{/if}>
        <span class="delimiter">&raquo;</span>
        <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewforum.php?forum=<{$forum_id}>"><{$forum_name}></a>
        <span class="delimiter">&raquo;</span>
        <strong><{$topic_title}></strong> <{if $topicstatus}><{$topicstatus}><{/if}>
    </div>
</div>
<div class="clear"></div>
<br>
<{if $tagbar}>
    <div class="taglist" style="padding: 5px;">
        <{include file="db:tag_bar.tpl"}>
    </div>
<{/if}>

<br>

<{if $online}>
    <div class="left" style="padding: 5px;">
        <{$smarty.const._MD_NEWBB_BROWSING}>&nbsp;
        <{foreach item=user from=$online.users}>
            <a href="<{$user.link}>">
                <{if $user.level eq 2}>
                    <span class="online_admin"><{$user.uname}></span>
                <{elseif $user.level eq 1}>
                    <span class="online_moderator"><{$user.uname}></span>
                <{else}>
                    <{$user.uname}>
                <{/if}>
            </a>
            &nbsp;
        <{/foreach}>
        <{if $online.num_anonymous}>
            &nbsp;<{$online.num_anonymous}> <{$smarty.const._MD_NEWBB_ANONYMOUS_USERS}>
        <{/if}>
    </div>
    <br>
<{/if}>

<{if $viewer_level gt 1}>
    <!-- irmtfan hardcode removed style="float: right; text-align: right;" -->
    <div class="icon_right" id="admin">
        <{if $mode gt 1}>
        <!-- irmtfan mistype forum_posts_admin => form_posts_admin - action="topicmanager.php" => action="action.post.php" --> 
        <form name="form_posts_admin" action="action.post.php" method="POST" onsubmit="if(window.document.form_posts_admin.op.value &lt; 1) { return false; }">
            <{$smarty.const._ALL}>: <input type="checkbox" name="post_check" id="post_check" value="1" onclick="xoopsCheckAll('form_posts_admin', 'post_check');"/>
            <!-- irmtfan mistype mode => op  -->
            <select name="op">
                <option value="0"><{$smarty.const._SELECT}></option>
                <option value="delete"><{$smarty.const._DELETE}></option>
                <{if $status eq "pending"}>
                    <option value="approve"><{$smarty.const._MD_NEWBB_APPROVE}></option>
                <{elseif $status eq "deleted"}>
                    <option value="restore"><{$smarty.const._MD_NEWBB_RESTORE}></option>
                <{/if}>
            </select>
            <input type="hidden" name="topic_id" value="<{$topic_id}>"/>
            <input type="submit" name="submit" value="<{$smarty.const._SUBMIT}>"/> |
            <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewtopic.php?topic_id=<{$topic_id}>" target="_self" title="<{$smarty.const._MD_NEWBB_TYPE_VIEW}>"><{$smarty.const._MD_NEWBB_TYPE_VIEW}></a>
            <{else}>
            <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewtopic.php?topic_id=<{$topic_id}>&amp;status=active#admin" target="_self" title="<{$smarty.const._MD_NEWBB_TYPE_ADMIN}>"><{$smarty.const._MD_NEWBB_TYPE_ADMIN}></a> |
            <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewtopic.php?topic_id=<{$topic_id}>&amp;status=pending#admin" target="_self" title="<{$smarty.const._MD_NEWBB_TYPE_PENDING}>"><{$smarty.const._MD_NEWBB_TYPE_PENDING}></a> |
            <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewtopic.php?topic_id=<{$topic_id}>&amp;status=deleted#admin" target="_self" title="<{$smarty.const._MD_NEWBB_TYPE_DELETED}>"><{$smarty.const._MD_NEWBB_TYPE_DELETED}></a>
            <{/if}>
    </div>
    <br>
<{/if}>
<div class="clear"></div>
<br>
<!-- irmtfan add to not show polls in admin mode -->
<{if $mode lte 1}>
    <{if $topic_poll}>
        <{if $topic_pollresult}>
            <{include file="db:newbb_poll_results.tpl" poll=$poll}>
        <{else}>
            <{include file="db:newbb_poll_view.tpl" poll=$poll}>
        <{/if}>
    <{/if}>
<{/if}>
<div class="clear"></div>
<br>

<div style="padding: 5px;">
    <!-- irmtfan hardcode removed style="float: left; text-align:left;"" -->
    <span class="icon_left">
        <!-- irmtfan correct prev and next icons -->
<a id="threadtop"></a><{$down}><a href="#threadbottom"><{$smarty.const._MD_NEWBB_BOTTOM}></a>&nbsp;&nbsp;<{$previous}>&nbsp;<a
                href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewtopic.php?order=<{$order_current}>&amp;topic_id=<{$topic_id}>&amp;forum=<{$forum_id}>&amp;move=prev"><{$smarty.const._MD_NEWBB_PREVTOPIC}></a>&nbsp;&nbsp;<{$next}>&nbsp;<a
                href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewtopic.php?order=<{$order_current}>&amp;topic_id=<{$topic_id}>&amp;forum=<{$forum_id}>&amp;move=next"><{$smarty.const._MD_NEWBB_NEXTTOPIC}></a>
</span>
    <!-- irmtfan hardcode removed style="float: right; text-align:right;"" -->
    <span class="icon_right">
<{$forum_reply}>&nbsp;<{$forum_addpoll}>&nbsp;<{$forum_post_or_register}>
</span>
</div>
<div class="clear"></div>
<br>

<div>
    <div class="dropdown">
        <select name="topicoption" id="topicoption" onchange="if(this.options[this.selectedIndex].value.length >0 ) { window.document.location=this.options[this.selectedIndex].value;}">
            <option value=""><{$smarty.const._MD_NEWBB_TOPICOPTION}></option>
            <{if $viewer_level gt 1}>
                <{foreach item=act from=$admin_actions}>
                    <option value="<{$act.link}>"><{$act.name}></option>
                <{/foreach}>
            <{/if}>
            <{if count($adminpoll_actions) > 0 }>
                <option value="">--------</option>
                <option value=""><{$smarty.const._MD_NEWBB_POLLOPTIONADMIN}></option>
                <{foreach item=actpoll from=$adminpoll_actions}>
                    <option value="<{$actpoll.link}>"><{$actpoll.name}></option>
                <{/foreach}>
            <{/if}>
        </select>
        <!-- irmtfan user should not see rating if he dont have permission -->
        <{if $rating_enable && $forum_post && $forum_reply}>
            <select
                    name="rate" id="rate"
                    onchange="if(this.options[this.selectedIndex].value.length >0 ) { window.document.location=this.options[this.selectedIndex].value;}">
                <option value=""><{$smarty.const._MD_NEWBB_RATE}></option>
                <option value="<{$xoops_url}>/modules/<{$xoops_dirname}>/ratethread.php?topic_id=<{$topic_id}>&amp;forum=<{$forum_id}>&amp;rate=5"><{$smarty.const._MD_NEWBB_RATE5}></option>
                <option value="<{$xoops_url}>/modules/<{$xoops_dirname}>/ratethread.php?topic_id=<{$topic_id}>&amp;forum=<{$forum_id}>&amp;rate=4"><{$smarty.const._MD_NEWBB_RATE4}></option>
                <option value="<{$xoops_url}>/modules/<{$xoops_dirname}>/ratethread.php?topic_id=<{$topic_id}>&amp;forum=<{$forum_id}>&amp;rate=3"><{$smarty.const._MD_NEWBB_RATE3}></option>
                <option value="<{$xoops_url}>/modules/<{$xoops_dirname}>/ratethread.php?topic_id=<{$topic_id}>&amp;forum=<{$forum_id}>&amp;rate=2"><{$smarty.const._MD_NEWBB_RATE2}></option>
                <option value="<{$xoops_url}>/modules/<{$xoops_dirname}>/ratethread.php?topic_id=<{$topic_id}>&amp;forum=<{$forum_id}>&amp;rate=1"><{$smarty.const._MD_NEWBB_RATE1}></option>
            </select>
        <{/if}>

        <select
                name="viewmode" id="viewmode"
                onchange="if(this.options[this.selectedIndex].value.length >0 ) { window.location=this.options[this.selectedIndex].value;}">
            <option value=""><{$smarty.const._MD_NEWBB_VIEWMODE}></option>
            <{foreach item=act from=$viewmode_options}>
                <option value="<{$act.link}>"><{$act.title}></option>
            <{/foreach}>
        </select>
        <!-- START irmtfan add topic search -->
        <{if $mode lte 1}>
            <form id="search-topic" action="<{$xoops_url}>/modules/<{$xoops_dirname}>/search.php" method="get">
                <fieldset>
                    <input name="term" id="term" type="text" size="15" value="<{$smarty.const._MD_NEWBB_SEARCHTOPIC}>..." onBlur="if(this.value==='') this.value='<{$smarty.const._MD_NEWBB_SEARCHTOPIC}>...'"
                           onFocus="if(this.value =='<{$smarty.const._MD_NEWBB_SEARCHTOPIC}>...' ) this.value=''"/>
                    <input type="hidden" name="forum" id="forum" value="<{$forum_id}>"/>
                    <input type="hidden" name="sortby" id="sortby" value="p.post_time desc"/>
                    <input type="hidden" name="topic" id="topic" value="<{$topic_id}>"/>
                    <input type="hidden" name="action" id="action" value="yes"/>
                    <input type="hidden" name="searchin" id="searchin" value="both"/>
                    <input type="hidden" name="show_search" id="show_search" value="post_text"/>
                    <input type="submit" class="formButton" value="<{$smarty.const._MD_NEWBB_SEARCH}>"/>
                </fieldset>
            </form>
        <{/if}>
        <!-- END irmtfan add topic search -->
    </div>
    <!-- irmtfan hardcode removed style="float: right; text-align:right;" -->
    <div class="icon_right">
        <{$forum_page_nav|replace:'form':'div'|replace:'id="xo-pagenav"':''}> <!-- irmtfan to solve nested forms and id="xo-pagenav" issue -->
    </div>
</div>
<div class="clear"></div>
<br>
<br>

<{if $viewer_level gt 1 && $topic_status == 1}>
    <div class="resultMsg"><{$smarty.const._MD_NEWBB_TOPICLOCK}></div>
    <br>
<{/if}>
<!-- irmtfan remove here and move to the newbb_thread.tpl
<{*<{if $post_id == 0}><div id="aktuell"></div><{/if}> *}>
-->
<{foreach item=topic_post from=$topic_posts}>
    <{include file="db:newbb_thread.tpl" topic_post=$topic_post mode=$mode}>
    <br>
    <br>
    <{foreachelse}>
    <div style="text-align: center;width:100%;font-size:1.5em;padding:5px;"><{$smarty.const._MD_NEWBB_ERRORPOST}></div>
<{/foreach}>

<{if $mode gt 1}>
    </form>
<{/if}>

<br>
<div class="forum_header">
    <div class="forum_title">
        <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/index.php"><{$smarty.const._MD_NEWBB_FORUMHOME}></a>
        <span class="delimiter">&raquo;</span>
        <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/index.php?cat=<{$category.id}>"><{$category.title}></a>
        <{if $parentforum}>
            <{foreach item=forum from=$parentforum}>
                <span class="delimiter">&raquo;</span>
                <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewforum.php?forum=<{$forum.forum_id}>"><{$forum.forum_name}></a>
            <{/foreach}>
        <{/if}>
        <span class="delimiter">&raquo;</span>
        <a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewforum.php?forum=<{$forum_id}>"><{$forum_name}></a>
        <span class="delimiter">&raquo;</span>
        <strong><{$topic_title}></strong> <{if $topicstatus}><{$topicstatus}><{/if}>
    </div>
</div>
<div class="clear"></div>
<br>

<div>
    <div class="left">
        <!-- irmtfan correct prev and next icons add up-->
        <a id="threadbottom"></a><{$p_up}><a href="#threadtop"><{$smarty.const._MD_NEWBB_TOP}></a>&nbsp;&nbsp;<{$previous}>&nbsp;<a
                href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewtopic.php?viewmode=flat&amp;order=<{$order_current}>&amp;topic_id=<{$topic_id}>&amp;forum=<{$forum_id}>&amp;move=prev"><{$smarty.const._MD_NEWBB_PREVTOPIC}></a>&nbsp;&nbsp;<{$next}>
        &nbsp;<a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/viewtopic.php?viewmode=flat&amp;order=<{$order_current}>&amp;topic_id=<{$topic_id}>&amp;forum=<{$forum_id}>&amp;move=next"><{$smarty.const._MD_NEWBB_NEXTTOPIC}></a>
    </div>
    <!-- irmtfan hardcode removed style="float: right; text-align:right;"" -->
    <div class="icon_right">
        <{$forum_page_nav|replace:'form':'div'|replace:'id="xo-pagenav"':''}> <!-- irmtfan to solve nested forms and id="xo-pagenav" issue -->
    </div>
</div>
<div class="clear"></div>
<br>

<div class="left" style="padding: 5px;">
    <{$forum_reply}>&nbsp;<{$forum_addpoll}>&nbsp;<{$forum_post_or_register}>
</div>
<div class="clear"></div>
<br>
<br>

<{if $quickreply.show}>
    <div>
        <!-- irmtfan improve toggle method to ToggleBlockCategory (this.children[0] for IE7&8) change display to style and icon to displayImage for more comprehension -->
        <a href="#threadbottom"
           onclick="ToggleBlockCategory('qr', (this.firstElementChild || this.children[0]), '<{$quickreply.icon.expand}>', '<{$quickreply.icon.collapse}>','<{$smarty.const._MD_NEWBB_HIDE|escape:'quotes'}> <{$smarty.const._MD_NEWBB_QUICKREPLY|escape:'quotes'}>','<{$smarty.const._MD_NEWBB_SEE|escape:'quotes'}> <{$smarty.const._MD_NEWBB_QUICKREPLY|escape:'quotes'}>')">
            <{$quickreply.displayImage}>
        </a>
    </div>
    <br>
    <!-- irmtfan move semicolon -->
    <div id="qr" style="display: <{$quickreply.style}>;">
        <div><{$quickreply.form}></div>
    </div>
    <br>
    <br>
<{/if}>

<div>
    <!-- irmtfan hardcode removed style="float: left; text-align: left;" -->
    <div class="icon_left">
        <{foreach item=perm from=$permission_table}>
            <div><{$perm}></div>
        <{/foreach}>
    </div>
    <!-- irmtfan hardcode removed style="float: right; text-align: right;" -->
    <div class="icon_right">
        <form action="<{$xoops_url}>/modules/<{$xoops_dirname}>/search.php" method="get">
            <input name="term" id="term" type="text" size="15"/>
            <input type="hidden" name="forum" id="forum" value="<{$forum_id}>"/>
            <input type="hidden" name="sortby" id="sortby" value="p.post_time desc"/>
            <input type="hidden" name="since" id="since" value="<{$forum_since}>"/>
            <input type="hidden" name="action" id="action" value="yes"/>
            <input type="hidden" name="searchin" id="searchin" value="both"/>
            <input type="submit" class="formButton" value="<{$smarty.const._MD_NEWBB_SEARCH}>"/><br>
            [<a href="<{$xoops_url}>/modules/<{$xoops_dirname}>/search.php"><{$smarty.const._MD_NEWBB_ADVSEARCH}></a>]
        </form>
        <br>
        <{$forum_jumpbox}>
    </div>
</div>
<div class="clear"></div>
<br>

<{include file='db:newbb_notification_select.tpl'}>
<!-- irmtfan remove

<script type="text/javascript">
<!--xoopsGetElementById('aktuell').scrollIntoView(true);
</script>
-->
<!-- START irmtfan add scroll js function to scroll down to current post or top of the topic -->
<script type="text/javascript">
    if (document.body.scrollIntoView && window.location.href.indexOf('#') == -1) {
        var el = xoopsGetElementById('<{$forum_post_prefix}><{$post_id}>');
        if (el) {
            el.scrollIntoView(true);
        }
    }
</script>
<!-- END irmtfan add scroll js function to scroll down to current post or top of the topic -->
