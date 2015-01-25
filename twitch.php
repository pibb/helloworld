<?php
	require_once("library/webpage.php");
	Webpage::self();
?>
<!doctype html>
<body>
<object type="application/x-shockwave-flash" height="628" width="800" id="live_embed_player_flash" data="http://www.twitch.tv/swflibs/TwitchPlayer.swf?old=1" bgcolor="#000000" class="videoplayer">
<param name="allowFullScreen" value="true" />
<param name="allowScriptAccess" value="always" />
<param name="allowNetworking" value="all" />
<param name="movie" value="http://www.twitch.tv/swflibs/TwitchPlayer.swf?old=1" />
<param name="flashvars" value="hostname=www.twitch.tv&channel=<?php echo $_GET['c'];  ?>&auto_play=true" />
</object>
</body>