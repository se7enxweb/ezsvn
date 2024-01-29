<form action={concat("/svn/client/" )|ezurl} method="post" name="svn" id="svn">

<div class="content-navigation">

<div class="context-block">

<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">SVN client</h1>

<div class="header-mainline"></div>

</div></div></div></div></div></div>


<div class="box-ml"><div class="box-mr">

<div class="context-information">
<p class="modified">Client output</p>
<div class="break"></div>
</div>

{if $output}

<div class="mainobject-window" title="Client output">
<div class="fixedsize">
<div class="holdinplace">
<pre>
{$output|wash()}
</pre>
</div>
</div>
</div>
<div class="break"></div>
{/if}


</div></div>


<div class="controlbar">

<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">

<div class="block">
{include uri="design:gui/loader.tpl"}
<div class="left">
    <input class="button" type="submit" id="Execute" name="Execute" value="Syncronize" title="Start SVN syncronization." onclick="lon('{"Please wait..."|i18n("extension/ezsvn")}');">
</div>


<div class="right">
    <input class="button" type="submit" name="Skip" value="Skip" >
</div>




<div class="break"></div>

</div>

</div></div></div></div></div></div>

</div>

</div>

</form>