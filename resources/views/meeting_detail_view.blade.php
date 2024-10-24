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
			color: #555555;
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

		table td {
			border-left: 1px solid #ecf0f1;
			border-right: 1px solid #ecf0f1;
		}

		table tr:nth-of-type(even) td,
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
	</style>
</head>
<body>
	<header class="clearfix">
		<div id="company">
			@php
			$appSetting = App\Models\AppSetting::first();
			@endphp
			<h2 class="name" style="text-align: center; margin-right: 25px; color: #00338d">
				<img src="./kpmg-logo.jpg" width="150"><br>
				{{ $appSetting->app_name }}
			</h2>
		</div>
	</header>
	<main> 
		<br>
		<br>
		<table width="100%" style="padding: 5px 10px;">
			<thead>
				<tr class="orange">
					<th class="orange text-left" colspan="3">
						<strong> Meeting Title: {{ $meeting->meeting_title }}</strong>
					</th>
					
					<th class="orange text-right" colspan="2">
						<strong>
							Meeting Reference Number: {{ $meeting->meeting_ref_no }}
						</strong>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="desc" width="25%">
						<strong>Organized By</strong>
					</td>
					<td class="desc" width="25%">
						{{ $meeting->organiser->name }}
					</td>
					<td></td>
					<td class="desc" width="25%">
						<strong>Meeting Date/time</strong>
					</td>
					<td class="desc" width="25%">
						{{ $meeting->meeting_date }}
					</td>
				</tr> 

				<tr>
					<td class="desc">
						<strong>Meeting Start Time</strong>
					</td>
					<td class="desc">
						{{ $meeting->meeting_time_start }}
					</td><td></td>
					<td class="desc">
						<strong>Meeting End Time</strong>
					</td>
					<td class="desc">
						{{ $meeting->meeting_time_end }}
					</td>
				</tr>

				<tr>
					<td class="desc">
						<strong>Agenda Of Meeting</strong>
					</td>
					<td class="desc" colspan="4">
						{!! $meeting->agenda_of_meeting !!}
					</td>
				</tr> 
				

				<tr>
					<td class="desc">
						<strong>Meeting Uid</strong>
					</td>
					<td class="desc">
						{{ $meeting->meeting_uid }}
					</td>
					<td></td>
					<td class="desc">
						<strong>Meeting Link</strong>
					</td>
					<td class="desc">
						{{ $meeting->meeting_link }}
					</td>
				</tr> 
				<tr>
					<td class="desc">
						<strong>Is Repeat</strong>
					</td>
					<td class="desc">
						{{ ($meeting->is_repeat==1) ? 'Yes' : 'No' }}
					</td>
					<td></td>
					<td class="desc">
						<strong>Status</strong>
					</td>
					<td class="desc">
						{{ ($meeting->status==1) ? 
						'Active' : 'Inactive' }}
					</td>
				</tr> 
				<tr>
					<td class="desc">
						<strong>Created At</strong>
					</td>
					<td class="desc" colspan="4">
						{{ $meeting->created_at }}
					</td>
				</tr>
				
				@if($meeting->documents->count()>0)
				<tr>
					<td class="desc">
						<strong>Documents</strong>
					</td>
					<td class="desc" colspan="4">
						@foreach($meeting->documents as $nkey => $document)
						<a href="{{ $document->file_name }}" target="_blank">#{{$nkey+1}}: {{ $document->uploading_file_name }}</a><br>
						@endforeach
					</td>
				</tr>
				@endif
				
			</tbody>
		</table>

		<table width="100%" style="padding: 5px 10px;">
			<thead>
				<tr class="orange">
					<th class="orange text-left" colspan="4">
						<strong>
							Meeting Attendees
						</strong>
					</th>
				</tr>
			</thead>
			<tbody>
			@foreach($meeting->attendees as $key => $attendee)
				<tr>
					<td class="desc">
						<strong>#{{$key+1}}: Name</strong>
					</td>
					<td class="desc">
						{!! $attendee->user->name !!}
					</td>
					<td class="desc">
						<strong>Email</strong>
					</td>
					<td class="desc">
						{!! $attendee->user->email !!}
					</td>
				</tr> 
			@endforeach
			</tbody>
		</table> 
		<br>

		<table width="100%" style="padding: 5px 10px;">
			<thead>
				<tr class="orange">
					<th class="orange text-left" colspan="4">
						<strong>
							Meeting Notes
						</strong>
					</th>
				</tr>
			</thead>
			<tbody>
			@foreach($meeting->notes as $key => $note)
				<tr>
					<td class="desc" width="15%">
						<strong>#{{$key+1}}: Note</strong>
					</td>
					<td class="desc" colspan="3">
						{!! $note->notes !!}
					</td>
				</tr> 
				<tr>
					<td class="desc">
						<strong>Decision</strong>
					</td>
					<td class="desc" colspan="3">
						{!! $note->decision !!}
					</td>
				</tr>
				<tr>
					<td class="desc">
						<strong>Created At</strong>
					</td>
					<td class="desc" colspan="3">
						{{ @$note->created_at }}
					</td>
				</tr>
				<tr>
					<td class="desc" width="25%">
						<strong>Created By</strong>
					</td>
					<td class="desc" width="25%">
						{{ @$note->createdBy->name }}
					</td>
					<td class="desc" width="25%">
						<strong>Edited By</strong>
					</td>
					<td class="desc" width="25%">
						{{ @$note->editedBy->name }}
					</td>
				</tr>
				<tr>
					<td class="desc">
						<strong>Status</strong>
					</td>
					<td class="desc" colspan="3">
						{{ ($note->status==1) ? 'Active' : 'Inactive' }}
					</td>
				</tr>
				@if($note->documents->count()>0)
				<tr>
					<td class="desc">
						<strong>Documents</strong>
					</td>
					<td class="desc" colspan="3">
						@foreach($note->documents as $notekey => $document)
						<a href="{{ $document->file_name }}" target="_blank">#{{$notekey+1}}: {{ $document->uploading_file_name }}</a><br>
						@endforeach
					</td>
				</tr>
				@endif

				<tr class="">
					<th class="text-left" colspan="4">
						&nbsp;
					</th>
				</tr>

				@if($note->actionItems->count()>0)
					<tr class="">
						<th class="desc text-left" colspan="4">
							<strong>
								Meeting Action Items
							</strong>
						</th>
					</tr>
					@foreach($note->actionItems as $keyn => $actionItem)
						<tr>
							<td class="desc">
								<strong>#{{$keyn+1}}: MM Ref Id</strong>
							</td>
							<td class="desc">
								{!! $actionItem->mm_ref_id !!}
							</td>
							<td class="desc">
								<strong>Date Opened</strong>
							</td>
							<td class="desc">
								{!! $actionItem->date_opened !!}
							</td>
						</tr> 
						<tr>
							<td class="desc">
								<strong>Responsibility</strong>
							</td>
							<td class="desc" colspan="3">
								{!! @$actionItem->owner->name !!}
							</td>
						</tr>
						<tr>
							<td class="desc">
								<strong>Task</strong>
							</td>
							<td class="desc" colspan="3">
								{!! $actionItem->task !!}
							</td>
						</tr> 
						<tr>
							<td class="desc">
								<strong>Due Date</strong>
							</td>
							<td class="desc">
								{!! $actionItem->due_date !!}
							</td>
							<td class="desc">
								<strong>Complete Percentage</strong>
							</td>
							<td class="desc">
								{!! $actionItem->complete_percentage !!}%
							</td>
						</tr>
						<tr>
							<td class="desc">
								<strong>Priority</strong>
							</td>
							<td class="desc">
								{!! $actionItem->priority !!}
							</td>
							<td class="desc">
								<strong>Status</strong>
							</td>
							<td class="desc">
								{!! $actionItem->status !!}
							</td>
						</tr> 
						<tr>
							<td class="desc">
								<strong>Complete Date</strong>
							</td>
							<td class="desc">
								{!! $actionItem->complete_date !!}
							</td>
							<td class="desc">
								<strong>Verified Date</strong>
							</td>
							<td class="desc">
								{!! $actionItem->verified_date !!}
							</td>
						</tr> 
						<tr>
							<td class="desc">
								<strong>Comment</strong>
							</td>
							<td class="desc" colspan="3">
								{!! $actionItem->comment !!}
							</td>
						</tr>

						@if($actionItem->documents->count()>0)
						<tr>
							<td class="desc">
								<strong>Documents</strong>
							</td>
							<td class="desc" colspan="3">
								@foreach($actionItem->documents as $dockey => $document)
								<a href="{{ $document->file_name }}" target="_blank">#{{$dockey+1}}: {{ $document->uploading_file_name }}</a><br>
								@endforeach
							</td>
						</tr>
						@endif
						<tr class="">
							<th class="text-left" colspan="4">
								&nbsp;
							</th>
						</tr>
					@endforeach
				@endif
			@endforeach
			</tbody>
		</table> 
		<br>

		{{--
		<br>

		<br/>
		<!-- <div id="thanks">Thank you!</div> -->
		<div>
			<small>Note: This is a system generated mail. Please do not reply on this,</small><br><br>
			Regards,<br>
			KPMG PIVOT Team<br>
			<a href="mailto:in-fmpivotsupport@kpmg.com">in-fmpivotsupport@kpmg.com</a><br>
			<hr>
			2023 KPMG International Cooperative<br>
			<hr>
			KPMG (in India) allows reasonable personal use of the e-mail system. Views and opinions expressed in these communications do not necessarily represent those of KPMG (in India).<br>

			******************************************************************************************************************************************************************************************************<br>
			DISCLAIMER<br>
			The information in this e-mail is confidential and may be legally privileged. It is intended solely for the addressee. Access to this e-mail by anyone else is unauthorized. If you have received this communication in error, please address with the subject heading "Received in error," send to postmaster1@kpmg.com, then delete the e-mail and destroy any copies of it. If you are not the intended recipient, any disclosure, copying, distribution or any action taken or omitted to be taken in reliance on it, is prohibited and may be unlawful. Any opinions or advice contained in this e-mail are subject to the terms and conditions expressed in the governing KPMG client engagement letter. Opinions, conclusions and other information in this e-mail and any attachments that do not relate to the official business of the firm are neither given nor endorsed by it.
			<br><br>
			KPMG cannot guarantee that e-mail communications are secure or error-free, as information could be intercepted, corrupted, amended, lost, destroyed, arrive late or incomplete, or contain viruses.
			<br><br>
			KPMG, an Indian partnership and a member firm of KPMG International Cooperative ("KPMG International"), an English entity that serves as a coordinating entity for a network of independent firms operating under the KPMG name. KPMG International Cooperative (“KPMG International”) provides no services to clients. Each member firm of KPMG International Cooperative (“KPMG International”) is a legally distinct and separate entity and each describes itself as such.
			<br><br>
			"Notwithstanding anything inconsistent contained in the meeting invite to which this acceptance pertains, this acceptance is restricted solely to confirming my availability for the proposed call and should not be construed in any manner as acceptance of any other terms or conditions. Specifically, nothing contained herein may be construed as an acceptance (or deemed acceptance) of any request or notification for recording of the call, which can be done only if it is based on my explicit and written consent and subject to the terms and conditions on which such consent has been granted"<br>
			******************************************************************************************************************************************************************************************************

		</div>
		--}}
	</main>
</body>
</html>