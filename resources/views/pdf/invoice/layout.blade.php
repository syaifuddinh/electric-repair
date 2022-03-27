<!DOCTYPE html>
<html>
<head>
<title>Cetak Invoice</title>

<style media="screen">

	body {
		font-family: arial, initial;
		font-size:12px;
	}
	.text-right {
		text-align: right;
		padding-right: 5px;
	}
	.text-top tr td {
		vertical-align: top;
	}
		/* Create two equal columns that floats next to each other */
	.column {
	  float: left;
	  width: 50%;
	  /* padding: 10px; */
	}

	table.font-besar tr td {
		font-size:13px !important;
	}
	table.font-besar thead tr td {
		font-size:13px !important;
	}
	table.font-besar tbody tr td {
		font-size:13px !important;
	}

	/* Clear floats after the columns */
	.row:after {
	  content: "";
	  display: table;
	  clear: both;
	}
	.text-center {
		text-align: center;
	}
	.font-bold {
		font-weight: bold;
	}
	.border-left{
	  border-left: 1px solid black;
	}
	.border-bottom{
		border-bottom: 1px solid black;
	}
	.border-top{
		border-top: 1px solid black;
	}
	.border-right{
		border-right: 1px solid black;
	}
	.page-break {
	  page-break-after: always;
	}
	.content {
		margin-top: 20%;
		margin-bottom: 20%;
	}
	.header, .header-space,
	.footer, .footer-space {
	  height: 100px;
	}

	.header {
	  position: fixed;
	  top: 0;
	}

	.footer {
	  position: fixed;
	  bottom: 0;
	}
	table.no-horizontal tbody tr td {
		border-left: 1px solid;
		border-right: 1px solid;
	}
	table.text-vertical tr td {
		vertical-align: top;
	}
	table.no-horizontal tbody tr:last-child td {
		border-bottom: 1px solid;
	}
	table.no-horizontal tbody tr:first-child td {
		border-top: 1px solid;
	}
</style>
</head>
<body>
	@yield('content')
</body>
</html>
