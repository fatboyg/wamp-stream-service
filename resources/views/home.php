<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>iStream</title>

	<style type="text/css">

	::selection { background-color: #E13300; color: white; }
	::-moz-selection { background-color: #E13300; color: white; }

	body {
		background-color: #fff;
		margin: 40px;
		font: 13px/20px normal Helvetica, Arial, sans-serif;
		color: #4F5155;
	}

	a {
		color: #003399;
		background-color: transparent;
		font-weight: normal;
	}

	h1 {
		color: #444;
		background-color: transparent;
		border-bottom: 1px solid #D0D0D0;
		font-size: 19px;
		font-weight: normal;
		margin: 0 0 14px 0;
		padding: 14px 15px 10px 15px;
	}

	#body {
		margin: 0 15px 0 15px;
	}


	p.footer {
		text-align: right;
		font-size: 11px;
		border-top: 1px solid #D0D0D0;
		line-height: 32px;
		padding: 0 10px 0 10px;
		margin: 20px 0 0 0;
	}

	.artContainer {
		width: 36%;
        border: 1px solid #D0D0D0;
        box-shadow: 0 0 8px #D0D0D0;
	}

	#container {
		margin: 10px;
		border: 1px solid #D0D0D0;
		box-shadow: 0 0 8px #D0D0D0;
	}
	</style>

	<script src="/assets/js/autobahn.min.js"></script>
	<script src="//code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="/assets/js/app.js"></script>

</head>
<body>

<div id="container">
	<h1>iRadio Live Stream</h1>

	<div id="body">

		<div class="playerContainer" style="float:left;">
            <div >
                <img class="artContainer" src="<?php echo URL::to("/assets/not-connected.png"); ?>"/>
            </div>
		</div>

		<div class="playerInfo" style="float:right;">


			<div class="">
				<span class="title">Title</span>
				<span class="subtitle">Subtitle</span>
			</div>
		</div>

        <div style="clear: both"></div>
	</div>

    <script>
        var wsUrl = '<?php
            $url = app('url'); $url->forceScheme('ws');
            echo $url->to('/ws'); ?>';
        var appinstance = new myApp();
    </script>
</div>

</body>
</html>