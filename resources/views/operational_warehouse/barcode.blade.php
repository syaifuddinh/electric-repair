<html>
    <head>
        <style>
            @media print 
            {
                @page {
                    size: 8cm 5cm;
                    margin: 5mm 5mm 5mm 5mm;
                }
            }
        </style>
    </head>
    <body>
        <?php 
            use Milon\Barcode\DNS1D; 
         ?>
        <h1>
            {!! DNS1D::getBarcodeSVG($code, "UPCA") !!}            
        </h1>
        <script>
            window.print()
        </script>
    </body>
</html>
