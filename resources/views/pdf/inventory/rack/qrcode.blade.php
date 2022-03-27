<html>
    <head>
    	<title>Barcode</title>
        <style>
            thead td {
                text-align:center
            }
			tbody td {
				padding:1.4mm;
			}
        </style>
    </head>
    <body>
        <div style='text-align:center'>
            {!! QrCode::size(160)->generate($url) !!}
        </div>
    </body>
</html>