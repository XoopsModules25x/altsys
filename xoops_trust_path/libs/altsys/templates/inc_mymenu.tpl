<style scoped="scoped"><{include file='db:altsys_inc_mymenu.css'}></style>
<div class="altsys_mymenu">
    <{foreach from=$adminmenu item="menuitem"}>
        <div>
            <nobr><a href="<{$menuitem.link|escape}>" class="<{if $menuitem.selected}>selected<{else}>unselected<{/if}>"><{$menuitem.title|escape}></a> | </nobr>
        </div>
    <{/foreach}>
</div>
<hr class="altsys_mymenu_separator" />
