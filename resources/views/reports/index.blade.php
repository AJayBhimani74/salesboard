@extends('layouts.app')
@section('title', 'Sales')
@section('content')
<div class="head"><h1>Sales</h1></div>

<form method="GET" class="card">
    <div class="row">
        <label>From <input type="date" name="from" value="{{ $filters['from'] }}"></label>
        <label>To <input type="date" name="to" value="{{ $filters['to'] }}"></label>
        <label>Region
            <select name="region"><option value="">All</option>
                @foreach ($regions as $r)<option @selected($filters['region'] === $r)>{{ $r }}</option>@endforeach
            </select>
        </label>
        <label>Category
            <select name="category"><option value="">All</option>
                @foreach ($categories as $c)<option @selected($filters['category'] === $c)>{{ $c }}</option>@endforeach
            </select>
        </label>
    </div>
    @error('to') <p class="error">{{ $message }}</p> @enderror
    <button class="btn" type="submit">Apply</button>
    <a class="btn" href="{{ route('reports.csv', array_filter($filters)) }}">Download CSV</a>
    <a class="btn" href="{{ route('reports.pdf', array_filter($filters)) }}">Download PDF</a>
</form>

<div class="kpis">
    <div class="kpi"><div class="muted">Revenue</div><div class="value">@include('partials.money', ['paise' => $report['revenue']])</div></div>
    <div class="kpi"><div class="muted">Orders</div><div class="value">{{ number_format($report['orders']) }}</div></div>
    <div class="kpi"><div class="muted">Average order</div><div class="value">@include('partials.money', ['paise' => $report['average_order']])</div></div>
    <div class="kpi"><div class="muted">Cancelled</div><div class="value">{{ $report['cancelled_rate'] }}%</div></div>
</div>

<div class="card">
    <h1>Revenue by month</h1>
    @php
        $months = $report['monthly'];
        $max = max(1, max($months ?: [0]));
        $count = max(1, count($months));
        $barWidth = 700 / $count;
    @endphp
    <svg class="bars" viewBox="0 0 720 220" width="100%" role="img" aria-label="Revenue by month">
        @foreach ($months as $month => $revenue)
            @php
                $h = 160 * $revenue / $max;
                $x = 10 + $loop->index * $barWidth;
            @endphp
            <rect class="bar" x="{{ $x + 3 }}" y="{{ 180 - $h }}" width="{{ max(2, $barWidth - 6) }}" height="{{ $h }}"><title>{{ $month }}: ₹{{ number_format($revenue / 100) }}</title></rect>
            @if ($count <= 14 || $loop->index % 2 === 0)
                <text x="{{ $x + $barWidth / 2 }}" y="200" text-anchor="middle">{{ \Carbon\Carbon::parse($month.'-01')->format('M y') }}</text>
            @endif
        @endforeach
    </svg>
</div>

<div class="two">
    @foreach (['by_category' => 'Revenue by category', 'by_region' => 'Revenue by region'] as $key => $title)
        <div class="card">
            <h1>{{ $title }}</h1>
            @php $top = max(1, max($report[$key] ?: [0])); @endphp
            @forelse ($report[$key] as $label => $revenue)
                <div class="hbar">
                    <span>{{ $label }}</span>
                    <span class="track"><span class="fill" style="display:block;width: {{ round(100 * $revenue / $top) }}%"></span></span>
                    <span>@include('partials.money', ['paise' => $revenue])</span>
                </div>
            @empty
                <p class="muted">No sales in this range.</p>
            @endforelse
        </div>
    @endforeach
</div>

<p class="muted">Calculated {{ $report['generated_at'] }}. Results are cached for 10 minutes or until an order changes.</p>
@endsection
