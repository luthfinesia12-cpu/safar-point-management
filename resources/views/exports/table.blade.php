<!doctype html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<title>{{ $title }}</title>
	<style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #24363a; }
		h1 { margin: 0 0 4px; color: #356f6b; }
		h2 { margin: 18px 0 6px; font-size: 13px; }
		.meta { color: #667477; margin-bottom: 14px; }
		.filters { margin: 4px 0 12px; }
		table { border-collapse: collapse; width: 100%; }
		th { background: #6fb7b1; color: #fff; }
		th, td { border: 1px solid #b9c9c7; padding: 6px; text-align: left; }
		tfoot td { font-weight: bold; background: #f3eadb; }
	</style>
</head>
<body>
	@if($logoDataUri)
		<img src="{{ $logoDataUri }}" alt="Logo perusahaan" style="max-height: 48px; margin-bottom: 8px;">
	@endif
	<h1>{{ $company?->name ?? 'Safar Point Management' }}</h1>
	<h2>{{ $title }}</h2>
	<div class="meta">
		Dicetak {{ $printedAt->format('d/m/Y H:i:s') }} WIB oleh {{ $printedBy }}
		@if($company?->address) | {{ $company->address }} @endif
	</div>
	@if(collect($filters)->filter()->isNotEmpty())
		<div class="filters">Filter: {{ collect($filters)->filter()->map(fn ($value, $key) => $key.'='.$value)->implode(', ') }}</div>
	@endif
	<table>
		<thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
		<tbody>@foreach($rows as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach</tbody>
		<tfoot><tr><td colspan="{{ count($headers) - 1 }}">Total</td><td>{{ $total }}</td></tr></tfoot>
	</table>
</body>
</html>