<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1,user-scalable=yes">
    <title>RapiDoc</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="//cdn.jsdelivr.net/gh/highlightjs/cdn-release@9.18.1/build/styles/default.min.css">
    <script src="//cdn.jsdelivr.net/gh/highlightjs/cdn-release@9.18.1/build/highlight.min.js"></script>
    <style>
        .btn {
            width: 90px;
            height: 32px;
            padding: 2px;
            font-size: 13px;
            background-color: #707cd2;
            color: #fff;
            border: none;
            margin: 0 2px;
            border-radius: 2px;
            cursor: pointer;
            outline: none;
        }

        .btn.medium {
            width: 75px;
            height: 24px
        }

        .btn.small {
            width: 60px;
            height: 24px
        }

        rapi-doc {
            width: 100%;
        }

        .img-container {
            text-align: center;
            display: block;
        }
    </style>
    <script>
		function getRapiDoc() {
			return document.getElementById("thedoc");
		}

		function changeRenderStyle() {
			let currRender = getRapiDoc().getAttribute('render-style');
			let newRender = currRender === "read" ? "view" : "read";
			getRapiDoc().setAttribute('render-style', newRender);
			toggleAttr('show-header');
		}

		function toggleAttr(attr) {
			if (getRapiDoc().getAttribute(attr) === 'false') {
				getRapiDoc().setAttribute(attr, "true");
			} else {
				getRapiDoc().setAttribute(attr, "false");
			}
		}
    </script>
</head>
<body>
<rapi-doc
        id="thedoc"
        heading-text=""
        goto-path="Overview"
        spec-url="../api.json"
        allow-server-selection="true"
        show-header="true"
        theme="dark"
        bg-color="#1f1f1f"
        header-color="#1b1a1a"
        primary-color="#2cabe3"
        nav-bg-color="#1b1a1a"
        allow-try="true"
        allow-api-list-style-selection="true"
        regular-font="Nunito"
        schema-style="tree"
        render-style="view"
        default-schema-tab="example"
        sort-tags="true"
        allow-spec-url-load="false"
        allow-spec-file-load="false"
>
    <img src="../../plugins/images/organizr/logo.png" style="height: 50px" slot="logo">
    <div style="display:flex; margin:10px; justify-content:center;flex-wrap: wrap;" slot="logo">
        <button class="btn read-button" onclick="changeRenderStyle()">Change View</button>
    </div>
    <span class="img-container" slot="nav-logo">
			<img src="../../plugins/images/organizr/logo-wide.png" style="width: 300px">
		</span>
    <div slot="nav-logo" style="width:100%; display: flex; flex-direction:column;">
        <div style="display: flex;justify-content: center; margin: 2px 0">
            <button class='btn' onclick="changeRenderStyle()">Change View</button>
        </div>
    </div>
</rapi-doc>
<script src="rapidoc-min.js">
</script>
</body>
</html>