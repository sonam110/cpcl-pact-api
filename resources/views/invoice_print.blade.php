<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>KPMG MEETING DETAIL</title>
	<style type="text/css">
		.clearfix:after {
			content: "";
			display: table;
			clear: both;
		}

		a {
			color: #0087C3;
			text-decoration: none;
		}

		body {
			margin: 0 auto;
			color: #000;
			background: #FFFFFF;
			font-family: opensanscondensed;
			font-size: 14px;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			text-align: left;
			overflow: hidden;
			border: #00338d 3px solid;
		}

		table td,
		table th {
			border-top: 1px solid #ecf0f1;
			padding: 10px;
		}

		table th {
			color: white !important;
		}

		table thead tr.orange {
			color: #FFFFFF !important;
			font-size: 16px !important;
			background: #00338d !important;
		}

		.orange {
			color: #FFFFFF !important;
			font-weight: bold;
		}

		.gray {
			color: #000 !important;
			font-size: 14px !important;
			background: #e7e9ee !important;
		}
		.blue {
			color: #FFFFFF !important;
			font-size: 14px !important;
			background: #00338d !important;
		}

		/*table td {
			border-left: 1px solid #ecf0f1;
			border-right: 1px solid #ecf0f1;
		}*/

		/*table tr:nth-of-type(even) td,
		table tr:nth-of-type(odd) td {
			background-color: #fff;
		}

		#thanks {
			font-size: 20px !important;
			text-align: center;
		}

		.boxhead a {
			color: #0087C3;
			text-decoration: none;
		}
		.text-left{
			text-align: left;
		}
		.text-right{
			text-align: right;
		}
		*/
		table {
			padding: 30px;
			width: 100%;
			overflow: hidden;
			border: 0px transparent solid; 
		}

		table tr td {
			border: 0px transparent solid;
		}
	</style>
</head>
<body>
	<!-- <header class="clearfix">
		<div id="company">
			@php
			$appSetting = App\Models\AppSetting::first();
			@endphp
			<h2 class="name" style="text-align: center; margin-right: 25px; color: #00338d">
				<table class="headTable" width="100%" border="0px">
					<tr>
						<td><?php echo date('d/m/y h:i A') ?></td>
					</tr>
					<tr>
						<td style="text-align:left">
							<img src="./client-logo.jpg" width="70">
						</td>
						<td style="text-align:center">
							<h3>Bill Details</h3>
							<p>Reciept Of Bill Entry</p>
						</td>
						<td style="text-align:right" width="70">
							<h2 class="name" style="text-align: center; color: #00338d">
								<img src="./kpmg-logo.jpeg" width="100">
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							</h2>
						</td>
					</tr>
				</table>

			</h2>
		</div>
	</header> -->
	<main>
		<br>
		<div style="border: 1px solid #00338b;" class="container">
			<small style="margin-left: 5px !important; margin-top: 10px !important; font-size: 10px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo date('d/m/y h:i A') ?></small>
			<br>
			<table width="100%" border="0px" style="margin-top: -10px;">
				<tr>
					<td style="text-align:left; width:33%">
						<img src="./client-logo.jpg" width="80">
					</td>
					<td style="text-align:center;  width:33%; font-size:20px">
						<strong>Bill Details</strong>
						<p style="margin-top: 0px; font-size:16px">(Reciept Of Bill Entry)</p>
					</td>
					<td style="text-align:right">
						<h2 class="name" style="text-align: center; color: #00338d; margin-right:-10px;text-align:right ">
							<img src="./kpmg-logo.jpeg" width="100">
						</h2>
					</td>
				</tr>
			</table>
			<div style="padding-left: 5px;padding-right: 5px;margin-top:-10px; ">
				<hr style="color: black;">
			</div>
			<table>
				<tbody>
					<tr>
						<td>Bill Entry Number :</td>
						<td colspan="5">{{ $invoice->unique_no }}</td>
						<td>Entry Date :</td>
						<td colspan="5">{{ \Carbon\Carbon::parse($invoice->created_at)->format('d-M-y') }}</td>
					</tr>
					<tr>
						<td>Vendor Code :</td>
						<td colspan="5">{{ @$invoice->contract->vendor_code }}</td>
						<td>Vendor name :</td>
						<td colspan="5">{{ strtoupper($invoice->vendor_name) }}</td>
					</tr>
					<tr>
						<td>SAP PO Number : </td>
						<td colspan="5">{{ $invoice->contract_number }}</td>
						<td>Invoice Number : </td>
						<td colspan="5">{{$invoice->invoice_no }}</td>
					</tr>
					<tr>
						<td>Invoice Date : </td>
						<td colspan="5">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-M-y') }}</td>
						<td>Currency : </td>
						<td colspan="5">{{ $invoice->currency ? $invoice->currency : 'INR' }}</td>
					</tr>
					<tr>
						<td>Bill Amount Basic : </td>
						<td colspan="5">{{ $invoice->basic_amount }}</td>
						<td>Gst Amount : </td>
						<td colspan="5">{{ $invoice->gst_amount }}</td>
					</tr>
					<tr>
						<td>Bill Type : </td>
						<td colspan="5">{{ $invoice->invoice_type }}</td>
						<td>RA Bill Number : </td>
						<td colspan="5">{{$invoice->ra_bill_number }}</td>
					</tr>
				</tbody>
			</table>
		</div>

		<br/>
		<!-- <div id="thanks">Thank you!</div> -->
		<div>
          
         <!--  <small>Note: This is a system generated mail. Please do not reply on this,</small><br><br> -->

Regards,<br>
KPMG PIVOT Team<br>
<a href="mailto:in-fmpivotsupport@kpmg.com">in-fmpivotsupport@kpmg.com</a><br>
<hr>
{{ date('Y') }} KPMG International Cooperative<br>
<hr>

{{-- KPMG (in India) allows reasonable personal use of the e-mail system. Views and opinions expressed in these communications do not necessarily represent those of KPMG (in India).<br>

***********************************************************************************************************<br>
<div style="font-size:12px; text-align: justify">
    DISCLAIMER<br>
The information in this e-mail is confidential and may be legally privileged. It is intended solely for the addressee. Access to this e-mail by anyone else is unauthorized. If you have received this communication in error, please address with the subject heading "Received in error," send to postmaster1@kpmg.com, then delete the e-mail and destroy any copies of it. If you are not the intended recipient, any disclosure, copying, distribution or any action taken or omitted to be taken in reliance on it, is prohibited and may be unlawful. Any opinions or advice contained in this e-mail are subject to the terms and conditions expressed in the governing KPMG client engagement letter. Opinions, conclusions and other information in this e-mail and any attachments that do not relate to the official business of the firm are neither given nor endorsed by it.
<br><br>
KPMG cannot guarantee that e-mail communications are secure or error-free, as information could be intercepted, corrupted, amended, lost, destroyed, arrive late or incomplete, or contain viruses.
<br><br>
KPMG, an Indian partnership and a member firm of KPMG International Cooperative ("KPMG International"), an English entity that serves as a coordinating entity for a network of independent firms operating under the KPMG name. KPMG International Cooperative (“KPMG International”) provides no services to clients. Each member firm of KPMG International Cooperative (“KPMG International”) is a legally distinct and separate entity and each describes itself as such.
<br><br>
"Notwithstanding anything inconsistent contained in the meeting invite to which this acceptance pertains, this acceptance is restricted solely to confirming my availability for the proposed call and should not be construed in any manner as acceptance of any other terms or conditions. Specifically, nothing contained herein may be construed as an acceptance (or deemed acceptance) of any request or notification for recording of the call, which can be done only if it is based on my explicit and written consent and subject to the terms and conditions on which such consent has been granted"<br>
</div>
***********************************************************************************************************
--}}

      </div>

	</main>
</body>
</html>